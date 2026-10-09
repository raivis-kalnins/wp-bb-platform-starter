(function () {
  'use strict';
  var el = document.getElementById('wpbb-analytics-data');
  if (!el) return;
  var payload = {};
  try { payload = JSON.parse(el.textContent || '{}'); } catch (e) { return; }

  function draw() {
    if (!window.google || !google.visualization) return;
    var trafficEl = document.getElementById('wpbb-chart-traffic');
    if (trafficEl) {
      var traffic = [['Date', 'Views', 'Visitors']];
      (payload.daily || []).forEach(function (r) { traffic.push([r.day, Number(r.views || 0), Number(r.visitors || 0)]); });
      if (traffic.length === 1) traffic.push(['No data', 0, 0]);
      new google.visualization.AreaChart(trafficEl).draw(google.visualization.arrayToDataTable(traffic), { backgroundColor:'transparent', legend:{position:'top',alignment:'end'}, chartArea:{left:55,top:42,width:'82%',height:'68%'}, hAxis:{textStyle:{fontSize:10},showTextEvery:Math.max(1,Math.ceil((traffic.length-1)/8))}, vAxis:{minValue:0}, pointSize:2, lineWidth:2 });
    }
    var countryEl = document.getElementById('wpbb-chart-countries');
    if (countryEl) {
      var countries = [['Country','Views']];
      (payload.countries || []).forEach(function(r){ countries.push([r.country || 'Unknown', Number(r.views || 0)]); });
      if (countries.length === 1) countries.push(['Unknown',0]);
      new google.visualization.BarChart(countryEl).draw(google.visualization.arrayToDataTable(countries), { backgroundColor:'transparent',legend:{position:'none'},chartArea:{left:85,top:20,width:'72%',height:'78%'},hAxis:{minValue:0} });
    }
    var deviceEl = document.getElementById('wpbb-chart-devices');
    if (deviceEl) {
      var devices = [['Device','Views']];
      (payload.devices || []).forEach(function(r){ devices.push([r.device || 'unknown', Number(r.views || 0)]); });
      if (devices.length === 1) devices.push(['No data',1]);
      new google.visualization.PieChart(deviceEl).draw(google.visualization.arrayToDataTable(devices), { backgroundColor:'transparent',pieHole:.62,legend:{position:'right'},chartArea:{left:10,top:10,width:'90%',height:'88%'} });
    }
    var refEl = document.getElementById('wpbb-chart-referrers');
    if (refEl) {
      var refs = [['Source','Views']];
      (payload.referrers || []).slice(0,10).forEach(function(r){ refs.push([r.source || 'Direct', Number(r.views || 0)]); });
      if (refs.length === 1) refs.push(['Direct',0]);
      new google.visualization.ColumnChart(refEl).draw(google.visualization.arrayToDataTable(refs), { backgroundColor:'transparent',legend:{position:'none'},chartArea:{left:48,top:18,width:'84%',height:'70%'},hAxis:{slantedText:true,slantedTextAngle:28,textStyle:{fontSize:10}},vAxis:{minValue:0} });
    }
  }

  if (window.google && google.charts) {
    google.charts.load('current', { packages:['corechart'] });
    google.charts.setOnLoadCallback(draw);
  }
  var timer;
  window.addEventListener('resize', function(){ clearTimeout(timer); timer=setTimeout(draw,180); });
}());
