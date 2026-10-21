const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');
const source=fs.readFileSync(path.join(__dirname,'../master/assets/master/js/master.js'),'utf8');
const chunk=source.slice(0,source.indexOf('    function initConfirmSave()'))+'window.__modalTest={openModal,closeModal,initModals};}());';
const nodes=[],events={},attrs={};let checks=0;
const document={activeElement:null,body:{appendChild(n){const i=nodes.indexOf(n);if(i>=0)nodes.splice(i,1);nodes.push(n);n.parentNode=this;return n;}},documentElement:{getAttribute:k=>attrs[k],setAttribute:(k,v)=>attrs[k]=v},getElementById(id){for(const n of nodes)if(n.heading.id===id)return n.heading;return null;},querySelector(){return null;},querySelectorAll(s){return s==='.modal.is-open'?nodes.filter(n=>n.classList.contains('is-open')):s==='.modal'?nodes:[];},addEventListener(k,fn){events[k]=fn;}};
function focusable(name){return{name,isConnected:true,disabled:false,focus(){document.activeElement=this;}};}
function modal(name){const properties={},classes=new Set(),heading={id:'',textContent:name};const dialog={setAttribute:(k,v)=>properties[k]=v,getAttribute:k=>properties[k],hasAttribute:k=>k in properties,querySelector:s=>s==='h2'?heading:null};const input=focusable(name+' input');return{name,style:{removeProperty(k){if(k==='z-index')delete this.zIndex;}},heading,input,properties,classList:{add:c=>classes.add(c),remove:c=>classes.delete(c),contains:c=>classes.has(c)},setAttribute(k,v){properties[k]=v;},getAttribute:k=>properties[k],querySelector:s=>s==='.modal-card'?dialog:s==='input, select, textarea, button'?input:null};}
const context={document,window:{getComputedStyle:n=>({zIndex:n.style.zIndex||'40'})},console,requestAnimationFrame:fn=>fn()};context.window.requestAnimationFrame=context.requestAnimationFrame;
vm.runInNewContext(chunk,context);const api=context.window.__modalTest;
function ck(name,ok){checks++;assert.ok(ok,name);}
const confirm=modal('confirm'),edit=modal('edit'),opener=focusable('Edit button');document.body.appendChild(confirm);document.body.appendChild(edit);
api.initModals();api.openModal(edit,opener);const save=focusable('Save form');document.activeElement=save;api.openModal(confirm,save);
ck('Confirm originally before lazy edit is now topmost',nodes[nodes.length-1]===confirm||Number(confirm.style.zIndex)>Number(edit.style.zIndex));
ck('Confirm has dialog semantics',confirm.properties.role==='dialog'&&confirm.properties['aria-modal']==='true'&&!!confirm.properties['aria-labelledby']);
ck('Top confirm gets focus',document.activeElement===confirm.input);
api.closeModal(confirm);ck('Closing confirm keeps edit open',edit.classList.contains('is-open')&&!confirm.classList.contains('is-open'));ck('Closing confirm restores form Save focus',document.activeElement===save);
api.openModal(confirm,save);events.keydown({key:'Escape',preventDefault(){}});ck('Escape closes top confirm only',!confirm.classList.contains('is-open')&&edit.classList.contains('is-open'));ck('Escape from confirm returns form Save focus',document.activeElement===save);
events.keydown({key:'Escape',preventDefault(){}});ck('Second Escape closes edit and returns original opener',!edit.classList.contains('is-open')&&document.activeElement===opener);
const result={passed:checks,failed:0,scope:'isolated DOM/state regression; does not prove browser pixels or hit testing'};fs.writeFileSync(path.join(__dirname,'../.e2e/master-pagination-20261021/ciko-modal-stack-results.json'),JSON.stringify(result,null,2));process.stdout.write(JSON.stringify(result));
