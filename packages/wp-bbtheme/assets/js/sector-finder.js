(function(){
  'use strict';
  function closestFinder(form){return form.closest('[data-wpbb-sector-finder]');}
  async function submitFinder(form){
    var root=closestFinder(form); if(!root) return;
    var target=root.querySelector('[data-sector-finder-results]'); if(!target) return;
    var ajaxUrl=root.getAttribute('data-ajax-url'); if(!ajaxUrl) return;
    target.classList.add('is-loading'); root.setAttribute('aria-busy','true');
    try{
      var body=new FormData(form);
      var response=await fetch(ajaxUrl,{method:'POST',credentials:'same-origin',body:body,headers:{'X-Requested-With':'XMLHttpRequest'}});
      var data=await response.json();
      if(!data || !data.success || !data.data || typeof data.data.html!=='string') throw new Error('Invalid finder response');
      target.innerHTML=data.data.html;
    }catch(e){
      form.submit();
    }finally{
      target.classList.remove('is-loading'); root.removeAttribute('aria-busy');
    }
  }
  document.addEventListener('submit',function(event){
    var form=event.target.closest('[data-sector-finder-form]');
    if(!form) return;
    if(!window.fetch || !closestFinder(form)) return;
    event.preventDefault(); submitFinder(form);
  });
  document.addEventListener('click',function(event){
    var button=event.target.closest('[data-sector-finder-reset]'); if(!button) return;
    var form=button.closest('form'); if(!form) return;
    window.setTimeout(function(){submitFinder(form);},0);
  });
})();
