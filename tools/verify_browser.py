import sys, json
from pathlib import Path
root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(root / '.tools/python'))
from playwright.sync_api import sync_playwright

out = root / 'design-audit/browser'
out.mkdir(parents=True, exist_ok=True)
results=[]
with sync_playwright() as p:
    browser = p.chromium.launch(executable_path='C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', headless=True, args=['--disable-gpu'])
    page = browser.new_page(viewport={'width':1440,'height':1000}, device_scale_factor=1)
    errors=[]
    page.on('pageerror', lambda error: errors.append(str(error)))
    for width in [1440,768,390]:
        page.set_viewport_size({'width':width,'height':1000})
        response=page.goto('http://127.0.0.1:8000/',wait_until='networkidle',timeout=90000)
        page.evaluate('document.fonts.ready')
        page.evaluate("async () => { const imgs=[...document.querySelectorAll('img[src]')]; imgs.forEach(i=>i.loading='eager'); await Promise.all(imgs.map(i=>i.decode().catch(()=>{}))); }")
        page.screenshot(path=str(out/f'home-{width}.png'),full_page=True)
        results.append({'path':'/','width':width,'status':response.status,'overflow':page.evaluate('document.documentElement.scrollWidth > innerWidth'),'brokenImages':page.locator('img[src]').evaluate_all('(imgs)=>imgs.filter(i=>i.getClientRects().length && (!i.complete||!i.naturalWidth)).map(i=>i.src)'),'height':page.evaluate('document.body.scrollHeight')})
    page.set_viewport_size({'width':1440,'height':1000})
    for path in ['tentang-kami','program','fasilitas','guru-staf','galeri','kontak','pendaftaran/1','admin/login']:
        response=page.goto('http://127.0.0.1:8000/'+path,wait_until='networkidle',timeout=90000)
        page.evaluate("async () => { const imgs=[...document.querySelectorAll('img[src]')]; imgs.forEach(i=>i.loading='eager'); await Promise.all(imgs.map(i=>i.decode().catch(()=>{}))); }")
        page.screenshot(path=str(out/(path.replace('/','-')+'.png')),full_page=True)
        results.append({'path':path,'status':response.status,'title':page.title(),'overflow':page.evaluate('document.documentElement.scrollWidth > innerWidth')})
    browser.close()
(out/'report.json').write_text(json.dumps({'pages':results,'jsErrors':errors},indent=2),encoding='utf-8')
print(json.dumps({'pages':results,'jsErrors':errors},indent=2))
