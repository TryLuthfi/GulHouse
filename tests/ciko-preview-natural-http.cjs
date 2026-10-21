const assert=require('node:assert/strict');
(async()=>{
let pass=0;function check(v,m){assert(v,m);pass++;}
const base='http://localhost/GulHouse/';
let res=await fetch(base+'landing/');check(res.status===200,'landing HTTP200');const html=await res.text();
const section=html.match(/<div class="preview-mosaic[^]*?<\/div>/)?.[0];check(!!section,'preview present');
const figures=[...section.matchAll(/<figure[^]*?<\/figure>/g)].map(m=>m[0]);check(figures.length===4,'four preview images');
for(const figure of figures){check(!/preview-large|data-parallax|background-image|height=|style=/.test(figure),'no old span or inline crop');const imgs=[...figure.matchAll(/<img\b[^>]*src="([^"]+)"/g)];check(imgs.length===1,'one image per figure');res=await fetch(imgs[0][1].replace(/&amp;/g,'&'));check(res.status===200&&/^image\//.test(res.headers.get('content-type')),'image media accessible');check(/<figcaption>/.test(figure),'caption retained');}
const css=await(await fetch(base+'landing/assets/landing/css/landing.css?v=ciko-natural')).text();
const rules=[...css.matchAll(/\.preview-mosaic\s*(?:figure|img|figure:hover img)?\s*\{([^}]+)\}/g)];
check(!rules.some(m=>/grid-auto-rows|grid-row:|height:\s*(?:100%|\d+px)|min-height:\s*(?:190|220)px/.test(m[1])),'no forced image or grid heights');
const imgRules=[...css.matchAll(/\.preview-mosaic img\s*\{([^}]+)\}/g)];check(imgRules.length===1,'single shared image rule without per-photo override');
const img=imgRules[0]?.[1];check(/height:\s*auto/.test(img)&&/object-fit:\s*contain/.test(img)&&/transform:\s*none/.test(img),'uncropped image inside equal frame');
check(/aspect-ratio:\s*4\s*\/\s*3/.test(img),'all preview canvases use equal 4:3 aspect');
check(!/preview-mosaic[^{}]*(?:nth-child|first-child|preview-large)/.test(css),'no first or individual photo enlargement');
const hover=css.match(/\.preview-mosaic figure:hover img\s*\{([^}]+)\}/)?.[1];check(/transform:\s*none/.test(hover)&&/filter:\s*none/.test(hover),'hover keeps image size and appearance');
check(!/\.preview-mosaic \.preview-large/.test(css),'large first-photo span removed');
check(/@media \(max-width: 680px\)[^]*?\.preview-mosaic\s*\{\s*grid-template-columns:\s*1fr;/.test(css),'mobile single column');
check(/\.preview-mosaic figcaption\s*\{([^}]+)\}/.test(css)&&!css.match(/\.preview-mosaic figcaption\s*\{([^}]+)\}/)[1].includes('position: absolute'),'caption outside photo');
console.log(JSON.stringify({pass,preview_images:4,scope:'public HTTP and CSS source, not browser pixels'}));
})().catch(e=>{console.error(e);process.exitCode=1});
