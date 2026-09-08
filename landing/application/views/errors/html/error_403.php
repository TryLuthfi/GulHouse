<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$faviconUrl = function_exists('base_url')
  ? base_url('assets/dist/img/solid%20logo%20tkm%20landscape%20transparent.png')
  : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>503 - MAINTENANCE</title>
<?php if ($faviconUrl !== ''): ?>
  <link rel="icon" type="image/png" href="<?= $faviconUrl ?>">
  <link rel="shortcut icon" type="image/png" href="<?= $faviconUrl ?>">
<?php endif; ?>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    html,body { width:100%; height:100%; background:#06010f; overflow:hidden; font-family:Arial,Helvetica,sans-serif; }
    canvas#bg { position:fixed; inset:0; width:100%; height:100%; }
    .ui {
      position:fixed; right:7%; top:50%; transform:translateY(-50%);
      z-index:10; color:#fff;
    }
    .err-code {
      font-size:110px; font-weight:900; line-height:1;
      font-family:'Arial Black',Arial,sans-serif;
      color:#fff;
      text-shadow: 0 0 60px rgba(255,100,40,0.6), 0 0 120px rgba(255,60,20,0.3);
    }
    .err-title {
      font-size:20px; font-weight:700; letter-spacing:5px;
      text-transform:uppercase; color:#fff; margin:12px 0 18px;
      opacity:0.9;
    }
    .err-sub { font-size:14px; color:#9080b8; line-height:1.9; }
    @media(max-width:600px){
      .ui { right:auto; left:50%; top:auto; bottom:8%; transform:translateX(-50%); text-align:center; }
      .err-code { font-size:72px; }
    }
  </style>
</head>
<body>
<canvas id="bg"></canvas>
<div class="ui">
  <div class="err-code">503</div>
  <div class="err-title">MAINTENANCE...</div>
  <div class="err-sub">
    The service you requested<br>
    is not available at this time.<br>
    Try again later.
  </div>
</div>
<script>
(function(){
const c = document.getElementById('bg');
const ctx = c.getContext('2d');
let W, H;
function resize(){ W=c.width=window.innerWidth; H=c.height=window.innerHeight; }
resize();
window.addEventListener('resize', resize);

const rng = s=>{ let x=Math.sin(s)*10000; return x-Math.floor(x); };
const STARS = Array.from({length:260},(_,i)=>({
  x:rng(i*3.1), y:rng(i*7.3),
  r:rng(i*2.9)*1.6+0.25,
  base:rng(i*5.1), sp:rng(i*1.9)*0.6+0.2, ph:rng(i*4.3)*6.28
}));

function drawSphere(cx,cy,r,colorFn,lightAngle,ambientStr){
  const iw=Math.ceil(r*2)+4, ih=Math.ceil(r*2)+4;
  const img=ctx.createImageData(iw,ih);
  const d=img.data;
  const lx=Math.cos(lightAngle)*0.7, ly=-Math.sin(lightAngle)*0.45, lz=0.72;
  for(let py=0;py<ih;py++){
    for(let px=0;px<iw;px++){
      const dx=(px-r-1)/r, dy=(py-r-1)/r, dz2=1-dx*dx-dy*dy;
      if(dz2<0) continue;
      const dz=Math.sqrt(dz2);
      let diff=dx*lx+dy*ly+dz*lz;
      diff=Math.max(0,diff);
      const spec=Math.pow(Math.max(0,dz*0.9-dx*lx-dy*ly),22)*0.55;
      const light=ambientStr+diff*(1-ambientStr);
      const u=(Math.atan2(dx,dz)/Math.PI*0.5+0.5);
      const v=(Math.asin(Math.max(-1,Math.min(1,dy)))/Math.PI+0.5);
      const [br,bg,bb]=colorFn(u,v);
      const idx=(py*iw+px)*4;
      d[idx]=Math.min(255,br*light+spec*255);
      d[idx+1]=Math.min(255,bg*light+spec*200);
      d[idx+2]=Math.min(255,bb*light+spec*160);
      d[idx+3]=255;
    }
  }
  const ob=document.createElement('canvas');
  ob.width=iw; ob.height=ih;
  ob.getContext('2d').putImageData(img,0,0);
  ctx.save();
  ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2); ctx.clip();
  ctx.drawImage(ob,cx-r-1,cy-r-1);
  ctx.restore();
}

function sunColor(u,v){
  const n1=Math.sin(u*20+v*14)*0.5+0.5;
  const n2=Math.sin(u*11-v*9+1.4)*0.5+0.5;
  const n3=Math.sin(u*30+v*6+2.1)*0.5+0.5;
  const n4=Math.sin(u*7+v*18+0.7)*0.5+0.5;
  const lava=n1*0.4+n2*0.3+n3*0.15+n4*0.15;
  return [195+60*lava, 55+85*lava, 8+22*lava];
}
function saturnColor(u,v){
  const band=Math.sin(v*24+Math.sin(u*8)*0.4)*0.5+0.5;
  const swirl=Math.sin(u*35+v*10)*0.12;
  const t=band+swirl;
  return [155+65*t, 115+60*t, 75+40*t];
}
function moonColor(u,v){
  const c1=Math.sin(u*16+v*13)*0.5+0.5;
  const c2=Math.sin(u*9-v*11+2)*0.5+0.5;
  const c3=Math.sin(u*22+v*7+1)*0.5+0.5;
  const t=c1*0.5+c2*0.3+c3*0.2;
  const b=95+55*t;
  return [b,b-6,b-10];
}
function jupiterColor(u,v){
  const band=Math.abs(Math.sin(v*22+Math.sin(u*5)*0.3));
  const swirl=Math.sin(u*18+v*7+1)*0.4+0.4;
  const spot=Math.exp(-((u-0.35)**2+(v-0.55)**2)*80)*0.7;
  return [175+50*band+spot*60, 115+35*swirl-spot*20, 75+20*band];
}
function earthColor(u,v){
  const ocean=Math.sin(u*13+v*9)*0.5+0.5;
  const land=Math.sin(u*20-v*15+1.2)*0.5+0.5;
  const cloud=Math.sin(u*40+v*30+2)*0.5+0.5;
  if(cloud>0.72) return [220,225,235];
  if(ocean>0.52) return [25+15*land,70+45*land,150+70*ocean];
  return [45+85*land,115+65*land,35+30*land];
}

function drawGlow(cx,cy,r,rr,gr,br,alpha){
  const grad=ctx.createRadialGradient(cx,cy,r*0.7,cx,cy,r*2.6);
  grad.addColorStop(0,`rgba(${rr},${gr},${br},${alpha})`);
  grad.addColorStop(0.5,`rgba(${rr},${gr},${br},${alpha*0.3})`);
  grad.addColorStop(1,'rgba(0,0,0,0)');
  ctx.beginPath(); ctx.arc(cx,cy,r*2.6,0,Math.PI*2);
  ctx.fillStyle=grad; ctx.fill();
}

function drawAtmo(cx,cy,r,rr,gr,br){
  const grad=ctx.createRadialGradient(cx,cy,r*0.9,cx,cy,r*1.22);
  grad.addColorStop(0,`rgba(${rr},${gr},${br},0)`);
  grad.addColorStop(0.6,`rgba(${rr},${gr},${br},0.28)`);
  grad.addColorStop(1,'rgba(0,0,0,0)');
  ctx.beginPath(); ctx.arc(cx,cy,r*1.22,0,Math.PI*2);
  ctx.fillStyle=grad; ctx.fill();
}

function drawRing(cx,cy,r,tilt){
  const rIn=r*1.28, rOut=r*2.15;
  const steps=1800;
  for(let i=0;i<steps;i++){
    const angle=(i/steps)*Math.PI*2;
    const frac=rng(Math.floor(i*1.7)*11.3);
    const rd=rIn+(rOut-rIn)*frac;
    const x=cx+Math.cos(angle)*rd;
    const y=cy+Math.sin(angle)*rd*Math.sin(tilt);
    const behind=Math.sin(angle)<0;
    const alpha=behind?0.12:0.5;
    const t=(rd-rIn)/(rOut-rIn);
    const gray=130+60*t;
    const warm=t>0.5?10:0;
    ctx.beginPath(); ctx.arc(x,y,1.0,0,Math.PI*2);
    ctx.fillStyle=`rgba(${gray+warm},${gray},${gray-5},${alpha})`;
    ctx.fill();
  }
}

function drawSatellite(cx,cy,rot){
  ctx.save();
  ctx.translate(cx,cy); ctx.rotate(rot);
  const body=ctx.createLinearGradient(-10,0,10,0);
  body.addColorStop(0,'#8898aa'); body.addColorStop(0.5,'#c8d8e8'); body.addColorStop(1,'#8898aa');
  ctx.fillStyle=body;
  ctx.beginPath(); ctx.roundRect(-10,-5,20,10,2); ctx.fill();
  ctx.strokeStyle='rgba(100,160,200,0.4)'; ctx.lineWidth=0.5;
  ctx.strokeRect(-10,-5,20,10);

  const panelL=ctx.createLinearGradient(-36,-4,-14,-4);
  panelL.addColorStop(0,'#0d4a8a'); panelL.addColorStop(0.5,'#2a80cc'); panelL.addColorStop(1,'#1a60aa');
  ctx.fillStyle=panelL; ctx.fillRect(-36,-4,22,8);
  const panelR=ctx.createLinearGradient(14,-4,36,-4);
  panelR.addColorStop(0,'#1a60aa'); panelR.addColorStop(0.5,'#2a80cc'); panelR.addColorStop(1,'#0d4a8a');
  ctx.fillStyle=panelR; ctx.fillRect(14,-4,22,8);
  ctx.strokeStyle='rgba(100,180,255,0.3)'; ctx.lineWidth=0.8;
  for(let i=-34;i<36;i+=5){
    ctx.beginPath(); ctx.moveTo(i,-4); ctx.lineTo(i,4); ctx.stroke();
  }
  ctx.strokeStyle='#aabbcc'; ctx.lineWidth=1;
  ctx.beginPath(); ctx.moveTo(0,-5); ctx.lineTo(0,-14); ctx.stroke();
  ctx.strokeStyle='#bbccdd'; ctx.lineWidth=1.5;
  ctx.beginPath(); ctx.arc(4,-18,5,Math.PI,0); ctx.stroke();
  ctx.restore();
}

let t=0, spinU=0;
function frame(){
  ctx.clearRect(0,0,W,H);

  const bgGrad=ctx.createRadialGradient(W*0.2,H*0.4,0,W*0.5,H*0.5,W*0.85);
  bgGrad.addColorStop(0,'#0e0625'); bgGrad.addColorStop(1,'#06010f');
  ctx.fillStyle=bgGrad; ctx.fillRect(0,0,W,H);

  [[0.06,0.18,120,16,'50,15,110'],[0.16,0.72,90,14,'15,35,95'],[0.4,0.88,70,12,'70,25,95']].forEach(([fx,fy,rr,al,col])=>{
    const g=ctx.createRadialGradient(fx*W,fy*H,0,fx*W,fy*H,rr);
    g.addColorStop(0,`rgba(${col},0.${al})`);
    g.addColorStop(1,'rgba(0,0,0,0)');
    ctx.fillStyle=g; ctx.beginPath(); ctx.ellipse(fx*W,fy*H,rr*2.2,rr*0.9,0.5,0,Math.PI*2); ctx.fill();
  });

  STARS.forEach(s=>{
    const a=s.base*0.3+0.7*Math.abs(Math.sin(t*s.sp+s.ph));
    ctx.beginPath(); ctx.arc(s.x*W,s.y*H,s.r,0,Math.PI*2);
    ctx.fillStyle=`rgba(255,255,255,${a})`; ctx.fill();
  });

  const sunR=Math.min(W,H)*0.16;
  const sunX=W*0.08+sunR+18;
  const sunY=H*0.4;

  drawGlow(sunX,sunY,sunR,255,130,40,0.22);
  spinU+=0.003;
  drawSphere(sunX,sunY,sunR,(u,v)=>sunColor((u+spinU)%1,v),Math.PI*0.14,0.28);
  drawAtmo(sunX,sunY,sunR,255,180,60);

  const satR=Math.min(W,H)*0.082;
  const satX=W*0.26;
  const satY=H*0.73;
  drawGlow(satX,satY,satR,190,150,90,0.1);
  drawSphere(satX,satY,satR,(u,v)=>saturnColor((u+t*0.015)%1,v),Math.PI*0.22,0.16);
  drawRing(satX,satY,satR,0.36);

  const jA=t*0.19;
  const jOr=Math.min(W,H)*0.30;
  const jR=Math.min(W,H)*0.052;
  const jX=sunX+Math.cos(jA)*jOr;
  const jY=sunY+Math.sin(jA)*jOr*0.35;
  drawGlow(jX,jY,jR,210,150,80,0.11);
  drawSphere(jX,jY,jR,(u,v)=>jupiterColor((u+t*0.05)%1,v),Math.PI*0.26,0.14);

  const eA=t*0.12+2.4;
  const eOr=Math.min(W,H)*0.19;
  const eR=Math.min(W,H)*0.036;
  const eX=sunX+Math.cos(eA)*eOr;
  const eY=sunY+Math.sin(eA)*eOr*0.3;
  drawGlow(eX,eY,eR,60,130,230,0.16);
  drawSphere(eX,eY,eR,(u,v)=>earthColor((u+t*0.08)%1,v),Math.PI*0.23,0.11);
  drawAtmo(eX,eY,eR,80,160,255);

  const mA=t*0.65;
  const mOr=eR*3.4;
  const mR=eR*0.34;
  const mX=eX+Math.cos(mA)*mOr;
  const mY=eY+Math.sin(mA)*mOr*0.5;
  drawSphere(mX,mY,mR,(u,v)=>moonColor(u,v),Math.PI*0.26,0.11);

  const saA=t*0.85+1.2;
  const saOr=eR*5.8;
  const saX=eX+Math.cos(saA)*saOr;
  const saY=eY+Math.sin(saA)*saOr*0.42;
  drawSatellite(saX,saY,saA+Math.PI*0.5);

  t+=0.016;
  requestAnimationFrame(frame);
}
frame();
})();
</script>
</body>
</html>
