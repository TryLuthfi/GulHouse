const fs=require('fs'),path=require('path');
(async()=>{
 const root=path.resolve(__dirname,'..'),checks=[];const ck=(name,ok)=>checks.push({name,status:ok?'PASS':'FAIL'});
 const res=await fetch('http://localhost/GulHouse/landing/'),html=await res.text();ck('Landing renders without PHP errors',res.status===200&&!/A PHP Error|A Database Error|Fatal error:/.test(html));
 const filters=[...html.matchAll(/<button\b[^>]*data-filter-field="property"[^>]*data-filter-value="([^"]+)"[^>]*>([^<]+)<\/button>/g)].map(m=>({value:m[1],label:m[2].trim()}));
 ck('Exactly one APT filter',filters.filter(x=>x.value==='APT'&&x.label==='APT').length===1);
 ck('No individual apartment building filter choices',!filters.some(x=>/^APT\s/.test(x.value)));
 ck('House filter choices remain separate',['GH 1','GH 2','GH 3'].every(code=>filters.some(x=>x.value===code)));
 const cards=[...html.matchAll(/<article\b([^>]*data-room-card[^>]*)>([\s\S]*?)<\/article>/g)].map(m=>({attrs:m[1],body:m[2],group:m[1].match(/\bdata-property="([^"]+)"/)?.[1],code:m[1].match(/\bdata-property-code="([^"]+)"/)?.[1]}));
 const apts=cards.filter(c=>c.group==='APT');ck('Three priced apartment cards in shared APT group',apts.length===3);ck('Actual property identities remain distinct',new Set(apts.map(c=>c.code)).size===3&&apts.every(c=>/^APT\s/.test(c.code)));
 for(const [code,name]of[['APT THE NEST','Apartemen The Nest'],['APT MEJESTIC','Apartemen Mejestic'],['APT PIK 2','Apartemen PIK 2']]){
  const c=apts.find(x=>x.code===code);ck(code+' displays friendly building name',!!c&&c.body.includes('<h3>'+name+'</h3>'));
  ck(code+' carries shared APT label',!!c&&/<span>APT<\/span>/.test(c.body));
  ck(code+' has individual location element',!!c&&/<p class="room-city">[^<]+<\/p>/.test(c.body));
 }
 ck('Archived shared property not used as actual card identity',!cards.some(c=>c.code==='APT'));
 const summary=html.match(/<section class="section split" id="rooms">([\s\S]*?)<\/section>/)?.[1]||'';
 const summaryCards=[...summary.matchAll(/<article\b[^>]*property-room-card[^>]*>([\s\S]*?)<\/article>/g)].map(x=>x[1]);
 ck('Summary apartment type labels use APT',summaryCards.filter(x=>/<span>APT<\/span>/.test(x)).length===3);
 const js=fs.readFileSync(path.join(root,'landing/assets/landing/js/landing.js'),'utf8');ck('Existing filter compares grouped data-property',/getAttribute\('data-property'\)|dataset\.property/.test(js));
 const model=fs.readFileSync(path.join(root,'landing/application/models/Landing_model.php'),'utf8');ck('Public data retains property-specific city and name',model.includes("'city' => $this->public_property_city($row['property_code'],$row['city'],$row['property_address'])")&&model.includes("'property_name' => $row['property_name']"));
 const result={passed:checks.filter(c=>c.status==='PASS').length,failed:checks.filter(c=>c.status==='FAIL').length,checks,database_writes:0};fs.writeFileSync(path.join(root,'.e2e/apartment-split-20261021/ciko-group-http-results.json'),JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));process.exitCode=result.failed?1:0;
})().catch(e=>{console.error(e.message);process.exitCode=1;});
