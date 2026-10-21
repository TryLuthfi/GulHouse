const assert=require('node:assert/strict');
const fs=require('node:fs');
(async()=>{
let count=0;const test=(value,message)=>{assert(value,message);count++;};
const base='http://localhost/GulHouse/';
let res=await fetch(base+'landing/');const html=await res.text();test(res.status===200,'public page 200');
const cards=[...html.matchAll(/<article class="room-card[^]*?<\/article>/g)].map(m=>m[0]);test(cards.length>0,'room cards rendered');
let images=0;
for(const card of cards){test(!/data-parallax|background-image/i.test(card),'no parallax or duplicate background');const match=card.match(/<div class="room-photo">\s*<img[^>]*src="([^"]+)"[^>]*style="object-position:\s*(\d+)%\s+(\d+)%/);if(!match)continue;images++;test(+match[2]<=100&&+match[3]<=100,'bounded focus rendered');const image=await fetch(match[1].replace(/&amp;/g,'&'));test(image.status===200&&/^image\//.test(image.headers.get('content-type')),'image served');}
test(images>=3,'at least three photo cards checked');
const css=await(await fetch(base+'landing/assets/landing/css/landing.css?v=ciko-focus')).text();
const photoRules=[...css.matchAll(/\.room-photo\s*\{([^}]+)\}/g)];test(photoRules.length===1,'no competing room photo/mobile override');test(/aspect-ratio:\s*4\s*\/\s*3/.test(photoRules[0][1]),'ratio 4:3');test(!/height:|transform:|background-image:/.test(photoRules[0][1]),'no height crop or background layers');
const view=fs.readFileSync('landing/application/views/landing_home.php','utf8');test(!/data-parallax-card/.test(view),'card parallax removed');
res=await fetch(base+'master/photos/set-focus',{method:'POST',body:new URLSearchParams({property:'GH 2',type:'VIP',file:'nonexistent.png',focus_x:'1',focus_y:'1'}),redirect:'manual'});test(res.status===403,'CSRF-less POST rejected');
res=await fetch(base+'master/photos/set-focus',{redirect:'manual'});test(res.status===303&&/login/.test(res.headers.get('location')),'anonymous GET requires login');
console.log(JSON.stringify({pass:count,public_images:images,scope:'anonymous public HTTP and source; no gallery writes'}));
})().catch(e=>{console.error(e);process.exitCode=1});
