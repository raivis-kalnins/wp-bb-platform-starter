(function(){
  'use strict';
  var timer;
  function draw(){
    if(!window.google||!google.visualization||!window.WPBBCRM)return;
    var c=WPBBCRM.currency||'';
    var common={backgroundColor:'transparent',legend:{position:'top',alignment:'end'},chartArea:{left:58,right:72,top:45,width:'75%',height:'68%'}};
    var el=document.getElementById('wpbb-crm-segments');
    if(el&&WPBBCRM.segments&&WPBBCRM.segments.length>1){var d=google.visualization.arrayToDataTable(WPBBCRM.segments);new google.visualization.ComboChart(el).draw(d,Object.assign({},common,{seriesType:'bars',series:{1:{type:'line',targetAxisIndex:1,lineWidth:2,pointSize:3}},vAxes:{0:{minValue:0},1:{format:c+'#,##0'}}}));}
    el=document.getElementById('wpbb-crm-monthly');
    if(el&&WPBBCRM.monthly&&WPBBCRM.monthly.length>1){var m=google.visualization.arrayToDataTable(WPBBCRM.monthly);new google.visualization.ComboChart(el).draw(m,Object.assign({},common,{seriesType:'bars',series:{2:{type:'line',targetAxisIndex:1,lineWidth:2,pointSize:3}},hAxis:{slantedText:true,slantedTextAngle:28},vAxes:{0:{minValue:0},1:{format:c+'#,##0'}}}));}
    el=document.getElementById('wpbb-crm-products');
    if(el&&WPBBCRM.products&&WPBBCRM.products.length>1){var p=google.visualization.arrayToDataTable(WPBBCRM.products);new google.visualization.BarChart(el).draw(p,{backgroundColor:'transparent',legend:{position:'none'},chartArea:{left:185,right:35,top:20,width:'66%',height:'78%'},hAxis:{format:c+'#,##0'}});}
    el=document.getElementById('wpbb-crm-cohorts');
    if(el&&WPBBCRM.cohorts&&WPBBCRM.cohorts.length>1){var h=google.visualization.arrayToDataTable(WPBBCRM.cohorts);new google.visualization.ComboChart(el).draw(h,Object.assign({},common,{seriesType:'bars',series:{1:{type:'line',targetAxisIndex:1,lineWidth:2,pointSize:3}},hAxis:{slantedText:true,slantedTextAngle:28},vAxes:{0:{minValue:0},1:{minValue:0,maxValue:100,format:"#'%'"}}}));}
  }
  document.addEventListener('DOMContentLoaded',function(){if(window.google&&google.charts){google.charts.load('current',{packages:['corechart']});google.charts.setOnLoadCallback(draw);window.addEventListener('resize',function(){clearTimeout(timer);timer=setTimeout(draw,180);});}});
}());
