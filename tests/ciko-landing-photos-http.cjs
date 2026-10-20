const fs=require('fs'),path=require('path'),crypto=require('crypto'),assert=require('assert');
(async()=>{
 const root=path.resolve(__dirname,'..'),meta=JSON.parse(fs.readFileSync(path.join(root,'uploads/.landing-photos.json'))),checks=[];
 const ck=(name,ok)=>{checks.push({name,status:ok?'PASS':'FAIL'});};
 const res=await fetch('http://localhost/GulHouse/landing/'),html=await res.text();ck('Public landing HTTP200',res.status===200);
 const preview=html.match(/<section class="section photo-preview-section">([\s\S]*?)<\/section>/)?.[1]||'';
 const srcs=[...preview.matchAll(/<img\b[^>]*src="([^"]+)"/g)].map(x=>x[1]);
 ck('Exactly4 preview images',srcs.length===4);
 ck('Preview follows configured order GH2',srcs.length===4&&srcs.every((s,i)=>decodeURIComponent(s).endsWith(meta.preview[i])&&meta.preview[i].startsWith('GH2/')));
 const daily=html.match(/<section class="visual-band"[^>]*>/)?.[0]||'';
 ck('Daily background uses configured GH2 photo',decodeURIComponent(daily).includes(meta.daily));
 ck('Daily background does not use stock photo',!daily.includes('unsplash'));
 for(const key of [...meta.preview,meta.daily]){
 const url='http://localhost/GulHouse/landing/index.php/media/gallery/'+key.split('/').map(encodeURIComponent).join('/');
 const r=await fetch(url),buf=Buffer.from(await r.arrayBuffer()),expected=fs.readFileSync(path.join(root,'uploads',key));
 ck('Image HTTP/MIME/hash '+key,r.status===200&&r.headers.get('content-type').startsWith('image/')&&crypto.createHash('sha256').update(buf).digest('hex')===crypto.createHash('sha256').update(expected).digest('hex'));
 }
 const get=await fetch('http://localhost/GulHouse/master/photos/save-landing',{redirect:'manual'});ck('Anonymous save GET denied',get.status===303&&get.headers.get('location').includes('login'));
 const post=await fetch('http://localhost/GulHouse/master/photos/save-landing',{method:'POST',headers:{'content-type':'application/x-www-form-urlencoded'},body:'daily=invalid',redirect:'manual'});ck('Save POST without CSRF denied',post.status===403);
 const metaAfter=JSON.parse(fs.readFileSync(path.join(root,'uploads/.landing-photos.json')));ck('HTTP audit does not change live selection',JSON.stringify(metaAfter)===JSON.stringify(meta));
 const result={passed:checks.filter(x=>x.status==='PASS').length,failed:checks.filter(x=>x.status==='FAIL').length,checks};
 fs.mkdirSync(path.join(root,'.e2e/landing-photos-20261021/ciko'),{recursive:true});fs.writeFileSync(path.join(root,'.e2e/landing-photos-20261021/ciko/http-results.json'),JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));process.exitCode=result.failed?1:0;
})().catch(e=>{console.error(e.message);process.exitCode=1;});
