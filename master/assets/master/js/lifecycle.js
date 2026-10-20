(function(){'use strict';document.querySelectorAll('form[data-lc-form]').forEach(function(form){form.addEventListener('submit',function(event){if(form.getAttribute('aria-busy')==='true'){event.preventDefault();return;}if(!form.checkValidity()){return;}var max=10*1024*1024;var bad=false;form.querySelectorAll('input[type=file]').forEach(function(input){if(input.files&&input.files[0]&&input.files[0].size>max){input.setCustomValidity('Ukuran file maksimal 10 MB.');input.reportValidity();bad=true;}else{input.setCustomValidity('');}});if(bad){event.preventDefault();return;}if(form.dataset.confirm&&!window.confirm(form.dataset.confirm)){event.preventDefault();return;}var buttons=form.querySelectorAll('button[type=submit]');buttons.forEach(function(button){button.disabled=true;button.dataset.originalText=button.textContent;button.textContent='Menyimpan…';});form.setAttribute('aria-busy','true');});form.querySelectorAll('input[type=file]').forEach(function(input){input.addEventListener('change',function(){input.setCustomValidity('');});});});document.querySelectorAll('[data-preview-file]').forEach(function(link){link.addEventListener('click',function(event){var target=document.getElementById(link.dataset.previewFile);if(target){event.preventDefault();target.src=link.href;target.hidden=false;target.focus();}});});window.addEventListener('pageshow',function(){document.querySelectorAll('button[data-original-text]').forEach(function(button){button.disabled=false;button.textContent=button.dataset.originalText;});document.querySelectorAll('form[aria-busy]').forEach(function(form){form.removeAttribute('aria-busy');});});})();

(function(){var toggle=document.querySelector('[data-lc-menu]');if(!toggle)return;document.body.dataset.lcNavReady='true';toggle.addEventListener('click',function(){var expanded=toggle.getAttribute('aria-expanded')==='true';toggle.setAttribute('aria-expanded',String(!expanded));document.body.dataset.lcNavigation=expanded?'closed':'open';toggle.textContent=expanded?'Menu pengelola':'Tutup menu pengelola';});})();

(function(){
    'use strict';
    document.querySelectorAll('[data-room-change]').forEach(function(form){
        var select=form.querySelector('[data-room-change-select]');
        var preview=form.querySelector('[data-room-change-preview]');
        var submit=form.querySelector('[data-room-change-submit]');
        var status=form.querySelector('[data-room-change-status]');
        var keys=['rent_delta','deposit_delta','rent_outstanding','rent_credit','deposit_outstanding','deposit_credit'];
        function update(){
            var option=select.options[select.selectedIndex];
            preview.hidden=true;submit.disabled=true;
            if(!option||!option.value||option.disabled){status.textContent='Pilih kamar untuk melihat tarif master dan selisih pembayaran.';return;}
            var financial;
            try{financial=JSON.parse(option.dataset.financial||'{}');}catch(e){financial={};}
            var valid=keys.every(function(key){return Object.prototype.hasOwnProperty.call(financial,key)&&Number.isFinite(Number(financial[key]));});
            if(!valid){status.textContent='Rincian selisih belum tersedia. Muat ulang halaman sebelum mengubah kamar.';return;}
            var values=Object.assign({},financial,{monthly_price:Number(option.dataset.monthlyPrice),deposit:Number(option.dataset.deposit)});
            form.querySelectorAll('[data-change-value]').forEach(function(node){node.textContent='Rp '+Number(values[node.dataset.changeValue]).toLocaleString('id-ID');});
            preview.hidden=false;submit.disabled=false;
            status.textContent='Tarif dan selisih untuk '+option.textContent.trim()+'. Tinjau sebelum menyimpan.';
        }
        select.addEventListener('change',update);update();
    });
})();
