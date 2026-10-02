import sys
from pathlib import Path
root=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(root/'.tools/python'))
import pymupdf as fitz

page=fitz.open('E:/figma elementary school design/contoh Full Pages.pdf')[0]
drawings=page.get_drawings()
def svg_path(drawing):
    parts=[];last=None
    for item in drawing['items']:
        if item[0] in ('l','c'):
            start=item[1]
            if last!=start: parts.append(f'M {start.x:g},{start.y:g}')
            if item[0]=='l':
                end=item[2];parts.append(f'L {end.x:g},{end.y:g}');last=end
            else:
                parts.append('C '+' '.join(f'{p.x:g},{p.y:g}' for p in item[2:]));last=item[-1]
        elif item[0]=='re':
            r=item[1];parts.append(f'M{r.x0:g},{r.y0:g} H{r.x1:g} V{r.y1:g} H{r.x0:g} Z');last=None
    if drawing['closePath']:parts.append('Z')
    color='#'+''.join(f'{round(c*255):02x}' for c in drawing['fill'])
    return '<path d="'+' '.join(parts)+'" fill="'+color+'" fill-opacity="'+str(drawing['fill_opacity'])+'"/>'

# Original decorative paths, with source geometry and opacity preserved.
svg='<svg xmlns="http://www.w3.org/2000/svg" width="1296" height="506" viewBox="0 0 1296 506"><g transform="translate(-112 -188)">'+''.join(svg_path(drawings[i]) for i in [17,18,19,20])+'</g></svg>'
(root/'public/assets/design/hero-decoration.svg').write_text(svg,encoding='utf-8')
print('Exported original hero decoration paths.')

wave='<svg xmlns="http://www.w3.org/2000/svg" width="1296" height="440" viewBox="0 0 1296 440"><g transform="translate(-1712 -192)">'+svg_path(drawings[373])+'</g></svg>'
(root/'public/assets/design/page-hero-decoration.svg').write_text(wave,encoding='utf-8')
whatsapp='<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30"><g transform="translate(-1219 -848)">'+''.join(svg_path(drawings[i]) for i in [356,357])+'</g></svg>'
(root/'public/assets/design/whatsapp.svg').write_text(whatsapp,encoding='utf-8')
print('Exported original page hero wave and WhatsApp icon.')
