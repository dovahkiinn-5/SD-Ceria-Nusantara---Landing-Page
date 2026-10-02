from pathlib import Path
from PIL import Image

root = Path(__file__).resolve().parents[1]
assets = root / 'public/assets/design'
# Split the six original portraits in the PDF's embedded image, preserving the source pixels.
with Image.open(assets / 'asset-06.png') as portraits:
    for i in range(6):
        x, y = (i % 3) * 512, (i // 3) * 512
        portraits.crop((x, y, x + 512, y + 512)).save(assets / f'teacher-{i + 1}.png')
print('Six original portrait assets prepared.')
