/**
 * The dsh-annotate service: the annotation API other plugins call.
 *
 * Exposed on `ctx.annotate` so the tool row (`./tool.js`), the GUI, or any
 * later plugin shares one in-memory view of the store instead of racing on
 * the file. All mutations serialize through one promise chain.
 *
 * @module dsh-annotate
 */

import { Service } from '@deepseek-ai/cordis'
import {
  nextId,
  normalizeFile,
  readStore,
  storePath,
  writeStore,
} from './store.js'

/**
 * @typedef {import('./store.js').Annotation} Annotation
 * @typedef {import('./store.js').AnnotationDocument} AnnotationDocument
 */

/**
 * Filter accepted by {@link AnnotateService.list}.
 * @typedef {object} AnnotationFilter
 * @property {string} [file]  Only annotations for this file.
 * @property {boolean} [resolved] Only resolved/unresolved entries.
 */

export class AnnotateService extends Service {
  /** @type {AnnotationDocument | undefined} Lazily loaded store. */
  #document
  /** @type {Promise<unknown>} Tail of the mutation chain. */
  #queue = Promise.resolve()

  /**
   * @param {import('@deepseek-ai/cordis').Context} ctx Plugin context.
   * @param {object} [config] Row configuration from the profile patch.
   * @param {string} [config.root] Annotated root; defaults to the session workspace.
   */
  constructor (ctx, config = {}) {
    super(ctx, 'annotate')
    this.root = config.root ?? process.cwd()
  }

  /**
   * Store path this service reads and writes.
   * @returns {string} Absolute path to the annotation file.
   */
  get path () {
    return storePath(this.root)
  }

  /** Load the store once. @returns {Promise<AnnotationDocument>} */
  async #load () {
    this.#document ??= await readStore(this.root)
    return this.#document
  }

  /**
   * Run `mutate` against the loaded document, then persist it. Mutations run
   * one at a time so two concurrent calls cannot interleave writes.
   * @template T
   * @param {(document: AnnotationDocument) => T} mutate Applied to a fresh copy.
   * @returns {Promise<T>} Whatever `mutate` returned.
   */
  #transaction (mutate) {
    const run = this.#queue.then(async () => {
      const document = structuredClone(await this.#load())
      const result = mutate(document)
      await writeStore(this.root, document)
      this.#document = document
      return result
    })
    this.#queue = run.catch(() => {})
    return run
  }

  /**
   * List annotations, newest last.
   * @param {AnnotationFilter} [filter] Optional narrowing.
   * @returns {Promise<Annotation[]>} Matching annotations.
   */
  async list (filter = {}) {
    const document = await this.#load()
    const file = filter.file === undefined ? undefined : normalizeFile(filter.file)
    return document.annotations.filter((annotation) => {
      if (file !== undefined && annotation.file !== file) return false
      if (filter.resolved !== undefined && (annotation.resolved === true) !== filter.resolved) return false
      return true
    })
  }

  /**
   * Attach a note to one file region.
   * @param {object} input Annotation to add.
   * @param {string} input.file The target file, relative to the root.
   * @param {number} input.line First line, 1-based.
   * @param {number} [input.endLine] Last line, inclusive; must be >= `line`.
   * @param {string} input.note Reviewer text; must not be blank.
   * @param {string} [input.author] Who wrote it.
   * @returns {Promise<Annotation>} The stored annotation.
   */
  async add ({ file, line, endLine, note, author }) {
    const target = normalizeFile(file)
    if (!Number.isInteger(line) || line < 1) {
      throw new Error(`line must be a positive integer, got ${JSON.stringify(line)}`)
    }
    if (endLine !== undefined && (!Number.isInteger(endLine) || endLine < line)) {
      throw new Error(`endLine must be an integer >= line (${line}), got ${JSON.stringify(endLine)}`)
    }
    if (typeof note !== 'string' || note.trim() === '') {
      throw new Error('note must be a non-empty string')
    }

    return this.#transaction((document) => {
      const annotation = {
        id: nextId(document),
        file: target,
        line,
        ...(endLine === undefined || endLine === line ? {} : { endLine }),
        note: note.trim(),
        ...(author === undefined ? {} : { author }),
        createdAt: new Date().toISOString(),
      }
      document.annotations.push(annotation)
      return annotation
    })
  }

  /**
   * Remove one annotation by id.
   * @param {string} id Annotation id.
   * @returns {Promise<boolean>} Whether an annotation was removed.
   */
  async remove (id) {
    return this.#transaction((document) => {
      const index = document.annotations.findIndex((annotation) => annotation.id === id)
      if (index === -1) return false
      document.annotations.splice(index, 1)
      return true
    })
  }

  /**
   * Mark one annotation resolved or unresolved.
   * @param {string} id Annotation id.
   * @param {boolean} [resolved] New state; defaults to `true`.
   * @returns {Promise<Annotation>} The updated annotation.
   * @throws {Error} when no annotation has that id.
   */
  async resolve (id, resolved = true) {
    return this.#transaction((document) => {
      const annotation = document.annotations.find((entry) => entry.id === id)
      if (annotation === undefined) throw new Error(`no annotation with id ${JSON.stringify(id)}`)
      annotation.resolved = resolved
      return annotation
    })
  }
}

export default AnnotateService
