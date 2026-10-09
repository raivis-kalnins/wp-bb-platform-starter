document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.wpbb-booking').forEach(function (wrap) {
    var form = wrap.querySelector('.wpbb-booking__form');
    if (!form || !window.wpbbBooking) return;
    var booked = [];
    try { booked = JSON.parse(wrap.getAttribute('data-booked') || '[]'); } catch (e) {}
    var provider = form.querySelector('[name="provider_id"]');
    var date = form.querySelector('[name="date"]');
    var time = form.querySelector('[name="time"]');
    var message = wrap.querySelector('.wpbb-booking__message');
    var allTimes = time ? Array.from(time.options).map(function (o) { return { value:o.value, text:o.textContent }; }) : [];

    function key() { return (provider ? provider.value : '0') + '|' + (date ? date.value : '') + '|' + (time ? time.value : ''); }
    function refreshTimes() {
      if (!time) return;
      var chosen = time.value;
      time.innerHTML = '';
      allTimes.forEach(function (item) {
        var option = new Option(item.text, item.value);
        if (item.value && date && date.value && booked.indexOf((provider ? provider.value : '0') + '|' + date.value + '|' + item.value) !== -1) {
          option.disabled = true;
          option.textContent += ' — unavailable';
        }
        time.appendChild(option);
      });
      if (chosen) time.value = chosen;
    }
    if (date) date.addEventListener('change', refreshTimes);
    if (provider) {
      provider.addEventListener('change', refreshTimes);
      var requestedProvider = new URLSearchParams(window.location.search).get('doctor') || new URLSearchParams(window.location.search).get('provider');
      if (requestedProvider && Array.from(provider.options).some(function (option) { return option.value === requestedProvider; })) {
        provider.value = requestedProvider;
      }
    }
    refreshTimes();

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!form.reportValidity()) return;
      if (booked.indexOf(key()) !== -1) {
        message.textContent = 'That appointment time is unavailable.';
        message.className = 'wpbb-booking__message small text-danger';
        return;
      }
      var button = form.querySelector('[type="submit"]');
      if (button) button.disabled = true;
      message.textContent = 'Sending…';
      message.className = 'wpbb-booking__message small';
      var data = new FormData(form);
      data.append('action', 'wpbb_submit_booking');
      data.append('nonce', wpbbBooking.nonce);
      fetch(wpbbBooking.ajaxUrl, { method:'POST', body:data, credentials:'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (json) {
          if (!json.success) throw new Error((json.data && json.data.message) || wpbbBooking.error);
          if (date && time && date.value && time.value) booked.push((provider ? provider.value : '0') + '|' + date.value + '|' + time.value);
          message.textContent = (json.data && json.data.message) || wrap.getAttribute('data-success') || wpbbBooking.success;
          message.className = 'wpbb-booking__message small text-success';
          form.reset();
          refreshTimes();
        })
        .catch(function (error) {
          message.textContent = error.message || wpbbBooking.error;
          message.className = 'wpbb-booking__message small text-danger';
        })
        .finally(function () { if (button) button.disabled = false; });
    });
  });
});
