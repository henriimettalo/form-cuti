window.__ModuleLoader__.load({ id: '@deepseek-ai/dsh-client-ui-sidebar-browser', factory: function(require) { var module = { exports: {} }; var exports = module.exports;
const cssDispose = () => { const style = document.createElement('style'); style.textContent = "/* .host-source/packages/client/ui-sidebar-browser/src/client/view/Browser.module.css */\n.Browser_root {\n  display: flex;\n  flex: 1 1 auto;\n  flex-direction: column;\n  height: 100%;\n  min-height: 0;\n  color: var(--dsw-alias-label-primary);\n  background: var(--dsw-alias-bg-base);\n}\n.Browser_toolbar {\n  display: flex;\n  flex: 0 0 auto;\n  gap: 4px;\n  align-items: center;\n  box-sizing: border-box;\n  height: 38px;\n  padding: 5px 6px;\n  border-bottom: 0.5px solid var(--dsw-alias-border-l3);\n}\n.Browser_tool {\n  display: inline-flex;\n  flex: 0 0 auto;\n  align-items: center;\n  justify-content: center;\n  width: 28px;\n  height: 28px;\n  padding: 0;\n  color: var(--dsw-alias-label-secondary);\n  background: transparent;\n  border: 0;\n  border-radius: var(--dsw-radius-sm);\n  cursor: pointer;\n}\n.Browser_tool:hover:not(:disabled) {\n  color: var(--dsw-alias-label-primary);\n  background: var(--dsw-alias-interactive-bg-hover);\n}\n.Browser_tool:disabled {\n  color: var(--dsw-alias-label-quaternary);\n  cursor: default;\n}\n.Browser_sandboxOff {\n  color: var(--dsw-alias-state-error-primary);\n  background: color-mix(in srgb, var(--dsw-alias-state-error-primary) 10%, transparent);\n}\n.Browser_addressBox {\n  position: relative;\n  flex: 1 1 auto;\n  min-width: 0;\n}\n.Browser_address {\n  width: 100%;\n  height: 28px;\n  box-sizing: border-box;\n  padding: 0 34px 0 9px;\n  color: var(--dsw-alias-label-primary);\n  font: var(--dsw-font-xxs-12);\n  background: var(--dsw-alias-bg-layer-1);\n  border: 0.5px solid var(--dsw-alias-border-l2);\n  border-radius: var(--dsw-radius-sm);\n}\n.Browser_addressGo {\n  position: absolute;\n  top: 0;\n  right: 0;\n  visibility: hidden;\n  opacity: 0;\n}\n.Browser_addressBox:focus-within .Browser_addressGo {\n  visibility: visible;\n  opacity: 1;\n}\n.Browser_addressUnknown {\n  --dsh-scrollbar-thumb: var(--dsw-alias-scrollbar-bg-l2);\n  --dsh-scrollbar-thumb-hover: var(--dsw-alias-scrollbar-hover-l2);\n  color: var(--dsw-alias-label-tertiary);\n  background: var(--dsw-alias-bg-layer-2);\n}\n.Browser_addressUnknown:focus {\n  color: var(--dsw-alias-label-primary);\n  background: var(--dsw-alias-bg-layer-1);\n}\n.Browser_addressChanged {\n  position: absolute;\n  top: 6px;\n  right: 8px;\n  padding-left: 8px;\n  color: var(--dsw-alias-label-tertiary);\n  font: var(--dsw-font-xxxs-11);\n  line-height: 16px;\n  pointer-events: none;\n  background: var(--dsw-alias-bg-layer-2);\n}\n.Browser_addressBox:focus-within .Browser_addressChanged {\n  visibility: hidden;\n  opacity: 0;\n}\n.Browser_address:focus {\n  outline: 1px solid var(--dsw-alias-state-business-primary);\n  outline-offset: -1px;\n}\n.Browser_frame {\n  display: flex;\n  flex: 1 1 auto;\n  width: 100%;\n  min-height: 0;\n  border: 0;\n  background: var(--dsw-alias-bg-base);\n}\n.Browser_content {\n  position: relative;\n  display: flex;\n  flex: 1 1 auto;\n  min-width: 0;\n  min-height: 0;\n}\n.Browser_viewport {\n  display: flex;\n  flex: 1 1 auto;\n  min-width: 0;\n  min-height: 0;\n}\n.Browser_placeholder {\n  position: absolute;\n  inset: 0;\n  display: flex;\n  pointer-events: none;\n}\n.Browser_restore {\n  position: absolute;\n  inset: 0;\n  display: flex;\n  flex-direction: column;\n  align-items: center;\n  gap: 10px;\n  padding: 24px;\n  overflow: auto;\n  text-align: center;\n}\n.Browser_restoreLabel {\n  margin: auto 0 0;\n  color: var(--dsw-alias-label-tertiary);\n  font: var(--dsw-font-xxs-12);\n}\n.Browser_restoreTitle {\n  max-width: 100%;\n  margin: 0;\n  overflow-wrap: anywhere;\n  font: var(--dsw-font-xs-13);\n  font-weight: 500;\n}\n.Browser_restoreUrl {\n  max-width: 100%;\n  margin: 0;\n  overflow-wrap: anywhere;\n  color: var(--dsw-alias-label-secondary);\n  font: var(--dsw-font-xxs-12);\n}\n.Browser_restore > :last-child {\n  flex-shrink: 0;\n  margin-bottom: auto;\n}\n.Browser_webview {\n  display: flex;\n  flex: 1 1 auto;\n  min-width: 0;\n  min-height: 0;\n  width: 100%;\n  height: 100%;\n  border: 0;\n  -webkit-app-region: no-drag;\n}\nhtml:has([data-dockkit-pointer]) .Browser_webview {\n  pointer-events: none;\n}\n.Browser_start {\n  display: flex;\n  flex: 1 1 auto;\n  align-items: center;\n  justify-content: center;\n  min-height: 0;\n  padding: 24px;\n  color: var(--dsw-alias-label-tertiary);\n  font: var(--dsw-font-xs-13);\n  text-align: center;\n}\n.Browser_failure {\n  flex: 0 0 auto;\n  padding: 6px 12px;\n  color: var(--dsw-alias-state-error-primary);\n  font: var(--dsw-font-xxxs-11);\n  background: color-mix(in srgb, var(--dsw-alias-state-error-primary) 8%, transparent);\n}\n.Browser_sandboxWarning {\n  flex: 0 0 auto;\n  padding: 6px 12px;\n  color: var(--dsw-alias-label-secondary);\n  font: var(--dsw-font-xxxs-11);\n  background: var(--dsw-alias-state-warn-tertiary);\n}\n.Browser_limit {\n  flex: 0 0 auto;\n  margin: 0;\n  padding: 4px 10px;\n  overflow: hidden;\n  color: var(--dsw-alias-label-tertiary);\n  font: var(--dsw-font-xxxs-11);\n  text-overflow: ellipsis;\n  white-space: nowrap;\n  border-top: 0.5px solid var(--dsw-alias-border-l3);\n}\n.Browser_titleIcon {\n  flex: 0 0 auto;\n  margin-right: 4px;\n}\n/*# sourceMappingURL=client.css.map */\n"; document.head.append(style); return () => style.remove(); };

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

// .host-source/packages/client/ui-sidebar-browser/src/client/index.ts
var index_exports = {};
__export(index_exports, {
  apply: () => apply,
  inject: () => inject
});
module.exports = __toCommonJS(index_exports);

// .host-source/packages/client/ui-sidebar-browser/src/client/view/BrowserBody.tsx
var import_react = require("react");
var import_dsh_client_ui_primitives = require("@deepseek-ai/dsh-client-ui-primitives");

// .host-source/packages/client/ui-sidebar-browser/src/client/browser/BrowserFrame.ts
function emptyBrowserFrame() {
  return {
    target: void 0,
    address: "empty",
    loading: false,
    canGoBack: false,
    canGoForward: false,
    error: void 0,
    sandboxEnabled: void 0
  };
}

// .host-source/packages/client/ui-sidebar-browser/src/client/browser/BrowserPersistence.ts
function currentBrowserTarget(state) {
  return state === void 0 || state.index < 0 ? void 0 : state.entries[state.index];
}
function browserAddressCheckpoint(target, revision) {
  return {
    entries: [target],
    index: 0,
    request: { target, revision },
    navigation: { status: "known", revision },
    failure: void 0
  };
}

// .host-source/packages/client/ui-sidebar-browser/src/client/view/Browser.module.css
var Browser_default = {
  root: "Browser_root",
  toolbar: "Browser_toolbar",
  tool: "Browser_tool",
  sandboxOff: "Browser_sandboxOff",
  addressBox: "Browser_addressBox",
  address: "Browser_address",
  addressGo: "Browser_addressGo",
  addressUnknown: "Browser_addressUnknown",
  addressChanged: "Browser_addressChanged",
  frame: "Browser_frame",
  content: "Browser_content",
  viewport: "Browser_viewport",
  placeholder: "Browser_placeholder",
  restore: "Browser_restore",
  restoreLabel: "Browser_restoreLabel",
  restoreTitle: "Browser_restoreTitle",
  restoreUrl: "Browser_restoreUrl",
  webview: "Browser_webview",
  start: "Browser_start",
  failure: "Browser_failure",
  sandboxWarning: "Browser_sandboxWarning",
  limit: "Browser_limit",
  titleIcon: "Browser_titleIcon"
};

// .host-source/packages/client/ui-sidebar-browser/src/client/view/BrowserBody.tsx
var import_jsx_runtime = require("react/jsx-runtime");
var EMPTY_FRAME = emptyBrowserFrame();
function SandboxPolicyIcon({ sandboxed }) {
  return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("svg", { width: "15", height: "15", viewBox: "0 0 16 16", fill: "none", "aria-hidden": true, children: [
    /* @__PURE__ */ (0, import_jsx_runtime.jsx)("path", { d: import_dsh_client_ui_primitives.SHIELD_OUTLINE_PATH, stroke: "currentColor", strokeWidth: import_dsh_client_ui_primitives.ICON_REGULAR_STROKE, strokeLinejoin: "round" }),
    sandboxed ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("path", { d: "M12.1654 5.7552L8.9447 9.41475C8.73044 9.65816 8.53628 9.8804 8.35774 10.0423C8.1713 10.2114 7.94235 10.3717 7.64016 10.4254C7.48207 10.4535 7.32 10.4552 7.16151 10.4294C6.85843 10.3801 6.62728 10.2223 6.43836 10.0559C6.25752 9.89653 6.06037 9.67732 5.84264 9.43705L4.72925 8.20897L5.63557 7.38707L6.74897 8.61594C6.98603 8.87755 7.12974 9.03533 7.24673 9.13839C7.31033 9.19443 7.34485 9.21476 7.35823 9.22122C7.38068 9.22484 7.40352 9.22515 7.42593 9.22122C7.40522 9.22502 7.42893 9.23294 7.53583 9.136C7.65132 9.03126 7.79316 8.87139 8.02643 8.60638L11.2479 4.94763L12.1654 5.7552Z", fill: "currentColor" }) : /* @__PURE__ */ (0, import_jsx_runtime.jsx)("path", { d: "M10.6074 4.40278L8.00975 6.99973L10.6074 9.59739L9.59736 10.6074L6.9997 8.00978L4.40274 10.6074L3.3927 9.59739L5.98966 6.99973L3.3927 4.40278L4.40274 3.39273L6.9997 5.98969L9.59736 3.39273L10.6074 4.40278Z", fill: "currentColor", transform: "translate(1.2 0.8)" })
  ] });
}
function useBrowserDraft(url, revision) {
  const [edit, setEdit] = (0, import_react.useState)();
  return [
    edit?.revision === revision ? edit.value : url ?? "",
    (value) => {
      setEdit({ revision, value });
    }
  ];
}
function BrowserBody(props) {
  const { mount, loadUrl, restore, goBack, goForward, reload, setSandbox, useBrowserState, useStore, useTabInfo, t } = props;
  const { tab } = useTabInfo();
  (0, import_react.useEffect)(() => tab.actions.bindCommands({ refresh: () => {
    reload(tab.id);
  } }), [tab.actions, tab.id, reload]);
  const saved = useStore((state2) => state2.byTab[tab.id]);
  const initial = (0, import_react.useRef)(saved);
  const initialUrl = (0, import_react.useRef)(tab.navigation.params?.url);
  const viewportId = (0, import_react.useId)();
  const [mountEpoch, setMountEpoch] = (0, import_react.useState)(0);
  const state = useBrowserState(tab.id);
  const frame = state?.frame ?? EMPTY_FRAME;
  const restoreTarget = state === void 0 ? currentBrowserTarget(initial.current) : state.restoreTarget;
  const target = frame.target ?? restoreTarget;
  const [draft, setDraft] = useBrowserDraft(target?.url ?? initialUrl.current, state?.addressRevision ?? 0);
  (0, import_react.useLayoutEffect)(() => {
    const hide = mount({
      tabId: tab.id,
      signal: tab.signal,
      viewportId,
      applicationOrigin: window.location.origin,
      initial: initial.current,
      initialUrl: initialUrl.current,
      openTab: (url) => {
        tab.actions.openTab("browser", { params: { url }, revealIfOpened: false });
      }
    });
    setMountEpoch((value) => value + 1);
    return hide;
  }, [mount, tab.id, tab.signal, tab.actions, viewportId, props.actions]);
  const unknown = frame.address === "unknown";
  const externalUrl = unknown ? void 0 : target?.url;
  const sandboxed = frame.sandboxEnabled;
  const failure = state?.addressFailure;
  const error = frame.error;
  const submit = (event) => {
    event.preventDefault();
    loadUrl(tab.id, draft);
  };
  const annotationOwner = (0, import_react.useMemo)(() => ({
    tabId: tab.id,
    url: unknown ? void 0 : frame.target?.url,
    loading: frame.loading || mountEpoch === 0,
    annotate: (options) => props.annotate(tab.id, options),
    cancelAnnotation: () => {
      props.cancelAnnotation(tab.id);
    }
  }), [tab.id, unknown, frame.target?.url, frame.loading, mountEpoch, props.annotate, props.cancelAnnotation]);
  return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { className: Browser_default.root, children: [
    /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("form", { className: Browser_default.toolbar, onSubmit: submit, children: [
      /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", { type: "button", className: Browser_default.tool, "aria-label": t("back"), title: t("back"), disabled: !frame.canGoBack, onClick: () => {
        goBack(tab.id);
      }, children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_dsh_client_ui_primitives.IconChevronLeftOutlineRegular, {}) }),
      /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", { type: "button", className: Browser_default.tool, "aria-label": t("forward"), title: t("forward"), disabled: !frame.canGoForward, onClick: () => {
        goForward(tab.id);
      }, children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_dsh_client_ui_primitives.IconChevronRightOutlineRegular, {}) }),
      props.renderSlot("sidebar.right.tab.browser.toolbar", annotationOwner),
      /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_dsh_client_ui_primitives.Tooltip, { label: t("reload"), shortcutKeys: tab.refreshShortcut?.keys, side: "bottom", delayMs: 500, children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", { type: "button", className: Browser_default.tool, "aria-label": t("reload"), "aria-keyshortcuts": tab.refreshShortcut?.aria, disabled: target === void 0 || mountEpoch === 0, onClick: () => {
        reload(tab.id);
      }, children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_dsh_client_ui_primitives.IconRefreshOutlineRegular, {}) }) }),
      /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { className: Browser_default.addressBox, children: [
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
          "input",
          {
            className: [Browser_default.address, unknown ? Browser_default.addressUnknown : ""].join(" "),
            value: draft,
            "aria-label": t("address.placeholder"),
            placeholder: t("address.placeholder"),
            spellCheck: false,
            onChange: (event) => {
              setDraft(event.currentTarget.value);
            }
          }
        ),
        unknown && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { className: Browser_default.addressChanged, children: t("address.changed") }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", { type: "submit", className: [Browser_default.tool, Browser_default.addressGo].join(" "), "aria-label": t("go"), title: t("go"), children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_dsh_client_ui_primitives.IconLinkOutlineRegular, {}) })
      ] }),
      /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
        "button",
        {
          type: "button",
          className: Browser_default.tool,
          "aria-label": t("external"),
          title: t("external"),
          disabled: externalUrl === void 0,
          onClick: externalUrl === void 0 ? void 0 : () => {
            window.open(externalUrl, "_blank", "noopener,noreferrer");
          },
          children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_dsh_client_ui_primitives.IconRightUpOutlineRegular, { size: 14 })
        }
      ),
      sandboxed !== void 0 && /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
        "button",
        {
          type: "button",
          className: [Browser_default.tool, sandboxed ? "" : Browser_default.sandboxOff].join(" "),
          "aria-label": t(sandboxed ? "sandbox.disable" : "sandbox.enable"),
          title: t(sandboxed ? "sandbox.disable" : "sandbox.enable"),
          "aria-pressed": !sandboxed,
          onClick: () => {
            setSandbox(tab.id, !sandboxed);
          },
          children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(SandboxPolicyIcon, { sandboxed })
        }
      )
    ] }),
    sandboxed === false && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: Browser_default.sandboxWarning, role: "status", children: t("sandbox.warning") }),
    error !== void 0 && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: Browser_default.failure, role: "status", children: error.code !== void 0 && error.description !== void 0 ? t("load.failed.detail", { code: String(error.code), description: error.description }) : t("load.failed") }),
    failure !== void 0 && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: Browser_default.failure, role: "alert", children: t(`error.${failure}`) }),
    /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { className: Browser_default.content, "aria-busy": frame.loading, children: [
      /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { id: viewportId, className: Browser_default.viewport, "aria-label": t("type.label") }),
      restoreTarget !== void 0 && /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("section", { className: Browser_default.restore, "aria-label": t("restore.previous"), children: [
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { className: Browser_default.restoreLabel, children: t("restore.previous") }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { className: Browser_default.restoreTitle, children: restoreTarget.title }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { className: Browser_default.restoreUrl, children: restoreTarget.url }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_dsh_client_ui_primitives.Button, { variant: "primary", size: "sm", disabled: mountEpoch === 0, onClick: () => {
          restore(tab.id);
        }, children: t("restore.action") })
      ] }),
      restoreTarget === void 0 && (target === void 0 || frame.loading) && error === void 0 && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: Browser_default.placeholder, children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: Browser_default.start, children: t(target === void 0 ? "start" : "loading") }) })
    ] }),
    props.renderSlot("sidebar.right.tab.browser.annotations", annotationOwner),
    unknown && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { className: Browser_default.limit, children: t("address.unknown") })
  ] });
}

// .host-source/packages/client/ui-sidebar-browser/src/client/view/BrowserTitle.tsx
var import_dsh_client_ui_primitives2 = require("@deepseek-ai/dsh-client-ui-primitives");
var import_jsx_runtime2 = require("react/jsx-runtime");
function BrowserTitle({ useTabInfo, useStore }) {
  const { tab } = useTabInfo();
  const entry = useStore((state) => currentBrowserTarget(state.byTab[tab.id]));
  return /* @__PURE__ */ (0, import_jsx_runtime2.jsxs)(import_jsx_runtime2.Fragment, { children: [
    /* @__PURE__ */ (0, import_jsx_runtime2.jsx)(import_dsh_client_ui_primitives2.IconGlobeOutlineRegular, { className: Browser_default.titleIcon }),
    entry?.title ?? tab.title
  ] });
}

// .host-source/packages/client/ui-sidebar-browser/src/client/browser/BrowserController.ts
var import_dsh_client_store = require("@deepseek-ai/dsh-client-store");

// .host-source/packages/client/ui-sidebar-browser/src/client/browser/url.ts
var MAX_BROWSER_URL_LENGTH = 16 * 1024;
function parseBrowserAddress(input, applicationOrigin) {
  const trimmed = input.trim();
  if (trimmed === "") return { ok: false, reason: "empty" };
  if (trimmed.length > MAX_BROWSER_URL_LENGTH) return { ok: false, reason: "invalid" };
  const explicitScheme = /^[A-Za-z][A-Za-z\d+.-]*:(?!\d+(?:[/?#]|$))/u.test(trimmed);
  const candidate = explicitScheme ? trimmed : `https://${trimmed}`;
  let url;
  try {
    url = new URL(candidate);
  } catch {
    return { ok: false, reason: "invalid" };
  }
  if (url.username !== "" || url.password !== "") return { ok: false, reason: "credentials" };
  if (url.protocol === "https:" || url.protocol === "http:") {
    if (applicationOrigin !== void 0 && applicationOrigin !== "null") {
      try {
        if (url.origin === new URL(applicationOrigin).origin) return { ok: false, reason: "application-origin" };
      } catch {
      }
    }
    return { ok: true, target: { kind: url.protocol === "https:" ? "https" : "http", url: url.href, title: url.hostname } };
  }
  return { ok: false, reason: "protocol" };
}

// .host-source/packages/client/ui-sidebar-browser/src/client/browser/BrowserController.ts
var BrowserController = class {
  /** @param options - identity, persistence, page factory and source-tab navigation. */
  constructor(options) {
    this.options = options;
    this.actions = options.actions;
    this.checkpoint = options.initial;
    this.page = options.createPage({
      initial: options.initial,
      persist: (state) => {
        if (this.disposed) return;
        this.checkpoint = state;
        this.actions.replace(options.tabId, state);
      },
      openRequested: (value) => {
        if (this.disposed) return;
        const result = parseBrowserAddress(value, options.applicationOrigin);
        if (!result.ok) {
          this.addressFailed(result.reason);
          return;
        }
        options.openTab(result.target.url);
      }
    });
    this.store = (0, import_dsh_client_store.createSnapshotStore)({
      frame: this.page.frame.getSnapshot(),
      restoreTarget: currentBrowserTarget(this.checkpoint),
      addressFailure: void 0,
      addressRevision: 0
    });
    this.unsubscribe = this.page.frame.subscribe(() => {
      if (this.disposed) return;
      const current = this.store.getSnapshot();
      const frame = this.page.frame.getSnapshot();
      const changed = frame.target?.url !== current.frame.target?.url;
      this.store.set({
        frame,
        restoreTarget: frame.target === void 0 ? currentBrowserTarget(this.checkpoint) : void 0,
        addressFailure: changed ? void 0 : current.addressFailure,
        addressRevision: current.addressRevision + Number(changed)
      });
    });
    options.signal.addEventListener("abort", this.abort, { once: true });
  }
  options;
  page;
  store;
  unsubscribe;
  actions;
  checkpoint;
  started = false;
  disposed = false;
  disposal;
  annotation;
  abort = () => {
    void this.dispose();
  };
  /** @returns immutable state for the common toolbar. */
  getSnapshot = () => this.store.getSnapshot();
  /** @param listener - state invalidation. @returns unsubscribe callback. */
  subscribe = (listener) => this.store.subscribe(listener);
  /**
   * Attach the page without transferring ownership of its tab occurrence.
   * @param viewportId - mounted content container.
   * @returns physical attachment cleanup only.
   */
  mount(viewportId) {
    this.publishSaved();
    const unmount = this.page.presentation.mount(viewportId);
    return () => {
      this.cancelAnnotation();
      unmount();
    };
  }
  /** @param options - localized picker configuration. @returns saved evidence or cancellation. */
  async annotate(options) {
    this.cancelAnnotation();
    const frame = this.page.frame.getSnapshot();
    if (this.disposed || frame.loading || frame.address === "unknown" || frame.target === void 0 || this.page.presentation.annotate === void 0) throw new Error("annotation-unavailable");
    const lifetime = new AbortController();
    this.annotation = lifetime;
    try {
      const result = await this.page.presentation.annotate(frame.target.url, options, lifetime.signal);
      return lifetime.signal.aborted ? { cancelled: true } : result;
    } finally {
      if (this.annotation === lifetime) this.annotation = void 0;
    }
  }
  /** Cancel the current tab's picker. */
  cancelAnnotation() {
    this.annotation?.abort();
    this.annotation = void 0;
  }
  /**
   * Consume initial navigation once; a saved checkpoint alone never starts a page.
   * @param initialUrl - explicit typed-open address, or absence.
   */
  start(initialUrl) {
    if (this.started || this.disposed) return;
    this.started = true;
    if (initialUrl !== void 0) this.loadUrl(initialUrl);
  }
  /** Load the saved address only after an explicit restore action. */
  restore() {
    const target = this.store.getSnapshot().restoreTarget;
    if (target !== void 0) this.loadUrl(target.url);
  }
  /**
   * Validate an address before navigation, publishing invalid input for correction.
   * @param value - address-bar or typed-open input.
   */
  loadUrl(value) {
    if (this.disposed) return;
    const parsed = parseBrowserAddress(value, this.options.applicationOrigin);
    if (!parsed.ok) {
      this.addressFailed(parsed.reason);
      return;
    }
    this.command(() => {
      this.page.frame.loadUrl(parsed.target);
    });
  }
  /** Delegate Back to the page's navigation provider. */
  goBack() {
    this.command(() => {
      this.page.frame.goBack();
    });
  }
  /** Delegate Forward to the page's navigation provider. */
  goForward() {
    this.command(() => {
      this.page.frame.goForward();
    });
  }
  /** Restore a saved address, or reload the already requested page. */
  reload() {
    if (this.store.getSnapshot().restoreTarget !== void 0) this.restore();
    else this.command(() => {
      this.page.frame.reload();
    });
  }
  /**
   * Apply the optional embedding-sandbox control; unsupported providers remain unchanged.
   * @param enabled - whether to enforce the provider's embedding sandbox.
   */
  setSandbox(enabled) {
    const sandbox = this.page.frame.sandbox;
    if (sandbox !== void 0) this.command(() => {
      sandbox.setEnabled(enabled);
    });
  }
  /**
   * Redirect future checkpoint writes to a replacement Session binding.
   * @param actions - replacement persistence writer.
   */
  rebind(actions) {
    this.actions = actions;
  }
  /**
   * Release the page and detach occurrence and state listeners.
   * @returns after page teardown; repeated callers join the same disposal.
   */
  dispose() {
    if (this.disposal !== void 0) return this.disposal;
    this.disposed = true;
    this.cancelAnnotation();
    this.options.signal.removeEventListener("abort", this.abort);
    this.unsubscribe();
    this.disposal = this.page.frame.dispose();
    return this.disposal;
  }
  publishSaved() {
    if (this.checkpoint !== void 0) this.actions.replace(this.options.tabId, this.checkpoint);
  }
  addressFailed(reason) {
    this.store.set({ ...this.store.getSnapshot(), addressFailure: reason });
  }
  command(run) {
    if (this.disposed) return;
    this.cancelAnnotation();
    const current = this.store.getSnapshot();
    this.store.set({ ...current, addressFailure: void 0, addressRevision: current.addressRevision + 1 });
    run();
  }
};
function createBrowserControllers(actions, createPage, isTabOpen) {
  let currentActions = actions;
  const controllers = /* @__PURE__ */ new Map();
  const controller = (id) => controllers.get(id)?.controller;
  return {
    annotate: (id, options) => controller(id)?.annotate(options) ?? Promise.reject(new Error("annotation-unavailable")),
    cancelAnnotation: (id) => {
      controller(id)?.cancelAnnotation();
    },
    keyedHooks: { browserState: (key) => controller(key) },
    mount(request) {
      const { tabId, signal } = request;
      if (signal.aborted) return () => {
      };
      let held = controllers.get(tabId);
      if (held?.signal !== signal) {
        if (held !== void 0) {
          held.signal.removeEventListener("abort", held.forget);
          void held.controller.dispose();
        }
        const created = new BrowserController({ ...request, actions: currentActions, createPage });
        const forget = () => {
          controllers.delete(tabId);
          if (!isTabOpen(tabId)) currentActions.forget(tabId);
        };
        held = { signal, controller: created, forget };
        controllers.set(tabId, held);
        signal.addEventListener("abort", forget, { once: true });
      }
      const hide = held.controller.mount(request.viewportId);
      held.controller.start(request.initialUrl);
      return hide;
    },
    dispose: async () => {
      const pending = [...controllers.values()].map(({ signal, controller: controller2, forget }) => {
        signal.removeEventListener("abort", forget);
        return controller2.dispose();
      });
      controllers.clear();
      await Promise.all(pending);
    },
    rebind: (actions2) => {
      currentActions = actions2;
      for (const { controller: controller2 } of controllers.values()) controller2.rebind(actions2);
    },
    loadUrl: (id, value) => {
      controller(id)?.loadUrl(value);
    },
    restore: (id) => {
      controller(id)?.restore();
    },
    goBack: (id) => {
      controller(id)?.goBack();
    },
    goForward: (id) => {
      controller(id)?.goForward();
    },
    reload: (id) => {
      controller(id)?.reload();
    },
    setSandbox: (id, enabled) => {
      controller(id)?.setSandbox(enabled);
    }
  };
}

// .host-source/packages/client/ui-sidebar-browser/src/client/browser/IframeImpl.ts
var import_dsh_client_store2 = require("@deepseek-ai/dsh-client-store");

// .host-source/packages/client/ui-sidebar-browser/src/client/browser/BrowserNavigation.ts
var MAX_BROWSER_HISTORY = 100;
var BrowserNavigation = class _BrowserNavigation {
  value;
  /**
   * @param initial - persisted state restored for this tab, or a fresh empty state.
   */
  constructor(initial = _BrowserNavigation.empty()) {
    this.value = initial;
  }
  /**
   * Create state before a tab has a controlled navigation target.
   * @returns empty serializable state.
   */
  static empty() {
    return { entries: [], index: -1, request: void 0, navigation: { status: "empty" }, failure: void 0 };
  }
  /**
   * Read the selected application-history entry.
   * @param state - serializable tab state.
   * @returns the current target, if any.
   */
  static current(state) {
    return state === void 0 || state.index < 0 ? void 0 : state.entries[state.index];
  }
  /**
   * Test whether the Web carrier can use the preceding application-history entry.
   * @param state - serializable tab state.
   * @returns whether Back is available.
   */
  static canGoBack(state) {
    return state.navigation.status !== "unknown" && state.index > 0;
  }
  /**
   * Test whether the Web carrier can use the following application-history entry.
   * @param state - serializable tab state.
   * @returns whether Forward is available.
   */
  static canGoForward(state) {
    return state.navigation.status !== "unknown" && state.index >= 0 && state.index < state.entries.length - 1;
  }
  /** Current immutable serializable state. */
  get snapshot() {
    return this.value;
  }
  /** Whether the Web iframe can safely use the application-owned Back entry. */
  get canGoBack() {
    return _BrowserNavigation.canGoBack(this.value);
  }
  /** Whether the Web iframe can safely use the application-owned Forward entry. */
  get canGoForward() {
    return _BrowserNavigation.canGoForward(this.value);
  }
  /**
   * Add a controlled target and discard its stale forward branch.
   * @param target - validated canonical target.
   * @returns the new load request.
   */
  navigate(target) {
    const entries = [...this.value.entries.slice(0, this.value.index + 1), target];
    if (entries.length > MAX_BROWSER_HISTORY) entries.splice(0, entries.length - MAX_BROWSER_HISTORY);
    return this.request(target, { ...this.value, entries, index: entries.length - 1 });
  }
  /**
   * Select the preceding application-known target.
   * @returns a new load request, or undefined when unavailable.
   */
  back() {
    if (!this.canGoBack) return void 0;
    const index = this.value.index - 1;
    const target = this.value.entries[index];
    return this.request(target, { ...this.value, index });
  }
  /**
   * Select the following application-known target.
   * @returns a new load request, or undefined when unavailable.
   */
  forward() {
    if (!this.canGoForward) return void 0;
    const index = this.value.index + 1;
    const target = this.value.entries[index];
    return this.request(target, { ...this.value, index });
  }
  /**
   * Start another load of the last application-known target.
   * @returns a new load request, or undefined before the first target.
   */
  reload() {
    const target = _BrowserNavigation.current(this.value);
    return target === void 0 ? void 0 : this.request(target, this.value);
  }
  /**
   * Record a frame load for its captured revision.
   * @param revision - revision bound to the rendered frame.
   */
  frameLoaded(revision) {
    const navigation = this.value.navigation;
    if (navigation.status === "empty" || navigation.revision !== revision) return;
    if (navigation.status === "loading") {
      this.value = { ...this.value, navigation: { status: "known", revision } };
    } else if (navigation.status === "known") {
      this.value = { ...this.value, navigation: { status: "unknown", revision } };
    }
  }
  request(target, basis) {
    const request = { revision: (this.value.request?.revision ?? 0) + 1, target };
    this.value = {
      ...basis,
      request,
      navigation: { status: "loading", revision: request.revision },
      failure: void 0
    };
    return request;
  }
};

// .host-source/packages/client/ui-sidebar-browser/src/client/browser/IframeImpl.ts
var IframeImpl = class {
  /** @param options - initial checkpoint and persistence writer. @param presentation - iframe DOM adapter. */
  constructor(options, presentation) {
    this.options = options;
    this.presentation = presentation;
    this.navigation = new BrowserNavigation(options.initial);
    this.store = (0, import_dsh_client_store2.createSnapshotStore)({ ...emptyBrowserFrame(), sandboxEnabled: this.sandboxed });
  }
  options;
  presentation;
  sandbox = { setEnabled: (enabled) => {
    this.setSandbox(enabled);
  } };
  navigation;
  store;
  sandboxed = true;
  error;
  disposed = false;
  disposal;
  /**
   * Record a controlled load or a later navigation to an unreadable address.
   * @param revision - document generation whose iframe emitted load.
   */
  handleLoaded(revision) {
    if (this.disposed) return;
    this.navigation.frameLoaded(revision);
    this.publish();
  }
  /**
   * Publish a failure only for the current document generation.
   * @param revision - document generation whose iframe reported failure.
   */
  handleLoadFailed(revision) {
    if (this.disposed || this.navigation.snapshot.request?.revision !== revision) return;
    this.error = { code: void 0, description: void 0 };
    this.publish();
  }
  /** @returns immutable navigation state. */
  getSnapshot = () => this.store.getSnapshot();
  /** @param listener - state invalidation. @returns unsubscribe callback. */
  subscribe = (listener) => this.store.subscribe(listener);
  /** @param target - validated address; submitting the current address reloads it. */
  loadUrl(target) {
    if (this.disposed) return;
    const request = BrowserNavigation.current(this.navigation.snapshot)?.url === target.url ? this.navigation.reload() : this.navigation.navigate(target);
    this.load(request);
  }
  /** Move through application-known history while the iframe address remains known. */
  goBack() {
    if (!this.disposed) this.load(this.navigation.back());
  }
  /** Move through application-known history while the iframe address remains known. */
  goForward() {
    if (!this.disposed) this.load(this.navigation.forward());
  }
  /** Reload the last application-known address. */
  reload() {
    if (!this.disposed) this.load(this.navigation.reload());
  }
  /** @returns after the presentation has been removed; repeated calls join disposal. */
  dispose() {
    if (this.disposal !== void 0) return this.disposal;
    this.disposed = true;
    this.presentation.dispose();
    this.disposal = Promise.resolve();
    return this.disposal;
  }
  setSandbox(enabled) {
    if (this.disposed || enabled === this.sandboxed) return;
    this.sandboxed = enabled;
    if (this.store.getSnapshot().target === void 0) {
      this.store.set({ ...this.store.getSnapshot(), sandboxEnabled: enabled });
      return;
    }
    this.load(this.navigation.reload());
  }
  load(request) {
    if (request === void 0) return;
    this.error = void 0;
    this.publish();
    this.presentation.show({ target: request.target, revision: request.revision, sandboxed: this.sandboxed });
  }
  snapshot() {
    const state = this.navigation.snapshot;
    const target = BrowserNavigation.current(state);
    return {
      target,
      address: target === void 0 ? "empty" : state.navigation.status === "unknown" ? "unknown" : "requested",
      loading: state.navigation.status === "loading" && this.error === void 0,
      canGoBack: this.navigation.canGoBack,
      canGoForward: this.navigation.canGoForward,
      error: this.error,
      sandboxEnabled: this.sandboxed
    };
  }
  publish() {
    this.store.set(this.snapshot());
    this.options.persist(this.navigation.snapshot);
  }
};

// .host-source/packages/client/ui-sidebar-browser/src/client/annotation/protocol.ts
function object(value) {
  return !!value && typeof value === "object" && !Array.isArray(value);
}
function string(value, limit) {
  return typeof value === "string" && value.length <= limit;
}
function number(value) {
  return typeof value === "number" && Number.isFinite(value) && Math.abs(value) <= 1e7;
}
function rect(value) {
  return object(value) && number(value.x) && number(value.y) && number(value.width) && number(value.height) && value.width >= 0 && value.height >= 0;
}
function isAnnotationResult(value) {
  if (!object(value)) return false;
  if (value.cancelled === true) return Object.keys(value).length === 1;
  if (value.intent !== "comment" && value.intent !== "question" || !string(value.note, 1e3) || !value.note.trim() || !string(value.url, 8192) || !string(value.title, 300))
    return false;
  if (value.styleChanges !== void 0 && (!object(value.styleChanges) || Object.keys(value.styleChanges).length > 10 || !Object.entries(value.styleChanges).every(
    ([key, change]) => [
      "color",
      "background-color",
      "opacity",
      "font-family",
      "font-size",
      "font-weight",
      "padding",
      "margin",
      "gap"
    ].includes(key) && object(change) && string(change.before, 1e3) && string(change.after, 1e3)
  )))
    return false;
  if (value.screenshot !== void 0 && (!object(value.screenshot) || value.screenshot.mediaType !== "image/jpeg" || !string(value.screenshot.data, 4e6) || !/^[A-Za-z0-9+/]+={0,2}$/.test(value.screenshot.data) || !number(value.screenshot.width) || !number(value.screenshot.height) || value.screenshot.width <= 0 || value.screenshot.width > 1600 || value.screenshot.height <= 0 || value.screenshot.height > 1600))
    return false;
  try {
    if (!["http:", "https:"].includes(new URL(value.url).protocol)) return false;
  } catch {
    return false;
  }
  if (!object(value.viewport) || !number(value.viewport.width) || !number(value.viewport.height) || value.viewport.width <= 0 || value.viewport.height <= 0)
    return false;
  const selection = value.selection;
  if (!object(selection) || !["element", "region", "point"].includes(String(selection.kind)) || !rect(selection.rect) || !rect(selection.viewportRect) || !object(selection.scroll) || !number(selection.scroll.x) || !number(selection.scroll.y) || !Array.isArray(selection.candidates) || selection.candidates.length > 12)
    return false;
  if (selection.kind === "element" && selection.candidates.length !== 1) return false;
  for (const item of selection.candidates) {
    if (!object(item) || !string(item.selector, 8192) || !Array.isArray(item.shadowHosts) || item.shadowHosts.length > 32 || !item.shadowHosts.every((x) => string(x, 8192)) || item.matchCount !== 1 || !string(item.tag, 200) || !string(item.text, 400) || !rect(item.rect) || !object(item.styles) || Object.keys(item.styles).length > 16 || !Object.entries(item.styles).every(([key, val]) => string(key, 100) && string(val, 1e3)) || item.source !== void 0 && !string(item.source, 1e3))
      return false;
  }
  try {
    return JSON.stringify({ ...value, screenshot: void 0 }).length <= 64e3;
  } catch {
    return false;
  }
}

// .host-source/packages/client/ui-sidebar-browser/src/client/annotation/iframe-annotation.ts
function annotateIframe(frame, url, options, signal) {
  if (signal.aborted) return Promise.resolve({ cancelled: true });
  return new Promise((resolve, reject) => {
    const channel = new MessageChannel();
    let ended = false;
    let ready = false;
    const handshake = setTimeout(() => {
      if (!ready) finish(void 0, new Error("annotation-unavailable"));
    }, 3e3);
    function finish(value, error) {
      if (ended) return;
      ended = true;
      channel.port1.postMessage({ type: "cancel" });
      channel.port1.close();
      channel.port2.close();
      clearTimeout(handshake);
      signal.removeEventListener("abort", cancel);
      frame.removeEventListener("load", cancel);
      if (error) reject(error);
      else resolve(value || { cancelled: true });
    }
    function cancel() {
      finish({ cancelled: true });
    }
    signal.addEventListener("abort", cancel, { once: true });
    frame.addEventListener("load", cancel, { once: true });
    channel.port1.onmessage = (event) => {
      if (event.data?.ready === true && !ready) {
        ready = true;
        clearTimeout(handshake);
        return;
      }
      if (!isAnnotationResult(event.data)) {
        finish(void 0, new Error("annotation-invalid"));
        return;
      }
      if (!("cancelled" in event.data) && event.data.url !== url) {
        finish(void 0, new Error("annotation-page-changed"));
        return;
      }
      finish(event.data);
    };
    try {
      if (!frame.contentWindow) {
        finish(void 0, new Error("annotation-unavailable"));
        return;
      }
      frame.contentWindow.postMessage(
        { channel: "dsh-browser-annotation-v1", type: "start", options },
        new URL(url).origin,
        [channel.port2]
      );
      frame.focus();
    } catch (error) {
      finish(void 0, error instanceof Error ? error : new Error("annotation-unavailable"));
    }
  });
}

// .host-source/packages/client/ui-sidebar-browser/src/client/view/IframePresentation.ts
var WEB_BROWSER_SANDBOX = "allow-scripts allow-forms allow-same-origin allow-popups allow-popups-to-escape-sandbox";
var IframePresentation = class {
  /** @param events - provider-owned load and remount callbacks. */
  constructor(events) {
    this.events = events;
  }
  events;
  host;
  element;
  document;
  rendered = false;
  /** @param url - controller-known URL. @param options - picker copy. @param signal - lifetime. @returns saved annotation or cancellation. */
  annotate(url, options, signal) {
    if (this.element === void 0) return Promise.reject(new Error("annotation-unavailable"));
    return annotateIframe(this.element, url, options, signal);
  }
  /** @param viewportId - mounted placeholder. @returns removes only the iframe presentation. */
  mount(viewportId) {
    const host = document.getElementById(viewportId);
    if (host === null) throw new Error("iframe presentation: viewport is not mounted");
    this.element?.remove();
    this.element = void 0;
    this.host = host;
    if (this.rendered) this.events.remounted();
    else this.render();
    return () => {
      if (this.host !== host) return;
      this.element?.remove();
      this.element = void 0;
      this.host = void 0;
    };
  }
  /**
   * Retain the prepared document and render it when a container is mounted.
   * @param value - prepared document and event generation.
   */
  show(value) {
    this.document = value;
    this.render();
  }
  /** Remove the owned DOM and retained presentation data. */
  dispose() {
    this.element?.remove();
    this.element = void 0;
    this.host = void 0;
    this.document = void 0;
  }
  render() {
    const current = this.document;
    const host = this.host;
    if (current === void 0 || host === void 0) return;
    this.element?.remove();
    const element = document.createElement("iframe");
    element.className = Browser_default.frame;
    element.src = current.target.url;
    element.title = current.target.title;
    element.referrerPolicy = "no-referrer";
    element.dataset.sidebarBrowserFrame = "iframe";
    if (current.sandboxed) element.setAttribute("sandbox", WEB_BROWSER_SANDBOX);
    element.addEventListener("load", () => {
      if (this.element === element) this.events.loaded(current.revision);
    });
    element.addEventListener("error", () => {
      if (this.element === element) this.events.failed(current.revision);
    });
    this.element = element;
    this.rendered = true;
    host.append(element);
  }
};

// .host-source/packages/client/ui-sidebar-browser/src/client/pages.ts
function createIframePage(options) {
  const presentation = new IframePresentation({
    loaded: (revision) => {
      frame.handleLoaded(revision);
    },
    failed: (revision) => {
      frame.handleLoadFailed(revision);
    },
    remounted: () => {
      frame.reload();
    }
  });
  const frame = new IframeImpl(options, presentation);
  return { frame, presentation };
}

// .host-source/packages/client/ui-sidebar-browser/src/client/electron/ElectronWebViewImpl.ts
var import_dsh_client_store3 = require("@deepseek-ai/dsh-client-store");
var ElectronWebViewImpl = class {
  /**
   * @param options - saved address, persistence and source-tab opening callback.
   * @param bridge - main-process guest operations.
   * @param workspace - resolves the storage account once for this frame lifetime.
   * @param presentation - tag and Sidebar placement adapter.
   */
  constructor(options, bridge, workspace, presentation) {
    this.options = options;
    this.bridge = bridge;
    this.workspace = workspace;
    this.presentation = presentation;
    this.checkpoint = currentBrowserTarget(options.initial);
    this.store = (0, import_dsh_client_store3.createSnapshotStore)(emptyBrowserFrame());
  }
  options;
  bridge;
  workspace;
  presentation;
  store;
  lifetime = new AbortController();
  guestLifetime;
  element;
  lease;
  workspaceKey;
  initializing;
  ready = false;
  pending;
  revision = 0;
  firstDocument = true;
  checkpoint;
  disposal;
  attachment;
  releases = /* @__PURE__ */ new Set();
  /** @returns immutable carrier-neutral navigation state. */
  getSnapshot = () => this.store.getSnapshot();
  /** @param listener - state invalidation. @returns unsubscribe callback. */
  subscribe = (listener) => this.store.subscribe(listener);
  /** A physical mount may recreate a lost guest; ordinary Sidebar hiding never calls this. */
  attach() {
    if (this.lifetime.signal.aborted) return;
    this.attachment = new AbortController();
    this.pending ??= this.store.getSnapshot().target;
    if (this.pending !== void 0) {
      this.store.set({
        ...this.store.getSnapshot(),
        address: "requested",
        loading: true,
        canGoBack: false,
        canGoForward: false,
        error: void 0
      });
      this.initialize();
    }
  }
  /** Invalidate pending attachment before the containing DOM is removed. */
  detach() {
    this.attachment?.abort();
    this.attachment = void 0;
    this.pending = this.store.getSnapshot().target;
    void this.dropGuest();
  }
  /** @param target - validated address, loaded without replacing the guest. */
  loadUrl(target) {
    if (this.lifetime.signal.aborted) return;
    const current = this.store.getSnapshot();
    if (this.ready && current.address === "observed" && current.target?.url === target.url) {
      this.reload();
      return;
    }
    this.revision++;
    this.pending = target;
    this.store.set({ ...current, target, address: "requested", loading: true, error: void 0 });
    this.persist(target);
    if (this.ready) this.loadPending();
    else this.initialize();
  }
  /** Move backward through Chromium history. */
  goBack() {
    if (this.store.getSnapshot().canGoBack) this.navigate("goBack");
  }
  /** Move forward through Chromium history. */
  goForward() {
    if (this.store.getSnapshot().canGoForward) this.navigate("goForward");
  }
  /** Reload the actual current page, or retry failed guest creation. */
  reload() {
    const current = this.store.getSnapshot();
    if (this.lifetime.signal.aborted || current.target === void 0) return;
    if (!this.ready || current.error !== void 0) {
      this.revision++;
      this.pending = current.target;
      this.store.set({ ...current, loading: true, error: void 0 });
      if (this.ready) this.loadPending();
      else this.initialize();
    } else this.navigate("reload");
  }
  /** @returns after pending initialization and the owned guest have been released. */
  dispose() {
    if (this.disposal !== void 0) return this.disposal;
    this.lifetime.abort();
    this.pending = void 0;
    this.disposal = Promise.all([this.dropGuest(), this.initializing]).then(() => Promise.all(this.releases)).then(() => {
    });
    this.presentation.dispose();
    return this.disposal;
  }
  navigate(command) {
    if (this.lifetime.signal.aborted || !this.ready || this.element === void 0) return;
    this.revision++;
    this.pending = void 0;
    this.store.set({ ...this.store.getSnapshot(), loading: true, error: void 0 });
    try {
      this.element[command]();
    } catch (error) {
      this.commandFailed(error);
    }
  }
  initialize() {
    const attachment = this.attachment;
    if (attachment === void 0 || this.initializing !== void 0 || this.element !== void 0 || this.lifetime.signal.aborted) return;
    const signal = AbortSignal.any([this.lifetime.signal, attachment.signal]);
    this.initializing = this.createGuest(signal).catch(async (error) => {
      await this.dropGuest();
      if (!signal.aborted) this.commandFailed(error);
    }).finally(() => {
      this.initializing = void 0;
      if (this.attachment !== attachment && this.pending !== void 0) this.initialize();
    });
  }
  async createGuest(attachmentSignal) {
    this.workspaceKey ??= await this.workspace(attachmentSignal);
    if (attachmentSignal.aborted) return;
    const reservation = await this.bridge.acquire(this.workspaceKey);
    if (attachmentSignal.aborted) {
      await this.release(reservation.lease);
      return;
    }
    this.lease = reservation.lease;
    this.guestLifetime = new AbortController();
    const signal = AbortSignal.any([attachmentSignal, this.guestLifetime.signal]);
    const element = this.presentation.createElement(reservation);
    this.element = element;
    const unsubscribeOpen = this.bridge.onOpenRequested(reservation.lease, (url) => {
      if (this.element === element && !signal.aborted) this.options.openRequested(url);
    });
    signal.addEventListener("abort", unsubscribeOpen, { once: true });
    element.addEventListener("dom-ready", () => {
      this.ready = true;
      this.observe(this.store.getSnapshot().address !== "requested");
      this.loadPending();
    }, { signal });
    element.addEventListener("did-navigate", () => {
      this.observe(true);
    }, { signal });
    element.addEventListener("did-navigate-in-page", (event) => {
      if (event.isMainFrame) this.observe(true);
    }, { signal });
    element.addEventListener("did-start-navigation", (event) => {
      if (event.isMainFrame) {
        this.store.set({ ...this.store.getSnapshot(), loading: true, error: void 0 });
      }
    }, { signal });
    for (const name of ["did-start-loading", "did-stop-loading", "page-title-updated"]) {
      element.addEventListener(name, () => {
        this.observe(name === "page-title-updated" && this.store.getSnapshot().address === "observed");
      }, { signal });
    }
    element.addEventListener("did-fail-load", (event) => {
      const failure = event;
      if (failure.isMainFrame && failure.errorCode !== -3) {
        this.failed({ code: failure.errorCode, description: failure.errorDescription });
      }
    }, { signal });
    for (const name of ["render-process-gone", "destroyed"]) {
      element.addEventListener(name, () => {
        void this.dropGuest();
        this.failed();
      }, { signal });
    }
    this.presentation.present(element);
  }
  loadPending() {
    const target = this.pending;
    const element = this.element;
    if (!this.ready || target === void 0 || element === void 0) return;
    const revision = this.revision;
    this.pending = void 0;
    void element.loadURL(target.url).catch((error) => {
      if (this.element !== element || this.lifetime.signal.aborted || this.revision !== revision) return;
      if (typeof error === "object" && error !== null && "code" in error && error.code === "ERR_ABORTED") return;
      if (this.store.getSnapshot().error === void 0) this.commandFailed(error);
    });
  }
  observe(committed) {
    if (!this.ready || this.element === void 0 || this.lifetime.signal.aborted) return;
    try {
      this.observeReady(this.element, committed);
    } catch (error) {
      this.commandFailed(error);
    }
  }
  observeReady(element, committed) {
    const current = this.store.getSnapshot();
    const parsed = committed ? parseBrowserAddress(element.getURL()) : void 0;
    if (parsed?.ok && this.firstDocument) {
      element.clearHistory();
      this.firstDocument = false;
    }
    let target = current.target;
    let address = current.address;
    if (parsed?.ok) {
      target = { ...parsed.target, title: element.getTitle() || parsed.target.title };
      address = "observed";
      this.presentation.show(target.title);
    }
    const loading = current.error === void 0 && element.isLoading();
    const canGoBack = element.canGoBack();
    const canGoForward = element.canGoForward();
    if (target?.url !== current.target?.url || target?.title !== current.target?.title || address !== current.address || loading !== current.loading || canGoBack !== current.canGoBack || canGoForward !== current.canGoForward) {
      this.store.set({ ...current, target, address, loading, canGoBack, canGoForward });
    }
    if (parsed?.ok && target !== void 0) this.persist(target);
  }
  persist(target) {
    if (target.url === this.checkpoint?.url && target.title === this.checkpoint.title) return;
    this.checkpoint = target;
    this.options.persist(browserAddressCheckpoint(target, this.revision));
  }
  failed(error = { code: void 0, description: void 0 }) {
    if (this.lifetime.signal.aborted) return;
    const current = this.store.getSnapshot();
    this.store.set({
      ...current,
      loading: false,
      error,
      canGoBack: this.ready && current.canGoBack,
      canGoForward: this.ready && current.canGoForward
    });
  }
  commandFailed(error) {
    console.error("Desktop browser operation failed", error);
    this.failed();
  }
  dropGuest() {
    this.guestLifetime?.abort();
    this.guestLifetime = void 0;
    this.presentation.clear();
    this.element = void 0;
    this.ready = false;
    this.firstDocument = true;
    const lease = this.lease;
    this.lease = void 0;
    return lease === void 0 ? Promise.resolve() : this.release(lease);
  }
  release(lease) {
    const released = this.bridge.release(lease).catch((error) => {
      console.error("Desktop browser guest release failed", error);
    }).finally(() => {
      this.releases.delete(released);
    });
    this.releases.add(released);
    return released;
  }
};

// .host-source/packages/client/ui-sidebar-browser/src/client/annotation/page-inspector.ts
function annotatePage(options) {
  window.__DSH_PAGE_ANNOTATION_CANCEL__?.();
  const doc = document;
  const originalUrl = location.href;
  const originalFocus = doc.activeElement;
  const root = doc.createElement("div");
  root.setAttribute("data-dsh-page-annotation", "");
  root.style.cssText = "position:fixed;inset:0;z-index:2147483647;pointer-events:none;";
  const shadow = root.attachShadow({ mode: "open" });
  const style = doc.createElement("style");
  style.textContent = `
    :host{all:initial;color-scheme:${options.dark ? "dark" : "light"}}
    *{box-sizing:border-box}
    .outline{position:fixed;pointer-events:none;border:2px solid #2979ff;border-radius:5px;background:#2979ff0c}
    .hint{position:fixed;top:12px;left:50%;transform:translateX(-50%);padding:8px 12px;border-radius:8px;background:#181a20;color:#fff;font:13px/20px system-ui;max-width:calc(100vw - 24px);pointer-events:none}
    .editor,.options{position:fixed;pointer-events:auto;border:0;background:${options.dark ? "#292a2d" : "#fff"};color:${options.dark ? "#ededee" : "#303338"};box-shadow:0 0 0 .5px ${options.dark ? "#55565b" : "#dcdfe3"},0 2px 8px #00000014;font:14px/22px system-ui}
    .editor{border-radius:26px;corner-shape:round;padding:7px 8px;width:min(384px,calc(100vw - 24px));overflow:hidden;max-height:calc(100vh - 24px);display:flex;flex-direction:column}
    .editor.expanded{width:min(420px,calc(100vw - 24px));border-radius:24px;padding:0}
    .expanded>.row{padding:12px 14px;flex:none}
    .expanded>.row>.save{display:none}
    .expanded textarea{max-height:min(66px,20vh)}
    .editor:focus-within{box-shadow:0 0 0 .5px ${options.dark ? "#85868b" : "#bfc4cc"},0 2px 8px #00000014}
    .row{display:flex;align-items:center;gap:10px}
    button,select,textarea{font:inherit;color:inherit}
    button{border:0;border-radius:8px;padding:6px 8px;background:transparent;cursor:pointer;flex:none}
    button:disabled{opacity:.4;cursor:default}
    button:hover:not(:disabled){background:${options.dark ? "#3b3c40" : "#f0f1f3"}}
    button:focus-visible,select:focus-visible{outline:2px solid #2979ff;outline-offset:2px}
    .options-toggle,.save{display:flex;align-items:center;justify-content:center;padding:0;width:30px;height:30px;border-radius:50%;corner-shape:round}
    .options-toggle{color:${options.dark ? "#bdc0c5" : "#737880"}}
    .save,.save:disabled{background:${options.dark ? "#797c82" : "#a2a5a9"};color:#fff;opacity:1}
    .save:not(:disabled){background:${options.dark ? "#d2d3d6" : "#777c83"};color:${options.dark ? "#242529" : "#fff"}}
    .save:hover:not(:disabled){background:${options.dark ? "#eeeff1" : "#60656d"}}
    textarea{display:block;flex:1;width:100%;min-width:0;height:22px;max-height:110px;resize:none;border:0;background:transparent;padding:0;margin:0;outline:0;line-height:22px;overflow-y:auto}
    textarea::placeholder{color:${options.dark ? "#aaaeb5" : "#888e96"};opacity:1}
    .options{position:static;box-shadow:none;flex:1;min-height:0;overflow:auto;font-size:14px;line-height:22px;scrollbar-width:thin}
    .target-row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:7px 20px;background:${options.dark ? "#35363a" : "#f4f4f4"}}
    .target{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:${options.dark ? "#c9ccd1" : "#656b73"}}
    .target-controls{display:flex;gap:2px;flex:none}
    .target-controls button{font-size:13px;padding:0;width:24px;height:24px}
    .properties{padding:12px 20px;display:grid;gap:10px}
    .property{display:grid;grid-template-columns:minmax(90px,1fr) minmax(0,180px);align-items:center;gap:12px;color:${options.dark ? "#c9ccd1" : "#6e747b"}}
    .field{display:flex;align-items:center;gap:9px;border:.5px solid ${options.dark ? "#57595f" : "#dedfe2"};border-radius:13px;padding:7px 10px;min-width:0}
    .field input{font:13px/20px ui-monospace,monospace;background:transparent;color:inherit;border:0;outline:0;width:100%;min-width:0}
    .field:focus-within{border-color:${options.dark ? "#a2a5ac" : "#a5a9b1"}}
    .swatch{width:22px;height:22px;border-radius:7px;flex:none;box-shadow:inset 0 0 0 .5px #8886}
    .intent{display:flex;align-items:center;justify-content:space-between;padding:0 20px 12px;gap:8px;font-size:13px;color:${options.dark ? "#c9ccd1" : "#6e747b"}}
    select{font:inherit;border:.5px solid ${options.dark ? "#57595f" : "#dedfe2"};background:transparent;border-radius:10px;padding:5px 8px;max-width:160px}
    .footer{border-top:.5px solid ${options.dark ? "#44464b" : "#eee"};display:flex;align-items:center;justify-content:space-between;padding:10px 12px;flex:none}
    .footer>.cancel{border:.5px solid ${options.dark ? "#55565b" : "#e5e6e9"};border-radius:20px;padding:6px 12px;font-size:13px}
    .style-hint{font-size:12px;line-height:18px;margin:3px 0;color:${options.dark ? "#adb1b8" : "#8a9097"}}
    .error{color:${options.dark ? "#ffb0a9" : "#b82920"};font-size:12px;padding:3px 40px 1px}
    .hidden{display:none!important}
  `;
  const outline = doc.createElement("div");
  outline.className = "outline hidden";
  const hint = doc.createElement("div");
  hint.className = "hint";
  hint.textContent = options.copy.select;
  const editor = doc.createElement("form");
  editor.className = "editor hidden";
  editor.setAttribute("aria-label", options.copy.placeholder);
  const row = doc.createElement("div");
  row.className = "row";
  function icon(parts) {
    const svg = doc.createElementNS("http://www.w3.org/2000/svg", "svg");
    for (const [name, value] of Object.entries({
      width: "18",
      height: "18",
      viewBox: "0 0 16 16",
      fill: "none",
      stroke: "currentColor",
      "stroke-width": "1.3",
      "stroke-linecap": "round",
      "stroke-linejoin": "round",
      "aria-hidden": "true"
    }))
      svg.setAttribute(name, value);
    for (const [tag, attributes] of parts) {
      const node = doc.createElementNS("http://www.w3.org/2000/svg", tag);
      for (const [name, value] of Object.entries(attributes)) node.setAttribute(name, value);
      svg.append(node);
    }
    return svg;
  }
  const settings = doc.createElement("button");
  settings.type = "button";
  settings.className = "options-toggle";
  settings.title = options.copy.settings;
  settings.setAttribute("aria-label", options.copy.settings);
  settings.setAttribute("aria-expanded", "false");
  settings.setAttribute("aria-haspopup", "dialog");
  settings.append(
    icon([
      ["path", { d: "M2.3 5h5.85M12.05 5h1.65" }],
      ["circle", { cx: "9.95", cy: "5", r: "1.45" }],
      ["path", { d: "M2.3 11h1.65M7.85 11h5.85" }],
      ["circle", { cx: "5.75", cy: "11", r: "1.45" }]
    ])
  );
  const optionsPanel = doc.createElement("section");
  optionsPanel.className = "options hidden";
  optionsPanel.setAttribute("role", "dialog");
  optionsPanel.setAttribute("aria-label", options.copy.settings);
  const intentRow = doc.createElement("div");
  intentRow.className = "intent";
  const intentLabel = doc.createElement("span");
  intentLabel.textContent = options.copy.comment + " / " + options.copy.question;
  const intent = doc.createElement("select");
  intent.setAttribute("aria-label", options.copy.comment + " / " + options.copy.question);
  for (const [value, label] of [
    ["comment", options.copy.comment],
    ["question", options.copy.question]
  ]) {
    const option = doc.createElement("option");
    option.value = value;
    option.textContent = label;
    intent.append(option);
  }
  intent.value = options.intent;
  const input = doc.createElement("textarea");
  input.placeholder = options.copy.placeholder;
  input.setAttribute("aria-label", options.copy.placeholder);
  input.maxLength = 1e3;
  const save = doc.createElement("button");
  save.type = "submit";
  save.className = "save";
  save.disabled = true;
  save.append(
    icon([
      [
        "path",
        { d: "M2.25 8.5L5.49732 11.7473C5.90519 12.1552 6.57263 12.1344 6.95426 11.7018L13.75 4" }
      ]
    ])
  );
  save.title = options.copy.save;
  save.setAttribute("aria-label", options.copy.save);
  row.append(settings, input, save);
  const targetLabel = doc.createElement("span");
  targetLabel.className = "target";
  const controls = doc.createElement("div");
  controls.className = "controls";
  function control(label, glyph) {
    const button = doc.createElement("button");
    button.type = "button";
    button.title = label;
    button.setAttribute("aria-label", label);
    button.textContent = glyph + "  " + label;
    controls.append(button);
    return button;
  }
  const parent = control(options.copy.parent, "\u2191");
  const child = control(options.copy.child, "\u2193");
  const cancel = control(options.copy.cancel, "\xD7");
  const error = doc.createElement("div");
  error.className = "error hidden";
  error.setAttribute("role", "alert");
  intentRow.append(intentLabel, intent);
  const targetRow = doc.createElement("div");
  targetRow.className = "target-row";
  controls.className = "target-controls";
  cancel.remove();
  parent.textContent = "\u2191";
  child.textContent = "\u2193";
  targetRow.append(targetLabel, controls);
  const properties = doc.createElement("div");
  properties.className = "properties";
  const styleHint = doc.createElement("p");
  styleHint.className = "style-hint";
  const fields = /* @__PURE__ */ new Map();
  for (const [property, label] of [
    ["color", options.copy.color],
    ["background-color", options.copy.background],
    ["opacity", options.copy.opacity],
    ["font-family", options.copy.font],
    ["font-size", options.copy.fontSize],
    ["font-weight", options.copy.fontWeight],
    ["gap", options.copy.spacing]
  ]) {
    const item = doc.createElement("label");
    item.className = "property";
    const labelText = doc.createElement("span");
    labelText.textContent = label;
    const field = doc.createElement("div");
    field.className = "field";
    const value = doc.createElement("input");
    value.setAttribute("aria-label", label);
    value.dataset.property = property;
    value.maxLength = 1e3;
    let swatch;
    if (property === "color" || property === "background-color") {
      swatch = doc.createElement("span");
      swatch.className = "swatch";
      swatch.setAttribute("aria-hidden", "true");
      field.append(swatch);
    }
    field.append(value);
    item.append(labelText, field);
    properties.append(item);
    fields.set(property, { input: value, before: "", ...swatch ? { swatch } : {} });
  }
  properties.append(styleHint);
  const footer = doc.createElement("div");
  footer.className = "footer hidden";
  cancel.className = "cancel";
  cancel.textContent = options.copy.cancel;
  const expandedSave = save.cloneNode(true);
  footer.append(cancel, expandedSave);
  optionsPanel.append(targetRow, properties, intentRow);
  editor.append(row, optionsPanel, error, footer);
  shadow.append(style, outline, hint, editor);
  const surface = doc.createElement("div");
  surface.style.cssText = "position:fixed;inset:0;pointer-events:auto;cursor:crosshair;touch-action:none";
  shadow.prepend(surface);
  doc.documentElement.append(root);
  let target = null;
  let hovered = null;
  let selection = null;
  let gesture = null;
  let suspended = false;
  let suppressClick = false;
  const descendants = [];
  let animation = 0;
  let ended = false;
  let settle;
  const result = new Promise((resolve) => {
    settle = resolve;
  });
  const lifecycle = new AbortController();
  function changes() {
    if (selection?.kind !== "element") return {};
    return Object.fromEntries(
      [...fields].flatMap(([key, field]) => {
        let after = field.input.value.trim();
        if ((key === "font-size" || key === "gap") && /^\d+(\.\d+)?$/.test(after)) after += "px";
        return after === field.before ? [] : [[key, { before: field.before, after }]];
      })
    );
  }
  function refreshProperties(element) {
    const computed = element ? getComputedStyle(element) : null;
    for (const [key, field] of fields) {
      field.before = computed?.getPropertyValue(key) || "";
      field.input.value = field.before;
      field.input.disabled = !element;
      if (field.swatch) field.swatch.style.background = field.before;
    }
    properties.classList.toggle("area", !element);
    for (const field of fields.values())
      field.input.closest(".property")?.classList.toggle("hidden", !element);
    styleHint.textContent = element ? options.copy.styleHint : options.copy.elementOnly;
  }
  for (const [key, field] of fields) {
    field.input.addEventListener(
      "input",
      () => {
        if (field.swatch && CSS.supports(key, field.input.value))
          field.swatch.style.background = field.input.value;
        intent.value = "comment";
        error.classList.add("hidden");
        draw();
      },
      { signal: lifecycle.signal }
    );
    field.input.addEventListener(
      "keydown",
      (event) => {
        if (event.key === "Enter" && !event.isComposing && event.keyCode !== 229) {
          event.preventDefault();
          event.stopPropagation();
          submit(event);
        }
      },
      { signal: lifecycle.signal }
    );
  }
  const observer = new ResizeObserver(draw);
  function finish(value) {
    if (ended) return;
    ended = true;
    lifecycle.abort();
    cancelAnimationFrame(animation);
    observer.disconnect();
    root.remove();
    if (window.__DSH_PAGE_ANNOTATION_CANCEL__ === dismiss)
      delete window.__DSH_PAGE_ANNOTATION_CANCEL__;
    if (originalFocus instanceof HTMLElement && originalFocus.isConnected)
      originalFocus.focus({ preventScroll: true });
    settle(value);
  }
  function dismiss() {
    finish({ cancelled: true });
  }
  window.__DSH_PAGE_ANNOTATION_CANCEL__ = dismiss;
  function showError(message) {
    error.textContent = message;
    error.classList.remove("hidden");
  }
  function valid(element) {
    return !!element && element !== doc.body && element !== doc.documentElement && !element.closest("[data-dsh-page-annotation]");
  }
  function hit(x, y) {
    surface.style.pointerEvents = "none";
    editor.style.pointerEvents = "none";
    let node = doc.elementFromPoint(x, y);
    while (node?.shadowRoot) {
      const inner = node.shadowRoot.elementFromPoint(x, y);
      if (!inner || inner === node) break;
      node = inner;
    }
    surface.style.pointerEvents = suspended ? "none" : "auto";
    editor.style.pointerEvents = "auto";
    return valid(node) ? node : null;
  }
  function rectInDocument(element) {
    const rect2 = element.getBoundingClientRect();
    return { x: rect2.x + scrollX, y: rect2.y + scrollY, width: rect2.width, height: rect2.height };
  }
  function gestureRect(x, y) {
    return {
      x: Math.min(gesture.x, x),
      y: Math.min(gesture.y, y),
      width: Math.max(1, Math.abs(x - gesture.x)),
      height: Math.max(1, Math.abs(y - gesture.y))
    };
  }
  function selector(element) {
    const scope = element.getRootNode();
    if (element.id) {
      const id = "#" + CSS.escape(element.id);
      if (scope.querySelectorAll(id).length === 1) return id;
    }
    const pieces = [];
    let node = element;
    while (node) {
      let part = node.localName;
      const peers = node.parentElement ? Array.from(node.parentElement.children).filter((x) => x.localName === node.localName) : [];
      if (peers.length > 1) part += `:nth-of-type(${peers.indexOf(node) + 1})`;
      pieces.unshift(part);
      const path = pieces.join(" > ");
      if (scope.querySelectorAll(path).length === 1 && scope.querySelector(path) === element)
        return path;
      node = node.parentElement;
    }
    return pieces.join(" > ");
  }
  function locator(element) {
    const shadowHosts = [];
    let scope = element.getRootNode();
    while (scope instanceof ShadowRoot) {
      shadowHosts.unshift(selector(scope.host));
      scope = scope.host.getRootNode();
    }
    return {
      selector: selector(element),
      shadowHosts,
      matchCount: element.getRootNode().querySelectorAll(
        selector(element)
      ).length
    };
  }
  function draw() {
    if (ended) return;
    if (location.href !== originalUrl) {
      dismiss();
      return;
    }
    if (suspended) return;
    const node = target || hovered;
    if (selection?.kind === "element" && !target?.isConnected) {
      outline.classList.add("hidden");
      showError(options.copy.targetGone);
      save.disabled = expandedSave.disabled = true;
      return;
    }
    save.disabled = expandedSave.disabled = !selection || !input.value.trim() && !Object.keys(changes()).length;
    const documentRect = selection?.kind === "element" && target ? rectInDocument(target) : selection?.rect || (node?.isConnected ? rectInDocument(node) : null);
    if (!documentRect) {
      outline.classList.add("hidden");
      return;
    }
    const rect2 = {
      left: documentRect.x - scrollX,
      top: documentRect.y - scrollY,
      width: documentRect.width,
      height: documentRect.height,
      bottom: documentRect.y - scrollY + documentRect.height
    };
    outline.classList.remove("hidden");
    Object.assign(outline.style, {
      left: `${rect2.left - (selection?.kind === "point" ? 5 : 0)}px`,
      top: `${rect2.top - (selection?.kind === "point" ? 5 : 0)}px`,
      width: `${selection?.kind === "point" ? 10 : rect2.width}px`,
      height: `${selection?.kind === "point" ? 10 : rect2.height}px`,
      borderStyle: selection?.kind === "region" ? "dashed" : "solid"
    });
    if (editor.classList.contains("hidden")) return;
    const width = editor.getBoundingClientRect().width;
    const height = editor.getBoundingClientRect().height;
    const left = Math.max(12, Math.min(rect2.left, innerWidth - width - 12));
    const below = rect2.bottom + 8;
    const above = rect2.top - height - 8;
    const top = Math.max(
      12,
      Math.min(
        below + height <= innerHeight - 12 ? below : above >= 12 ? above : rect2.top + 8,
        innerHeight - height - 12
      )
    );
    Object.assign(editor.style, { left: `${left}px`, top: `${top}px` });
  }
  function setOptions(open, restoreFocus = false) {
    optionsPanel.classList.toggle("hidden", !open);
    editor.classList.toggle("expanded", open);
    footer.classList.toggle("hidden", !open);
    settings.setAttribute("aria-expanded", String(open));
    draw();
    if (open) settings.focus({ preventScroll: true });
    else if (restoreFocus) settings.focus({ preventScroll: true });
  }
  settings.addEventListener(
    "click",
    () => {
      setOptions(optionsPanel.classList.contains("hidden"));
    },
    { signal: lifecycle.signal }
  );
  intent.addEventListener(
    "change",
    () => {
      setOptions(false);
      input.focus({ preventScroll: true });
    },
    { signal: lifecycle.signal }
  );
  input.addEventListener(
    "input",
    () => {
      input.style.height = "22px";
      input.style.height = `${Math.min(110, input.scrollHeight)}px`;
      error.classList.add("hidden");
      draw();
    },
    { signal: lifecycle.signal }
  );
  function track() {
    draw();
    if (!ended) animation = requestAnimationFrame(track);
  }
  function choose(element) {
    if (!valid(element)) return;
    observer.disconnect();
    target = element;
    selection = { kind: "element", rect: rectInDocument(element) };
    observer.observe(element);
    hint.classList.add("hidden");
    editor.classList.remove("hidden");
    error.classList.add("hidden");
    const where = locator(element);
    targetLabel.textContent = `<${element.localName}>`;
    targetLabel.title = [...where.shadowHosts, where.selector].join(" \u203A ");
    refreshProperties(element);
    parent.disabled = !valid(element.parentElement) && !(element.getRootNode() instanceof ShadowRoot);
    child.disabled = descendants.length === 0;
    draw();
    if (!animation) animation = requestAnimationFrame(track);
    input.focus({ preventScroll: true });
  }
  function chooseArea(kind, rect2) {
    observer.disconnect();
    target = null;
    selection = { kind, rect: rect2 };
    hint.classList.add("hidden");
    editor.classList.remove("hidden");
    error.classList.add("hidden");
    targetLabel.textContent = kind === "region" ? `${options.copy.region} \xB7 ${Math.round(rect2.width)} \xD7 ${Math.round(rect2.height)}` : options.copy.point;
    parent.disabled = child.disabled = true;
    targetLabel.title = "";
    refreshProperties(null);
    draw();
    if (!animation) animation = requestAnimationFrame(track);
    input.focus({ preventScroll: true });
  }
  function isOverlay(event) {
    return event.composedPath().includes(root);
  }
  window.addEventListener(
    "pointermove",
    (event) => {
      if (suspended || !editor.classList.contains("hidden")) return;
      if (gesture && gesture.id === event.pointerId) {
        gesture.dragging ||= Math.hypot(event.clientX - gesture.clientX, event.clientY - gesture.clientY) >= 5;
        if (gesture.dragging) {
          selection = {
            kind: "region",
            rect: gestureRect(event.clientX + scrollX, event.clientY + scrollY)
          };
          draw();
          return;
        }
      }
      const node = hit(event.clientX, event.clientY);
      hovered = event.shiftKey && valid(node?.parentElement || null) ? node.parentElement : node;
      draw();
    },
    { capture: true, signal: lifecycle.signal }
  );
  window.addEventListener(
    "pointerdown",
    (event) => {
      if (suspended) {
        suppressClick = false;
        return;
      }
      if (isOverlay(event) && !event.composedPath().includes(surface)) return;
      setOptions(false);
      event.preventDefault();
      event.stopImmediatePropagation();
      suppressClick = true;
      if (event.button !== 0 || !editor.classList.contains("hidden")) return;
      gesture = {
        id: event.pointerId,
        x: event.clientX + scrollX,
        y: event.clientY + scrollY,
        clientX: event.clientX,
        clientY: event.clientY,
        dragging: false
      };
      surface.setPointerCapture(event.pointerId);
    },
    { capture: true, signal: lifecycle.signal }
  );
  window.addEventListener(
    "pointerup",
    (event) => {
      if (!gesture || gesture.id !== event.pointerId || suspended) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      const current = gesture;
      const area = gestureRect(event.clientX + scrollX, event.clientY + scrollY);
      gesture = null;
      if (surface.hasPointerCapture(event.pointerId))
        surface.releasePointerCapture(event.pointerId);
      if (current.dragging || Math.hypot(event.clientX - current.clientX, event.clientY - current.clientY) >= 5)
        chooseArea("region", area);
      else {
        const node = hit(event.clientX, event.clientY);
        descendants.length = 0;
        if (node) choose(event.shiftKey && valid(node.parentElement) ? node.parentElement : node);
        else
          chooseArea("point", {
            x: event.clientX + scrollX,
            y: event.clientY + scrollY,
            width: 0,
            height: 0
          });
      }
    },
    { capture: true, signal: lifecycle.signal }
  );
  function abortGesture() {
    if (!gesture) return;
    if (surface.hasPointerCapture(gesture.id)) surface.releasePointerCapture(gesture.id);
    gesture = null;
    if (editor.classList.contains("hidden")) selection = null;
    draw();
  }
  window.addEventListener("pointercancel", abortGesture, {
    capture: true,
    signal: lifecycle.signal
  });
  window.addEventListener(
    "click",
    (event) => {
      if (isOverlay(event) && !event.composedPath().includes(surface)) return;
      if (suspended && !suppressClick) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      suppressClick = false;
    },
    { capture: true, signal: lifecycle.signal }
  );
  function resume() {
    if (!suspended) return;
    suspended = false;
    surface.style.pointerEvents = "auto";
    hint.textContent = options.copy.select;
    if (selection) {
      editor.classList.remove("hidden");
      hint.classList.add("hidden");
    } else hint.classList.remove("hidden");
    draw();
  }
  window.addEventListener(
    "keydown",
    (event) => {
      if (event.isComposing || event.keyCode === 229) return;
      if (event.key === "Escape") {
        event.preventDefault();
        event.stopImmediatePropagation();
        if (optionsPanel.classList.contains("hidden")) dismiss();
        else setOptions(false, true);
        return;
      }
      if (isOverlay(event) && !event.composedPath().includes(surface)) return;
      if (event.code === "Space" && !event.altKey && !event.ctrlKey && !event.metaKey) {
        event.preventDefault();
        event.stopImmediatePropagation();
        abortGesture();
        suspended = true;
        setOptions(false);
        surface.style.pointerEvents = "none";
        outline.classList.add("hidden");
        editor.classList.add("hidden");
        hint.classList.remove("hidden");
        hint.textContent = options.copy.interact;
      } else if (!suspended && !isOverlay(event)) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
    },
    { capture: true, signal: lifecycle.signal }
  );
  window.addEventListener(
    "keyup",
    (event) => {
      if (event.code === "Space" && suspended) {
        event.preventDefault();
        event.stopImmediatePropagation();
        resume();
      }
    },
    { capture: true, signal: lifecycle.signal }
  );
  window.addEventListener(
    "blur",
    () => {
      abortGesture();
      resume();
    },
    { signal: lifecycle.signal }
  );
  doc.addEventListener(
    "visibilitychange",
    () => {
      if (doc.hidden) {
        abortGesture();
        resume();
      }
    },
    { signal: lifecycle.signal }
  );
  window.addEventListener("pagehide", dismiss, { once: true, signal: lifecycle.signal });
  parent.addEventListener(
    "click",
    () => {
      if (!target) return;
      const scope = target.getRootNode();
      const node = target.parentElement || (scope instanceof ShadowRoot ? scope.host : null);
      if (valid(node)) {
        setOptions(false);
        descendants.push(target);
        choose(node);
      }
    },
    { signal: lifecycle.signal }
  );
  child.addEventListener(
    "click",
    () => {
      const node = descendants.pop();
      if (node?.isConnected) {
        setOptions(false);
        choose(node);
      }
    },
    { signal: lifecycle.signal }
  );
  cancel.addEventListener("click", dismiss, { signal: lifecycle.signal });
  function evidence(element) {
    const where = locator(element);
    const computed = getComputedStyle(element);
    const styles = {};
    for (const name of [
      "display",
      "position",
      "font-size",
      "font-family",
      "color",
      "background-color",
      "padding",
      "margin",
      "gap",
      "opacity",
      "font-weight"
    ])
      styles[name] = computed.getPropertyValue(name).slice(0, 1e3);
    const source = element.closest("[data-web-annotator-source]")?.getAttribute("data-web-annotator-source")?.slice(0, 1e3);
    return {
      ...where,
      tag: element.localName,
      text: (element instanceof HTMLElement ? element.innerText : element.textContent || "").replace(/\s+/g, " ").trim().slice(0, 400),
      rect: rectInDocument(element),
      styles,
      ...source ? { source } : {}
    };
  }
  function candidates(rect2) {
    const found = /* @__PURE__ */ new Set();
    for (let row2 = 0; row2 < 5; row2++)
      for (let col = 0; col < 5; col++) {
        const node = hit(
          rect2.x - scrollX + rect2.width * (col + 0.5) / 5,
          rect2.y - scrollY + rect2.height * (row2 + 0.5) / 5
        );
        if (node) found.add(node);
      }
    return [...found].slice(0, 12).map(evidence);
  }
  function submit(event) {
    event.preventDefault();
    if (!selection || selection.kind === "element" && !target?.isConnected || location.href !== originalUrl) {
      showError(options.copy.targetGone);
      return;
    }
    const styleChanges = changes();
    for (const [property, { after }] of Object.entries(styleChanges)) {
      if (!CSS.supports(property, after) || property === "opacity" && !(Number(after) >= 0 && Number(after) <= 1)) {
        showError(options.copy.invalidStyle);
        setOptions(true);
        fields.get(property)?.input.focus();
        return;
      }
    }
    if (!input.value.trim() && !Object.keys(styleChanges).length) {
      showError(options.copy.empty);
      input.focus();
      return;
    }
    const rect2 = selection.kind === "element" ? rectInDocument(target) : selection.rect;
    const items = selection.kind === "element" ? [evidence(target)] : candidates(rect2);
    finish({
      intent: intent.value === "question" ? "question" : "comment",
      note: input.value.trim() || options.copy.applyStyles,
      ...Object.keys(styleChanges).length ? { styleChanges } : {},
      url: originalUrl,
      title: doc.title.slice(0, 300),
      viewport: { width: innerWidth, height: innerHeight },
      selection: {
        kind: selection.kind,
        rect: rect2,
        viewportRect: { ...rect2, x: rect2.x - scrollX, y: rect2.y - scrollY },
        scroll: { x: scrollX, y: scrollY },
        candidates: items
      }
    });
  }
  editor.addEventListener("submit", submit, { signal: lifecycle.signal });
  input.addEventListener(
    "keydown",
    (event) => {
      if (event.key === "Enter" && !event.shiftKey && !event.isComposing && event.keyCode !== 229) {
        event.preventDefault();
        event.stopPropagation();
        submit(event);
      }
    },
    { signal: lifecycle.signal }
  );
  animation = requestAnimationFrame(track);
  return result;
}

// .host-source/packages/client/ui-sidebar-browser/src/client/annotation/screenshot.ts
async function markScreenshot(dataUrl, annotation) {
  const picture = new Image();
  picture.src = dataUrl;
  await picture.decode();
  if (!picture.naturalWidth || !picture.naturalHeight) throw new Error("annotation-image-failed");
  const scale = Math.min(1, 1600 / Math.max(picture.naturalWidth, picture.naturalHeight));
  const canvas = document.createElement("canvas");
  canvas.width = Math.round(picture.naturalWidth * scale);
  canvas.height = Math.round(picture.naturalHeight * scale);
  const context = canvas.getContext("2d");
  if (!context) throw new Error("annotation-image-failed");
  context.drawImage(picture, 0, 0, canvas.width, canvas.height);
  const sx = canvas.width / annotation.viewport.width;
  const sy = canvas.height / annotation.viewport.height;
  const area = annotation.selection.viewportRect;
  context.strokeStyle = "#1683ff";
  context.fillStyle = "#1683ff";
  context.lineWidth = 2;
  const x = area.x * sx, y = area.y * sy;
  if (annotation.selection.kind === "point") {
    if (x < 0 || y < 0 || x > canvas.width || y > canvas.height)
      throw new Error("annotation-image-failed");
    context.beginPath();
    context.arc(x, y, 7, 0, Math.PI * 2);
    context.stroke();
  } else {
    const left = Math.max(1, x), top = Math.max(1, y);
    const right = Math.min(canvas.width - 1, x + area.width * sx);
    const bottom = Math.min(canvas.height - 1, y + area.height * sy);
    if (right <= left || bottom <= top) throw new Error("annotation-image-failed");
    context.strokeRect(left, top, right - left, bottom - top);
  }
  const data = canvas.toDataURL("image/jpeg", 0.8).split(",")[1];
  if (data.length > 4e6) throw new Error("annotation-image-failed");
  return { mediaType: "image/jpeg", data, width: canvas.width, height: canvas.height };
}

// .host-source/packages/client/ui-sidebar-browser/src/client/electron/ElectronWebviewPresentation.ts
var ElectronWebviewPresentation = class {
  constructor(events) {
    this.events = events;
  }
  events;
  element;
  host;
  /** @param url - controller-known page. @param options - picker configuration. @param signal - lifetime. @returns bounded guest evidence. */
  async annotate(url, options, signal) {
    const element = this.element;
    if (signal.aborted) return { cancelled: true };
    if (element === void 0 || element.getURL() !== url) throw new Error("annotation-unavailable");
    const cancel = () => {
      void element.executeJavaScript("window.__DSH_PAGE_ANNOTATION_CANCEL__?.()").catch(() => {
      });
    };
    signal.addEventListener("abort", cancel, { once: true });
    const navigation = () => {
      cancel();
    };
    element.addEventListener("did-start-navigation", navigation);
    try {
      element.focus();
      const result = await element.executeJavaScript(`(${annotatePage.toString()})(${JSON.stringify(options)})`);
      if (signal.aborted || this.element !== element) return { cancelled: true };
      if (!isAnnotationResult(result)) throw new Error("annotation-invalid");
      if (!("cancelled" in result) && (result.url !== url || element.getURL() !== url)) throw new Error("annotation-page-changed");
      if (!("cancelled" in result)) {
        try {
          const image = await element.capturePage();
          const screenshot = await markScreenshot(image.toDataURL(), result);
          if (signal.aborted || this.element !== element) return { cancelled: true };
          if (element.getURL() !== url || element.isLoading()) throw new Error("annotation-page-changed");
          const view = await element.executeJavaScript("({width:innerWidth,height:innerHeight,x:scrollX,y:scrollY})");
          if (typeof view !== "object" || view === null || !("width" in view) || !("height" in view) || !("x" in view) || !("y" in view) || view.width !== result.viewport.width || view.height !== result.viewport.height || view.x !== result.selection.scroll.x || view.y !== result.selection.scroll.y) return result;
          return { ...result, screenshot };
        } catch (error) {
          if (signal.aborted || this.element !== element) return { cancelled: true };
          if (element.getURL() !== url || element.isLoading()) throw new Error("annotation-page-changed");
          return result;
        }
      }
      return result;
    } finally {
      signal.removeEventListener("abort", cancel);
      element.removeEventListener("did-start-navigation", navigation);
    }
  }
  /** @param viewportId - committed content container. @returns ends attachment and releases its guest. */
  mount(viewportId) {
    const host = document.getElementById(viewportId);
    if (host === null) throw new Error("Electron presentation: content container is not mounted");
    if (this.host !== void 0) this.events.unmounted();
    this.host = host;
    this.events.mounted();
    return () => {
      if (this.host !== host) return;
      this.events.unmounted();
      this.host = void 0;
    };
  }
  /**
   * Configure a detached webview for an approved guest reservation.
   * @param reservation - main-approved partition and bootstrap lease.
   * @returns a detached, configured webview.
   */
  createElement(reservation) {
    const element = document.createElement("webview");
    element.className = Browser_default.webview;
    element.dataset.sidebarBrowserFrame = "webview";
    element.setAttribute("name", reservation.lease);
    element.setAttribute("partition", reservation.partition);
    element.setAttribute("allowpopups", "");
    element.setAttribute("src", "about:blank#" + reservation.lease);
    return element;
  }
  /**
   * Attach a prepared guest, removing any previous guest DOM from the container.
   * @param element - configured guest with its navigation listeners already installed.
   * @throws when no content container is mounted.
   */
  present(element) {
    if (this.host === void 0) throw new Error("Electron presentation: cannot attach without a content container");
    this.clear();
    this.element = element;
    this.host.append(element);
  }
  /**
   * Set the guest element's accessible label.
   * @param title - observed document title.
   */
  show(title) {
    this.element?.setAttribute("aria-label", title);
  }
  /** Destroy only the guest DOM; a replacement may use the same mounted container. */
  clear() {
    this.element?.remove();
    this.element = void 0;
  }
  /** Destroy the guest DOM and release its content container. */
  dispose() {
    this.clear();
    this.host = void 0;
  }
};

// .host-source/packages/client/ui-sidebar-browser/src/client/electron/pages.ts
function createElectronPage(options, bridge, workspace) {
  const presentation = new ElectronWebviewPresentation({
    mounted: () => {
      frame.attach();
    },
    unmounted: () => {
      frame.detach();
    }
  });
  const frame = new ElectronWebViewImpl(options, bridge, workspace, presentation);
  return { frame, presentation };
}

// .host-source/packages/client/ui-sidebar-browser/src/client/electron/workspace.ts
async function browserWorkspace(source, sessionId, signal) {
  signal.throwIfAborted();
  if (source.getSnapshot().phase !== "ready") {
    await new Promise((resolve, reject) => {
      const abort = () => {
        stop();
        const reason = signal.reason;
        reject(reason);
      };
      const stop = source.subscribe(() => {
        if (source.getSnapshot().phase !== "ready") return;
        stop();
        signal.removeEventListener("abort", abort);
        resolve();
      });
      signal.addEventListener("abort", abort, { once: true });
    });
  }
  signal.throwIfAborted();
  const workspace = source.getSnapshot().items.find((item) => item.sessionIds.some((id) => id === sessionId));
  return workspace === void 0 ? `session:${sessionId}` : `cwd:${workspace.path}`;
}

// .host-source/packages/client/ui-sidebar-browser/src/client/definition.tsx
var import_dsh_client_ui_primitives3 = require("@deepseek-ai/dsh-client-ui-primitives");
var BROWSER_KIND = "browser";
var BROWSER_ID = "@deepseek-ai/dsh-client-ui-sidebar-browser";
function browserDefinition(t) {
  return {
    id: BROWSER_ID,
    kind: BROWSER_KIND,
    multiple: true,
    priority: "builtin",
    title: () => t("type.label"),
    guide: [{
      id: "new",
      commandId: "browser.new",
      order: 30,
      title: () => t("guide.title"),
      description: () => t("guide.description"),
      icon: import_dsh_client_ui_primitives3.GuideArtworkBrowser
    }]
  };
}

// .host-source/packages/client/ui-sidebar-browser/src/client/locales.ts
var zh = {
  "type.label": "\u6D4F\u89C8\u5668",
  "guide.title": "\u6D4F\u89C8\u5668",
  "guide.description": "\u6D4F\u89C8\u7F51\u9875",
  "shortcut.noSession": "\u8BF7\u5148\u6253\u5F00\u4E00\u4E2A\u4F1A\u8BDD",
  "address.placeholder": "\u8F93\u5165 HTTP(S) \u5730\u5740",
  "address.changed": "URL \u5DF2\u53D8\u5316",
  back: "\u540E\u9000",
  forward: "\u524D\u8FDB",
  reload: "\u5237\u65B0",
  go: "\u524D\u5F80",
  external: "\u5728\u7CFB\u7EDF\u6D4F\u89C8\u5668\u4E2D\u6253\u5F00",
  "sandbox.disable": "\u5173\u95ED\u6C99\u7BB1\u9650\u5236",
  "sandbox.enable": "\u6062\u590D\u6C99\u7BB1\u9650\u5236",
  "sandbox.warning": "\u6C99\u7BB1\u9650\u5236\u5DF2\u5173\u95ED\uFF1B\u9875\u9762\u53EF\u4EE5\u5BFC\u822A\u9876\u5C42\u5E94\u7528\uFF0C\u5E76\u4F7F\u7528\u4E0B\u8F7D\u3001\u6A21\u6001\u5BF9\u8BDD\u6846\u4E0E\u8F93\u5165\u9501\u5B9A\u3002",
  start: "\u8F93\u5165 HTTP(S) \u5730\u5740\u5F00\u59CB\u6D4F\u89C8",
  loading: "\u6B63\u5728\u6253\u5F00\u2026",
  "restore.previous": "\u4E0A\u6B21\u6253\u5F00",
  "restore.action": "\u6062\u590D\u9875\u9762",
  "error.empty": "\u8BF7\u8F93\u5165\u5730\u5740\u3002",
  "error.invalid": "\u8FD9\u4E2A\u5730\u5740\u65E0\u6548\u6216\u8FC7\u957F\u3002",
  "error.protocol": "\u53EA\u652F\u6301 HTTP \u548C HTTPS \u5730\u5740\uFF1B\u672C\u5730\u6587\u4EF6\u8BF7\u4F7F\u7528\u6587\u6863\u9884\u89C8\u3002",
  "error.credentials": "\u5730\u5740\u4E0D\u80FD\u5305\u542B\u7528\u6237\u540D\u6216\u5BC6\u7801\u3002",
  "error.application-origin": "\u4E0D\u80FD\u5728\u5D4C\u5165\u6D4F\u89C8\u5668\u4E2D\u6253\u5F00 DSH \u5E94\u7528\u81EA\u8EAB\u3002",
  "load.failed": "\u9875\u9762\u52A0\u8F7D\u5931\u8D25\uFF1B\u8BF7\u5237\u65B0\u91CD\u8BD5\u6216\u5728\u7CFB\u7EDF\u6D4F\u89C8\u5668\u4E2D\u6253\u5F00\u3002",
  "load.failed.detail": "\u9875\u9762\u52A0\u8F7D\u5931\u8D25 ({code}): {description}",
  "address.unknown": "\u9875\u9762\u5DF2\u8DF3\u8F6C\uFF1B\u5F53\u524D\u8F7D\u4F53\u65E0\u6CD5\u8BFB\u53D6\u65B0\u7684 URL\u3002"
};
var en = {
  "type.label": "Browser",
  "guide.title": "Browser",
  "guide.description": "Browse web pages",
  "shortcut.noSession": "Open a session first",
  "address.placeholder": "Enter an HTTP(S) address",
  "address.changed": "URL changed",
  back: "Back",
  forward: "Forward",
  reload: "Reload",
  go: "Go",
  external: "Open in system browser",
  "sandbox.disable": "Disable sandbox restrictions",
  "sandbox.enable": "Restore sandbox restrictions",
  "sandbox.warning": "Sandbox restrictions are disabled; the page can navigate the top-level app and use downloads, modal dialogs, and input locks.",
  start: "Enter an HTTP(S) address to start browsing",
  loading: "Opening\u2026",
  "restore.previous": "Previously opened",
  "restore.action": "Restore page",
  "error.empty": "Enter an address.",
  "error.invalid": "That address is invalid or too long.",
  "error.protocol": "Only HTTP and HTTPS addresses are supported; use Document Preview for local files.",
  "error.credentials": "Addresses cannot contain a username or password.",
  "error.application-origin": "The embedded browser cannot open the DSH application itself.",
  "load.failed": "The page could not load; reload or open it in the system browser.",
  "load.failed.detail": "Page load failed ({code}): {description}",
  "address.unknown": "The page navigated; this carrier cannot read its new URL."
};

// .host-source/packages/client/ui-sidebar-browser/src/client/browser/store.ts
var import_dsh_client_store4 = require("@deepseek-ai/dsh-client-store");
function createBrowserStore() {
  return (0, import_dsh_client_store4.defineStore)({
    init: () => ({ byTab: {} }),
    persist: "dsh.sidebar-browser.v1",
    actions: {
      replace: (draft, tabId, state) => {
        draft.byTab[tabId] = state;
      },
      forget: (draft, tabId) => {
        const byTab = {};
        for (const [id, state] of Object.entries(draft.byTab)) {
          if (id !== tabId) byTab[id] = state;
        }
        draft.byTab = byTab;
      }
    }
  });
}

// .host-source/packages/client/ui-sidebar-browser/src/client/index.ts
var inject = ["slots", "locale", "sidebarRight", "sidebarRightTabs"];
function apply(ctx) {
  ctx.effect(cssDispose);
  const namespace = "sidebarBrowser";
  const t = ctx.locale.bind(namespace);
  ctx.inject(["shortcuts"], (ctx2) => {
    ctx2.effect(() => ctx2.shortcuts.register({
      id: "browser.new",
      label: () => t("guide.title"),
      aliases: ["browser", "new browser tab"],
      defaults: {
        "desktop:macos": { code: "KeyT", modifiers: ["primary"] },
        "desktop:windows": { code: "KeyT", modifiers: ["primary"] },
        "desktop:linux": { code: "KeyT", modifiers: ["primary"] },
        "web:macos": { code: "KeyT", modifiers: ["primary", "alt"] },
        "web:windows": { code: "KeyT", modifiers: ["primary", "alt"] }
      },
      // Each tab plugin owns its command's availability, localized refusal, and tab kind.
      /* jscpd:ignore-start */
      regions: ["page", "editable", "terminal"],
      modals: [],
      resolve: ({ target: element }) => {
        const target = ctx2.sidebarRight.commandTarget(element);
        if (target === void 0) return { status: "blocked", reason: t("shortcut.noSession") };
        return { status: "handled", run: () => {
          ctx2.sidebarRight.openTabFromTarget("browser", target);
        } };
      }
      /* jscpd:ignore-end */
    }), "ui-sidebar-browser: shortcut");
  });
  const store = createBrowserStore();
  const openTabs = ctx.sidebarRight.openTabs;
  const carrier = globalThis.dshDesktop;
  const desktop = carrier?.protocolVersion === 1 ? carrier.browser : void 0;
  ctx.effect(() => ctx.locale.register(namespace, { zh, en }), "ui-sidebar-browser.copy");
  ctx.effect(() => ctx.sidebarRightTabs.register({ ...browserDefinition(t), keepMounted: desktop !== void 0 }), "ui-sidebar-browser.type");
  const installFrames = (scope, factory) => {
    const controllers = /* @__PURE__ */ new Map();
    scope.effect(() => async () => {
      const pending = [...controllers.values()].map((controller) => controller.dispose());
      controllers.clear();
      await Promise.all(pending);
    }, "ui-sidebar-browser.frames");
    scope.effect(() => scope.slots.inject("sidebar.right.pane.tab", () => scope.slots.register({
      name: "sidebar.right.pane.tab",
      key: BROWSER_ID,
      locale: namespace,
      store,
      children: {
        "sidebar.right.tab.browser.toolbar": { kind: "list", scope: "session" },
        "sidebar.right.tab.browser.annotations": { kind: "list", scope: "session" }
      },
      inject: (sessionId, actions) => {
        const existing = controllers.get(sessionId);
        if (existing !== void 0) {
          existing.rebind(actions);
          return existing;
        }
        const controller = createBrowserControllers(actions, factory(sessionId), (tabId) => openTabs.getSnapshot().some((tab) => tab.sessionId === sessionId && tab.tabId === tabId));
        controllers.set(sessionId, controller);
        return controller;
      }
    }, BrowserBody)), "ui-sidebar-browser.body");
  };
  if (desktop === void 0) installFrames(ctx, () => createIframePage);
  else ctx.inject(["workspaces"], (scope) => {
    installFrames(scope, (sessionId) => (options) => createElectronPage(
      options,
      desktop,
      (signal) => browserWorkspace(scope.workspaces.list, sessionId, signal)
    ));
  });
  ctx.effect(() => ctx.slots.inject("sidebar.right.pane.tab.title", () => ctx.slots.register({
    name: "sidebar.right.pane.tab.title",
    key: BROWSER_ID,
    store
  }, BrowserTitle)), "ui-sidebar-browser.title");
}
return module.exports; } });
//# sourceMappingURL=client.js.map
