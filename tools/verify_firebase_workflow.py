"""Live, reversible Firestore verification. Requires the local Laravel server.

Only this run's uniquely marked registrations/visits are deleted. The temporary
CMS edit is restored in finally, including when an assertion fails. Credentials
are read privately and are never written to reports or console output.
"""
import datetime
import json
import re
import secrets
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / '.tools/python'))
import pymupdf
from playwright.sync_api import sync_playwright, expect

BASE = 'http://127.0.0.1:8000'
PHP = 'C:/xampp/php/php.exe'
OUT = ROOT / 'design-audit/browser'
OUT.mkdir(parents=True, exist_ok=True)
MARKER = 'Uji Firebase ' + secrets.token_hex(6)
REPORT = {'marker': MARKER, 'checks': [], 'cleanupErrors': [], 'passed': False}


def read_cloud(action, **arguments):
    completed = subprocess.run(
        [PHP, str(ROOT / 'tools/inspect_firebase_verification.php')],
        input=json.dumps({'action': action, **arguments}), text=True,
        encoding='utf-8', capture_output=True, cwd=ROOT, timeout=100,
    )
    try:
        result = json.loads(completed.stdout)
    except ValueError:
        raise AssertionError('Read-only cloud verifier did not return valid JSON') from None
    assert completed.returncode == 0 and result.get('ok'), (
        'Read-only cloud verifier failed: ' + result.get('errorType', 'unknown')
    )
    return result['result']


def save_report():
    (OUT / 'firebase-workflow-report.json').write_text(
        json.dumps(REPORT, indent=2, ensure_ascii=False), encoding='utf-8'
    )


credentials = (ROOT / 'storage/app/private/local-admin.txt').read_text(encoding='utf-8')
email = re.search(r'^Email: (.+)$', credentials, re.MULTILINE).group(1).strip()
password = re.search(r'^Password: (.+)$', credentials, re.MULTILINE).group(1).strip()
fixture = OUT / ('firebase-document-' + MARKER.rsplit(' ', 1)[-1] + '.pdf')
document = pymupdf.open()
document.new_page().insert_text((72, 72), 'Dokumen uji koneksi Firestore; bukan dokumen pendaftar.')
document.save(fixture)
document.close()

initial_state = None
initial_content = None
cms_attempted = False
reference = None
visit_id = None
failure = None

with sync_playwright() as playwright:
    browser = playwright.chromium.launch(
        executable_path='C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
        headless=True,
    )
    context = browser.new_context(viewport={'width': 1440, 'height': 1000}, accept_downloads=True)
    page = context.new_page()
    page.set_default_timeout(30000)
    page.set_default_navigation_timeout(90000)
    page.on('dialog', lambda dialog: dialog.accept())

    def login():
        page.goto(BASE + '/admin')
        if '/admin/login' in page.url:
            page.locator('[name=email]').fill(email)
            page.locator('[name=password]').fill(password)
            page.get_by_role('button', name='Masuk', exact=True).click()
        expect(page).to_have_url(BASE + '/admin')

    def set_description(value):
        page.goto(BASE + '/admin/konten/home')
        page.locator('[name="values[description]"]').fill(value)
        page.get_by_role('button', name='Simpan konten', exact=True).click()
        expect(page.locator('[role=status]')).to_contain_text('Konten berhasil disimpan')

    try:
        initial_state = read_cloud('state')
        REPORT['initialState'] = initial_state
        assert initial_state['driver'] == 'firestore'
        assert initial_state['project'] == 'sd-ceria-nusantara'
        assert initial_state['cloudCounts']['content'] == 8
        assert read_cloud('admin') == {'exists': True, 'active': True, 'passwordMatches': True, 'role': 'owner'}
        login()
        REPORT['checks'].append('Admin login uses the account and password hash read directly from Firestore')

        initial_content = read_cloud('content')
        assert initial_content['exists'] and isinstance(initial_content['description'], str)
        page.goto(BASE + '/admin/konten/home')
        expect(page.locator('[name="values[description]"]')).to_have_value(initial_content['description'])
        cms_attempted = True
        set_description(MARKER)
        assert read_cloud('content')['description'] == MARKER
        public = context.new_page()
        public.goto(BASE + '/', wait_until='networkidle')
        expect(public.get_by_text(MARKER, exact=True)).to_be_visible()
        public.close()
        set_description(initial_content['description'])
        assert read_cloud('content')['description'] == initial_content['description']
        cms_attempted = False
        REPORT['checks'].append('A real CMS edit reached Firestore and the public page; the original text was restored')

        page.goto(BASE + '/pendaftaran/1', wait_until='networkidle')
        page.locator('[name=child_name]').fill(MARKER)
        page.locator('[name=nickname]').fill('Uji')
        page.locator('[name=birth_date]').fill('2019-04-10')
        page.locator('[name=gender]').select_option('Perempuan')
        page.get_by_role('button', name='Lanjutkan', exact=True).click()
        expect(page).to_have_url(BASE + '/pendaftaran/2')
        page.locator('[name=parent_name]').fill(MARKER + ' Wali')
        page.locator('[name=relationship]').select_option('Ibu')
        page.locator('[name=phone]').fill('081234567890')
        page.locator('[name=email]').fill('firebase-test@example.test')
        page.locator('[name=address]').fill('Alamat pengujian otomatis Firebase')
        page.get_by_role('button', name='Lanjutkan', exact=True).click()
        expect(page).to_have_url(BASE + '/pendaftaran/3')
        for field in ['birth_certificate', 'family_card']:
            page.locator('[name=' + field + ']').set_input_files(str(fixture))
        page.locator('[name=photo]').set_input_files(str(ROOT / 'public/assets/design/asset-02.png'))
        page.get_by_role('button', name='Lanjutkan', exact=True).click()
        expect(page).to_have_url(BASE + '/pendaftaran/4')
        expect(page.locator('.review-block').first).to_contain_text(MARKER)
        page.locator('[name=consent]').check()
        page.get_by_role('button', name='Kirim Formulir', exact=True).click()
        expect(page).to_have_url(BASE + '/pendaftaran/berhasil')
        reference = page.locator('.registration-number strong').inner_text().strip()
        REPORT['registrationReference'] = reference
        saved = read_cloud('record', collection='applications', id=reference, marker=MARKER)
        assert saved == {'exists': True, 'markerMatches': True, 'status': 'baru', 'documentCount': 3, 'hasConsent': True}
        REPORT['checks'].append('The four-step registration created a Firestore application with consent and three private file references')

        guest = browser.new_context()
        response = guest.request.get(BASE + '/admin/berkas/' + reference + '/photo', max_redirects=0)
        assert response.status == 302 and '/admin/login' in response.headers['location']
        response = guest.request.post(BASE + '/pendaftaran/kirim', form={'consent': '1'})
        assert response.status == 419
        guest.close()
        page.goto(BASE + '/admin/data/applications/' + reference)
        expect(page.locator('.detail-list').first).to_contain_text(MARKER)
        with page.expect_download() as download:
            page.locator('a[href$="/birth_certificate"]').click()
        assert download.value.suggested_filename == fixture.name
        page.locator('[name=status]').select_option('diterima')
        page.locator('[name=admin_notes]').fill(MARKER + '; data uji akan dihapus.')
        page.get_by_role('button', name='Simpan perubahan', exact=True).click()
        assert read_cloud('record', collection='applications', id=reference, marker=MARKER)['status'] == 'diterima'
        response = page.request.get(BASE + '/admin/pendaftar/export')
        assert response.status == 200 and reference in response.text()
        REPORT['checks'].append('The admin reviewed the Firestore application, downloaded its private document, updated its status and exported it')

        page.goto(BASE + '/kontak#kunjungan')
        page.locator('[data-visit-toggle]').click()
        form = page.locator('#visit-form')
        form.locator('[name=name]').fill(MARKER)
        form.locator('[name=phone]').fill('081234567890')
        form.locator('[name=email]').fill('firebase-test@example.test')
        day = datetime.date.today() + datetime.timedelta(days=8)
        while day.weekday() > 4:
            day += datetime.timedelta(days=1)
        form.locator('[name=date]').fill(day.isoformat())
        form.locator('[name=notes]').fill(MARKER + '; data uji akan dihapus.')
        form.locator('[name=consent]').check()
        form.get_by_role('button', name='Ajukan Kunjungan').click()
        expect(page).to_have_url(BASE + '/kunjungan/berhasil')
        visits = read_cloud('fixtures', marker=MARKER)['visits']
        assert len(visits) == 1
        visit_id = visits[0]
        REPORT['visitId'] = visit_id
        saved = read_cloud('record', collection='visits', id=visit_id, marker=MARKER)
        assert saved['exists'] and saved['markerMatches'] and saved['hasConsent'] and saved['status'] == 'baru'
        page.goto(BASE + '/admin/data/visits/' + visit_id)
        page.locator('[name=status]').select_option('dikonfirmasi')
        page.get_by_role('button', name='Simpan perubahan', exact=True).click()
        assert read_cloud('record', collection='visits', id=visit_id, marker=MARKER)['status'] == 'dikonfirmasi'
        REPORT['checks'].append('The visit request and admin confirmation were saved in Firestore')
    except Exception as error:
        failure = error
        REPORT['errorType'] = type(error).__name__
        # Do not record exception bodies: a browser diagnostic could include a password field.
    finally:
        if cms_attempted and initial_content:
            try:
                login()
                set_description(initial_content['description'])
                assert read_cloud('content')['description'] == initial_content['description']
                cms_attempted = False
            except Exception as error:
                REPORT['cleanupErrors'].append('CMS restore: ' + type(error).__name__)
        try:
            fixtures = read_cloud('fixtures', marker=MARKER)
            if any(fixtures.values()):
                login()
            for collection, identifiers in fixtures.items():
                for identifier in identifiers:
                    assert read_cloud('record', collection=collection, id=identifier, marker=MARKER)['markerMatches']
                    page.goto(BASE + '/admin/data/' + collection + '/' + identifier)
                    page.get_by_role('button', name='Hapus permanen', exact=True).click()
                    expect(page).to_have_url(BASE + '/admin/data/' + collection)
                    assert not read_cloud('record', collection=collection, id=identifier, marker=MARKER)['exists']
                    if collection == 'applications':
                        folder = ROOT / 'storage/app/private/applications' / identifier
                        assert not folder.exists() or not any(path.is_file() for path in folder.rglob('*'))
            assert read_cloud('fixtures', marker=MARKER) == {'applications': [], 'visits': []}
            REPORT['testDataCleaned'] = True
        except Exception as error:
            REPORT['testDataCleaned'] = False
            REPORT['cleanupErrors'].append('Fixture cleanup: ' + type(error).__name__)
        try:
            login()
            page.get_by_role('button', name='Keluar', exact=True).click()
            expect(page).to_have_url(BASE + '/admin/login')
            REPORT['logoutVerified'] = True
        except Exception as error:
            REPORT['cleanupErrors'].append('Logout: ' + type(error).__name__)
        browser.close()
        try:
            final_content = read_cloud('content')
            REPORT['contentRestoredExactly'] = bool(initial_content and final_content == initial_content)
            final_state = read_cloud('state')
            REPORT['finalState'] = final_state
            REPORT['sqliteUnchanged'] = bool(initial_state and all(
                initial_state[key] == final_state[key] for key in ['sqliteCount', 'sqliteFingerprint']
            ))
            REPORT['cloudCountsRestored'] = bool(initial_state and initial_state['cloudCounts'] == final_state['cloudCounts'])
        except Exception as error:
            REPORT['cleanupErrors'].append('Final independent verification: ' + type(error).__name__)
        REPORT['passed'] = (failure is None and not REPORT['cleanupErrors']
            and REPORT.get('testDataCleaned') and REPORT.get('contentRestoredExactly')
            and REPORT.get('sqliteUnchanged') and REPORT.get('cloudCountsRestored')
            and REPORT.get('logoutVerified'))
        save_report()
        print(json.dumps(REPORT, indent=2, ensure_ascii=False))

sys.exit(0 if REPORT['passed'] else 1)
