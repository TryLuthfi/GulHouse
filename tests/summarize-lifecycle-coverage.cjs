// Reconcile every discovered legacy browser scenario against completed reruns.
const fs=require('fs'),path=require('path');
const root=path.resolve(__dirname,'..');
const sources=[
 '.e2e-lifecycle-regression-20261019',
 '.e2e-lifecycle-regression-recheck-20261019',
 '.e2e-lifecycle-regression-wide-20261019',
 '.e2e-lifecycle-regression-mobile-20261019',
];
const runs=sources.map(dir=>({dir,run:JSON.parse(fs.readFileSync(path.join(root,dir,'report.json'),'utf8')).run}));
const key=t=>[t.file,t.targetId,JSON.stringify(t.titlePath)].join('|');
const expected=runs[0].run.results.filter(t=>t.selected),latest=new Map();
for(const {dir,run}of runs)for(const t of run.results)if(t.selected)latest.set(key(t),{file:t.file,target:t.targetId,title:t.titlePath,status:t.status,evidence:dir+'/report.json',runId:run.id});
const unresolved=expected.filter(t=>latest.get(key(t))?.status!=='passed').map(t=>latest.get(key(t))||{title:t.titlePath,target:t.targetId,status:'missing'});
const report={status:unresolved.length?'NEEDS FIX':'PASS',expectedScenarios:expected.length,passedScenarios:expected.length-unresolved.length,unresolved,
 byTarget:expected.reduce((o,t)=>{o[t.targetId]??={expected:0,passed:0};o[t.targetId].expected++;if(latest.get(key(t))?.status==='passed')o[t.targetId].passed++;return o;},{}),
 runs:runs.map(({dir,run})=>({evidence:dir+'/report.json',runId:run.id,status:run.status,summary:run.summary,modelTokens:run.usage.modelTokens,maxModelCallsInStep:run.usage.maxModelCallsInStep})),
 note:'Combined coverage, not a single green96-scenario run. Initial overpayment fixture pollution and mid-run config change were corrected; failed/skipped cases were rerun with frozen config on fresh synthetic schemas.'};
fs.mkdirSync(path.join(root,'.e2e/lifecycle'),{recursive:true});fs.writeFileSync(path.join(root,'.e2e/lifecycle/legacy-coverage.json'),JSON.stringify(report,null,2));
console.log(JSON.stringify({status:report.status,expected:report.expectedScenarios,passed:report.passedScenarios,unresolved:unresolved.length,byTarget:report.byTarget}));
if(unresolved.length)process.exitCode=1;
