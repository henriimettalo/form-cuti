/**
 * Storage primitives for dsh-annotate: read and write the annotation file.
 *
 * The store is a plain JSON document so a human can review, diff, and commit
 * it alongside the code it describes. Nothing here talks to the agent loop;
 * {@link module:dsh-annotate} owns that.
 *
 * @module dsh-annotate/src/store
 */

import { readFile, mkdir, writeFile, rename } from 'node:fs/promises'
import { dirname, join } from 'node:path'

/** Filename of the annotation store, relative to the annotated root. */
export const STORE_FILENAME = '.dsh-annotate.json'

/**
 * An immutable snapshot of the whole store.
 * @typedef {{ version: number, annotations: Annotation[] }} AnnotationDocument
 */

/**
 * One note attached to a contiguous run of lines.
 * @typedef {object} Annotation
 * @property {string} id            Stable short id (`a1`, `a2`, …).
 * @property {string} file          Path relative to the annotated root, POSIX separators.
 * @property {number} line          First annotated line, 1-based.
 * @property {number} [endLine]     Last annotated line; omitted for a single line.
 * @property {string} note          The reviewer's text.
 * @property {string} [author]      Who wrote it.
 * @property {string} createdAt     ISO 8601 timestamp.
 */

/** Current on-disk schema version. */
const SCHEMA_VERSION = 1

/**
 * Resolve the store path for a root directory.
 * @param {string} root Absolute or relative annotated root.
 * @returns {string} Absolute store path.
 */
export function storePath (root) {
  return join(root, STORE_FILENAME)
}

/**
 * Read the store, tolerating a missing file as "no annotations yet".
 * @param {string} root Annotated root directory.
 * @returns {Promise<AnnotationDocument>} The parsed document.
 * @throws {Error} when the file exists but is not a valid store.
 */
export async function readStore (root) {
  let text
  try {
    text = await readFile(storePath(root), 'utf8')
  } catch (error) {
    if (error?.code === 'ENOENT') return { version: SCHEMA_VERSION, annotations: [] }
    throw error
  }

  let parsed
  try {
    parsed = JSON.parse(text)
  } catch (error) {
    throw new Error(`${STORE_FILENAME}: not valid JSON (${String(error)})`)
  }

  if (!Array.isArray(parsed?.annotations)) {
    throw new Error(`${STORE_FILENAME}: expected an "annotations" array`)
  }

  return { version: parsed.version ?? SCHEMA_VERSION, annotations: parsed.annotations }
}

/**
 * Write the store atomically: a temp file in the same directory, then rename.
 * @param {string} root Annotated root directory.
 * @param {AnnotationDocument} document Document to persist.
 * @returns {Promise<void>}
 */
export async function writeStore (root, document) {
  const target = storePath(root)
  await mkdir(dirname(target), { recursive: true })
  const temp = `${target}.${process.pid}.tmp`
  await writeFile(temp, JSON.stringify(document, undefined, 2) + '\n', { mode: 0o644 })
  await rename(temp, target)
}

/**
 * Pick the next free id for `document`.
 * @param {AnnotationDocument} document Current document.
 * @returns {string} An unused id of the form `a<n>`.
 */
export function nextId (document) {
  let max = 0
  for (const annotation of document.annotations) {
    const match = /^a(\d+)$/.exec(annotation.id)
    if (match) max = Math.max(max, Number(match[1]))
  }
  return `a${max + 1}`
}

/**
 * Validate and normalize a file path to the POSIX form used in the store.
 * @param {string} file Caller-supplied path.
 * @returns {string} Normalized relative path.
 * @throws {Error} when the path is absolute or escapes the root.
 */
export function normalizeFile (file) {
  const posix = file.replaceAll('\\', '/')
  if (posix.startsWith('/') || /^[A-Za-z]:/.test(posix)) {
    throw new Error(`annotation paths must be relative to the annotated root, got ${JSON.stringify(file)}`)
  }
  if (posix.split('/').includes('..')) {
    throw new Error(`annotation path may not escape the annotated root, got ${JSON.stringify(file)}`)
  }
  return posix.replace(/^\.\//, '')
}
