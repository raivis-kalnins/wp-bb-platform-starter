(function () {
  'use strict';

  var data = window.WPBBWooMarketing || {};
  var resizeTimer = null;

  function toTable(rows) {
    return google.visualization.arrayToDataTable(rows);
  }

  function moneyFormat() {
    return (data.currencySymbol || '') + '#,##0';
  }

  function emptyChart(element, message) {
    if (!element) return;
    element.innerHTML = '<div class="wpbb-mi-chart-empty">' + message + '</div>';
  }

  function drawRevenue() {
    var element = document.getElementById('wpbb-mi-revenue-chart');
    if (!element || !data.revenueTrend || data.revenueTrend.length < 2) return;

    new google.visualization.AreaChart(element).draw(toTable(data.revenueTrend), {
      legend: 'none',
      backgroundColor: 'transparent',
      chartArea: { left: 68, top: 25, width: '83%', height: '72%' },
      vAxis: { format: moneyFormat(), minValue: 0 },
      pointSize: 2,
      lineWidth: 2,
      focusTarget: 'category'
    });
  }

  function drawOrders() {
    var element = document.getElementById('wpbb-mi-orders-chart');
    if (!element || !data.ordersTrend || data.ordersTrend.length < 2) return;

    new google.visualization.ComboChart(element).draw(toTable(data.ordersTrend), {
      backgroundColor: 'transparent',
      chartArea: { left: 55, right: 70, top: 35, width: '77%', height: '68%' },
      seriesType: 'bars',
      series: { 1: { type: 'line', targetAxisIndex: 1, lineWidth: 2, pointSize: 2 } },
      vAxes: { 0: { minValue: 0 }, 1: { format: moneyFormat(), minValue: 0 } },
      focusTarget: 'category'
    });
  }

  function drawSources() {
    var element = document.getElementById('wpbb-mi-source-chart');
    if (!element) return;

    if (data.sourceRevenue && data.sourceRevenue.length > 1) {
      new google.visualization.PieChart(element).draw(toTable(data.sourceRevenue), {
        backgroundColor: 'transparent',
        pieHole: 0.58,
        legend: { position: 'right' },
        chartArea: { left: 10, top: 20, width: '92%', height: '82%' },
        pieSliceText: 'percentage'
      });
    } else {
      emptyChart(element, 'No attributed revenue yet.');
    }
  }

  function drawProducts() {
    var element = document.getElementById('wpbb-mi-products-chart');
    if (!element) return;

    if (data.productRevenue && data.productRevenue.length > 1) {
      new google.visualization.BarChart(element).draw(toTable(data.productRevenue), {
        backgroundColor: 'transparent',
        legend: 'none',
        chartArea: { left: 175, top: 20, width: '64%', height: '82%' },
        hAxis: { format: moneyFormat(), minValue: 0 },
        focusTarget: 'category'
      });
    } else {
      emptyChart(element, 'No product revenue in this period.');
    }
  }

  function drawCustomerMix() {
    var element = document.getElementById('wpbb-mi-customer-chart');
    if (!element) return;

    if (data.customerMix && data.customerMix.length > 1) {
      var hasValues = data.customerMix.slice(1).some(function (row) { return Number(row[1]) > 0; });
      if (hasValues) {
        new google.visualization.PieChart(element).draw(toTable(data.customerMix), {
          backgroundColor: 'transparent',
          pieHole: 0.62,
          legend: { position: 'right' },
          chartArea: { left: 10, top: 20, width: '92%', height: '82%' },
          pieSliceText: 'percentage'
        });
        return;
      }
    }
    emptyChart(element, 'New vs returning classification will appear when WooCommerce Analytics has order data.');
  }

  function drawCampaignFunnel() {
    var element = document.getElementById('wpbb-mi-campaign-chart');
    if (!element) return;

    if (data.campaignFunnel && data.campaignFunnel.length > 1) {
      var hasValues = data.campaignFunnel.slice(1).some(function (row) { return Number(row[1]) > 0 || Number(row[2]) > 0; });
      if (hasValues) {
        new google.visualization.ColumnChart(element).draw(toTable(data.campaignFunnel), {
          backgroundColor: 'transparent',
          chartArea: { left: 55, top: 35, width: '80%', height: '66%' },
          legend: { position: 'top', alignment: 'end' },
          vAxis: { minValue: 0 },
          hAxis: { slantedText: true, slantedTextAngle: 25 },
          focusTarget: 'category'
        });
        return;
      }
    }
    emptyChart(element, 'No tracked campaign visits yet. Use the link builder to start measuring visits and conversion.');
  }

  function drawAll() {
    if (!window.google || !google.visualization) return;
    drawRevenue();
    drawOrders();
    drawSources();
    drawProducts();
    drawCustomerMix();
    drawCampaignFunnel();
  }

  function bootCharts() {
    if (!window.google || !google.charts) {
      document.querySelectorAll('.wpbb-mi-chart').forEach(function (element) {
        emptyChart(element, 'Google Charts could not load. KPI cards and tables remain available.');
      });
      return;
    }

    google.charts.load('current', { packages: ['corechart'] });
    google.charts.setOnLoadCallback(drawAll);
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(drawAll, 160);
    });
  }

  function buildTrackedUrl() {
    var urlInput = document.getElementById('wpbb-mi-url');
    var result = document.getElementById('wpbb-mi-result');
    if (!urlInput || !result) return;

    var source = document.getElementById('wpbb-mi-source');
    var medium = document.getElementById('wpbb-mi-medium');
    var campaign = document.getElementById('wpbb-mi-campaign');
    var content = document.getElementById('wpbb-mi-content');
    var term = document.getElementById('wpbb-mi-term');

    try {
      var tracked = new URL(urlInput.value || window.location.origin);
      if (source && source.value) tracked.searchParams.set('utm_source', source.value.trim());
      if (medium && medium.value) tracked.searchParams.set('utm_medium', medium.value.trim());
      if (campaign && campaign.value) tracked.searchParams.set('utm_campaign', campaign.value.trim());
      if (content && content.value) tracked.searchParams.set('utm_content', content.value.trim());
      if (term && term.value) tracked.searchParams.set('utm_term', term.value.trim());
      result.value = tracked.toString();
      var copyButton = document.querySelector('.wpbb-mi-copy');
      if (copyButton) copyButton.setAttribute('data-copy', result.value);
    } catch (error) {
      window.alert('Please enter a valid destination URL.');
    }
  }

  function copyValue(button) {
    var result = document.getElementById('wpbb-mi-result');
    var value = button.getAttribute('data-copy') || (result ? result.value : '');
    if (!value) return;

    function showCopied() {
      var old = button.textContent;
      button.textContent = 'Copied';
      setTimeout(function () { button.textContent = old; }, 1000);
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value).then(showCopied);
      return;
    }

    var temporary = document.createElement('textarea');
    temporary.value = value;
    document.body.appendChild(temporary);
    temporary.select();
    document.execCommand('copy');
    temporary.remove();
    showCopied();
  }

  function bootActions() {
    var buildButton = document.getElementById('wpbb-mi-build');
    if (buildButton) buildButton.addEventListener('click', buildTrackedUrl);

    document.addEventListener('click', function (event) {
      var copyButton = event.target.closest('.wpbb-mi-copy');
      if (copyButton) copyValue(copyButton);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bootCharts();
    bootActions();
  });
}());
