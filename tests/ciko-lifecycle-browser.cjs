const fs=require('fs'),path=require('path'),assert=require('assert');
const {chromium}=require('C:/Users/TKM/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright-core');
const root=path.resolve(__dirname,'..'),runtime=JSON.parse(fs.readFileSync(path.join(root,'.e2e/lifecycle/runtime.json')));
const out=path.join(root,'.e2e/lifecycle/ciko');fs.mkdirSync(out,{recursive:true});
const result={checks:[],errors:[]};
async function check(name,fn){try{await fn();result.checks.push({name,status:'PASS'});}catch(e){result.checks.push({name,status:'FAIL',message:e.message});}}
(async()=>{const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});const context=await browser.newContext();const page=await context.newPage();page.on('pageerror',e=>result.errors.push(e.message));
const base=runtime.url+'/landing/index.php';await page.goto(base+'/resident/start');
await check('Initial KTP mandatory; marriage optional',async()=>{assert(await page.locator('[name=ktp_file]').getAttribute('required')!==null);assert(await page.locator('[name=marriage_file]').getAttribute('required')===null);});
for(const width of [1440,768,390,320])await check('Resident start no horizontal overflow '+width,async()=>{await page.setViewportSize({width,height:900});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));await page.screenshot({path:path.join(out,'start-'+width+'.png'),fullPage:true});});
const stamp=Date.now();await page.locator('[name=full_name]').fill('CIKO Synthetic '+stamp);await page.locator('[name=phone]').fill('08977'+String(stamp).slice(-7));const option=await page.locator('[name=room_id] option').nth(1).getAttribute('value');await page.locator('[name=room_id]').selectOption(option);await page.locator('[name=planned_start]').fill('2026-10-19');await page.locator('[name=consent]').check();
await check('Browser blocks missing KTP',async()=>{await page.getByRole('button',{name:'Kirim identitas awal'}).click();assert(page.url().includes('/resident/start'));assert(await page.locator('[name=ktp_file]').evaluate(e=>!e.validity.valid));});
await page.locator('[name=ktp_file]').setInputFiles({name:'CIKO-synthetic-KTP.pdf',mimeType:'application/pdf',buffer:fs.readFileSync(path.join(root,'tests/fixtures/payment-proof-valid.pdf'))});await page.getByRole('button',{name:'Kirim identitas awal'}).click();await page.waitForLoadState('networkidle');
await check('Synthetic intake reaches resident portal',async()=>assert(page.url().includes('/resident/portal')));
const code=await page.locator('.lc-code').textContent().catch(()=>null);
await check('One-time access code displayed',async()=>assert(code&&/^[a-f0-9]{64}$/.test(code.trim())));
await check('Full biodata cannot be submitted before verified payment',async()=>assert(await page.locator('form:has(input[name=action][value=full_profile])').count()===0));
await page.screenshot({path:path.join(out,'portal-before-payment.png'),fullPage:true});
await check('Portal has no horizontal overflow at 320px',async()=>assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1)));
const fileLink=page.locator('a[href*="resident/file/"]').first();const href=await fileLink.getAttribute('href').catch(()=>null);if(href){await check('Own KTP authenticated download succeeds',async()=>assert((await context.request.get(href)).status()===200));const anon=await browser.newContext();await check('KTP download unauthenticated rejected or redirected to access',async()=>{const response=await anon.request.get(href);assert(response.status()===404||response.status()===403||response.url().includes('resident/access'));});await anon.close();}
fs.writeFileSync(path.join(out,'state-private.json'),JSON.stringify({code,phone:'08977'+String(stamp).slice(-7),name:'CIKO Synthetic '+stamp,room:option}));fs.writeFileSync(path.join(out,'browser-results.json'),JSON.stringify(result,null,2));await browser.close();console.log(JSON.stringify(result,null,2));if(result.checks.some(x=>x.status==='FAIL'))process.exitCode=1;
})().catch(e=>{console.error(e.message);process.exitCode=1});
