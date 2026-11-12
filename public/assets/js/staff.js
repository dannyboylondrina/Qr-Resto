/* Staff alerts: polls /staff/pulse, plays a sound + shows a toast for new orders, payments to verify and service requests. */
(function () {
  const role = document.body.dataset.role;
  const KEY = 'df_pulse_' + role;
  let last = null;
  try { last = JSON.parse(sessionStorage.getItem(KEY)); } catch (e) {}

  const btn = document.getElementById('soundBtn');
  function soundUi() {
    const on = DF.audio && DF.audio.state === 'running';
    btn.textContent = on ? '🔔 Sound on' : '🔕 Enable sound';
    btn.classList.toggle('btn-green', on);
  }
  btn.addEventListener('click', () => { DF.enableSound(); setTimeout(soundUi, 100); DF.beep('pay'); });
  // Browsers only allow audio after a user gesture: re-enable on the first click if it was on before.
  if (localStorage.getItem('df_sound') === '1') {
    document.addEventListener('click', function once() { DF.enableSound(); setTimeout(soundUi, 100); document.removeEventListener('click', once); });
  }

  function setCount(name, n) {
    document.querySelectorAll('[data-count="' + name + '"]').forEach((el) => { el.textContent = n; el.classList.toggle('hidden', !n); });
  }

  async function pulse() {
    if (document.hidden) return;
    let p;
    try { p = await (await DF.get('staff/pulse')).json(); } catch (e) { return; }
    if (!p || !p.ok) return;
    setCount('orders', p.awaiting + p.verify);
    setCount('service', p.service);
    setCount('kitchen', p.kitchen);
    setCount('bills', p.bills);
    setCount('reservations', p.reservations);
    setCount('reviews', p.reviews);

    if (last) {
      let changed = false;
      if (role === 'kitchen' || document.body.classList.contains('kitchen-body')) {
        if (p.kitchen_max > last.kitchen_max) { DF.beep('order'); DF.toast('🔥 New order for the kitchen'); changed = true; }
      }
      if (role !== 'kitchen') {
        if (p.order_max > last.order_max) { DF.beep('order'); DF.toast('🧾 New order' + (p.order_label ? ': ' + p.order_label : '')); changed = true; }
        if (p.verify_max > last.verify_max) { DF.beep('pay'); DF.toast('💳 Payment to verify' + (p.verify_label ? ': ' + p.verify_label : '')); changed = true; }
        if (p.service_max > last.service_max) { DF.beep('service'); DF.toast('🙋 ' + (p.service_label || 'Service request')); changed = true; }
        if (p.res_max > last.res_max) { DF.beep('pay'); DF.toast('📅 New reservation request'); changed = true; }
        if (p.kitchen_max > last.kitchen_max) changed = true;
      }
      if (changed || p.hash !== last.hash) DF.refreshLive();
    }
    last = p;
    sessionStorage.setItem(KEY, JSON.stringify(p));
  }
  pulse();
  setInterval(pulse, 5000);
})();
