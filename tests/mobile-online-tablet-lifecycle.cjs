/* Read-only corroboration of ROOT's physical Android journey. Never completes steps by API. */
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const root=path.resolve(__dirname,'..'),dir=path.join(root,'.e2e/mobile/online');
const rt=JSON.parse(fs.readFileSync(path.join(dir,'runtime-private.json'),'utf8').replace(/^\uFEFF/,''));
const checkpoint=process.argv.includes('--checkpoint'),checks=[];let admin,resident;
assert(rt.synthetic_isolation_approved&&rt.synthetic_storage_isolated&&rt.synthetic_schema_verified&&rt.synthetic_contains_real_occupants===false&&rt.synthetic_base!==rt.production_base);
assert(rt.tablet_full_name&&rt.tablet_access_code&&rt.synthetic_room_id_tablet!==rt.synthetic_room_id_ciko);
function check(name,fn){try{fn();checks.push({name,status:'PASS'});}catch(e){checks.push({name,status:'PENDING_OR_FAIL',reason:e.message});}}
async function request(ep,token,payload){assert(!payload||['auth/admin','auth/resident','auth/logout'].includes(ep),'Business writes prohibited');const r=await fetch(rt.synthetic_base+'/'+ep,{method:payload?'POST':'GET',headers:{Accept:'application/json',...(token?{Authorization:'Bearer '+token}:{}),...(payload?{'Content-Type':'application/json'}:{})},body:payload?JSON.stringify(payload):undefined,signal:AbortSignal.timeout(30000)});const j=await r.json();assert.equal(r.status,200,ep+' HTTP200');assert(j.ok,ep+' APIok');return j.data;}
function plusDays(iso,n){const d=new Date(iso+'T00:00:00Z');d.setUTCDate(d.getUTCDate()+n);return d.toISOString().slice(0,10);}
(async()=>{
 admin=(await request('auth/admin',null,{username:rt.synthetic_username,password:rt.synthetic_password})).token;
 const listing=await request('admin/lifecycle?q='+encodeURIComponent(rt.tablet_full_name)+'&limit=100',admin);
 const matches=(listing.rows||listing.cases||listing.items||[]).filter(r=>r.full_name===rt.tablet_full_name);assert.equal(matches.length,1,'Dedicated tablet fixture unique');
 const d=await request('admin/lifecycle/'+matches[0].id,admin),c=d.case,p=d.payment_summary||{},q=d.checkout||{};
 assert.equal(Number(c.room_id),Number(rt.synthetic_room_id_tablet),'Only tablet case');assert.notEqual(Number(c.room_id),Number(rt.synthetic_room_id_ciko));
 resident=(await request('auth/resident',null,{access_code:rt.tablet_access_code})).token;
 const portal=await request('resident/portal',resident);
 check('Resident authenticated same dedicated tablet case',()=>assert.equal(Number(portal.case.id),Number(c.id)));
 const payment=await request('admin/payments/'+p.invoice_id,admin);
 const transactions=payment.payments||[];
 check('Exactly one native rent1.2m and deposit500k transaction persisted',()=>{const rent=transactions.filter(x=>x.payment_type==='rent'),deposit=transactions.filter(x=>x.payment_type==='deposit');assert.equal(rent.length,1);assert.equal(deposit.length,1);assert.equal(Number(rent[0].amount),1200000);assert.equal(Number(deposit[0].amount),500000);});
 check('Native payment verification persisted',()=>assert(c.payment_verified_at));
 check('Native full profile completed after verification',()=>assert(c.profile_completed_at&&c.profile_completed_at>=c.payment_verified_at));
 const proof=(d.files||[]).filter(f=>f.kind==='rent_proof');
 check('Resident native PDF rent proof remains private persisted record',()=>assert(proof.some(f=>f.mime==='application/pdf')));
 for(const stage of ['check_in','routine','check_out']){
  const all=(d.inspections||[]).filter(i=>i.stage===stage&&i.status==='final'),i=all.at(-1);
  check('Native '+stage+' final19 manual rows and actual signatures confirmed',()=>{assert(i);assert.equal(i.rows.length,19);assert(i.rows.every(r=>r.actual_qty!==null&&r.actual_qty!==''&&r.condition_status!=='uninspected'));assert.equal(Number(i.resident_signed),1);assert.equal(Number(i.manager_signed),1);const ac=i.rows.find(r=>r.item_name==='AC');if(stage==='check_in')assert.equal(ac.condition_status,'good');if(stage==='routine'){assert.equal(Number(i.supervisor_signed),1);assert.equal(ac.condition_status,'damaged');assert(Number(ac.maintenance_ticket_id)>0,'AC damage linked to maintenance without rewriting initial record');}assert.equal(Number(i.rows.find(r=>r.item_name==='Bantal').actual_qty),2);});
  check('Native '+stage+' PDF scan stored against exact final inspection',()=>assert(i&&(d.files||[]).some(f=>f.kind==='scan'&&Number(f.inspection_id)===Number(i.id)&&f.mime==='application/pdf')));
 }
 const ocrInspections=(d.inspections||[]).filter(i=>i.ocr?.ok===true&&i.ocr.items?.length===19);
 check('Native OCR result persisted with printed19labels',()=>assert(ocrInspections.length>0));
 check('Resident sees three final facility stages only',()=>{assert(['check_in','routine','check_out'].every(stage=>(portal.inspections||[]).some(i=>i.stage===stage&&i.status==='final')));assert((portal.inspections||[]).every(i=>i.status==='final'));});
 check('Notice date/planned exit stored with calendar15day interpretation',()=>{assert(q.notice_date&&q.planned_exit);assert(q.planned_exit>=plusDays(q.notice_date,15),'Plan respects H15 calendar');if(q.actual_date)assert.equal(Number(q.late_notice),Number(q.actual_date<plusDays(q.notice_date,15)),'Late flag recalculates actual early exit without automatic penalty');});
 check('Native checkout ended case and computes exact H+2 calendar deadline',()=>{assert.equal(c.status,'checked_out');assert(q.actual_date);assert.equal(q.refund_due,plusDays(q.actual_date,2));assert.equal(Number(q.deposit_snapshot),500000);});
 const proofDownloads=[];for(const r of d.refunds||[]){const file=(d.files||[]).find(f=>Number(f.id)===Number(r.proof_file_id));const response=await fetch(rt.synthetic_base+'/files/lifecycle/'+r.proof_file_id,{headers:{Authorization:'Bearer '+resident},signal:AbortSignal.timeout(30000)});const bytes=Buffer.from(await response.arrayBuffer());proofDownloads.push({status:response.status,mime:response.headers.get('content-type'),pdf:bytes.subarray(0,4).toString()==='%PDF',storedKind:file?.kind,storedMime:file?.mime});}
 check('Actual refund payments total500k without unresolved disputes or automatic penalty',()=>{assert.equal(Number(q.deduction_amount),0);assert.equal(Number(q.disputed_amount),0);assert.equal(Number(q.refundable_amount),500000);assert.equal(Number(q.paid_amount),500000);assert.equal((d.refunds||[]).reduce((s,r)=>s+Number(r.amount),0),500000);assert((d.refunds||[]).every(r=>r.proof_file_id&&r.reference_no&&r.transfer_date>=q.actual_date));assert.equal(new Set(d.refunds.map(r=>r.reference_no)).size,d.refunds.length);assert(proofDownloads.length>0&&proofDownloads.every(f=>f.status===200&&f.mime.includes('application/pdf')&&f.pdf&&f.storedKind==='refund_proof'&&f.storedMime==='application/pdf'),'Resident downloads actual private PDF refund proof');});
 check('Resident retains completed own refund history',()=>{assert.equal(Number(portal.checkout?.paid_amount),500000);assert.equal((portal.refunds||[]).reduce((s,r)=>s+Number(r.amount),0),500000);});
 const rootJourney=JSON.parse(fs.readFileSync(path.join(dir,'tablet-journey-results.json'),'utf8'));
 const pending=checks.filter(x=>x.status!=='PASS');
 const draftEvidence=(d.inspections||[]).filter(i=>i.status==='draft').map(i=>({id:Number(i.id),stage:i.stage,rows:i.rows.length,blankActualQty:i.rows.filter(r=>r.actual_qty===null||r.actual_qty==='').length,uninspected:i.rows.filter(r=>r.condition_status==='uninspected').length,ocrLabels:i.ocr?.items?.length||0}));
 const report={scope:'Read-only independent server corroboration of root-operated Android; not replacement for device button evidence',mode:checkpoint?'checkpoint':'final',status:pending.length?(checkpoint?'IN_PROGRESS':'NEEDS_FIX'):'PASS',caseId:Number(c.id),caseStatus:c.status,passed:checks.length-pending.length,total:checks.length,ocrStages:ocrInspections.map(i=>i.stage),draftEvidence,deviceCheckpointCount:(rootJourney.checks||[]).filter(x=>x.status==='PASS').length,checks};
 fs.writeFileSync(path.join(dir,'ciko/tablet-lifecycle-'+(checkpoint?'checkpoint':'final')+'-results.json'),JSON.stringify(report,null,2));
 console.log(JSON.stringify(report));if(!checkpoint&&pending.length)process.exitCode=1;
})().catch(e=>{console.error(e.message);process.exitCode=1}).finally(async()=>{for(const token of [resident,admin])if(token)try{await request('auth/logout',token,{})}catch{}});
