const fs=require('fs'),path=require('path');
(async()=>{
 const root=path.resolve(__dirname,'..'),checks=[];const ck=(name,ok)=>checks.push({name,status:ok?'PASS':'FAIL'});
 const r=await fetch('http://localhost/GulHouse/landing/'),html=await r.text();ck('Public landing HTTP200 without application errors',r.status===200&&!/A PHP Error|A Database Error|Fatal error:/.test(html));
 const buttons=[...html.matchAll(/<button\b[^>]*data-filter-field="property"[^>]*data-filter-value="([^"]+)"[^>]*>([^<]+)<\/button>/g)];
 ck('Exactly one shared APT filter',buttons.filter(x=>x[1]==='APT').length===1);ck('No split apartment building filter',!buttons.some(x=>/^APT\s/.test(x[1])));
 const cards=[...html.matchAll(/<article\b([^>]*data-room-card[^>]*)>([\s\S]*?)<\/article>/g)].map(m=>({attrs:m[1],body:m[2],group:m[1].match(/data-property="([^"]+)"/)?.[1],code:m[1].match(/data-property-code="([^"]+)"/)?.[1]}));
 const apt=cards.filter(x=>x.group==='APT');ck('Four priced apartment unit cards',apt.length===4);ck('All apartment cards use one actual APT property',apt.every(x=>x.code==='APT'));
 const links=apt.map(x=>x.body.match(/href="([^"]+)"[^>]*>Lihat detail/)?.[1]);ck('Four distinct canonical unit detail URLs',links.every(Boolean)&&new Set(links).size===4);
 ck('Every apartment unit card has its own city element',apt.every(x=>/<p class="room-city">[^<]*<\/p>/.test(x.body)));
 for(const [id,type]of[[128,'The Nest'],[129,'The Nest'],[130,'Mejestic'],[131,'PIK 2']]){const url=links.find(x=>x.endsWith('-unit-'+id));ck('Room '+id+' has unique unit link',!!url);if(url){const resp=await fetch(url),text=await resp.text();ck('Room '+id+' detail resolves correct type',resp.status===200&&text.includes(type));}}
 for(const [slug,type]of[['apt-the-nest','The Nest'],['apt-mejestic','Mejestic'],['apt-pik-2','PIK 2'],['apt-the-nest-the-nest','The Nest'],['apt-mejestic-mejestic','Mejestic'],['apt-pik-2-pik-2','PIK 2']]){const resp=await fetch('http://localhost/GulHouse/landing/rooms/'+slug),text=await resp.text();ck('Historical detail alias '+slug,resp.status===200&&text.includes(type));}
 const summary=html.match(/<section class="section split" id="rooms">([\s\S]*?)<\/section>/)?.[1]||'';ck('Shared APT summary explains per-unit location',summary.includes('Lokasi mengikuti masing-masing unit'));
 const view=fs.readFileSync(path.join(root,'master/application/views/manage/rooms.php'),'utf8');ck('Master create/edit exposes unit city field with limit',/name="unit_city" maxlength="120"/.test(view));ck('Master create/edit exposes unit address field with limit',/name="unit_address" rows="3" maxlength="2000"/.test(view));
 const result={passed:checks.filter(c=>c.status==='PASS').length,failed:checks.filter(c=>c.status==='FAIL').length,checks,database_writes:0,business_form_submissions:0};fs.writeFileSync(path.join(root,'.e2e/apartment-unit-location-20261021/ciko-http-results.json'),JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));process.exitCode=result.failed?1:0;
})().catch(e=>{console.error(e.message);process.exitCode=1;});
