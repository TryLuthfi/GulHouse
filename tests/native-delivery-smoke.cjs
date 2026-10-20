const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const{chromium}=require('playwright-core');
const root=path.resolve(__dirname,'..'),runtime=JSON.parse(fs.readFileSync(path.join(root,'.e2e/mobile/root/runtime.json'),'utf8').replace(/^\uFEFF/,''));
const results=[];function check(name,value){assert.ok(value,name);results.push({name,pass:true});console.log('PASS '+name);}
(async()=>{
  const api=runtime.url+'/master/index.php/api/mobile';
  const response=await fetch(api+'/catalog/rooms',{headers:{Origin:'http://127.0.0.1:50115'}});
  check('Native API namespace remains accessible',response.status===200);
  check('Preview CORS has exact loopback origin',response.headers.get('access-control-allow-origin')==='http://127.0.0.1:50115');
  check('API response is valid catalog JSON',(await response.json()).data.rooms.length>0);
  for(const privatePath of ['/mobile/src/api/client.ts','/mobile/android/app/debug.keystore','/master/application/models/Mobile_service.php','/.deploy/android-signing/release.local.json','/.tools/npm/package/package.json'])check('Private source remains blocked '+privatePath,(await fetch(runtime.url+privatePath)).status===403);
  const preflight=await fetch(api+'/auth/admin',{method:'OPTIONS',headers:{Origin:'http://127.0.0.1:50115','Access-Control-Request-Headers':'authorization,content-type','Access-Control-Request-Method':'POST'}});
  check('Authorized preview preflight succeeds',preflight.status===204);
  const denied=await fetch(api+'/auth/admin',{method:'OPTIONS',headers:{Origin:'https://untrusted.invalid'}});
  check('Unconfigured origin denied',denied.status===403&&!denied.headers.has('access-control-allow-origin'));
  const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
  try{
    for(const width of [320,390,768,1440]){
      const page=await browser.newPage({viewport:{width,height:844}}),errors=[];
      page.on('pageerror',e=>errors.push(e.message));
      await page.goto('http://127.0.0.1:50115');
      await page.getByRole('button',{name:'Detail APT - The Nest',exact:true}).waitFor({timeout:30000});
      check('Native public catalog loads at '+width,await page.getByRole('tab',{name:'Penghuni',exact:true}).isVisible());
      check('No page overflow at '+width,!await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth));
      await page.screenshot({path:path.join(root,`.e2e/mobile/catalog-${width}.png`),fullPage:false});
      await page.getByRole('button',{name:'Detail APT - The Nest',exact:true}).click();
      await page.getByRole('button',{name:'Jadwalkan survey',exact:true}).waitFor({timeout:30000});
      check('Native room detail and booking action at '+width,true);
      check('No uncaught React errors at '+width,errors.length===0);
      await page.close();
    }
  }finally{await browser.close();fs.writeFileSync(path.join(root,'.e2e/mobile/native-delivery-smoke.json'),JSON.stringify(results,null,2));}
})().catch(error=>{console.error(error.message);process.exitCode=1});
