/* Pure-source behavior audit. No app source writes, network or device. */
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),assert=require('node:assert/strict');
const root=path.resolve(__dirname,'..'),ts=require(path.join(root,'mobile/node_modules/typescript'));const checks=[];
function functionText(relative,name){const source=fs.readFileSync(path.join(root,relative),'utf8'),ast=ts.createSourceFile(relative,source,ts.ScriptTarget.Latest,true,ts.ScriptKind.TSX);let found;function visit(node){if(ts.isFunctionDeclaration(node)&&node.name?.text===name)found=node.getText(ast).replace(/^export\s+(?:default\s+)?/,'');ts.forEachChild(node,visit);}visit(ast);assert(found,'Actual function '+name);return found;}
function run(source,context){return vm.runInNewContext(ts.transpileModule(source,{compilerOptions:{target:ts.ScriptTarget.ES2022}}).outputText,context);}
function check(name,fn){fn();checks.push({name,status:'PASS'});}
const dashboard=fs.readFileSync(path.join(root,'mobile/src/screens/Dashboard.tsx'),'utf8');
const dashboardAst=ts.createSourceFile('Dashboard.tsx',dashboard,ts.ScriptTarget.Latest,true,ts.ScriptKind.TSX);
const sets=dashboardAst.statements.filter(n=>ts.isVariableStatement(n)&&n.declarationList.declarations.some(d=>['moneyMetrics','countMetrics'].includes(d.name.getText(dashboardAst)))).map(n=>n.getText(dashboardAst).replace(/^export\s+/, '')).join('\n');
const metric=run(sets+'\n'+functionText('mobile/src/screens/Dashboard.tsx','metricValue')+'\nmetricValue;', {money:v=>'Rp '+Number(v).toLocaleString('id-ID')});
for(const key of ['monthly_potential','payment_total_all','deposit_balance','latest_bill_total'])check(key+' uses grouped rupiah',()=>assert.equal(metric(key,123700000),'Rp 123.700.000'));
for(const key of ['unpaid_bills','payment_count','active_tenants'])check(key+' is count, not money',()=>assert.equal(metric(key,1200),'1.200'));
check('Rates have percent units',()=>{assert.equal(metric('collection_rate',100),'100%');assert.equal(metric('occupancy_rate',12.5),'12,5%');});
check('Phone, code, date preserve identity digits and leading zero',()=>{assert.equal(metric('phone','081234567890'),'081234567890');assert.equal(metric('code','0012'),'0012');assert.equal(metric('period','2026-10'),'2026-10');});
check('Blank values display dash rather than financial zero',()=>assert.equal(metric('deposit_balance',null),'—'));
check('Overdue total is rupiah rather than raw digits',()=>assert.equal(metric('past_due_total',1200000),'Rp 1.200.000'));
check('Bill count remains a count rather than rupiah',()=>assert.equal(metric('bills',8),'8'));
const items=Array.from({length:19},(_,i)=>({id:i+1,item_name:i===0?'Bantal':'Fixture'+i,standard_qty:i===0?2:1}));
let opened;
const inspectionForm=run(functionText('mobile/src/screens/Lifecycle.tsx','inspectionForm')+'\ninspectionForm;', {items,props:{session:{user:{name:'CIKO synthetic'}}},calendarDate:()=> '2026-10-19',rowsOf:v=>Array.isArray(v)?v:[],label:String,conditionOptions:{uninspected:'Belum diperiksa',good:'Baik'},open:(action,title,fields,initial)=>{opened={action,title,fields,initial};}});
inspectionForm();
check('New scan form exposes metadata + scan, not57 blank item controls',()=>{assert.equal(opened.fields.filter(f=>/^(condition|qty|notes)_\d+$/.test(f.key)).length,0);assert(opened.fields.some(f=>f.key==='scan_file'&&f.required));});
check('New scan still carries all19 explicit uninspected/blank initial values',()=>{for(const item of items){assert.equal(opened.initial['condition_'+item.id],'uninspected');assert.equal(opened.initial['qty_'+item.id],'');assert.equal(opened.initial['notes_'+item.id],'');}});
const previous={id:77,stage:'routine',check_date:'2026-10-18',period:'2026-10',rows:items.map(i=>({item_id:i.id,condition_status:i.id===1?'damaged':'good',actual_qty:i.standard_qty,notes:'Actual manual review'}))};
inspectionForm(previous);
check('Existing draft exposes57 manual controls and preserves Bantal2/damage',()=>{assert.equal(opened.fields.filter(f=>/^(condition|qty|notes)_\d+$/.test(f.key)).length,57);assert.equal(opened.initial.qty_1,2);assert.equal(opened.initial.condition_1,'damaged');assert.equal(opened.initial.inspection_id,77);assert.equal(opened.fields.find(f=>f.key==='scan_file').required,false);});
inspectionForm(previous,true);
check('Revision retains19 values but creates new record with mandatory scan/reason',()=>{assert.equal(opened.initial.revision_of,77);assert.equal(opened.initial.inspection_id,undefined);assert.equal(opened.initial.qty_1,2);assert(opened.fields.find(f=>f.key==='scan_file').required);assert(opened.fields.find(f=>f.key==='notes').required);});
const report={scope:'Independent pure behavior of actual extracted native UI functions; no physical rendering claim',status:'PASS',count:checks.length,checks};fs.writeFileSync(process.env.GULHOUSE_UI_SEMANTICS_OUT||path.join(root,'.e2e/mobile/online/ciko/native-ui-semantics-results.json'),JSON.stringify(report,null,2));console.log(JSON.stringify({status:report.status,count:report.count}));
