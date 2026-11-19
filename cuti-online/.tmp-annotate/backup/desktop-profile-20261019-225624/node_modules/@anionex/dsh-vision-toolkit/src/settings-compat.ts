import type { Context } from '@deepseek-ai/cordis'
import { LegacyConfig, VISION_TOOLKIT_SETTINGS_NAMESPACE, resolveConfig, type VisionToolkitConfig } from './config.ts'

type ChangeListener = (next: VisionToolkitConfig, previous: VisionToolkitConfig) => void | Promise<void>

export interface VisionSettingsBinding {
  get(): VisionToolkitConfig
  update(patch: Partial<VisionToolkitConfig>): Promise<void>
  watch(listener: ChangeListener): () => void
}

/** Bridge the registration API through 0.1.6 and the Config projection in 0.1.7. */
export function bindVisionSettings(ctx: Context, base: VisionToolkitConfig): VisionSettingsBinding {
  const service = ctx.settings as unknown as {
    register?: (
      ns: string,
      schema: typeof LegacyConfig,
      options: { base: VisionToolkitConfig; applies: 'live'; validate: (value: VisionToolkitConfig) => void },
    ) => VisionSettingsBinding
    describe(): Array<{ ns: string; value: unknown }>
    update(ns: string, patch: object): Promise<void>
  }
  if (typeof service.register === 'function') {
    return service.register(VISION_TOOLKIT_SETTINGS_NAMESPACE, LegacyConfig, {
      base,
      applies: 'live',
      validate: value => { resolveConfig(value) },
    })
  }

  // New Settings reads the Loader entry's exported Config. While apply() is
  // running the entry is not yet active, so describe() cannot see it; the
  // Loader-provided config is the initial value in that interval.
  let latest = base
  const read = (): VisionToolkitConfig => {
    const row = service.describe().find(item => item.ns === VISION_TOOLKIT_SETTINGS_NAMESPACE)
    return (row?.value as VisionToolkitConfig | undefined) ?? latest
  }
  return {
    get: read,
    update: patch => service.update(VISION_TOOLKIT_SETTINGS_NAMESPACE, patch),
    watch(listener) {
      const events = ctx as unknown as {
        on(event: 'settings/document-updated', handler: (ns: string) => void): () => void
      }
      return events.on('settings/document-updated', ns => {
        if (ns !== VISION_TOOLKIT_SETTINGS_NAMESPACE) return
        const next = read()
        if (JSON.stringify(next) === JSON.stringify(latest)) return
        const previous = latest
        latest = next
        void listener(next, previous)
      })
    },
  }
}
