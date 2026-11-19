import { LegacyConfig, VISION_TOOLKIT_SETTINGS_NAMESPACE, resolveConfig } from "./config.js";
/** Bridge the registration API through 0.1.6 and the Config projection in 0.1.7. */
export function bindVisionSettings(ctx, base) {
    const service = ctx.settings;
    if (typeof service.register === 'function') {
        return service.register(VISION_TOOLKIT_SETTINGS_NAMESPACE, LegacyConfig, {
            base,
            applies: 'live',
            validate: value => { resolveConfig(value); },
        });
    }
    // New Settings reads the Loader entry's exported Config. While apply() is
    // running the entry is not yet active, so describe() cannot see it; the
    // Loader-provided config is the initial value in that interval.
    let latest = base;
    const read = () => {
        const row = service.describe().find(item => item.ns === VISION_TOOLKIT_SETTINGS_NAMESPACE);
        return row?.value ?? latest;
    };
    return {
        get: read,
        update: patch => service.update(VISION_TOOLKIT_SETTINGS_NAMESPACE, patch),
        watch(listener) {
            const events = ctx;
            return events.on('settings/document-updated', ns => {
                if (ns !== VISION_TOOLKIT_SETTINGS_NAMESPACE)
                    return;
                const next = read();
                if (JSON.stringify(next) === JSON.stringify(latest))
                    return;
                const previous = latest;
                latest = next;
                void listener(next, previous);
            });
        },
    };
}
//# sourceMappingURL=settings-compat.js.map