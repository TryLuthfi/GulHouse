import json, pathlib
from PIL import Image, ImageDraw
root=pathlib.Path('D:/XAMPP/htdocs/GulHouse')
f=json.loads((root/'.e2e/photo-cover-20261020/ciko/fixture-private.json').read_text())
for k,g in f['groups'].items():
 for i,name in enumerate(g['files']):
  img=Image.new('RGB',(640,420),['#177f65','#1c5196','#ae6b20'][i%3]);d=ImageDraw.Draw(img);d.text((30,100),'CIKO SYNTHETIC GALLERY '+k,fill='white',stroke_width=1);d.text((30,150),name,fill='white',stroke_width=1);img.save(pathlib.Path(g['gallery'])/name)
print('Synthetic image fixtures created; no existing photos changed')
