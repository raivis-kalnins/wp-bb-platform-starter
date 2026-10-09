document.addEventListener('DOMContentLoaded', function () {
  var banner = document.querySelector('[data-wpbb-cookie-banner="1"]');
  if (!banner) return;
  try {
    var state = localStorage.getItem('wpbb_cookie_consent');
    if (state === 'accepted' || state === 'rejected') {
      banner.style.display = 'none';
      if (typeof window.wp_set_consent === 'function') window.wp_set_consent('statistics', state === 'accepted' ? 'allow' : 'deny');
      window.dispatchEvent(new CustomEvent('wpbb:consent-changed', {detail: {statistics: state === 'accepted'}}));
      return;
    }
  } catch (e) {}

  var accept = banner.querySelector('[data-wpbb-cookie-accept="1"]');
  var reject = banner.querySelector('[data-wpbb-cookie-reject="1"]');

  function save(value) {
    try { localStorage.setItem('wpbb_cookie_consent', value); } catch (e) {}
    banner.style.display = 'none';
    window.dispatchEvent(new CustomEvent('wpbb:consent-changed', {detail: {statistics: value === 'accepted'}}));
    if (typeof window.wp_set_consent === 'function') window.wp_set_consent('statistics', value === 'accepted' ? 'allow' : 'deny');
  }

  if (accept) accept.addEventListener('click', function () { save('accepted'); });
  if (reject) reject.addEventListener('click', function () { save('rejected'); });
});
