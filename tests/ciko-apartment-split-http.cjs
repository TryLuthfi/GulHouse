const fs=require('fs'),path=require('path'),assert=require('assert');
(async()=>{
 const root=path.resolve(__dirname,'..'),checks=[];const ck=(name,ok)=>{checks.push({name,status:ok?'PASS':'FAIL'});};
 const r=await fetch('http://localhost/GulHouse/landing/'),html=await r.text();ck('Landing HTTP200 without PHP error',r.status===200&&!/A PHP Error|A Database Error|Fatal error:/.test(html));
 ck('Archived shared APT no longer actual room property',!html.includes('data-property-code="APT"'));
 const cards=[...html.matchAll(/<article\b[^>]*data-room-card[^>]*data-property-code="([^"]+)"[^>]*>([\s\S]*?)<\/article>/g)];
 for(const [code,name,old]of[['APT THE NEST','Apartemen The Nest','apt-the-nest'],['APT MEJESTIC','Apartemen Mejestic','apt-mejestic'],['APT PIK 2','Apartemen PIK 2','apt-pik-2']]){
  const card=cards.find(x=>x[1]===code);ck(code+' has one readable public card',!!card&&card[2].includes('<h3>'+name+'</h3>'));
  if(!card)continue;
  const href=card[2].match(/href="([^"]+)"[^>]*>Lihat detail/)?.[1];ck(code+' exposes canonical detail link',!!href);
  if(href){const detail=await fetch(href),body=await detail.text();ck(code+' canonical detail HTTP200',detail.status===200&&body.includes(name));}
  const alias=await fetch('http://localhost/GulHouse/landing/rooms/'+old),body=await alias.text();ck(old+' legacy link still resolves correct building',alias.status===200&&body.includes(name));
 }
 ck('Unpriced Taman Anggrek not unexpectedly advertised',!cards.some(x=>x[1]==='APT TAMAN ANGGREK'));
 const result={passed:checks.filter(x=>x.status==='PASS').length,failed:checks.filter(x=>x.status==='FAIL').length,checks,business_form_submissions:0};
 fs.writeFileSync(path.join(root,'.e2e/apartment-split-20261021/ciko-http-results.json'),JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));process.exitCode=result.failed?1:0;
})().catch(e=>{console.error(e.message);process.exitCode=1;});
