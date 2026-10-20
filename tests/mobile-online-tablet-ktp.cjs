/* Independent read-only API corroboration of root's actual Android intake. No ADB/business writes. */
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),crypto=require('node:crypto');
const root=path.resolve(__dirname,'..'),dir=path.join(root,'.e2e/mobile/online'),rt=JSON.parse(fs.readFileSync(path.join(dir,'runtime-private.json'),'utf8').replace(/^\uFEFF/,''));
assert(rt.synthetic_isolation_approved&&rt.synthetic_storage_isolated&&rt.synthetic_schema_verified&&rt.synthetic_contains_real_occupants===false&&rt.synthetic_base!==rt.production_base);
assert(rt.tablet_full_name&&rt.tablet_access_code&&Number(rt.synthetic_room_id_tablet)>0&&rt.synthetic_room_id_tablet!==rt.synthetic_room_id_ciko);
const checks=[];let admin,resident;
function check(name,fn){fn();checks.push({name,status:'PASS'});}
async function request(ep,token,payload){assert(!payload||['auth/admin','auth/resident','auth/logout'].includes(ep),'Only auth POST allowed');const r=await fetch(rt.synthetic_base+'/'+ep,{method:payload?'POST':'GET',headers:{Accept:'application/json',...(token?{Authorization:'Bearer '+token}:{}),...(payload?{'Content-Type':'application/json'}:{})},body:payload?JSON.stringify(payload):undefined,signal:AbortSignal.timeout(30000)});const j=await r.json();assert.equal(r.status,200,ep+' status200');assert(j.ok,ep+' APIok');return j.data;}
(async()=>{
 admin=(await request('auth/admin',null,{username:rt.synthetic_username,password:rt.synthetic_password})).token;
 const listing=await request('admin/lifecycle?q='+encodeURIComponent(rt.tablet_full_name)+'&limit=100',admin);
 const rows=listing.rows||listing.cases||listing.items;assert(Array.isArray(rows),'Expected lifecycle listing');
 const matches=rows.filter(r=>r.full_name===rt.tablet_full_name);assert.equal(matches.length,1,'Exactly dedicated native fixture');
 const detail=await request('admin/lifecycle/'+matches[0].id,admin);
 check('Native-created case uses dedicated tablet room distinct from CIKO',()=>{assert.equal(detail.case.full_name,rt.tablet_full_name);assert.equal(Number(detail.case.room_id),Number(rt.synthetic_room_id_tablet));assert.notEqual(Number(detail.case.room_id),Number(rt.synthetic_room_id_ciko));});
 const ktp=detail.files.find(f=>Number(f.id)===Number(detail.case.ktp_file_id));
 check('Mandatory latest KTP persisted as actual image/png private document',()=>{assert(ktp);assert.equal(ktp.mime,'image/png');assert(Number(ktp.id)>0);});
 resident=(await request('auth/resident',null,{access_code:rt.tablet_access_code})).token;
 const portal=await request('resident/portal',resident);
 check('Returned one-time portal identity authenticates same native-created case',()=>assert.equal(Number(portal.case.id),Number(detail.case.id)));
 const response=await fetch(rt.synthetic_base+'/files/lifecycle/'+ktp.id,{headers:{Authorization:'Bearer '+resident},redirect:'manual',signal:AbortSignal.timeout(30000)});
 assert.equal(response.status,200);assert(response.headers.get('content-type').includes('image/png'));
 const uploaded=Buffer.from(await response.arrayBuffer()),original=fs.readFileSync(path.join(dir,'qa-ktp.png'));
 const sha=b=>crypto.createHash('sha256').update(b).digest('hex');
 check('Downloaded private KTP binary exactly matches Android-picked PNG SHA256 and size',()=>{assert.equal(uploaded.length,original.length);assert.equal(sha(uploaded),sha(original));assert.deepEqual(uploaded.subarray(0,8),Buffer.from([137,80,78,71,13,10,26,10]));});
 const report={scope:'Read-only independent HTTPS corroboration of root-operated actual Android native intake; no ADB/business writes',status:'PASS',caseId:Number(detail.case.id),roomSeparation:true,ktpMime:ktp.mime,ktpBytes:uploaded.length,ktpSha256:sha(uploaded),checks};
 fs.writeFileSync(path.join(dir,'ciko/tablet-ktp-readonly-results.json'),JSON.stringify(report,null,2));console.log(JSON.stringify({status:report.status,count:checks.length,caseId:report.caseId,bytes:report.ktpBytes,sha256:report.ktpSha256}));
})().catch(e=>{console.error(e.message);process.exitCode=1}).finally(async()=>{for(const token of [resident,admin])if(token)try{await request('auth/logout',token,{})}catch{}});
