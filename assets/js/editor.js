(function () {
  'use strict';

  var data = window.vssData;
  if (!data) {
    return;
  }

  var state = {
    on: false,
    dirty: false,
    selected: null,
    hover: null,
    rules: Array.isArray(data.rules) ? clone(data.rules) : [],
    history: [],
    future: [],
    draft: emptyDraft()
  };

  var els = {};

  document.addEventListener('DOMContentLoaded', boot);

  function boot() {
    var root = document.getElementById('vss-root');
    if (!root) {
      return;
    }
    root.hidden = false;
    root.className = 'vss-root';
    root.innerHTML =
      '<button type="button" class="vss-launcher" id="vss-launcher">Studio</button>' +
      '<div class="vss-highlight" id="vss-hover" hidden></div>' +
      '<div class="vss-selected" id="vss-select-box" hidden></div>' +
      '<div class="vss-label" id="vss-label" hidden></div>' +
      '<aside class="vss-panel" id="vss-panel" hidden></aside>' +
      '<div class="vss-toast" id="vss-toast" hidden></div>' +
      '<style id="vss-live"></style>';

    els.launcher = document.getElementById('vss-launcher');
    els.hover = document.getElementById('vss-hover');
    els.selectBox = document.getElementById('vss-select-box');
    els.label = document.getElementById('vss-label');
    els.panel = document.getElementById('vss-panel');
    els.toast = document.getElementById('vss-toast');
    els.live = document.getElementById('vss-live');

    renderPanel();
    paintLive();

    els.launcher.addEventListener('click', function (event) {
      event.preventDefault();
      toggle();
    });

    document.addEventListener('click', function (event) {
      var link = event.target.closest && event.target.closest('#wp-admin-bar-vss-toggle-editor a');
      if (!link) {
        return;
      }
      event.preventDefault();
      toggle();
    });

    document.addEventListener('mousemove', onMove, true);
    document.addEventListener('click', onClick, true);
    document.addEventListener('scroll', syncBoxes, true);
    window.addEventListener('resize', syncBoxes);
    document.addEventListener('keydown', onKey);
  }

  function toggle() {
    state.on = !state.on;
    document.body.classList.toggle('vss-editing', state.on);
    els.launcher.classList.toggle('is-on', state.on);
    els.launcher.textContent = state.on ? 'Exit Studio' : 'Studio';
    els.panel.hidden = !state.on;
    if (!state.on) {
      state.selected = null;
      hide(els.hover);
      hide(els.selectBox);
      hide(els.label);
      renderPanel();
    }
  }

  function renderPanel() {
    var draft = state.draft;
    var selected = state.selected;
    var body;

    if (!selected) {
      body = '<div class="vss-empty">' + escapeHtml(data.i18n.select) + '</div>';
    } else {
      body =
        '<div class="vss-switch"><div><strong>' + escapeHtml(draft.label || tagName(selected)) + '</strong><div class="vss-chip">' + escapeHtml(tagName(selected)) + '</div></div>' +
        '<label><input type="checkbox" data-field="hide"' + (draft.properties.display === 'none' ? ' checked' : '') + '> Hide</label></div>' +
        field('Label', '<input type="text" data-field="label" value="' + escapeAttr(draft.label) + '">') +
        field('Selector', '<input type="text" data-field="selector" value="' + escapeAttr(draft.selector) + '">') +
        field('Scope', '<select data-field="scope"><option value="global"' + selectedAttr(draft.scope, 'global') + '>Entire site</option><option value="page"' + selectedAttr(draft.scope, 'page') + '>This page only</option></select>') +
        '<div class="vss-row">' +
        colorField('Text', 'color', draft.properties.color) +
        colorField('Background', 'background-color', draft.properties['background-color']) +
        '</div>' +
        '<div class="vss-row">' +
        field('Font size', '<input type="text" data-prop="font-size" placeholder="18px" value="' + escapeAttr(draft.properties['font-size'] || '') + '">') +
        field('Weight', '<select data-prop="font-weight">' + weightOptions(draft.properties['font-weight']) + '</select>') +
        '</div>' +
        '<div class="vss-row">' +
        field('Align', '<select data-prop="text-align">' + alignOptions(draft.properties['text-align']) + '</select>') +
        field('Radius', '<input type="text" data-prop="border-radius" placeholder="8px" value="' + escapeAttr(draft.properties['border-radius'] || '') + '">') +
        '</div>' +
        '<div class="vss-row">' +
        field('Padding', '<input type="text" data-prop="padding" placeholder="12px" value="' + escapeAttr(draft.properties.padding || '') + '">') +
        field('Margin', '<input type="text" data-prop="margin" placeholder="0" value="' + escapeAttr(draft.properties.margin || '') + '">') +
        '</div>' +
        field('Opacity', '<input type="range" min="0" max="1" step="0.05" data-prop="opacity" value="' + escapeAttr(draft.properties.opacity || '1') + '">') +
        field('Custom CSS', '<textarea rows="3" data-field="custom_css" placeholder="letter-spacing: .02em;">' + escapeHtml(draft.custom_css) + '</textarea>') +
        '<div class="vss-panel__actions">' +
        '<button type="button" class="vss-btn vss-btn--danger" id="vss-delete">Remove rule</button>' +
        '</div>';
    }

    els.panel.innerHTML =
      '<div class="vss-panel__head"><div class="vss-panel__brand">' + escapeHtml(data.brand || 'Visual Site Studio') + '</div>' +
      '<div class="vss-panel__actions">' +
      '<button type="button" class="vss-btn" id="vss-undo" ' + disabled(!state.history.length) + '>Undo</button>' +
      '<button type="button" class="vss-btn" id="vss-redo" ' + disabled(!state.future.length) + '>Redo</button>' +
      '<button type="button" class="vss-btn vss-btn--primary" id="vss-save">' + escapeHtml(data.i18n.save) + '</button>' +
      '</div></div>' +
      '<div class="vss-panel__body">' + body + rulesList() + '</div>';

    bindPanel();
  }

  function bindPanel() {
    var saveBtn = document.getElementById('vss-save');
    var undoBtn = document.getElementById('vss-undo');
    var redoBtn = document.getElementById('vss-redo');
    var delBtn = document.getElementById('vss-delete');

    if (saveBtn) saveBtn.addEventListener('click', save);
    if (undoBtn) undoBtn.addEventListener('click', undo);
    if (redoBtn) redoBtn.addEventListener('click', redo);
    if (delBtn) delBtn.addEventListener('click', removeDraft);

    els.panel.querySelectorAll('[data-field], [data-prop]').forEach(function (input) {
      input.addEventListener('input', onField);
      input.addEventListener('change', onField);
    });

    els.panel.querySelectorAll('[data-rule-id]').forEach(function (row) {
      row.addEventListener('click', function () {
        var rule = state.rules.find(function (item) { return item.id === row.getAttribute('data-rule-id'); });
        if (!rule) return;
        state.draft = clone(rule);
        try {
          state.selected = document.querySelector(rule.selector);
        } catch (e) {
          state.selected = null;
        }
        syncBoxes();
        renderPanel();
      });
    });
  }

  function onField(event) {
    var input = event.target;
    if (input.dataset.field === 'hide') {
      if (input.checked) {
        state.draft.properties.display = 'none';
      } else {
        delete state.draft.properties.display;
      }
    } else if (input.dataset.field) {
      state.draft[input.dataset.field] = input.value;
    } else     if (input.dataset.prop) {
      if (input.value === '') {
        delete state.draft.properties[input.dataset.prop];
      } else {
        state.draft.properties[input.dataset.prop] = input.value;
      }
    }
    paintLive();
  }

  function onMove(event) {
    if (!state.on || isChrome(event.target)) {
      hide(els.hover);
      return;
    }
    var el = pick(event.target);
    if (!el) {
      hide(els.hover);
      return;
    }
    state.hover = el;
    placeBox(els.hover, el);
  }

  function onClick(event) {
    if (!state.on || isChrome(event.target)) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    var el = pick(event.target);
    if (!el) {
      return;
    }
    select(el);
  }

  function onKey(event) {
    if (!state.on) {
      return;
    }
    if (event.key === 'Escape') {
      state.selected = null;
      state.draft = emptyDraft();
      hide(els.selectBox);
      hide(els.label);
      renderPanel();
    }
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'z') {
      event.preventDefault();
      if (event.shiftKey) redo(); else undo();
    }
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
      event.preventDefault();
      save();
    }
  }

  function select(el) {
    if (state.draft && state.draft.selector) {
      commitDraft(false);
    }
    var selector = uniqueSelector(el);
    var existing = state.rules.find(function (rule) {
      return rule.selector === selector && (rule.scope === 'global' || Number(rule.page_id) === Number(data.pageId) || rule.path === data.path);
    });
    pushHistory();
    state.selected = el;
    state.draft = existing ? clone(existing) : Object.assign(emptyDraft(), {
      selector: selector,
      label: readableName(el)
    });
    placeBox(els.selectBox, el);
    els.label.hidden = false;
    els.label.textContent = tagName(el);
    placeBox(els.label, el, true);
    renderPanel();
    paintLive();
  }

  function commitDraft(recordHistory) {
    if (!state.draft.selector) {
      return;
    }
    if (recordHistory) {
      pushHistory();
    }
    var index = state.rules.findIndex(function (rule) { return rule.id === state.draft.id; });
    var next = Object.assign({}, state.draft, {
      enabled: true,
      page_id: data.pageId || 0,
      path: data.path || ''
    });
    if (index === -1) {
      state.rules.push(next);
    } else {
      state.rules[index] = next;
    }
    state.dirty = true;
  }

  function removeDraft() {
    if (!state.draft.id) {
      return;
    }
    pushHistory();
    state.rules = state.rules.filter(function (rule) { return rule.id !== state.draft.id; });
    state.selected = null;
    state.draft = emptyDraft();
    state.dirty = true;
    hide(els.selectBox);
    hide(els.label);
    paintLive();
    renderPanel();
  }

  function save() {
    commitDraft(false);
    fetch(data.restUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': data.nonce
      },
      body: JSON.stringify({ rules: state.rules })
    }).then(function (res) {
      if (!res.ok) throw new Error('save failed');
      return res.json();
    }).then(function (payload) {
      state.rules = payload.rules || state.rules;
      state.dirty = false;
      toast(data.i18n.saved);
      renderPanel();
    }).catch(function () {
      toast(data.i18n.error);
    });
  }

  function undo() {
    if (!state.history.length) return;
    state.future.push(clone(state.rules));
    state.rules = state.history.pop();
    state.dirty = true;
    paintLive();
    renderPanel();
  }

  function redo() {
    if (!state.future.length) return;
    state.history.push(clone(state.rules));
    state.rules = state.future.pop();
    state.dirty = true;
    paintLive();
    renderPanel();
  }

  function pushHistory() {
    state.history.push(clone(state.rules));
    if (state.history.length > 40) {
      state.history.shift();
    }
    state.future = [];
  }

  function paintLive() {
    var css = state.rules.map(compileRule).join('');
    if (state.draft && state.draft.selector) {
      css += compileRule(state.draft);
    }
    els.live.textContent = css;
  }

  function compileRule(rule) {
    if (!rule || !rule.selector) return '';
    var parts = [];
    Object.keys(rule.properties || {}).forEach(function (prop) {
      if (rule.properties[prop]) {
        parts.push(prop + ':' + rule.properties[prop] + ' !important');
      }
    });
    if (rule.custom_css) {
      parts.push(String(rule.custom_css).replace(/;+$/, ''));
    }
    if (!parts.length) return '';
    return rule.selector + '{' + parts.join(';') + ';}';
  }

  function rulesList() {
    if (!state.rules.length) {
      return '';
    }
    var rows = state.rules.map(function (rule) {
      return '<div class="vss-rule" data-rule-id="' + escapeAttr(rule.id) + '"><div><strong>' + escapeHtml(rule.label || rule.selector) + '</strong><small>' + escapeHtml(rule.selector) + '</small></div><span class="vss-chip">' + escapeHtml(rule.scope || 'global') + '</span></div>';
    }).join('');
    return '<div class="vss-rules"><strong>Saved rules</strong>' + rows + '</div>';
  }

  function pick(target) {
    if (!(target instanceof Element) || isChrome(target)) {
      return null;
    }
    var el = target;
    if (el === document.documentElement || el === document.body) {
      return null;
    }
    return el;
  }

  function isChrome(node) {
    if (!(node instanceof Element)) {
      return false;
    }
    return Boolean(node.closest('#wpadminbar, #vss-root, .vss-panel, .vss-launcher, .vss-toast'));
  }

  function uniqueSelector(el) {
    if (el.id && /^[A-Za-z][\w-]*$/.test(el.id) && document.querySelectorAll('#' + cssEscape(el.id)).length === 1) {
      return '#' + cssEscape(el.id);
    }
    var parts = [];
    var node = el;
    var depth = 0;
    while (node && node.nodeType === 1 && node !== document.documentElement && depth < 7) {
      if (node.id && /^[A-Za-z][\w-]*$/.test(node.id)) {
        parts.unshift('#' + cssEscape(node.id));
        break;
      }
      var part = node.tagName.toLowerCase();
      var cls = Array.prototype.find.call(node.classList, function (name) {
        return name && name.indexOf('vss-') !== 0 && /^[A-Za-z][\w-]*$/.test(name);
      });
      if (cls) {
        part += '.' + cssEscape(cls);
      }
      if (node.parentElement) {
        var same = Array.prototype.filter.call(node.parentElement.children, function (child) {
          return child.tagName === node.tagName;
        });
        if (same.length > 1) {
          part += ':nth-of-type(' + (same.indexOf(node) + 1) + ')';
        }
      }
      parts.unshift(part);
      node = node.parentElement;
      depth += 1;
      if (node === document.body) {
        break;
      }
    }
    return parts.join(' > ');
  }

  function placeBox(box, el, isLabel) {
    var rect = el.getBoundingClientRect();
    box.hidden = false;
    box.style.top = (rect.top + window.scrollY) + 'px';
    box.style.left = (rect.left + window.scrollX) + 'px';
    if (!isLabel) {
      box.style.width = Math.max(rect.width, 2) + 'px';
      box.style.height = Math.max(rect.height, 2) + 'px';
    }
  }

  function syncBoxes() {
    if (state.hover) placeBox(els.hover, state.hover);
    if (state.selected) {
      placeBox(els.selectBox, state.selected);
      placeBox(els.label, state.selected, true);
    }
  }

  function toast(message) {
    els.toast.hidden = false;
    els.toast.textContent = message;
    setTimeout(function () { els.toast.hidden = true; }, 2200);
  }

  function emptyDraft() {
    return {
      id: uid(),
      label: '',
      selector: '',
      properties: {},
      custom_css: '',
      scope: 'global',
      page_id: data.pageId || 0,
      path: data.path || '',
      enabled: true
    };
  }

  function field(label, control) {
    return '<label class="vss-field"><span>' + label + '</span>' + control + '</label>';
  }

  function colorField(label, prop, value) {
    var hex = toColor(value);
    return '<label class="vss-field"><span>' + label + '</span><div class="vss-color"><input type="color" data-prop="' + prop + '" value="' + hex + '"><input type="text" data-prop="' + prop + '" value="' + escapeAttr(value || '') + '" placeholder="#111827"></div></label>';
  }

  function weightOptions(current) {
    return ['', '400', '500', '600', '700', '800'].map(function (weight) {
      var label = weight ? weight : 'Default';
      return '<option value="' + weight + '"' + selectedAttr(String(current || ''), weight) + '>' + label + '</option>';
    }).join('');
  }

  function alignOptions(current) {
    return ['', 'left', 'center', 'right', 'justify'].map(function (align) {
      var label = align ? align : 'Default';
      return '<option value="' + align + '"' + selectedAttr(String(current || ''), align) + '>' + label + '</option>';
    }).join('');
  }

  function readableName(el) {
    if (el.getAttribute('aria-label')) return el.getAttribute('aria-label');
    var text = (el.innerText || '').replace(/\s+/g, ' ').trim();
    if (text) return text.slice(0, 32);
    return tagName(el);
  }

  function tagName(el) {
    return el && el.tagName ? el.tagName.toLowerCase() : 'element';
  }

  function toColor(value) {
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value || '') ? value : '#5b8def';
  }

  function selectedAttr(current, value) {
    return current === value ? ' selected' : '';
  }

  function disabled(off) {
    return off ? 'disabled' : '';
  }

  function hide(node) {
    if (node) node.hidden = true;
  }

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function uid() {
    if (window.crypto && crypto.randomUUID) {
      return crypto.randomUUID();
    }
    return 'vss-' + Date.now() + '-' + Math.random().toString(16).slice(2);
  }

  function cssEscape(value) {
    if (window.CSS && CSS.escape) return CSS.escape(value);
    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
  }

  function escapeAttr(value) {
    return escapeHtml(value);
  }
})();
