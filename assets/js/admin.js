(function () {
  'use strict';

  var cfg = window.vssAdmin;
  if (!cfg) {
    return;
  }

  var state = JSON.parse(JSON.stringify(cfg.state || {}));

  document.addEventListener('DOMContentLoaded', function () {
    bindTabs();
    fillSettings();
    renderStats();
    renderWidgets();
    renderSnippets();
    renderRules();

    document.getElementById('vss-add-widget').addEventListener('click', function () {
      state.widgets = state.widgets || [];
      state.widgets.push({
        id: uid(),
        title: 'Client widget',
        content: '<p>Add shortcuts or notes for your client.</p>',
        roles: ['administrator', 'editor']
      });
      renderWidgets();
    });

    document.getElementById('vss-add-snippet').addEventListener('click', function () {
      state.snippets = state.snippets || [];
      state.snippets.push({
        id: uid(),
        slug: 'snippet-' + (state.snippets.length + 1),
        title: 'New snippet',
        content: '<p>Scheduled or audience-specific content.</p>',
        visibility: 'all',
        start: '',
        end: ''
      });
      renderSnippets();
    });

    document.querySelectorAll('[data-vss-save]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        collectLists();
        save(btn.getAttribute('data-vss-save'));
      });
    });

    document.getElementById('vss-settings-form').addEventListener('submit', function (event) {
      event.preventDefault();
      var form = event.currentTarget;
      state.settings = Object.assign({}, state.settings, {
        brand_name: form.brand_name.value,
        min_role: form.min_role.value,
        hide_wp_logo: form.hide_wp_logo.checked,
        welcome_text: form.welcome_text.value
      });
      save('settings');
    });

    document.getElementById('vss-export').addEventListener('click', exportBackup);
    document.getElementById('vss-import').addEventListener('change', importBackup);
  });

  function bindTabs() {
    document.querySelectorAll('[data-vss-tab]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('[data-vss-tab]').forEach(function (el) { el.classList.remove('is-active'); });
        document.querySelectorAll('[data-vss-panel]').forEach(function (el) { el.classList.remove('is-active'); });
        btn.classList.add('is-active');
        var panel = document.querySelector('[data-vss-panel="' + btn.getAttribute('data-vss-tab') + '"]');
        if (panel) panel.classList.add('is-active');
      });
    });
  }

  function fillSettings() {
    var form = document.getElementById('vss-settings-form');
    var settings = state.settings || {};
    form.brand_name.value = settings.brand_name || 'Visual Site Studio';
    form.min_role.value = settings.min_role || 'administrator';
    form.hide_wp_logo.checked = Boolean(settings.hide_wp_logo);
    form.welcome_text.value = settings.welcome_text || '';
  }

  function renderStats() {
    var root = document.getElementById('vss-stats');
    var rules = (state.rules || []).length;
    var widgets = (state.widgets || []).length;
    var snippets = (state.snippets || []).length;
    root.innerHTML =
      '<h2>This site</h2>' +
      '<p class="vss-stat">' + rules + '</p><p class="vss-muted">saved style rules</p>' +
      '<p><strong>' + widgets + '</strong> dashboard widgets · <strong>' + snippets + '</strong> content snippets</p>';
  }

  function renderWidgets() {
    var root = document.getElementById('vss-widgets');
    root.innerHTML = (state.widgets || []).map(function (widget, index) {
      return '<article class="vss-item" data-widget="' + index + '">' +
        '<label>Title <input type="text" data-k="title" value="' + esc(widget.title) + '"></label>' +
        '<label>HTML content <textarea rows="4" data-k="content">' + esc(widget.content) + '</textarea></label>' +
        '<label>Visible to roles (comma separated) <input type="text" data-k="roles" value="' + esc((widget.roles || []).join(', ')) + '"></label>' +
        '<p><button type="button" class="button-link-delete" data-remove-widget="' + index + '">Remove</button></p>' +
        '</article>';
    }).join('') || '<p class="vss-muted">No widgets yet.</p>';

    root.querySelectorAll('[data-remove-widget]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        state.widgets.splice(Number(btn.getAttribute('data-remove-widget')), 1);
        renderWidgets();
      });
    });
  }

  function renderSnippets() {
    var root = document.getElementById('vss-snippets');
    root.innerHTML = (state.snippets || []).map(function (snippet, index) {
      return '<article class="vss-item" data-snippet="' + index + '">' +
        '<div class="vss-item__grid">' +
        '<label>Title <input type="text" data-k="title" value="' + esc(snippet.title) + '"></label>' +
        '<label>Slug <input type="text" data-k="slug" value="' + esc(snippet.slug) + '"></label>' +
        '</div>' +
        '<label>Content <textarea rows="4" data-k="content">' + esc(snippet.content) + '</textarea></label>' +
        '<div class="vss-item__grid">' +
        '<label>Visibility <select data-k="visibility">' +
        opt('all', snippet.visibility, 'Everyone') +
        opt('logged_in', snippet.visibility, 'Logged-in users') +
        opt('logged_out', snippet.visibility, 'Logged-out visitors') +
        '</select></label>' +
        '<label>Shortcode <input readonly class="vss-code" value="[vss_content slug=&quot;' + esc(snippet.slug) + '&quot;]"></label>' +
        '</div>' +
        '<div class="vss-item__grid">' +
        '<label>Start <input type="datetime-local" data-k="start" value="' + esc(snippet.start) + '"></label>' +
        '<label>End <input type="datetime-local" data-k="end" value="' + esc(snippet.end) + '"></label>' +
        '</div>' +
        '<p><button type="button" class="button-link-delete" data-remove-snippet="' + index + '">Remove</button></p>' +
        '</article>';
    }).join('') || '<p class="vss-muted">No snippets yet.</p>';

    root.querySelectorAll('[data-remove-snippet]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        state.snippets.splice(Number(btn.getAttribute('data-remove-snippet')), 1);
        renderSnippets();
      });
    });
  }

  function renderRules() {
    var root = document.getElementById('vss-rules-list');
    var rules = state.rules || [];
    if (!rules.length) {
      root.innerHTML = '<p class="vss-muted">No visual rules saved yet. Open the front end and restyle something.</p>';
      return;
    }
    root.innerHTML = '<h3>Saved visual rules</h3><ul>' + rules.map(function (rule) {
      return '<li><code>' + esc(rule.selector) + '</code> — ' + esc(rule.label || 'Untitled') + ' (' + esc(rule.scope) + ')</li>';
    }).join('') + '</ul>';
  }

  function collectLists() {
    document.querySelectorAll('[data-widget]').forEach(function (card) {
      var index = Number(card.getAttribute('data-widget'));
      var widget = state.widgets[index];
      if (!widget) return;
      widget.title = val(card, 'title');
      widget.content = val(card, 'content');
      widget.roles = val(card, 'roles').split(',').map(function (role) { return role.trim(); }).filter(Boolean);
    });
    document.querySelectorAll('[data-snippet]').forEach(function (card) {
      var index = Number(card.getAttribute('data-snippet'));
      var snippet = state.snippets[index];
      if (!snippet) return;
      ['title', 'slug', 'content', 'visibility', 'start', 'end'].forEach(function (key) {
        snippet[key] = val(card, key);
      });
    });
  }

  function save(which) {
    collectLists();
    var body = {};
    body[which] = state[which];
    request('state', { method: 'POST', body: JSON.stringify(body) }).then(function (payload) {
      state = Object.assign(state, payload);
      renderStats();
      renderRules();
      toast('Saved');
    }).catch(function () {
      toast('Save failed');
    });
  }

  function exportBackup() {
    request('export').then(function (payload) {
      var blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
      var url = URL.createObjectURL(blob);
      var link = document.createElement('a');
      link.href = url;
      link.download = 'visual-site-studio-backup.json';
      link.click();
      URL.revokeObjectURL(url);
    });
  }

  function importBackup(event) {
    var file = event.target.files && event.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function () {
      try {
        var payload = JSON.parse(reader.result);
        request('import', { method: 'POST', body: JSON.stringify(payload) }).then(function (next) {
          state = Object.assign(state, next);
          fillSettings();
          renderStats();
          renderWidgets();
          renderSnippets();
          renderRules();
          toast('Backup imported');
        });
      } catch (err) {
        toast('Invalid JSON');
      }
    };
    reader.readAsText(file);
  }

  function request(path, options) {
    options = options || {};
    return fetch(cfg.restUrl + path, {
      method: options.method || 'GET',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cfg.nonce
      },
      body: options.body
    }).then(function (res) {
      if (!res.ok) throw new Error('request failed');
      return res.json();
    });
  }

  function val(root, key) {
    var input = root.querySelector('[data-k="' + key + '"]');
    return input ? input.value : '';
  }

  function opt(value, current, label) {
    return '<option value="' + value + '"' + (current === value ? ' selected' : '') + '>' + label + '</option>';
  }

  function toast(message) {
    var el = document.getElementById('vss-toast');
    el.hidden = false;
    el.textContent = message;
    setTimeout(function () { el.hidden = true; }, 2000);
  }

  function uid() {
    return window.crypto && crypto.randomUUID ? crypto.randomUUID() : 'id-' + Date.now();
  }

  function esc(value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
  }
})();
