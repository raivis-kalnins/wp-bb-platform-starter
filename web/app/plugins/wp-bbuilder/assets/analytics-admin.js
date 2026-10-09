/* Local SVG charts: no Google Charts script, CDN, third-party request or fabricated data. */
(function () {
  'use strict';
  if (window.WPBBCharts) return;
  function node(tag, text, cls) { var n = document.createElement(tag); if (text !== undefined) n.textContent = text; if (cls) n.className = cls; return n; }
  function svgNode(tag, attrs) { var n = document.createElementNS('http://www.w3.org/2000/svg', tag); Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); }); return n; }
  function number(v) { v = Number(v); return Number.isFinite(v) ? v : 0; }
  function line(el, rows, series) {
    el.replaceChildren(); rows = rows || [];
    if (!rows.length) { el.appendChild(node('p', 'No recorded data in this period.', 'wpbb-chart-empty')); return; }
    var svg = svgNode('svg', { viewBox: '0 0 640 230', role: 'img', 'aria-label': 'Daily ' + series.map(function (s) { return s.label; }).join(' and ') + '. Exact values in the data table below.' });
    var max = Math.max.apply(null, [1].concat(rows.flatMap(function (r) { return series.map(function (s) { return number(r[s.key]); }); })));
    var left = 52, right = 624, top = 20, bottom = 184;
    for (var i = 0; i <= 4; i++) {
      var y = top + (bottom - top) * i / 4;
      svg.appendChild(svgNode('line', { x1: left, x2: right, y1: y, y2: y, 'class': 'wpbb-chart-grid' }));
      var label = svgNode('text', { x: left - 8, y: y + 4, 'text-anchor': 'end', 'class': 'wpbb-chart-label' }); label.textContent = Math.round(max * (1 - i / 4)).toLocaleString(); svg.appendChild(label);
    }
    var xPos = function (i) { return rows.length === 1 ? (left + right) / 2 : left + i * (right - left) / (rows.length - 1); };
    series.forEach(function (s, index) {
      var coords = rows.map(function (r, i) { return [xPos(i), bottom - number(r[s.key]) / max * (bottom - top)]; });
      svg.appendChild(svgNode('polyline', { points: coords.map(function (p) { return p.join(','); }).join(' '), 'class': 'wpbb-chart-series series-' + index, fill: 'none' }));
      if (rows.length < 40) coords.forEach(function (p, i) {
        var circle = svgNode('circle', { cx: p[0], cy: p[1], r: 2.4, 'class': 'wpbb-chart-point series-' + index });
        var title = svgNode('title'); title.textContent = rows[i].day + ': ' + s.label + ' ' + number(rows[i][s.key]); circle.appendChild(title); svg.appendChild(circle);
      });
    });
    var indexes = Array.from(new Set([0, Math.floor((rows.length - 1) / 2), rows.length - 1]));
    indexes.forEach(function (i) { var n = svgNode('text', { x: xPos(i), y: bottom + 23, 'text-anchor': i === 0 ? 'start' : i === rows.length - 1 ? 'end' : 'middle', 'class': 'wpbb-chart-label' }); n.textContent = rows[i].day; svg.appendChild(n); });
    el.appendChild(svg);
    var legend = node('div', undefined, 'wpbb-chart-legend'); series.forEach(function (s, i) { legend.appendChild(node('span', s.label, 'series-' + i)); }); el.appendChild(legend);
    var details = node('details'); details.appendChild(node('summary', 'View daily data')); var table = node('table', undefined, 'widefat striped');
    var head = node('thead'), hrow = node('tr'); ['Date'].concat(series.map(function (s) { return s.label; })).forEach(function (label) { hrow.appendChild(node('th', label)); }); head.appendChild(hrow); table.appendChild(head);
    var body = node('tbody'); rows.forEach(function (r) { var tr = node('tr'); tr.appendChild(node('td', r.day)); series.forEach(function (s) { tr.appendChild(node('td', number(r[s.key]).toLocaleString())); }); body.appendChild(tr); }); table.appendChild(body); details.appendChild(table); el.appendChild(details);
  }
  function bars(el, rows, labelKey, metricKey) {
    el.replaceChildren(); rows = rows || [];
    if (!rows.length) { el.appendChild(node('p', 'No recorded data in this period.', 'wpbb-chart-empty')); return; }
    var max = Math.max.apply(null, [1].concat(rows.map(function (r) { return number(r[metricKey]); })));
    var list = node('div', undefined, 'wpbb-chart-bars'); rows.slice(0, 10).forEach(function (r) {
      var item = node('div', undefined, 'wpbb-chart-bar'); var label = node('span', (r[labelKey] || 'Unknown') + ' - ' + number(r[metricKey]).toLocaleString()); var bar = node('i'); bar.style.width = Math.max(0, number(r[metricKey]) / max * 100) + '%'; bar.setAttribute('aria-hidden', 'true'); item.appendChild(label); item.appendChild(bar); list.appendChild(item);
    }); el.appendChild(list);
  }
  window.WPBBCharts = { line: line, bars: bars, node: node };
  function init() {
    var el = document.getElementById('wpbb-analytics-data'); if (!el) return;
    var data; try { data = JSON.parse(el.textContent || '{}'); } catch (e) { return; }
    var traffic = document.getElementById('wpbb-chart-traffic'); if (traffic) line(traffic, data.daily, [{ key: 'views', label: 'Page views' }, { key: 'visitors', label: 'Visitors' }]);
    [['countries', 'country'], ['devices', 'device'], ['referrers', 'source']].forEach(function (pair) { var target = document.getElementById('wpbb-chart-' + pair[0]); if (target) bars(target, data[pair[0]], pair[1], 'views'); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
}());
