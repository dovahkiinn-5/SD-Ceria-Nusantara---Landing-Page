import sys,json
from pathlib import Path
root=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(root/'.tools/python'))
from playwright.sync_api import sync_playwright
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path='C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless=True)
    page=browser.new_page(viewport={'width':1440,'height':1120})
    page.goto('http://127.0.0.1:8000/',wait_until='networkidle')
    page.evaluate('document.fonts.ready')
    page.screenshot(path=str(root/'design-audit/browser/desktop-top.png'))
    print(json.dumps(page.locator('main>section, .school-stats, .stats-note, .whatsapp-row, .site-footer').evaluate_all("els=>els.map(e=>({class:e.className,y:e.getBoundingClientRect().y,height:e.getBoundingClientRect().height}))"),indent=2))
    browser.close()
