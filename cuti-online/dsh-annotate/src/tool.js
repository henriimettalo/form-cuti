/**
 * The agent-facing half of dsh-annotate: one tool over the shared service.
 *
 * The tool row is separate from the service row so a profile can keep the
 * annotation store without exposing it to the model. Actions mirror
 * {@link AnnotateService}: `add`, `list`, `remove`, `resolve`.
 *
 * This module intentionally imports nothing from `@deepseek-ai/*`: a linked
 * plugin outside the profiles tree cannot resolve the installation's internal
 * packages, so the tool definition is a plain registry-shaped object.
 *
 * @module dsh-annotate/src/tool
 */

/** Services this row needs loaded before it registers its tool. */
const inject = ['tools', 'annotate']

/** Tool name advertised to the model. */
const TOOL_NAME = 'annotate'

/** The action verbs the tool accepts. */
const ACTIONS = ['add', 'list', 'remove', 'resolve']

/**
 * Register the `annotate` tool against an already-mounted {@link AnnotateService}.
 * @param {import('@deepseek-ai/cordis').Context} ctx Plugin context carrying the tool registry.
 * @returns {void}
 */
export function apply (ctx) {
  ctx.tools.register({
    name: TOOL_NAME,
    description:
      'Attach review notes to lines of code, list them, remove them, or mark them resolved. '
      + 'Annotations live in .dsh-annotate.json beside the code so they can be reviewed and committed.',
    parameters: {
      type: 'object',
      additionalProperties: false,
      required: ['action'],
      properties: {
        action: {
          type: 'string',
          enum: ACTIONS,
          description: 'What to do.',
        },
        file: {
          type: 'string',
          description: 'Action `add`/`list`: path relative to the annotated root.',
        },
        line: {
          type: 'integer',
          minimum: 1,
          description: 'Action `add`: first annotated line, 1-based.',
        },
        endLine: {
          type: 'integer',
          minimum: 1,
          description: 'Action `add`: last annotated line, inclusive. Omit for a single line.',
        },
        note: {
          type: 'string',
          description: 'Action `add`: the review note.',
        },
        id: {
          type: 'string',
          description: 'Actions `remove`/`resolve`: the annotation id.',
        },
        resolved: {
          type: 'boolean',
          description: 'Action `resolve`: target state, defaults to true. Action `list`: filter by state.',
        },
      },
    },
    output: {
      schema: {
        type: 'object',
        additionalProperties: false,
        required: ['summary'],
        properties: {
          summary: { type: 'string' },
        },
      },
      render: (_args, value) => [{ type: 'text', text: value.summary }],
    },
    /**
     * @param {object} args Schema-validated tool arguments.
     * @returns {Promise<{summary: string}>} Human-readable outcome.
     */
    async execute (args) {
      switch (args.action) {
        case 'add': {
          const annotation = await ctx.annotate.add({
            file: args.file,
            line: args.line,
            endLine: args.endLine,
            note: args.note,
          })
          return { summary: `Added ${format(annotation)}` }
        }
        case 'list': {
          const annotations = await ctx.annotate.list({
            file: args.file,
            resolved: args.resolved,
          })
          if (annotations.length === 0) return { summary: 'No annotations match.' }
          return { summary: annotations.map(format).join('\n') }
        }
        case 'remove': {
          const removed = await ctx.annotate.remove(args.id)
          return { summary: removed ? `Removed ${args.id}.` : `No annotation with id ${args.id}.` }
        }
        case 'resolve': {
          const annotation = await ctx.annotate.resolve(args.id, args.resolved ?? true)
          const state = annotation.resolved === true ? 'resolved' : 'unresolved'
          return { summary: `${annotation.id} is now ${state}.` }
        }
        default:
          throw new Error(`unsupported action ${JSON.stringify(args.action)}`)
      }
    },
    presentCall: (args) => ({
      card: 'generic',
      title: `annotate: ${args.action}`,
      kind: 'other',
      rawInput: args,
    }),
  })
}

/**
 * Render one annotation as `id — file:line[-endLine] — note [state]`.
 * @param {import('./store.js').Annotation} annotation The annotation.
 * @returns {string} One-line rendering.
 */
function format (annotation) {
  const range = annotation.endLine === undefined
    ? `${annotation.line}`
    : `${annotation.line}-${annotation.endLine}`
  const state = annotation.resolved === true ? ' [resolved]' : ''
  return `${annotation.id} — ${annotation.file}:${range} — ${annotation.note}${state}`
}

export default { inject, apply }
