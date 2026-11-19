window.__ModuleLoader__.load({ id: '@deepseek-ai/dsh-client-ui-layout', factory: function(require) { var module = { exports: {} }; var exports = module.exports;
const cssDispose = () => { const style = document.createElement('style'); style.textContent = "/* .host-source/packages/client/ui-layout/src/client/AppFrame.module.css */\n.AppFrame_frame {\n  position: relative;\n  display: grid;\n  grid-template-rows: minmax(0, 1fr) auto;\n  height: 100%;\n  overflow: hidden;\n  background: var(--dsw-alias-bg-base);\n}\n.AppFrame_frame[data-animating] {\n  transition: grid-template-columns var(--ds-transition-duration-slow) var(--ds-ease-in-out);\n}\n.AppFrame_frame[data-dragging] {\n  transition: none;\n}\n[data-windows-titlebar] .AppFrame_frame {\n  --dsh-windows-content-radius: 16px;\n  box-sizing: border-box;\n  padding-top: var(--dsh-windows-titlebar-height);\n  background: var(--dsw-specific-sidebar-fill);\n}\n[data-windows-titlebar] .AppFrame_centerCol {\n  background: var(--dsw-alias-bg-base);\n  border-radius: var(--dsh-windows-content-radius) 0 0 0;\n  corner-shape: round;\n}\n[data-windows-titlebar] .AppFrame_frame::before {\n  content: \"\";\n  position: absolute;\n  inset: 0 0 auto;\n  height: var(--dsh-windows-titlebar-height);\n  background: var(--dsw-specific-sidebar-fill);\n  -webkit-app-region: drag;\n}\n[data-windows-titlebar] .AppFrame_sidebarCol {\n  border-right: none;\n}\n@media (prefers-reduced-motion: reduce) {\n  .AppFrame_frame[data-animating] {\n    transition: none;\n  }\n}\n.AppFrame_sidebarCol {\n  min-width: 0;\n  overflow: hidden;\n  background: var(--dsw-specific-sidebar-fill);\n  border-right: 0.5px solid var(--dsw-alias-border-l3);\n}\n.AppFrame_centerCol {\n  min-width: 0;\n  display: flex;\n  flex-direction: column;\n  overflow: hidden;\n}\n.AppFrame_frame[data-rightbar-fullscreen] .AppFrame_centerCol {\n  visibility: hidden;\n}\n[data-platform=darwin] .AppFrame_frame {\n  background: transparent;\n}\nhtml[data-platform=darwin] {\n  --dsh-frame-top-clearance: 48px;\n}\nhtml[data-windows-titlebar] {\n  --dsh-frame-top-clearance: var(--dsh-windows-titlebar-height);\n  --dsh-frame-chrome-top: var(--dsh-windows-titlebar-height);\n}\nhtml[data-platform=darwin],\nhtml[data-windows-titlebar] {\n  --dsh-frame-overlay-top: calc(var(--dsh-frame-top-clearance) + 20px);\n}\nhtml[data-platform=darwin][data-fullscreen],\nhtml[data-windows-titlebar][data-fullscreen] {\n  --dsh-frame-overlay-top: 20px;\n  --dsh-frame-chrome-top: 0px;\n}\n[data-platform=darwin] .AppFrame_sidebarCol {\n  background:\n    linear-gradient(\n      to bottom,\n      rgb(122 155 240 / 0.1),\n      rgb(122 155 240 / 0) 35%,\n      rgb(143 137 184 / 0) 68%,\n      rgb(143 137 184 / 0.09)),\n    color-mix(in srgb, color-mix(in srgb, var(--dsw-specific-sidebar-fill) 97%, rgb(122 155 240)) 40%, transparent);\n  border-right: none;\n}\n[data-platform=darwin] [data-ds-dark-theme] .AppFrame_sidebarCol {\n  background:\n    linear-gradient(\n      to bottom,\n      rgb(122 155 240 / 0.08),\n      rgb(122 155 240 / 0) 35%,\n      rgb(143 137 184 / 0) 68%,\n      rgb(143 137 184 / 0.07)),\n    color-mix(in srgb, var(--dsw-specific-sidebar-fill) 50%, transparent);\n}\n@media (prefers-reduced-transparency: reduce) {\n  [data-platform=darwin] .AppFrame_sidebarCol,\n  [data-platform=darwin] [data-ds-dark-theme] .AppFrame_sidebarCol {\n    background: color-mix(in srgb, var(--dsw-specific-sidebar-fill) 90%, transparent);\n  }\n}\n[data-platform=darwin] .AppFrame_centerCol {\n  background: var(--dsw-alias-bg-base);\n  border-left: 0.5px solid var(--dsw-alias-border-l3);\n}\n[data-platform=darwin] .AppFrame_rightbarCol {\n  background: var(--dsw-alias-bg-base);\n}\n[data-platform=darwin] [data-sidebar-collapsed] .AppFrame_centerCol {\n  border-left: none;\n}\n[data-platform=darwin] .AppFrame_frame[data-sidebar-collapsed] {\n  --dsh-frame-leading-clearance: 160px;\n}\n[data-platform=darwin][data-fullscreen] .AppFrame_frame[data-sidebar-collapsed] {\n  --dsh-frame-leading-clearance: 84px;\n}\n[data-platform=darwin][data-fullscreen] .AppFrame_leadingSeat {\n  left: 12px;\n}\n.AppFrame_leadingSeat {\n  position: absolute;\n  top: 11px;\n  left: 88px;\n  z-index: 15;\n  display: flex;\n  align-items: center;\n  -webkit-app-region: no-drag;\n}\n.AppFrame_handle {\n  position: absolute;\n  grid-area: 1 / 1 / 2 / -1;\n  top: 0;\n  bottom: 0;\n  width: 8px;\n  margin-left: -4px;\n  cursor: col-resize;\n  z-index: 11;\n  touch-action: none;\n}\n.AppFrame_frame[data-animating] .AppFrame_handle {\n  transition: left var(--ds-transition-duration-slow) var(--ds-ease-in-out);\n}\n.AppFrame_frame[data-dragging] .AppFrame_handle {\n  transition: none;\n}\n.AppFrame_frame[data-rightbar-fullscreen],\n.AppFrame_frame[data-rightbar-fullscreen] .AppFrame_handle,\n.AppFrame_frame[data-rightbar-instant],\n.AppFrame_frame[data-rightbar-instant] .AppFrame_handle {\n  transition: none;\n}\n@media (prefers-reduced-motion: reduce) {\n  .AppFrame_frame[data-animating] .AppFrame_handle {\n    transition: none;\n  }\n}\n.AppFrame_rightbarCol {\n  position: relative;\n  min-width: 0;\n  overflow: visible;\n}\n.AppFrame_bottomRow {\n  grid-column: 1 / -1;\n  min-width: 0;\n  min-height: 0;\n  overflow: auto;\n  background: var(--dsw-alias-bg-base);\n}\n.AppFrame_overlayLayer {\n  position: absolute;\n  inset: 0;\n  z-index: 20;\n  pointer-events: none;\n}\n.AppFrame_overlayLayer > * {\n  pointer-events: auto;\n}\n/*# sourceMappingURL=client.css.map */\n"; document.head.append(style); return () => style.remove(); };

"use strict";
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __hasOwnProp = Object.prototype.hasOwnProperty;
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: true });
};
var __copyProps = (to, from, except, desc) => {
  if (from && typeof from === "object" || typeof from === "function") {
    for (let key of __getOwnPropNames(from))
      if (!__hasOwnProp.call(to, key) && key !== except)
        __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
  }
  return to;
};
var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

// .host-source/packages/client/ui-layout/src/client/index.ts
var index_exports = {};
__export(index_exports, {
  LayoutController: () => LayoutController,
  apply: () => apply,
  inject: () => inject
});
module.exports = __toCommonJS(index_exports);

// .host-source/packages/client/ui-layout/src/client/AppFrame.tsx
var import_react2 = require("react");

// .host-source/packages/client/ui-layout/src/client/columns.ts
var CENTER_MIN = 400;
var SIDEBAR_MIN = 264;
var SIDEBAR_MAX = 420;
var SIDEBAR_DEFAULT = 280;
var SIDEBAR_COLLAPSED = 56;
var SIDEBAR_AUTO_COLLAPSE = 1024;
var RIGHTBAR_MIN = 300;
var RIGHTBAR_MAX_RATIO = 0.7;
var RIGHTBAR_DEFAULT_RATIO = 0.45;
function clampWidth(px, min, max) {
  return Math.min(max, Math.max(min, Math.round(px)));
}
function computeColumns(viewport, sidebar, rightbar, collapsedWidth = SIDEBAR_COLLAPSED) {
  const s = sidebar === 0 ? collapsedWidth : clampWidth(sidebar, SIDEBAR_MIN, SIDEBAR_MAX);
  const available = viewport - s - CENTER_MIN;
  const r = rightbar === 0 || available < RIGHTBAR_MIN ? 0 : Math.min(available, clampWidth(rightbar, RIGHTBAR_MIN, viewport * RIGHTBAR_MAX_RATIO));
  return { sidebar: s, center: Math.max(0, viewport - s - r), rightbar: r };
}

// .host-source/packages/client/ui-layout/src/client/DocumentTitle.tsx
var import_react = require("react");
function DocumentTitle({ useSessions, usePanelInfo, productTitle }) {
  const showSessionTitle = usePanelInfo((info) => info.activePanelId === null);
  const title = useSessions((state) => {
    const current = Object.values(state.byId).find((session) => (session.retainedBy.mainView ?? 0) > 0)?.id;
    return !showSessionTitle || current === void 0 ? void 0 : state.byId[current]?.title;
  });
  (0, import_react.useEffect)(() => {
    document.title = title === void 0 ? productTitle : `${title} \u2014 ${productTitle}`;
    return () => {
      document.title = productTitle;
    };
  }, [productTitle, title]);
  return null;
}

// .host-source/packages/client/ui-layout/src/client/AppFrame.module.css
var AppFrame_default = {
  frame: "AppFrame_frame",
  centerCol: "AppFrame_centerCol",
  sidebarCol: "AppFrame_sidebarCol",
  rightbarCol: "AppFrame_rightbarCol",
  leadingSeat: "AppFrame_leadingSeat",
  handle: "AppFrame_handle",
  bottomRow: "AppFrame_bottomRow",
  overlayLayer: "AppFrame_overlayLayer"
};

// .host-source/packages/client/ui-layout/src/client/AppFrame.tsx
var import_jsx_runtime = require("react/jsx-runtime");
function CenterColumn(props) {
  return /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
    "div",
    {
      className: AppFrame_default.centerCol,
      "aria-hidden": props.covered || void 0,
      ...props.covered ? { inert: "" } : {},
      children: props.children
    }
  );
}
function MainPanel({ usePanelInfo, renderSlot }) {
  const panelId = usePanelInfo((info) => info.activePanelId);
  return renderSlot("main", {}, { entryKey: panelId ?? "conversation" });
}
function RightbarColumn(props) {
  return /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: AppFrame_default.rightbarCol, "data-rightbar-col": true, children: props.children });
}
function DragHandle(props) {
  const [dragging, setDragging] = (0, import_react2.useState)(false);
  const origin = (0, import_react2.useRef)(0);
  const latest = (0, import_react2.useRef)(0);
  const frame = (0, import_react2.useRef)(null);
  const capture = (0, import_react2.useRef)(null);
  const callbacks = (0, import_react2.useRef)({ onStart: props.onStart, onDrag: props.onDrag, onEnd: props.onEnd });
  callbacks.current = { onStart: props.onStart, onDrag: props.onDrag, onEnd: props.onEnd };
  const endDrag = (0, import_react2.useCallback)(() => {
    const active = capture.current;
    if (active === null) return;
    capture.current = null;
    if (frame.current !== null) {
      cancelAnimationFrame(frame.current);
      frame.current = null;
    }
    if (active.element.hasPointerCapture(active.id)) active.element.releasePointerCapture(active.id);
    setDragging(false);
    callbacks.current.onEnd();
  }, []);
  (0, import_react2.useEffect)(() => endDrag, [endDrag]);
  const onPointerDown = (0, import_react2.useCallback)((e) => {
    if (e.button !== 0 || capture.current !== null) return;
    e.preventDefault();
    e.currentTarget.setPointerCapture(e.pointerId);
    capture.current = { element: e.currentTarget, id: e.pointerId };
    origin.current = e.clientX;
    latest.current = e.clientX;
    callbacks.current.onStart();
    setDragging(true);
  }, []);
  const onPointerMove = (0, import_react2.useCallback)((e) => {
    if (capture.current?.id !== e.pointerId) return;
    latest.current = e.clientX;
    frame.current ??= requestAnimationFrame(() => {
      frame.current = null;
      callbacks.current.onDrag(latest.current - origin.current);
    });
  }, []);
  const onPointerUp = (0, import_react2.useCallback)((e) => {
    if (capture.current?.id !== e.pointerId) return;
    callbacks.current.onDrag(e.clientX - origin.current);
    endDrag();
  }, [endDrag]);
  const onPointerCancel = (0, import_react2.useCallback)((e) => {
    if (capture.current?.id === e.pointerId) endDrag();
  }, [endDrag]);
  return /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
    "div",
    {
      className: AppFrame_default.handle,
      style: { left: props.left },
      "data-side": props.side,
      "data-dragging": dragging || void 0,
      onPointerDown,
      onPointerMove,
      onPointerUp,
      onPointerCancel,
      onLostPointerCapture: onPointerCancel
    }
  );
}
function AppFrame({
  useStore,
  useSessions,
  usePanelInfo,
  actions,
  renderSlot,
  t
}) {
  const layoutInfo = useStore((state) => state.layoutInfo);
  const frameRef = (0, import_react2.useRef)(null);
  const viewport = layoutInfo.viewportWidth;
  (0, import_react2.useLayoutEffect)(() => {
    const el = frameRef.current;
    if (el === null) return;
    let raf = null;
    let disposed = false;
    const measure = () => {
      const width = el.getBoundingClientRect().width;
      if (width > 0) actions.setViewportWidth(width);
    };
    measure();
    const observer = new ResizeObserver(() => {
      if (disposed) return;
      raf ??= requestAnimationFrame(() => {
        raf = null;
        measure();
      });
    });
    observer.observe(el);
    return () => {
      disposed = true;
      observer.disconnect();
      if (raf !== null) cancelAnimationFrame(raf);
    };
  }, [actions]);
  const narrow = viewport < SIDEBAR_AUTO_COLLAPSE;
  const sidebarCollapsed = narrow ? !layoutInfo.narrowExpanded : layoutInfo.sidebar === 0;
  const sidebarPreference = sidebarCollapsed ? 0 : layoutInfo.sidebar === 0 ? SIDEBAR_DEFAULT : layoutInfo.sidebar;
  const rightbarPreference = layoutInfo.rightbar ?? viewport * RIGHTBAR_DEFAULT_RATIO;
  const darwin = document.documentElement.dataset.platform === "darwin";
  const collapsedWidth = darwin || document.documentElement.hasAttribute("data-windows-titlebar") ? 0 : SIDEBAR_COLLAPSED;
  const normal = computeColumns(viewport, !layoutInfo.rightbarShown && narrow ? 0 : sidebarPreference, rightbarPreference, collapsedWidth);
  const cols = computeColumns(viewport, sidebarPreference, layoutInfo.rightbarTrack ? rightbarPreference : 0, collapsedWidth);
  const colsRef = (0, import_react2.useRef)(cols);
  colsRef.current = cols;
  const rightbarWidth = (0, import_react2.useRef)(normal.rightbar);
  rightbarWidth.current = normal.rightbar;
  const sidebarBase = (0, import_react2.useRef)(0);
  const rightbarBase = (0, import_react2.useRef)(0);
  const [dragging, setDragging] = (0, import_react2.useState)(false);
  const [animating, setAnimating] = (0, import_react2.useState)(0);
  const trackToggle = `${sidebarCollapsed}:${layoutInfo.rightbarTrack}`;
  const previousToggle = (0, import_react2.useRef)(trackToggle);
  const previousViewport = (0, import_react2.useRef)(viewport);
  (0, import_react2.useLayoutEffect)(() => {
    const viewportChanged = previousViewport.current !== viewport;
    previousViewport.current = viewport;
    if (previousToggle.current === trackToggle) return;
    previousToggle.current = trackToggle;
    if (viewportChanged) return;
    setAnimating((token) => token + 1);
  }, [trackToggle, viewport]);
  (0, import_react2.useEffect)(() => {
    if (animating === 0) return;
    const frame = frameRef.current;
    if (frame === null) return;
    const settle = () => {
      setAnimating(0);
    };
    const onTransitionEnd = (event) => {
      if (event.target === frame && event.propertyName === "grid-template-columns") settle();
    };
    frame.addEventListener("transitionend", onTransitionEnd);
    const timer = setTimeout(settle, 600);
    return () => {
      frame.removeEventListener("transitionend", onTransitionEnd);
      clearTimeout(timer);
    };
  }, [animating]);
  const onDragEnd = (0, import_react2.useCallback)(() => {
    setDragging(false);
  }, []);
  const onSidebarStart = (0, import_react2.useCallback)(() => {
    sidebarBase.current = colsRef.current.sidebar;
    setDragging(true);
  }, []);
  const onSidebarDrag = (0, import_react2.useCallback)((dx) => {
    actions.setSidebar(sidebarBase.current + dx);
  }, [actions]);
  const onRightbarStart = (0, import_react2.useCallback)(() => {
    rightbarBase.current = rightbarWidth.current;
    setDragging(true);
  }, []);
  const onRightbarDrag = (0, import_react2.useCallback)((dx) => {
    actions.setRightbar(rightbarBase.current - dx);
  }, [actions]);
  const productTitle = "DeepSeek Harness";
  const rightbarMax = cols.rightbar === 0 ? 0 : clampWidth(rightbarPreference, RIGHTBAR_MIN, viewport * RIGHTBAR_MAX_RATIO);
  const sidebar = (0, import_react2.useMemo)(() => renderSlot("sidebar", {
    collapsed: sidebarCollapsed,
    width: cols.sidebar
  }), [renderSlot, sidebarCollapsed, cols.sidebar]);
  const main = (0, import_react2.useMemo)(() => /* @__PURE__ */ (0, import_jsx_runtime.jsx)(MainPanel, { usePanelInfo, renderSlot }), [usePanelInfo, renderSlot]);
  const overlays = (0, import_react2.useMemo)(() => renderSlot("shell.overlay", {}), [renderSlot]);
  const leading = (0, import_react2.useMemo)(() => renderSlot("shell.leading", {}), [renderSlot]);
  const leadingMounted = darwin && sidebarCollapsed;
  return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(
    "div",
    {
      ref: frameRef,
      className: AppFrame_default.frame,
      style: {
        ...document.documentElement.hasAttribute("data-windows-titlebar") ? { "--dsh-windows-sidebar-width": `${cols.sidebar}px` } : {},
        gridTemplateColumns: `${cols.sidebar}px minmax(${cols.rightbar === 0 ? 0 : CENTER_MIN}px, 1fr) minmax(0px, ${rightbarMax}px)`
      },
      "data-sidebar-collapsed": sidebarCollapsed || void 0,
      "data-rightbar-collapsed": cols.rightbar === 0 || void 0,
      "data-rightbar-fullscreen": layoutInfo.rightbarFullscreen || void 0,
      "data-rightbar-instant": layoutInfo.rightbarInstant || void 0,
      "data-dragging": dragging || void 0,
      "data-animating": animating > 0 || void 0,
      children: [
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
          DocumentTitle,
          {
            productTitle,
            useSessions,
            usePanelInfo
          }
        ),
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: AppFrame_default.sidebarCol, children: sidebar }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [
          /* @__PURE__ */ (0, import_jsx_runtime.jsx)(CenterColumn, { covered: layoutInfo.rightbarFullscreen, children: main }),
          /* @__PURE__ */ (0, import_jsx_runtime.jsx)(RightbarColumn, { children: renderSlot("rightbar", { width: normal.rightbar, viewportWidth: viewport, canShow: normal.rightbar > 0 }) })
        ] }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: AppFrame_default.bottomRow, "data-shell-bottom": true, children: renderSlot("shell.bottom", {}) }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: AppFrame_default.overlayLayer, "data-shell-overlay": true, children: overlays }),
        leadingMounted && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: AppFrame_default.leadingSeat, "data-shell-leading": true, children: leading }),
        !sidebarCollapsed && /* @__PURE__ */ (0, import_jsx_runtime.jsx)(DragHandle, { side: "sidebar", left: cols.sidebar, onStart: onSidebarStart, onDrag: onSidebarDrag, onEnd: onDragEnd }),
        layoutInfo.rightbarShown && !layoutInfo.rightbarFullscreen && normal.rightbar > 0 && /* @__PURE__ */ (0, import_jsx_runtime.jsx)(DragHandle, { side: "rightbar", left: viewport - normal.rightbar, onStart: onRightbarStart, onDrag: onRightbarDrag, onEnd: onDragEnd })
      ]
    }
  );
}

// .host-source/packages/client/ui-layout/src/client/stores.ts
var import_dsh_client_store = require("@deepseek-ai/dsh-client-store");
function createLayoutStore() {
  const handle = (0, import_dsh_client_store.defineStore)({
    init: () => ({
      panelInfo: { activePanelId: null },
      layoutInfo: {
        sidebar: SIDEBAR_DEFAULT,
        viewportWidth: window.innerWidth,
        narrowExpanded: false,
        rightbar: null,
        rightbarShown: false,
        rightbarTrack: false,
        rightbarFullscreen: false,
        rightbarInstant: false
      }
    }),
    actions: {
      selectPanel: (d, panelId) => {
        d.panelInfo.activePanelId = panelId;
      },
      retainMainPanels: (d, panelIds) => {
        if (d.panelInfo.activePanelId !== null && !panelIds.includes(d.panelInfo.activePanelId)) {
          d.panelInfo.activePanelId = null;
        }
      },
      setSidebar: (d, px) => {
        d.layoutInfo.rightbarInstant = false;
        d.layoutInfo.sidebar = clampWidth(px, SIDEBAR_MIN, SIDEBAR_MAX);
      },
      // Narrow toggles flip only the override: the width preference survives
      // untouched, so re-widening restores the pre-squeeze layout.
      toggleSidebar: (d) => {
        d.layoutInfo.rightbarInstant = false;
        if (d.layoutInfo.viewportWidth < SIDEBAR_AUTO_COLLAPSE) d.layoutInfo.narrowExpanded = !d.layoutInfo.narrowExpanded;
        else d.layoutInfo.sidebar = d.layoutInfo.sidebar === 0 ? SIDEBAR_DEFAULT : 0;
      },
      // Crossing the breakpoint in either direction drops the override: the
      // narrow default is auto-collapsed, the wide state is the preference.
      setViewportWidth: (d, width) => {
        if (d.layoutInfo.viewportWidth === width) return;
        d.layoutInfo.rightbarInstant = false;
        if (d.layoutInfo.viewportWidth < SIDEBAR_AUTO_COLLAPSE !== width < SIDEBAR_AUTO_COLLAPSE) {
          d.layoutInfo.narrowExpanded = false;
        }
        d.layoutInfo.viewportWidth = width;
      },
      setRightbar: (d, px) => {
        d.layoutInfo.rightbarInstant = false;
        d.layoutInfo.rightbar = clampWidth(px, RIGHTBAR_MIN, Math.max(RIGHTBAR_MIN, d.layoutInfo.viewportWidth * RIGHTBAR_MAX_RATIO));
      },
      openRightbar: (d, track, fullscreen) => {
        if (!d.layoutInfo.rightbarShown || d.layoutInfo.rightbarTrack !== track || d.layoutInfo.rightbarFullscreen !== fullscreen) {
          d.layoutInfo.rightbarInstant = d.layoutInfo.rightbarFullscreen && !fullscreen;
        }
        if (!d.layoutInfo.rightbarShown && d.layoutInfo.viewportWidth < SIDEBAR_AUTO_COLLAPSE) d.layoutInfo.narrowExpanded = false;
        d.layoutInfo.rightbar ??= Math.max(RIGHTBAR_MIN, Math.round(d.layoutInfo.viewportWidth * RIGHTBAR_DEFAULT_RATIO));
        d.layoutInfo.rightbarShown = true;
        d.layoutInfo.rightbarTrack = track;
        d.layoutInfo.rightbarFullscreen = fullscreen;
      },
      closeRightbar: (d) => {
        if (d.layoutInfo.rightbarShown) d.layoutInfo.rightbarInstant = d.layoutInfo.rightbarFullscreen;
        d.layoutInfo.rightbarShown = false;
        d.layoutInfo.rightbarTrack = false;
        d.layoutInfo.rightbarFullscreen = false;
      }
    }
  });
  return handle;
}

// .host-source/packages/client/ui-layout/src/client/service.ts
var LayoutController = class {
  /**
   * @param panels - actions of the instance shared with the root entry.
   * @param hasMainPanel - checks the live main-slot registry for a panel id.
   * @param panelInfo - root store's shared central-panel selection source.
   */
  constructor(panels, hasMainPanel, panelInfo) {
    this.panels = panels;
    this.hasMainPanel = hasMainPanel;
    this.panelInfo = panelInfo;
  }
  panels;
  hasMainPanel;
  panelInfo;
  navigation = new AbortController();
  /** Select a global panel or return to the Conversation. */
  selectPanel(panelId) {
    if (panelId !== null && !this.hasMainPanel(panelId)) {
      throw new Error(`layout.selectPanel: main panel "${panelId}" is not registered`);
    }
    this.navigation.abort();
    this.panels.selectPanel(panelId);
  }
  /** @returns the new pending navigation's cancellation signal. */
  beginNavigation() {
    this.navigation.abort();
    this.navigation = new AbortController();
    return this.navigation.signal;
  }
  /** Invalidate pending navigations when the layout owner is unloaded. */
  dispose() {
    this.navigation.abort();
  }
  /** Toggle the sidebar panel (closed ⟷ contract default width). */
  toggleSidebar() {
    this.panels.toggleSidebar();
  }
  /** Report the right panel's track and fullscreen presentation. */
  openRightbar(track, fullscreen) {
    this.panels.openRightbar(track, fullscreen);
  }
  /** Report the right panel as hidden: no track, no handle. */
  closeRightbar() {
    this.panels.closeRightbar();
  }
};

// .host-source/packages/client/ui-layout/src/client/shortcut-locales.ts
var zh = { toggle: "\u5C55\u5F00\uFF0F\u6536\u8D77\u5DE6\u4FA7\u680F" };
var en = { toggle: "Toggle left sidebar" };

// .host-source/packages/client/ui-layout/src/client/theme-presenter.ts
var DARK_ATTRIBUTE = "data-ds-dark-theme";
var THEME_SOURCE_ATTRIBUTE = "data-ds-theme-source";
var CONTENT_FONT_SIZE_VARIABLE = "--dsh-content-font-size";
var FONT_SIZE_VARIABLES = {
  text: CONTENT_FONT_SIZE_VARIABLE,
  code: "--dsh-code-font-size",
  terminal: "--dsh-terminal-font-size"
};
var FONT_FAMILY_VARIABLES = {
  text: "--dsh-font-family-text",
  code: "--dsh-font-family-code",
  terminal: "--dsh-font-family-terminal"
};
var ThemePresenter = class {
  /** Token names this presenter wrote in the last apply (its retraction set). */
  appliedTokens = [];
  /** The single metadata node this presenter inserts and removes. */
  themeColorMeta;
  /** Create the presenter-owned metadata node before the first snapshot arrives. */
  constructor() {
    this.themeColorMeta = document.createElement("meta");
    this.themeColorMeta.name = "theme-color";
  }
  /**
   * Project a snapshot onto the document: set root `color-scheme` and the body
   * palette attribute from `active.colorScheme` (never the id — `system` is
   * resolved upstream), publish the font sizes and font lists, then replace the
   * previously applied token variables with `active.tokens`. Browser
   * theme-color metadata follows the computed body background after those
   * writes, so the rendered palette remains the color authority.
   * @param snapshot - resolved theme snapshot from ctx.theme.
   */
  apply(snapshot) {
    const scheme = snapshot.active.colorScheme;
    document.documentElement.style.colorScheme = scheme;
    document.documentElement.setAttribute(
      THEME_SOURCE_ATTRIBUTE,
      snapshot.preference === "system" ? "system" : scheme
    );
    const body = document.body;
    if (scheme === "dark") body.setAttribute(DARK_ATTRIBUTE, "");
    else body.removeAttribute(DARK_ATTRIBUTE);
    for (const [role, name] of Object.entries(FONT_SIZE_VARIABLES)) {
      body.style.setProperty(name, `${snapshot.fontSizes[role]}px`);
    }
    for (const [kind, name] of Object.entries(FONT_FAMILY_VARIABLES)) {
      const list = snapshot.fontFamilies[kind];
      if (list === "") body.style.removeProperty(name);
      else body.style.setProperty(name, list);
    }
    for (const name of this.appliedTokens) body.style.removeProperty(name);
    this.appliedTokens = [];
    for (const [name, value] of Object.entries(snapshot.active.tokens)) {
      body.style.setProperty(name, value);
      this.appliedTokens.push(name);
    }
    this.themeColorMeta.content = getComputedStyle(body).backgroundColor;
    if (!this.themeColorMeta.isConnected) document.head.append(this.themeColorMeta);
  }
  /**
   * Retract root color-scheme, the theme-source attribute, the palette
   * attribute, token variables, the font sizes, font lists, and the owned metadata node.
   */
  dispose() {
    document.documentElement.style.removeProperty("color-scheme");
    document.documentElement.removeAttribute(THEME_SOURCE_ATTRIBUTE);
    const body = document.body;
    body.removeAttribute(DARK_ATTRIBUTE);
    for (const name of Object.values(FONT_SIZE_VARIABLES)) body.style.removeProperty(name);
    for (const name of Object.values(FONT_FAMILY_VARIABLES)) body.style.removeProperty(name);
    for (const name of this.appliedTokens) body.style.removeProperty(name);
    this.appliedTokens = [];
    this.themeColorMeta.remove();
  }
};

// .host-source/packages/client/ui-layout/src/client/index.ts
var inject = ["slots", "theme", "locale", "shortcuts"];
function apply(ctx) {
  ctx.effect(cssDispose);
  ctx.effect(() => ctx.locale.register("shortcuts.layout", { zh, en }), "layout: command labels");
  const t = ctx.locale.bind("shortcuts.layout");
  ctx.effect(() => {
    const handle = createLayoutStore();
    const instance = handle.create();
    const store = { ...handle, create: () => instance };
    const retainMainPanels = () => {
      instance.actions.retainMainPanels(ctx.slots.entries("main").flatMap((entry) => entry.options.key === void 0 ? [] : [entry.options.key]));
    };
    const panelInfo = {
      getSnapshot: () => instance.getSnapshot().panelInfo,
      subscribe: (listener) => instance.subscribe(listener)
    };
    const layout = new LayoutController(instance.actions, (id) => ctx.slots.entries("main").some((entry) => entry.options.key === id), panelInfo);
    const disposePanelInfo = ctx.slots.provideRoot({ hooks: { panelInfo: layout.panelInfo } });
    const disposeService = ctx.reflect.provide("layout", layout);
    const disposeRegistration = ctx.slots.register({
      name: "root",
      locale: "common",
      children: {
        "sidebar": { kind: "single", scope: "root" },
        "main": { kind: "keyed", scope: "root" },
        "rightbar": { kind: "single", scope: "root" },
        "shell.bottom": { kind: "single", scope: "root" },
        "shell.overlay": { kind: "list", scope: "root" },
        "shell.leading": { kind: "single", scope: "root" }
      },
      store
    }, AppFrame);
    const disposeShortcut = ctx.shortcuts.register({
      id: "sidebar.left.toggle",
      label: () => t("toggle"),
      aliases: ["sidebar", "toggle left sidebar"],
      defaults: {
        "desktop:macos": { code: "KeyB", modifiers: ["primary"] },
        "desktop:windows": { code: "KeyB", modifiers: ["primary"] },
        "desktop:linux": { code: "KeyB", modifiers: ["primary"] },
        "web:macos": { code: "KeyB", modifiers: ["primary", "alt"] },
        "web:windows": { code: "KeyB", modifiers: ["primary", "alt"] }
      },
      regions: ["page", "editable"],
      modals: [],
      resolve: () => ({ status: "handled", run: () => {
        layout.toggleSidebar();
      } })
    });
    const disposePanels = ctx.slots.subscribe("main", retainMainPanels);
    retainMainPanels();
    return () => {
      disposeShortcut();
      layout.dispose();
      disposePanels();
      disposeRegistration();
      disposePanelInfo();
      void disposeService();
    };
  }, "ui-layout: service + root registration");
  ctx.effect(() => {
    const presenter = new ThemePresenter();
    presenter.apply(ctx.theme.getTheme());
    const off = ctx.on("theme/change", (snapshot) => {
      presenter.apply(snapshot);
    });
    return () => {
      off();
      presenter.dispose();
    };
  }, "ui-layout: theme presenter");
}
return module.exports; } });
//# sourceMappingURL=client.js.map
