/* Living menu: table memory, item options, tray, promo, payment and order placement. */
(function () {
  const M = window.MENU;
  const $ = (s) => document.querySelector(s);
  const $$ = (s) => Array.from(document.querySelectorAll(s));
  const peso = (n) => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 });
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const itemsById = Object.fromEntries(M.items.map((i) => [i.id, i]));
  const TABLE_TTL = 6 * 3600 * 1000; // remember the table for the visit (6 hours)

  const store = {
    get(k, d) { try { const v = JSON.parse(localStorage.getItem('df_' + k)); return v ?? d; } catch (e) { return d; } },
    set(k, v) { localStorage.setItem('df_' + k, JSON.stringify(v)); },
    del(k) { localStorage.removeItem('df_' + k); },
  };

  const state = {
    type: 'dine_in',
    table: null,
    info: { hasVisit: false, canPayLater: false },
    cart: store.get('cart', []).filter((l) => itemsById[l.id]),
    promo: null,
  };

  // ------------------------------------------------------------------ table
  function loadTable() {
    if (M.scanned) {
      state.table = M.scanned;
      store.set('table', { ...M.scanned, at: Date.now() });
      history.replaceState(null, '', location.pathname); // keep the URL clean; table is remembered
    } else {
      const t = store.get('table', null);
      if (t && Date.now() - t.at < TABLE_TTL) state.table = t;
      else store.del('table');
    }
    state.type = M.type || store.get('type', 'dine_in');
    $('#guests').value = store.get('guests', state.table ? Math.min(state.table.seats, 2) : 2);
  }

  function fillTableSelect() {
    const sel = $('#tableSelect');
    const cur = state.table ? state.table.code : '';
    sel.innerHTML = '<option value="">' + esc(sel.options[0].text) + '</option>';
    const zones = {};
    M.tables.forEach((t) => (zones[t.zone] = zones[t.zone] || []).push(t));
    Object.entries(zones).forEach(([zone, list]) => {
      const og = document.createElement('optgroup');
      og.label = zone;
      list.forEach((t) => {
        if (t.status === 'occupied' && t.code !== cur) return; // only free tables (or your own)
        const o = document.createElement('option');
        o.value = t.code;
        o.textContent = t.code + ' · ' + t.seats + ' seats' + (t.status === 'reserved' ? ' · Reserved (only if booked under your name)' : '');
        if (t.code === cur) o.selected = true;
        og.appendChild(o);
      });
      if (og.children.length) sel.appendChild(og);
    });
  }

  async function refreshTableInfo() {
    state.info = { hasVisit: false, canPayLater: false };
    if (state.table) {
      try {
        const r = await (await DF.get('api/table/' + encodeURIComponent(state.table.code))).json();
        if (r.ok) state.info = r;
      } catch (e) { /* offline */ }
    }
    renderPayment();
  }

  function setTable(code) {
    const t = M.tables.find((x) => x.code === code);
    state.table = t ? { code: t.code, zone: t.zone, seats: t.seats } : null;
    if (state.table) store.set('table', { ...state.table, at: Date.now() });
    else store.del('table');
    renderSetup();
    refreshTableInfo();
  }

  function renderSetup() {
    $$('.segmented [data-type]').forEach((b) => b.classList.toggle('active', b.dataset.type === state.type));
    $('#dineFields').classList.toggle('hidden', state.type !== 'dine_in');
    $('#takeFields').classList.toggle('hidden', state.type !== 'take_away');
    const showBanner = state.type === 'dine_in' && state.table;
    $('#tableBanner').classList.toggle('hidden', !showBanner);
    document.body.classList.toggle('has-banner', !!showBanner);
    if (showBanner) {
      $('#bannerText').textContent = M.i18n.seated_at.replace('{code}', state.table.code).replace('{zone}', state.table.zone).replace('{seats}', state.table.seats);
    }
    $('#serviceBar').classList.toggle('hidden', !showBanner);
    $('#contactMobile').closest('.field').classList.toggle('hidden', state.type === 'take_away');
    $('#trayDest').textContent = state.type === 'take_away' ? '🛍️ Take-away' : state.table ? '🍽️ Table ' + state.table.code + ' · Dine-in' : '🍽️ Dine-in — choose your table';
    fillTableSelect();
    renderPayment();
  }

  // ------------------------------------------------------------------ cart
  const unitPrice = (l) => {
    const it = itemsById[l.id];
    let p = it.price;
    Object.entries(l.choices || {}).forEach(([gi, cis]) => cis.forEach((ci) => (p += Number(it.options[gi]?.choices[ci]?.price || 0))));
    return p;
  };
  const optText = (l) => {
    const it = itemsById[l.id];
    return Object.entries(l.choices || {}).map(([gi, cis]) => {
      const g = it.options[gi];
      if (!g || !cis.length) return '';
      return g.name + ': ' + cis.map((ci) => g.choices[ci]?.label).join(', ');
    }).filter(Boolean).join(' · ');
  };
  const subtotal = () => state.cart.reduce((s, l) => s + unitPrice(l) * l.qty, 0);
  const total = () => Math.max(0, subtotal() - (state.promo ? state.promo.discount : 0));

  function saveCart() { store.set('cart', state.cart); renderCart(); }

  function renderCart() {
    const box = $('#trayLines');
    box.innerHTML = state.cart.map((l, idx) => {
      const it = itemsById[l.id];
      return '<div class="tray-line"><div><b>' + esc(it.name) + '</b>' +
        (optText(l) ? '<div class="opts">' + esc(optText(l)) + '</div>' : '') +
        (l.note ? '<div class="opts">📝 ' + esc(l.note) + '</div>' : '') +
        '</div><div class="right"><b>' + peso(unitPrice(l) * l.qty) + '</b></div>' +
        '<div><div class="qty"><button type="button" data-q="' + idx + '" data-d="-1">−</button><span>' + l.qty + '</span><button type="button" data-q="' + idx + '" data-d="1">+</button></div></div>' +
        '<div class="right"><button type="button" class="link small" data-rm="' + idx + '">Remove</button></div></div>';
    }).join('');
    const empty = state.cart.length === 0;
    $('#trayEmpty').classList.toggle('hidden', !empty);
    $('#checkout').classList.toggle('hidden', empty);
    $('#placeBtn').disabled = empty;
    const count = state.cart.reduce((s, l) => s + l.qty, 0);
    $('#fabCount').textContent = count;
    $('#fabTotal').textContent = peso(total());
    $('#trayFab').classList.toggle('hidden', empty || $('#drawer').classList.contains('open'));
    $('#sumSub').textContent = peso(subtotal());
    $('#discRow').classList.toggle('hidden', !state.promo);
    if (state.promo) { $('#sumDisc').textContent = '−' + peso(state.promo.discount); $('#discCode').textContent = '(' + state.promo.code + ')'; }
    $('#sumTotal').textContent = peso(total());
    $$('[data-amount]').forEach((e) => (e.textContent = peso(total())));
  }

  let promoTimer;
  function revalidatePromo() {
    if (!state.promo) return;
    clearTimeout(promoTimer);
    promoTimer = setTimeout(() => applyPromo(state.promo.code, true), 400);
  }

  async function applyPromo(code, silent) {
    code = (code || '').trim().toUpperCase();
    if (!code) { state.promo = null; renderCart(); return; }
    const r = await DF.post('promo/check', { code, cart: JSON.stringify(state.cart) });
    const msg = $('#promoMsg');
    if (r.ok) { state.promo = { code: r.code, discount: r.discount }; msg.style.color = 'var(--green)'; msg.textContent = r.message; }
    else { state.promo = null; msg.style.color = 'var(--red)'; msg.textContent = r.error; if (!silent) DF.toast(r.error, 'err'); }
    renderCart();
  }

  // ------------------------------------------------------------------ item dialog
  let dlg = { id: null, qty: 1 };
  function openItem(id) {
    const it = itemsById[id];
    if (!it || !it.available) return;
    if (!it.options.length) { // no options: add straight away
      addLine({ id, qty: 1, choices: {}, note: '' });
      DF.toast('✓ ' + it.name, 'ok', 1800);
      return;
    }
    dlg = { id, qty: 1 };
    $('#dlgImg').src = it.photo; $('#dlgImg').alt = it.name;
    $('#dlgCat').textContent = it.category + ' · ~' + it.prep + ' ' + M.i18n.min;
    $('#dlgName').textContent = it.name;
    $('#dlgDesc').textContent = it.desc || '';
    $('#dlgNote').value = '';
    $('#dlgQty').textContent = '1';
    $('#dlgOptions').innerHTML = it.options.map((g, gi) => {
      const single = g.type !== 'multi';
      return '<div class="opt-group"><h4>' + esc(g.name) + '<span class="badge ' + (g.required ? 'rust' : '') + '">' + (g.required ? M.i18n.required : M.i18n.choose_any) + '</span></h4>' +
        g.choices.map((c, ci) => '<label class="opt"><span><input type="' + (single ? 'radio' : 'checkbox') + '" name="g' + gi + '" value="' + ci + '"' +
          (single && g.required && ci === 0 ? ' checked' : '') + '>' + esc(c.label) + '</span><span class="muted">' + (Number(c.price) > 0 ? '+' + peso(c.price) : '') + '</span></label>').join('') + '</div>';
    }).join('');
    updateDlgPrice();
    $('#itemDialog').showModal();
  }
  function dlgChoices() {
    const it = itemsById[dlg.id], ch = {};
    it.options.forEach((g, gi) => {
      const v = $$('#dlgOptions input[name="g' + gi + '"]:checked').map((i) => parseInt(i.value, 10));
      if (v.length) ch[gi] = v;
    });
    return ch;
  }
  function updateDlgPrice() {
    $('#dlgPrice').textContent = peso(unitPrice({ id: dlg.id, choices: dlgChoices() }) * dlg.qty);
  }
  function addLine(line) {
    const key = JSON.stringify([line.id, line.choices, line.note]);
    const same = state.cart.find((l) => JSON.stringify([l.id, l.choices, l.note]) === key);
    if (same) same.qty += line.qty; else state.cart.push(line);
    saveCart();
    revalidatePromo();
  }

  // ------------------------------------------------------------------ payment
  function renderPayment() {
    const methods = [...M.methods];
    if (state.type === 'dine_in' && state.info.canPayLater) methods.unshift('pay_later');
    $$('.pay-opt').forEach((o) => o.classList.toggle('hidden', !methods.includes(o.dataset.method)));
    let checked = $('input[name=pm]:checked');
    if (!checked || !methods.includes(checked.value)) {
      const first = $('.pay-opt[data-method="' + methods[0] + '"] input');
      if (first) first.checked = true;
      checked = first;
    }
    const m = checked ? checked.value : null;
    $('#boxGcash').classList.toggle('hidden', m !== 'gcash');
    $('#boxBank').classList.toggle('hidden', m !== 'online_banking');
    $('#proofFields').classList.toggle('hidden', !(m === 'gcash' || m === 'online_banking'));
    const addToTable = state.type === 'dine_in' && state.info.hasVisit;
    $('#placeBtn').textContent = addToTable ? M.i18n.add_to_table : M.i18n.place_order;
  }

  function initPaymentBoxes() {
    $('#gcashName').textContent = M.gcash.name || '—';
    $('#gcashNumber').textContent = M.gcash.number || '—';
    if (M.gcash.qr) { $('#gcashQr').src = M.gcash.qr; $('#gcashQr').classList.remove('hidden'); }
    const bs = $('#bankSelect');
    bs.innerHTML = M.banks.map((b) => '<option value="' + b.id + '">' + esc(b.bank) + '</option>').join('');
    const showBank = () => {
      const b = M.banks.find((x) => String(x.id) === bs.value) || M.banks[0];
      $('#bankName').textContent = b ? b.name : '—';
      $('#bankNumber').textContent = b ? b.number : '—';
    };
    bs.addEventListener('change', showBank);
    showBank();
  }

  // ------------------------------------------------------------------ place order
  async function placeOrder() {
    const err = $('#orderError');
    err.classList.add('hidden');
    const fail = (m) => { err.textContent = m; err.classList.remove('hidden'); };
    if (!state.cart.length) return;
    if (state.type === 'dine_in' && !state.table) {
      closeDrawer();
      $('#tableBox').scrollIntoView({ behavior: 'smooth', block: 'center' });
      $('#tableSelect').focus();
      return DF.toast('Please choose your table first.', 'err');
    }
    const m = ($('input[name=pm]:checked') || {}).value;
    if (!m) return fail('Please choose a payment method.');

    const fd = new FormData();
    fd.append('cart', JSON.stringify(state.cart));
    fd.append('type', state.type);
    fd.append('payment_method', m);
    if (state.promo) fd.append('promo_code', state.promo.code);
    if (state.type === 'dine_in') {
      fd.append('table', state.table.code);
      fd.append('guests', $('#guests').value);
      fd.append('name', $('#dineName').value);
      fd.append('mobile', $('#contactMobile').value);
    } else {
      fd.append('name', $('#taName').value);
      fd.append('mobile', $('#taMobile').value);
    }
    fd.append('email', $('#contactEmail').value);
    if (m === 'gcash' || m === 'online_banking') {
      fd.append('reference_no', $('#refNo').value);
      if (m === 'online_banking') fd.append('bank_account_id', $('#bankSelect').value);
      const f = $('#proofFile').files[0];
      if (f) {
        if (f.size > 5 * 1024 * 1024) return fail('The screenshot is too large (max 5 MB).');
        fd.append('proof', f);
      }
    }
    const btn = $('#placeBtn');
    btn.disabled = true; btn.textContent = '…';
    const r = await DF.post('order', fd);
    if (r.ok) {
      state.cart = []; store.set('cart', []);
      const mine = store.get('orders', []); mine.unshift(r.code); store.set('orders', [...new Set(mine)].slice(0, 10));
      location.href = r.redirect;
      return;
    }
    btn.disabled = false;
    renderPayment();
    fail(r.error || 'Could not place the order.');
  }

  // ------------------------------------------------------------------ drawer
  function openDrawer() { $('#drawer').classList.add('open'); $('#overlay').classList.add('open'); $('#trayFab').classList.add('hidden'); renderSetup(); renderCart(); }
  function closeDrawer() { $('#drawer').classList.remove('open'); $('#overlay').classList.remove('open'); renderCart(); }

  // ------------------------------------------------------------------ wire up
  document.addEventListener('DOMContentLoaded', () => {
    loadTable();
    initPaymentBoxes();
    renderSetup();
    renderCart();
    refreshTableInfo();

    $$('.segmented [data-type]').forEach((b) => b.addEventListener('click', () => {
      state.type = b.dataset.type; store.set('type', state.type); renderSetup(); refreshTableInfo();
    }));
    $('#tableSelect').addEventListener('change', (e) => setTable(e.target.value));
    $('#guests').addEventListener('change', (e) => store.set('guests', e.target.value));
    $('#notMyTable').addEventListener('click', () => {
      setTable('');
      $('#tableBox').scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => $('#tableSelect').focus(), 400);
    });

    $('#catFilter').addEventListener('click', (e) => {
      const b = e.target.closest('button[data-cat]');
      if (!b) return;
      $$('#catFilter button').forEach((x) => x.classList.toggle('active', x === b));
      $$('#menuGrid .menu-card').forEach((c) => c.classList.toggle('hidden', b.dataset.cat !== 'all' && c.dataset.cat !== b.dataset.cat));
    });

    $('#menuGrid').addEventListener('click', (e) => {
      const b = e.target.closest('[data-add]');
      if (b) openItem(parseInt(b.dataset.add, 10));
    });

    // dialog
    $('#dlgOptions').addEventListener('change', updateDlgPrice);
    $('#qtyMinus').addEventListener('click', () => { dlg.qty = Math.max(1, dlg.qty - 1); $('#dlgQty').textContent = dlg.qty; updateDlgPrice(); });
    $('#qtyPlus').addEventListener('click', () => { dlg.qty = Math.min(50, dlg.qty + 1); $('#dlgQty').textContent = dlg.qty; updateDlgPrice(); });
    $('#dlgCancel').addEventListener('click', () => $('#itemDialog').close());
    $('#dlgAdd').addEventListener('click', () => {
      const it = itemsById[dlg.id], choices = dlgChoices();
      const missing = it.options.find((g, gi) => g.required && !choices[gi]);
      if (missing) return DF.toast('Please choose ' + missing.name.toLowerCase() + '.', 'err');
      addLine({ id: dlg.id, qty: dlg.qty, choices, note: $('#dlgNote').value.trim().slice(0, 200) });
      $('#itemDialog').close();
      DF.toast('✓ ' + it.name, 'ok', 1800);
    });

    // tray
    $('#trayFab').addEventListener('click', openDrawer);
    $('#overlay').addEventListener('click', closeDrawer);
    $$('[data-close-drawer]').forEach((b) => b.addEventListener('click', closeDrawer));
    $('#trayLines').addEventListener('click', (e) => {
      const q = e.target.closest('[data-q]'), rm = e.target.closest('[data-rm]');
      if (q) { const l = state.cart[q.dataset.q]; l.qty = Math.max(1, Math.min(50, l.qty + parseInt(q.dataset.d, 10))); }
      if (rm) state.cart.splice(parseInt(rm.dataset.rm, 10), 1);
      if (q || rm) { saveCart(); revalidatePromo(); }
    });
    $('#promoBtn').addEventListener('click', () => applyPromo($('#promoInput').value));
    $('#payOptions').addEventListener('change', renderPayment);
    $('#placeBtn').addEventListener('click', placeOrder);

    // service requests
    $$('[data-service]').forEach((b) => b.addEventListener('click', async () => {
      if (!state.table) return;
      b.disabled = true;
      const r = await DF.post('service-request', { table: state.table.code, type: b.dataset.service });
      DF.toast(r.ok ? r.message : r.error, r.ok ? 'ok' : 'err');
      setTimeout(() => (b.disabled = false), 15000);
    }));

    if (location.hash === '#tray' && state.cart.length) openDrawer();
  });
})();
