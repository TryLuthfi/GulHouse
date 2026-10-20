// Meaningful adapter regressions: stale responses, durable sessions, private files, OCR timeout.
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),assert=require('node:assert/strict');
const ts=require('../mobile/node_modules/typescript');
const source=ts.transpileModule(fs.readFileSync(path.join(__dirname,'../mobile/src/api/client.ts'),'utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2022}}).outputText;
const store=new Map(),results=[],timers=[],downloads=[],deletes=[],shared=[];
let downloadMime='application/pdf';
let responder=async()=>new Response(JSON.stringify({ok:true,data:{}}),{status:200});
const secure={WHEN_UNLOCKED_THIS_DEVICE_ONLY:'device-only',getItemAsync:async key=>store.get(key)||null,setItemAsync:async(key,value)=>{await new Promise(r=>setTimeout(r,2));store.set(key,value)},deleteItemAsync:async key=>{await new Promise(r=>setTimeout(r,2));store.delete(key)}};
const files={cacheDirectory:'file:///private/cache/',downloadAsync:async(url,file,options)=>{downloads.push({url,file,options});return{status:200,uri:file,headers:{'Content-Type':downloadMime}}},deleteAsync:async file=>deletes.push(file),moveAsync:async()=>{}};
const mocks={'react-native':{Platform:{OS:'android'}},'expo-secure-store':secure,'expo-file-system/legacy':files,'expo-file-system':{File:class{constructor(uri){this.uri=uri}bytes(){return Promise.resolve(new Uint8Array([80,78,71]))}}},'expo-sharing':{isAvailableAsync:async()=>true,shareAsync:async(file,options)=>shared.push({file,options})}};
class NativeFormData{constructor(){this.parts=[]}append(key,value){this.parts.push([key,value])}}
const moduleObject={exports:{}};
vm.runInNewContext(source,{module:moduleObject,exports:moduleObject.exports,require:name=>{if(!mocks[name])throw Error('Unexpected dependency '+name);return mocks[name]},process:{env:{}},URL,URLSearchParams,AbortController,FormData:NativeFormData,Blob,fetch:(...args)=>responder(...args),setTimeout:(fn,ms)=>{timers.push(ms);return setTimeout(fn,ms)},clearTimeout,console});
const api=moduleObject.exports;
const calendarModule={exports:{}};
const calendarSource=ts.transpileModule(fs.readFileSync(path.join(__dirname,'../mobile/src/theme/calendar.ts'),'utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2022}}).outputText;
vm.runInNewContext(calendarSource,{module:calendarModule,exports:calendarModule.exports,Intl,Date});
function check(name,value){assert.ok(value,name);results.push({name,pass:true});console.log('PASS '+name);}
const unauthorized=()=>new Response(JSON.stringify({ok:false,error:{code:'UNAUTHENTICATED',message:'Sesi berakhir'}}),{status:401});
(async()=>{
  for(const [instant,expected] of [['2026-10-18T16:59:59Z','2026-10-18'],['2026-10-18T17:00:00Z','2026-10-19'],['2026-10-18T23:00:00Z','2026-10-19'],['2026-12-31T17:00:00Z','2027-01-01']])check('WIB calendar boundary '+instant,calendarModule.exports.calendarDate(new Date(instant))===expected);
  api.setApiBase('https://test.invalid/master/index.php/api/mobile');
  await api.persistSession('old-synthetic-token');
  let expired=0;api.onSessionExpired(()=>expired++);
  let deliver;responder=()=>new Promise(resolve=>deliver=resolve);
  const oldRequest=api.apiGet('me').catch(error=>error);
  await api.persistSession('fresh-synthetic-token');deliver(unauthorized());await oldRequest;
  check('Old 401 cannot log out a newer login',api.getAuthToken()==='fresh-synthetic-token'&&expired===0);
  check('Old response cannot erase durable new token',store.get('gulhouse.mobile.token')==='fresh-synthetic-token');
  responder=async()=>unauthorized();await api.apiGet('me').catch(()=>{});
  check('Current 401 clears session and notifies UI',api.getAuthToken()===null&&expired===1&&!store.has('gulhouse.mobile.token'));
  await api.persistSession('admin-synthetic-token');await api.apiPost('auth/payment',{phone:'synthetic',nik_last4:'0000'}).catch(()=>{});
  check('Bad public login preserves existing authorized session',api.getAuthToken()==='admin-synthetic-token'&&expired===1);
  await Promise.all([api.persistSession(null),api.persistSession('last-synthetic-token')]);
  check('Concurrent storage writes retain latest login',store.get('gulhouse.mobile.token')==='last-synthetic-token');
  responder=async()=>new Response(JSON.stringify({ok:true,data:{ok:true}}),{status:200});
  await api.apiPost('admin/lifecycle/1/action',{action:'inspection_extract'});
  check('OCR action receives extended request timeout',timers.at(-1)===120000);
  await api.openPrivateFile('template','house_rules','aturan.pdf');
  check('String template identifier preserved',downloads[0].url.endsWith('/files/template/house_rules'));
  check('Private token goes in header, never URL',downloads[0].options.headers.Authorization==='Bearer last-synthetic-token'&&!downloads[0].url.includes('synthetic-token'));
  check('Shared private file cache is removed',deletes.includes(downloads[0].file));
  downloadMime='image/jpeg';await api.openPrivateFile('document',3);
  check('Image attachment receives matching extension and MIME',shared.at(-1).file.endsWith('.jpg')&&shared.at(-1).options.mimeType==='image/jpeg');
  let upload;responder=async(url,options)=>{upload=options;return new Response(JSON.stringify({ok:true,data:{}}),{status:200})};
  await api.apiUpload('resident/intake',{consent:1,items:[{id:1}]},{ktp_file:{uri:'file:///private/cache/qa.png',name:'qa.png',mimeType:'image/png'}});
  const part=upload.body.parts.find(([key])=>key==='ktp_file')[1];
  check('Native upload uses byte-backed file with original name/MIME',typeof part.bytes==='function'&&part.name==='qa.png'&&part.type==='image/png'&&!('uri'in part)&&Buffer.from(await part.bytes()).toString()==='PNG');
  check('Multipart nested JSON payload retained',JSON.parse(upload.body.parts.find(([key])=>key==='payload')[1]).items[0].id===1);
  let invalid=false;try{api.setApiBase('https://name:password@test.invalid/api/mobile')}catch{invalid=true}
  check('Credential-bearing server URL rejected',invalid);
  const out=path.join(__dirname,'../.e2e/mobile/native-transport-results.json');fs.writeFileSync(out,JSON.stringify(results,null,2));
})().catch(error=>{console.error(error.message);process.exitCode=1});
