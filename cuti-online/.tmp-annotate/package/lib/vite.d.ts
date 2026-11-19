import type { Plugin } from 'vite';
export interface WebAnnotatorOptions { allowedParentOrigins?: string[]; sourceAnnotations?: boolean }
export declare function webAnnotator(options?: WebAnnotatorOptions): Plugin;
/** @deprecated Use WebAnnotatorOptions. */
export type LayoutCareOptions = WebAnnotatorOptions;
/** @deprecated Use webAnnotator. */
export declare const layoutCare: typeof webAnnotator;
export default webAnnotator;
