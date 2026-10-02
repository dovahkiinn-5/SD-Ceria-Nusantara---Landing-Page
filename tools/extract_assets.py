import sys, json
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / '.tools/python'))
import pymupdf as fitz
from PIL import Image, ImageDraw

root=Path(__file__).resolve().parents[1]
dest=root/'public/assets/design'
dest.mkdir(parents=True,exist_ok=True)
doc=fitz.open('E:/figma elementary school design/contoh Full Pages.pdf')
p=doc[0]
manifest=[]
for i,item in enumerate(p.get_images(full=True)):
    xref,smask=item[:2]
    pix=fitz.Pixmap(doc,xref)
    if smask: pix=fitz.Pixmap(pix,fitz.Pixmap(doc,smask))
    name=f'asset-{i+1:02}.png'
    pix.save(dest/name)
    manifest.append({'file':name,'xref':xref,'size':[pix.width,pix.height],'rects':[list(r) for r in p.get_image_rects(xref)]})
(root/'design-audit/assets.json').write_text(json.dumps(manifest,indent=2))
sheet=Image.new('RGB',(1000,((len(manifest)+3)//4)*220),'#edf5f9')
draw=ImageDraw.Draw(sheet)
for i,m in enumerate(manifest):
    im=Image.open(dest/m['file']).convert('RGB'); im.thumbnail((240,185))
    x=(i%4)*250;y=(i//4)*220
    sheet.paste(im,(x,y));draw.text((x+4,y+190),m['file']+' '+str(m['size']),fill='black')
sheet.save(root/'design-audit/asset-sheet.jpg')
for i in range(12):
    x=40+i*1600
    clip=fitz.Rect(x,80,x+1440,8000 if i==0 else 3000)
    p.get_pixmap(matrix=fitz.Matrix(.65,.65),clip=clip).save(root/f'design-audit/desktop-page-{i}.png')
    text=p.get_text(clip=fitz.Rect(x,80,x+1440,8080))
    (root/f'design-audit/page-{i}.txt').write_text(text,encoding='utf-8')
print(json.dumps(manifest))
