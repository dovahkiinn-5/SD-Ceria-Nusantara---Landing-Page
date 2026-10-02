import sys, json
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / '.tools/python'))
import fitz

root = Path(__file__).resolve().parents[1]
out = root / 'design-audit'
out.mkdir(exist_ok=True)
for path in Path('E:/figma elementary school design').glob('*.pdf'):
    doc = fitz.open(path)
    print(path.name, 'pages=', len(doc))
    pages = []
    for i, page in enumerate(doc):
        name = path.stem + '-' + str(i + 1)
        scale = min(1.0, 1400 / page.rect.width)
        page.get_pixmap(matrix=fitz.Matrix(scale, scale)).save(out / (name + '.png'))
        (out / (name + '.txt')).write_text(page.get_text(), encoding='utf-8')
        blocks = page.get_text('dict')
        for b in blocks['blocks']:
            b.pop('image', None)
            b.pop('mask', None)
        (out / (name + '.json')).write_text(json.dumps(blocks, ensure_ascii=False, indent=2), encoding='utf-8')
        pages.append({'page': i + 1, 'width': page.rect.width, 'height': page.rect.height, 'images': len(page.get_images()), 'fonts': page.get_fonts()})
    (out / (path.stem + '-metadata.json')).write_text(json.dumps(pages, indent=2), encoding='utf-8')
    print(json.dumps(pages))
