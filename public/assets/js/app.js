/* DineFlow shared helpers: CSRF-aware fetch, toasts, sound alerts, live-refreshing panels, ajax buttons/forms. */
(function () {
  const meta = (n) => (document.querySelector('meta[name="' + n + '"]') || {}).content || '';

  const DF = (window.DF = {
    base: meta('base-url'),
    csrfName: meta('csrf-name'),
    csrfHash: meta('csrf-hash'),

    url(path) { return this.base + String(path).replace(/^\//, ''); },

    async post(url, data) {
      const fd = data instanceof FormData ? data : new FormData();
      if (!(data instanceof FormData) && data) Object.entries(data).forEach(([k, v]) => fd.append(k, v));
      fd.set(this.csrfName, this.csrfHash);
      let res, json = {};
      try {
        res = await fetch(url.startsWith('http') ? url : this.url(url), {
          method: 'POST', body: fd, credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrfHash, Accept: 'application/json' },
        });
        json = await res.json().catch(() => ({}));
      } catch (e) {
        return { ok: false, error: 'Network problem — please check your connection and try again.' };
      }
      if (!res.ok && json.ok === undefined) json.ok = false;
      if (!res.ok && !json.error) json.error = json.message || (res.status === 403 ? 'Session expired — please reload the page.' : 'Something went wrong (' + res.status + ').');
      if (json.ok === undefined) json.ok = res.ok;
      return json;
    },

    async get(url) {
      const res = await fetch(url.startsWith('http') ? url : this.url(url), {
        credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (res.status === 401) { location.reload(); throw new Error('auth'); }
      return res;
    },

    toast(msg, type = '', ms = 4500) {
      let box = document.getElementById('toasts');
      if (!box) { box = document.createElement('div'); box.id = 'toasts'; document.body.appendChild(box); }
      const t = document.createElement('div');
      t.className = 'toast ' + type;
      t.textContent = msg;
      t.onclick = () => t.remove();
      box.appendChild(t);
      setTimeout(() => t.remove(), ms);
    },

    audio: null,
    enableSound() {
      try {
        this.audio = this.audio || new (window.AudioContext || window.webkitAudioContext)();
        this.audio.resume();
        localStorage.setItem('df_sound', '1');
      } catch (e) { /* no audio */ }
    },
    beep(kind = 'order') {
      if (!this.audio) return;
      const tones = { order: [880, 1175, 1568], service: [660, 660, 880], pay: [523, 784] }[kind] || [880];
      let t = this.audio.currentTime;
      tones.forEach((f) => {
        const o = this.audio.createOscillator(), g = this.audio.createGain();
        o.type = 'sine'; o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.35, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.22);
        o.connect(g).connect(this.audio.destination); o.start(t); o.stop(t + 0.25); t += 0.18;
      });
    },

    /** Re-fetches every [data-live] panel. */
    refreshLive() { document.querySelectorAll('[data-live]').forEach((el) => DF.loadLive(el)); },
    async loadLive(el) {
      if (el.dataset.busy) return;
      el.dataset.busy = '1';
      try {
        const res = await DF.get(el.dataset.live);
        if (res.ok) {
          const html = await res.text();
          if (html !== el.dataset.last) { el.innerHTML = html; el.dataset.last = html; el.dispatchEvent(new CustomEvent('df:updated', { bubbles: true })); }
        }
      } catch (e) { /* ignore, try next tick */ }
      delete el.dataset.busy;
    },
  });

  document.addEventListener('DOMContentLoaded', () => {
    // Live panels
    document.querySelectorAll('[data-live]').forEach((el) => {
      const ms = parseInt(el.dataset.interval || '5000', 10);
      if (!el.innerHTML.trim()) DF.loadLive(el);
      else el.dataset.last = el.innerHTML;
      setInterval(() => { if (!document.hidden && !document.querySelector('dialog[open]')) DF.loadLive(el); }, ms);
    });

    // Mobile nav
    const burger = document.querySelector('.burger');
    if (burger) burger.addEventListener('click', () => document.querySelector('.mobile-nav').classList.toggle('open'));

    // Flash messages fade
    document.querySelectorAll('.alert[data-autohide]').forEach((a) => setTimeout(() => a.remove(), 7000));
  });

  // Buttons: <button data-post="staff/orders/5/paid" data-confirm="Sure?" data-prompt="Reason" data-field="reason">
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-post]');
    if (!btn) return;
    e.preventDefault();
    if (btn.dataset.confirm && !confirm(btn.dataset.confirm)) return;
    const data = {};
    if (btn.dataset.prompt) {
      const v = prompt(btn.dataset.prompt, btn.dataset.default || '');
      if (v === null) return;
      if (!v.trim() && btn.dataset.required !== 'false') { DF.toast('Please enter a value.', 'err'); return; }
      data[btn.dataset.field || 'value'] = v.trim();
    }
    Object.keys(btn.dataset).filter((k) => k.startsWith('val')).forEach((k) => (data[k.slice(3).toLowerCase()] = btn.dataset[k]));
    btn.disabled = true;
    const r = await DF.post(btn.dataset.post, data);
    btn.disabled = false;
    DF.toast(r.ok ? r.message || 'Done' : r.error || 'Failed', r.ok ? 'ok' : 'err');
    if (r.ok) {
      if (r.redirect) location.href = r.redirect;
      else if (btn.dataset.reload !== undefined) location.reload();
      else DF.refreshLive();
    }
  });

  // Forms: <form data-ajax> posts via fetch, toasts, closes its dialog, refreshes live panels.
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-ajax]');
    if (!form) return;
    e.preventDefault();
    const submit = form.querySelector('[type=submit]');
    if (submit) submit.disabled = true;
    const r = await DF.post(form.action, new FormData(form));
    if (submit) submit.disabled = false;
    DF.toast(r.ok ? r.message || 'Saved' : r.error || 'Failed', r.ok ? 'ok' : 'err');
    if (r.ok) {
      if (r.redirect) return (location.href = r.redirect);
      if (form.dataset.reload !== undefined) return location.reload();
      const dlg = form.closest('dialog');
      if (dlg) dlg.close();
      form.reset();
      DF.refreshLive();
    }
  });
})();
