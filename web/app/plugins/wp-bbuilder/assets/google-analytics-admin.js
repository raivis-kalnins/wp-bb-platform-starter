(function () {
  'use strict';
  var cfg = window.wpbbGA, charts = window.WPBBCharts;
  if (!cfg || !charts) return;
  var node = charts.node;
  function num(v) { v = Number(v); return Number.isFinite(v) ? v : 0; }
  function growth(a, b) { if (!num(b)) return num(a) ? 'No previous baseline' : '0%'; var g = (num(a) - num(b)) / Math.abs(num(b)) * 100; return (g > 0 ? '+' : '') + g.toFixed(1) + '%'; }
  function metric(key, value, currency) {
    if (key === 'bounceRate') return (num(value) * 100).toFixed(1) + '%';
    if (key === 'averageSessionDuration') { var s = Math.round(num(value)); return Math.floor(s / 60) + 'm ' + (s % 60) + 's'; }
    if (key === 'purchaseRevenue' && /^[A-Z]{3}$/.test(currency || '')) { try { return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency }).format(num(value)); } catch (e) { /* use numeric fallback */ } }
    return num(value).toLocaleString(undefined, { maximumFractionDigits: key === 'purchaseRevenue' ? 2 : 0 });
  }
  function table(title, rows, dimension, count, label) {
    var section = node('div', undefined, 'wpbb-ga-table'); section.appendChild(node('h3', title));
    if (!rows.length) { section.appendChild(node('p', 'No recorded data in this period.')); return section; }
    var t = node('table', undefined, 'widefat striped'), thead = node('thead'), tr = node('tr'); tr.appendChild(node('th', label)); tr.appendChild(node('th', count === 'sessions' ? 'Sessions' : 'Views')); thead.appendChild(tr); t.appendChild(thead);
    var body = node('tbody'); rows.forEach(function (r) { var row = node('tr'); row.appendChild(node('td', r[dimension] || '(not set)')); row.appendChild(node('td', num(r[count]).toLocaleString())); body.appendChild(row); }); t.appendChild(body); section.appendChild(t); return section;
  }
  function draw(panel, report) {
    var content = panel.querySelector('.wpbb-ga-content'); content.replaceChildren();
    var p = report.property, w = report.window;
    content.appendChild(node('p', p.name + ' (#' + p.id + ') | ' + w.start + ' to ' + w.end + ' | Google timezone: ' + (p.timezone || 'property default'), 'wpbb-ga-period-label'));
    content.appendChild(node('p', 'Compared with ' + w.previous_start + ' to ' + w.previous_end + '. Calendar dates match the local report; timezone and measurement methods can differ.', 'wpbb-ga-note'));
    if (p.timezone && p.timezone !== w.timezone) content.appendChild(node('p', 'Timezone difference: WordPress uses ' + w.timezone + '; Google uses ' + p.timezone + '. Daily cutoffs are not identical.', 'wpbb-ga-warning'));
    // A far-ahead WordPress timezone can choose yesterday while it is still today in GA.
    try { var todayParts = new Intl.DateTimeFormat('en-CA', { timeZone: p.timezone || 'UTC', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date()); var obj = {}; todayParts.forEach(function (part) { obj[part.type] = part.value; }); if (w.end >= obj.year + '-' + obj.month + '-' + obj.day) content.appendChild(node('p', 'The final date is still in progress in this Google property timezone.', 'wpbb-ga-warning')); } catch (e) { /* timezone remains explicitly labelled */ }
    var realtime = node('div', undefined, 'wpbb-ga-realtime'); realtime.appendChild(node('span', 'Active users - last 30 minutes')); realtime.appendChild(node('strong', report.realtime === null ? 'Unavailable' : num(report.realtime).toLocaleString()));
    if (report.realtime_error) realtime.title = report.realtime_error; content.appendChild(realtime);
    var metrics = [['activeUsers', 'Active users'], ['screenPageViews', 'Views'], ['sessions', 'Sessions'], ['newUsers', 'New users'], ['bounceRate', 'Bounce rate'], ['averageSessionDuration', 'Average session'], ['ecommercePurchases', 'GA4 purchases'], ['purchaseRevenue', 'GA4 purchase revenue']];
    var grid = node('div', undefined, 'wpbb-ga-metrics'); metrics.forEach(function (m) { var card = node('div', undefined, 'wpbb-ga-card'); card.appendChild(node('span', m[1])); card.appendChild(node('strong', metric(m[0], report.current[m[0]], p.currency))); card.appendChild(node('small', growth(report.current[m[0]], report.previous[m[0]]) + ' vs previous period')); grid.appendChild(card); }); content.appendChild(grid);
    var chart = node('div', undefined, 'wpbb-ga-chart'); content.appendChild(chart);
    var byDay = {}; (report.daily || []).forEach(function (r) { var d = String(r.date); byDay[d.slice(0, 4) + '-' + d.slice(4, 6) + '-' + d.slice(6, 8)] = r; });
    var rows = [], date = new Date(w.start + 'T12:00:00Z'); for (var i = 0; i < w.days; i++) { var day = date.toISOString().slice(0, 10), r = byDay[day] || {}; rows.push({ day: day, visitors: num(r.activeUsers), views: num(r.screenPageViews) }); date.setUTCDate(date.getUTCDate() + 1); }
    charts.line(chart, rows, [{ key: 'visitors', label: 'Active users' }, { key: 'views', label: 'Views' }]);
    (report.warnings || []).forEach(function (warning) { content.appendChild(node('p', warning, 'wpbb-ga-warning')); });
    var columns = node('div', undefined, 'wpbb-ga-tables'); columns.appendChild(table('Top pages', report.pages || [], 'pagePath', 'screenPageViews', 'Page path')); columns.appendChild(table('Acquisition channels', report.channels || [], 'sessionDefaultChannelGroup', 'sessions', 'Channel')); content.appendChild(columns);
    content.appendChild(node('p', 'Google snapshot: ' + new Date(report.generated).toLocaleString() + '. Active-user totals are requested for the whole period, not summed across days. Google purchases are not a replacement for WooCommerce order records.', 'wpbb-ga-note'));
  }
  document.querySelectorAll('.wpbb-ga-panel').forEach(function (panel) {
    var status = panel.querySelector('.wpbb-ga-status'), refresh = panel.querySelector('.wpbb-ga-refresh'), select = panel.querySelector('.wpbb-ga-period'); if (!status || !refresh) return;
    var pending = false;
    async function load() {
      if (pending) return; pending = true; refresh.disabled = true; status.textContent = 'Loading Google Analytics...';
      var controller = new AbortController(), timeout = setTimeout(function () { controller.abort(); }, 65000);
      try {
        var body = new URLSearchParams({ action: 'wpbb_ga_report', nonce: cfg.nonce, days: select.value, start: panel.dataset.start || '', end: panel.dataset.end || '' });
        var response = await fetch(cfg.url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString(), signal: controller.signal });
        var result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.data && result.data.message || 'Report unavailable. Reload the page and check the Google connection.');
        draw(panel, result.data); status.textContent = 'Connected - reports from Google Analytics'; status.classList.remove('has-error');
      } catch (error) { status.textContent = error.name === 'AbortError' ? 'Google report timed out. Try again after checking the connection.' : error.message; status.classList.add('has-error'); panel.querySelector('.wpbb-ga-content').replaceChildren(); }
      finally { clearTimeout(timeout); pending = false; refresh.disabled = false; }
    }
    refresh.addEventListener('click', load); select.addEventListener('change', function () { panel.dataset.start = ''; panel.dataset.end = ''; load(); }); load();
  });
}());
