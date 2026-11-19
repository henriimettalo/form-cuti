import type { Context } from '@deepseek-ai/cordis';
import { type VisionToolkitConfig } from './config.ts';
type ChangeListener = (next: VisionToolkitConfig, previous: VisionToolkitConfig) => void | Promise<void>;
export interface VisionSettingsBinding {
    get(): VisionToolkitConfig;
    update(patch: Partial<VisionToolkitConfig>): Promise<void>;
    watch(listener: ChangeListener): () => void;
}
/** Bridge the registration API through 0.1.6 and the Config projection in 0.1.7. */
export declare function bindVisionSettings(ctx: Context, base: VisionToolkitConfig): VisionSettingsBinding;
export {};
//# sourceMappingURL=settings-compat.d.ts.map