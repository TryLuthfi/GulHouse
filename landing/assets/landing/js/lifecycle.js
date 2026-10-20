(function(){'use strict';document.querySelectorAll('form[data-lc-form]').forEach(function(form){form.addEventListener('submit',function(event){if(form.getAttribute('aria-busy')==='true'){event.preventDefault();return;}if(!form.checkValidity()){return;}var max=10*1024*1024;var bad=false;form.querySelectorAll('input[type=file]').forEach(function(input){if(input.files&&input.files[0]&&input.files[0].size>max){input.setCustomValidity('Ukuran file maksimal 10 MB.');input.reportValidity();bad=true;}else{input.setCustomValidity('');}});if(bad){event.preventDefault();return;}if(form.dataset.confirm&&!window.confirm(form.dataset.confirm)){event.preventDefault();return;}var buttons=form.querySelectorAll('button[type=submit]');buttons.forEach(function(button){button.disabled=true;button.dataset.originalText=button.textContent;button.textContent='Menyimpan…';});form.setAttribute('aria-busy','true');});form.querySelectorAll('input[type=file]').forEach(function(input){input.addEventListener('change',function(){input.setCustomValidity('');});});});document.querySelectorAll('[data-preview-file]').forEach(function(link){link.addEventListener('click',function(event){var target=document.getElementById(link.dataset.previewFile);if(target){event.preventDefault();target.src=link.href;target.hidden=false;target.focus();}});});window.addEventListener('pageshow',function(){document.querySelectorAll('button[data-original-text]').forEach(function(button){button.disabled=false;button.textContent=button.dataset.originalText;});document.querySelectorAll('form[aria-busy]').forEach(function(form){form.removeAttribute('aria-busy');});});})();

(function(){var toggle=document.querySelector('[data-lc-menu]');if(!toggle)return;document.body.dataset.lcNavReady='true';toggle.addEventListener('click',function(){var expanded=toggle.getAttribute('aria-expanded')==='true';toggle.setAttribute('aria-expanded',String(!expanded));document.body.dataset.lcNavigation=expanded?'closed':'open';toggle.textContent=expanded?'Menu pengelola':'Tutup menu pengelola';});})();

(function(){
    'use strict';
    document.querySelectorAll('[data-lc-upload]').forEach(function(box){
        var input=box.querySelector('input[type=file]');
        var name=box.querySelector('[data-lc-file-name]');
        var preview=box.querySelector('[data-lc-file-preview]');
        var clear=box.querySelector('[data-lc-file-clear]');
        var objectUrl=null;
        function render(){
            if(objectUrl){URL.revokeObjectURL(objectUrl);objectUrl=null;}
            var file=input.files&&input.files[0];
            name.textContent=file?file.name+' · '+(file.size/1024/1024).toFixed(2)+' MB':'Belum ada berkas dipilih.';
            clear.hidden=!file;
            preview.hidden=true;
            preview.removeAttribute('src');
            if(file&&/^image\/(jpeg|png)$/.test(file.type)&&file.size<=10*1024*1024){objectUrl=URL.createObjectURL(file);preview.src=objectUrl;preview.hidden=false;}
        }
        input.addEventListener('change',render);
        clear.addEventListener('click',function(){input.value='';input.setCustomValidity('');render();input.focus();});
        window.addEventListener('pagehide',function(){if(objectUrl){URL.revokeObjectURL(objectUrl);objectUrl=null;}});
        render();
    });
})();
