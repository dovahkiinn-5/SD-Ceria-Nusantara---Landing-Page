# Firebase connection and website data mapping — team update

Use this document to connect another website to the existing school database or to diagnose its connection. The source application is Laravel 12 / PHP 8.2+. A Node.js server example is included for a different stack.

For **which website section reads which Firebase document/field**, start at [Website-to-Firestore mapping](#51-website-to-firestore-mapping). The latest teacher directory and the homepage teacher preview now have different data sources; see [Teacher and staff data](#52-teacher-and-staff-data-after-the-team-update).

Reviewed on **17 October 2026 (Asia/Jakarta)**, including fetched remote changes, local dashboard work in progress, and a fresh read-only inspection of Firestore collection counts and schemas. No database records were changed for this guide.

| Source reviewed | Version / scope |
| --- | --- |
| Landing page, latest remote branch `origin/feature/site-refresh-hero-controls` | `a68b4bf` — `new revisii`, 16 October 2026, 22:39 WIB |
| Landing page, local checkout in `E:\Landing Page Website` | `a68b4bf`; matches the fetched upstream |
| Admin dashboard in `E:\Admin Dashboard SDCN`, branch `main2-juan` | `4b8114d` — `forgot pass updt 2`; matches fetched upstream, plus local modified/untracked account-management changes |
| Shared Firestore | Project `sd-ceria-nusantara`, database `(default)`; collection inventory and field shapes read directly |

Mappings below describe the inspected source and live database schema, not a claim that either version is deployed or that every workflow has been run. No branch was merged and no application code was edited for this review. Dashboard account changes labeled **local work in progress** are present on this machine but uncommitted; they may differ from teammates' deployed copies. Unpushed changes on other machines are outside this snapshot. This guide covers the landing page and the separate admin dashboard; no additional portal integration is assumed.

**Changes that matter for database wiring:**

- `/guru-staf` renders **30 fictional profiles, 9 subject cards, and the full organization structure from a local PHP file**, not from Firestore. It now supports individual photo overrides and calculated group counts.
- The homepage still reads the **6-entry `content/teachers.team` array**. The dashboard's operational staff screen reads a third source: `dashboard_records` filtered by `kind = staff` (4 records observed).
- The dashboard has a shared content draft/publish workflow, student and financial records, admission activation, and optional Firebase Authentication integration. Those connections are mapped in sections 5.6–5.9.
- Landing registration now requires **four attachments**, including KIA (`child_identity_card`), and optionally saves domicile coordinates plus server-calculated distance. The separate dashboard PPDB form still requests three attachments. See section 5.8.
- `/pengumuman` reads `applications/{reference}` and the child's birth date to display the result; accepted applicants receive the configured WhatsApp group link. Contact-map and distance settings have separate sources. See sections 5.10–5.11.
- Dashboard local work in progress adds Firebase email-verification gating, contact-phone/profile updates, and account-deletion state. These are labeled in section 5.9.

## 1. Exact database settings

| Setting | Value |
| --- | --- |
| Firebase project ID | `sd-ceria-nusantara` |
| Database product | Cloud Firestore |
| Database ID | `(default)` |
| Known document for a read check | `content/home` |
| Credential type used by this application | Server-side Google service account JSON |
| REST OAuth scope | `https://www.googleapis.com/auth/datastore` |

[Open Firestore Console](https://console.firebase.google.com/project/sd-ceria-nusantara/firestore/databases/-default-/data).

Document resource name:

```text
projects/sd-ceria-nusantara/databases/(default)/documents/{collection}/{documentId}
```

REST URL for the known document:

```text
https://firestore.googleapis.com/v1/projects/sd-ceria-nusantara/databases/(default)/documents/content/home
```

This database uses collections and documents. A Realtime Database `databaseURL`, SQL connection string, or browser Firebase API key does not replace the server authentication used here. The REST API accepts a service-account OAuth access token in `Authorization: Bearer <access_token>`; the REST response uses typed Firestore fields. [Firestore REST authentication and format](https://firebase.google.com/docs/firestore/use-rest-api).

Use these project values only if the other website should access this same database. For a separate website/database, substitute its project ID, database ID, credentials, and document paths together.

## 2. Credentials and permissions

The working installation reads its private JSON from:

```text
E:\Landing Page Website\storage\app\private\firebase-service-account.json
```

The key itself is intentionally absent from this document. Configure it privately on the target server, outside its public directory and Git repository. A new website can use its own authorized service account for this project. Its credential JSON must have a valid `type`, `project_id`, `client_email`, and `private_key`; this application's validator requires `project_id` to equal `FIREBASE_PROJECT_ID`.

For normal database reads and writes, the service account needs an appropriate project IAM role such as `roles/datastore.user`. Server libraries use IAM and bypass Firestore Security Rules, so server routes must enforce the website's own authentication and authorization. [Firestore IAM roles](https://firebase.google.com/docs/firestore/security/iam).

The source application supports either:

- `FIREBASE_CREDENTIALS`: a readable absolute path to the JSON on that server.
- `FIREBASE_CREDENTIALS_JSON_BASE64`: Base64 encoding of the entire JSON, stored as a private server environment variable. This takes precedence over the file path when nonempty. Base64 is encoding, not encryption.

The current Laravel implementation reads these custom variables directly. It does not automatically discover credentials from `GOOGLE_APPLICATION_CREDENTIALS` or a Firebase CLI login.

Do not put the credential JSON or Base64 value in browser code or variables prefixed with `VITE_` or `NEXT_PUBLIC_`. [Firebase server SDK setup](https://firebase.google.com/docs/admin/setup).

## 3. Laravel connection settings

For a copy of this application, use:

```dotenv
SCHOOL_STORE=firestore
FIREBASE_PROJECT_ID=sd-ceria-nusantara
FIREBASE_DATABASE_ID="(default)"
FIREBASE_CREDENTIALS="/absolute/server/path/firebase-service-account.json"
SESSION_DRIVER=firestore
CACHE_STORE=firestore
QUEUE_CONNECTION=sync
```

Alternatively, omit the credential path and configure `FIREBASE_CREDENTIALS_JSON_BASE64` through the hosting provider's private environment settings. Remove any stale Base64 value if you intend to use the file instead.

For a second, independent installation sharing this database, give its runtime data separate namespaces:

```dotenv
SESSION_COOKIE=other_school_website_session
CACHE_PREFIX=other_school_website_cache_
```

These separate session/cache records; application records such as `content` and `applications` remain shared. Keep the target website's own `APP_KEY` stable. Set `APP_URL` to its URL and use `SESSION_SECURE_COOKIE=true` when served over HTTPS.

The `firestore` drivers and `SCHOOL_STORE` are custom application features, not built-in Laravel drivers. Adding these variables alone to an unrelated Laravel project will not create a Firestore integration.

Source implementation files:

| File | Responsibility |
| --- | --- |
| `config/school.php` | Maps Firebase environment variables |
| `app/Services/FirebaseCredentials.php` | Validates credentials, obtains OAuth tokens, sets TLS options |
| `app/Contracts/DocumentStore.php` | Application document-store interface |
| `app/Services/FirestoreDocumentStore.php` | REST reads/writes and Firestore field encoding |
| `app/Providers/AppServiceProvider.php` | Registers document, session, cache, and auth providers |
| `app/Services/FirestoreSessionHandler.php` | Firestore session storage |
| `app/Services/FirestoreCacheStore.php` and `FirestoreCacheLock.php` | Cache and lock storage |
| `app/Auth/DocumentUserProvider.php` | Laravel login against `admins` documents |
| `app/Services/FirestoreFileStorage.php` | Private uploaded-file metadata and chunks |
| `routes/console.php` | Read-only `school:firebase-check` command |

The source uses Composer package `google/auth` (`^1.55`) for OAuth and Laravel's HTTP client for Firestore REST calls.

After configuring a copy of this project, run from its root:

```text
php artisan config:clear
php artisan school:firebase-check
```

Expected output:

```text
Koneksi dan autentikasi Firestore berhasil.
```

On the original Windows computer, PHP is available at `C:\xampp\php\php.exe`:

```powershell
& 'C:\xampp\php\php.exe' artisan config:clear
& 'C:\xampp\php\php.exe' artisan school:firebase-check
```

The check reads Firestore and does not seed or migrate records. Restart the target server or redeploy after changing hosting environment variables.

## 4. Node.js server connection check

For a Node.js website, use the Firebase Admin SDK in its server runtime. Use a Node version supported by the installed SDK; the current setup documentation lists Node.js 22+. [Installation and runtime requirements](https://firebase.google.com/docs/admin/setup).

Install in the target project:

```sh
npm install firebase-admin
```

Create a private, Git-ignored `.env.firebase-check` file:

```dotenv
FIREBASE_PROJECT_ID=sd-ceria-nusantara
FIREBASE_DATABASE_ID="(default)"
FIREBASE_CREDENTIALS="/absolute/server/path/firebase-service-account.json"
```

For Windows, use forward slashes in the credential path, for example `E:/Landing Page Website/storage/app/private/firebase-service-account.json` when running on the original computer. Alternatively, supply `FIREBASE_CREDENTIALS_JSON_BASE64` privately and omit the path.

Save the following as `check-firestore.mjs` in the target project. It only reads `content/home` and prints identifiers and existence, not document contents:

```js
import { readFileSync } from 'node:fs';
import { cert, initializeApp, deleteApp } from 'firebase-admin/app';
import { getFirestore } from 'firebase-admin/firestore';

let app;
try {
  const projectId = process.env.FIREBASE_PROJECT_ID;
  const databaseId = process.env.FIREBASE_DATABASE_ID || '(default)';
  const encoded = process.env.FIREBASE_CREDENTIALS_JSON_BASE64;
  const credentialPath = process.env.FIREBASE_CREDENTIALS;

  if (!projectId || (!encoded && !credentialPath)) {
    throw new Error('Set FIREBASE_PROJECT_ID and a server credential source.');
  }
  if (databaseId !== '(default)') {
    throw new Error('This check targets the existing (default) database.');
  }
  if (process.env.FIRESTORE_EMULATOR_HOST) {
    throw new Error('Remove FIRESTORE_EMULATOR_HOST to verify the cloud database.');
  }

  const json = encoded
    ? Buffer.from(encoded, 'base64').toString('utf8')
    : readFileSync(credentialPath, 'utf8');
  const serviceAccount = JSON.parse(json);
  if (serviceAccount.project_id !== projectId) {
    throw new Error('Credential project_id does not match FIREBASE_PROJECT_ID.');
  }

  app = initializeApp({ projectId, credential: cert(serviceAccount) });
  const db = getFirestore(app); // Uses the (default) database.
  const snapshot = await db.doc('content/home').get();
  console.log({ projectId, databaseId, path: snapshot.ref.path, exists: snapshot.exists });
  if (!snapshot.exists) process.exitCode = 1;
} catch (error) {
  console.error('Firestore check failed:', error.code ?? error.name, error.message);
  process.exitCode = 1;
} finally {
  if (app) await deleteApp(app);
}
```

Run locally with the private env file:

```sh
node --env-file=.env.firebase-check check-firestore.mjs
```

When the hosting platform already injects these variables, use `node check-firestore.mjs`. Success should report `exists: true`. An `exists: false` result means the request succeeded but that document was absent in the selected project/database.

This example is provided for the target website and has not been executed there. It uses the default-database API documented in the [Admin SDK Firestore reference](https://firebase.google.com/docs/reference/admin/node/firebase-admin.firestore). In a long-running application, initialize the Admin app once and reuse it; the cleanup above is for a one-off diagnostic script. Serve browser requests through authorized backend routes.

## 5. Collection and document map

Counts below are the read snapshot taken on 17 October 2026, not fixed requirements. The queries were sequential, so runtime counts are not an atomic database snapshot.

| Collection | Observed count | Document ID / purpose |
| --- | ---: | --- |
| `content` | 8 | Fixed page/section IDs listed below |
| `admins` | 4 | SHA-256 of trimmed, lowercased email; Laravel admin accounts |
| `applications` | 9 | Landing references use `CN-{year}-{last 10 ULID characters}`; dashboard public PPDB uses `PPDB-{year}-{10 random characters}`; preserve existing IDs |
| `visits` | 1 | ULID; school visit requests |
| `uploaded_files` | 26 | SHA-256 of normalized file path; file metadata |
| `uploaded_file_chunks` | 30 | SHA-256 of `path:index`; zero-based file chunks |
| `runtime_sessions` | 51 | Hashed session namespace and session ID |
| `runtime_cache` | 31 | Hashed cache namespace/key |
| `runtime_locks` | 0 | Cache-lock records when created |
| `dashboard_records` | 57 | Operational school records; filter by top-level `kind` |
| `dashboard_legacy_content` | 7 | Preserved legacy content imported from the old dashboard; not the published landing content |
| `dashboard_runtime` | 1 | `workflows` document with a numeric `revision` used by dashboard transactions |
| `school_payments` | 3 | Payment records linked by `invoice_id` |
| `dashboard_content_drafts` | 0 | Supported by dashboard code; no draft documents at inspection |
| `school_documents` | 0 | Supported by dashboard code; no standalone document records at inspection |
| `admission_activations` | 0 | Supported by dashboard code; no activation-link records at inspection |

Existing content documents:

```text
content/about
content/contact
content/facilities
content/gallery
content/home
content/program
content/settings
content/teachers
```

Selected schema details from the source application:

- `content/home`: `title`, `description`, `eyebrow`, `image`, `programs`, `advantages`, `testimonials`, `faq`, `values`, and profile/principal fields.
- `content/settings`: school name, tagline, logo, address, email, WhatsApp, hours, admission period, fee, terms, privacy, and other school settings.
- `admins`: `name`, `email`, `password`, `role`, `active`, `created_at`; the dashboard also supports `firebase_uid`, `updated_at`, remember-login fields, `photo_path`, and local-work-in-progress fields `phone` and `deletion_pending`. Firebase `emailVerified` is read from Firebase Authentication, not treated as a stored Firestore flag. The landing page expects a Laravel password hash; Firebase-authenticated dashboard accounts may instead have an empty `password`. See section 5.9 before assuming login compatibility.
- `applications`: landing submissions include `reference`, `submission_hash`, `child`, `parent`, `documents`, `status`, `notes`, `consent_at`, `created_at`. Dashboard-managed records can also include `dashboard`, `admin_notes`, and `updated_at`; dashboard-created submissions need not have the landing page's `submission_hash`. Current landing `documents` contains `birth_certificate`, `family_card`, `child_identity_card`, and `photo`, each with `path`, `name`, `size`; older/dashboard submissions may contain only three entries and optional `mime`. Optional `parent.domicile_latitude`, `.domicile_longitude`, and `.domicile_distance_km` were found in live documents. Preserve unknown dashboard fields such as `appointment` when updating these shared records.
- `visits`: `name`, `phone`, `email`, `date`, optional `notes`, `consent`, `status`, `created_at`.
- `uploaded_files`: `path`, `content_type`, `size`, `chunk_count`, `created_at`.
- `uploaded_file_chunks`: `file_id`, integer `index`, and Base64 `data`.

The source writes `created_at` as an ISO 8601 string. It uses that field when ordering admin, registration, and visit records. Its REST adapter supplies an `id` property from the document name when reading; `id` is not necessarily a stored Firestore field. A Node SDK consumer should use `snapshot.id` separately from `snapshot.data()`.

Uploads use this application's custom Firestore format: at most 1 MiB per file, split into 512 KiB chunks. File paths in registration records refer to those documents, not Firebase Storage download URLs. Session/cache payloads use PHP serialization and should not be treated as portable Node.js sessions.

### 5.1 Website-to-Firestore mapping

These mappings were traced through the reviewed versions' routes, Blade templates, components, and controllers. Landing-page behavior below describes `a68b4bf`; dashboard account changes are labeled where they are local work in progress.

Read a path such as `content/teachers` as **collection `content` -> document ID `teachers`**. Fields such as `team` live inside that document. In the tables, `[]` means every array entry and `[0]` means the first entry; these are explanatory JavaScript-style expressions, not additional Firestore document paths.

| Website page | Main data source | Other data used on the page |
| --- | --- | --- |
| Homepage `/` | `content/home` | `content/settings`, `content/facilities`, `content/teachers` |
| About `/tentang-kami` | `content/about` | `content/settings`, `content/home` for the principal's name |
| Programs `/program` | `content/program` | `content/settings` for shared site layout |
| Facilities `/fasilitas` | `content/facilities` | `content/settings` for the facility mosaic image and layout |
| Teachers and staff `/guru-staf` | Local `resources/content/staff-directory.php` for `people`, `subjects`, `structure`; `content/teachers.values` for values cards | Shared settings, local photo assets/overrides, hardcoded hero copy |
| News/gallery `/galeri` | `content/gallery` | `content/settings` for shared site layout |
| Contact `/kontak` | `content/contact` | `content/settings` for contact details and map address |
| Privacy `/privasi` | `content/settings` | Field `privacy` supplies the page text |
| Terms `/syarat-ketentuan` | `content/settings` | Field `terms` supplies the page text |
| Admission results `/pengumuman` | `applications/{reference}` | `child.birth_date`, `child.child_name`, `status`; `WHATSAPP_GROUP_URL` for accepted result, shared contact settings |

All public pages also use `content/home.description` for the layout's meta description. The following sections distinguish page-specific data from shared fields.

### 5.2 Teacher and staff data after the team update

There are now **three separate staff representations**. They are not automatically synchronized.

| Where information appears | Actual source | Profile fields | Observed size |
| --- | --- | --- | ---: |
| Public directory `/guru-staf`, commit `a68b4bf` | Local PHP file `resources/content/staff-directory.php`, array `people` | `name`, `role`, `group`, `description`, `portrait`, optional `photo` | 30 fictional profiles |
| Homepage teacher preview and principal portrait | Firestore document `content/teachers`, array `team` | `name`, `role`, `photo` | 6 entries |
| Separate dashboard `/admin/guru` | Firestore collection `dashboard_records`, documents with `kind = "staff"` | `data.name`, `data.position`, `data.assignment`, and other operational fields | 4 records |

Historical versions through `2ec7139` rendered `/guru-staf` from `content/teachers.team`. The current local and remote versions use the file-based directory. A different deployed version may still show the older behavior.

**New directory: local sources and exact keys**

```text
resources/content/staff-directory.php
  people[]
    name             person's display name
    role             job title / teaching role
    group            pimpinan | guru | staf
    description      responsibility / profile description
    portrait         integer index into the shared portrait image
    photo            optional individual image path; overrides the sheet in cards
  subjects[]
    title            subject name
    by               teacher / team display text
    text             subject description
  structure
    head             index into people[]
    branches[]
      title          branch heading
      summary        branch explanation
      tone           CSS color variant (blue | green | yellow)
      lead           index into people[]
      teams[]
        title        team heading
        members[]    indices into people[]
```

The file contains 3 leadership profiles (`pimpinan`), 16 teaching profiles (`guru`), 11 support profiles (`staf`), and 9 subject cards. The source explicitly labels these as fictional examples.

| New directory feature | Source / behavior |
| --- | --- |
| Profile names, job titles, descriptions, category tags | `people[].name`, `.role`, `.description`, `.group` in the local PHP file |
| Subject titles, descriptions, teacher labels | `subjects[].title`, `.text`, `.by` in the local PHP file; `by` is display text, not a teacher-ID relationship |
| Directory and organization portraits | `people[].photo` when set; otherwise local `/assets/team/team-portraits.png` and the integer `portrait` index |
| Portrait rendering | `staff-portrait.blade.php` sets x = `(portrait % 5) * 25%`, y = `floor(portrait / 5) * 20%`; CSS background size is `500% 600%` |
| Hero portrait | `people[0].portrait`; the hero component call does not pass the individual `photo` override |
| Organization principal | `people[structure.head]`; currently head index `0` |
| Organization branch leads | `people[branch.lead]`; current lead indices `1`, `2`, and `21` |
| Organization individual members | Each `branch.teams[].members[]` index resolves to a `people[]` entry |
| Category filtering | `public/js/team-directory.js` filters rendered cards by `data-team-group`; no Firestore query |
| Search | The same JS searches rendered lowercase name + role + description; no Firestore query |
| Result count and category-button counts | Initial counts are calculated from `people` and `group`; JS recalculates the visible result count when filtering |
| Organization branch/team counts | Calculated from the branch lead and `members` arrays |
| Organization headings and descriptions | Local `structure.branches[].title`, `.summary`, `.teams[].title` |
| Hero text and remaining static facts | Hardcoded in `resources/views/pages/teachers.blade.php`; total profile count is calculated |
| Values cards on the new page | Still reads Firestore `content/teachers.values[]` with `title`, `text`, `icon` |

Reordering `people` changes organization membership unless the indices in `structure` are updated. Portrait indices must stay aligned with the local sheet. Individual photo overrides currently point to `/assets/team/maria-simanjuntak.jpg` and `/assets/team/lina-susanti.png`. Hover/focus/tap interactions in `organization-person.blade.php` and `team-directory.js` reveal local photos; they do not fetch or write Firestore records. There are currently no Firestore document IDs for these 30 profiles, 9 subject cards, or organization branches.

**Existing Firestore teacher fields**

In Firebase Console, open **Firestore Database -> `content` -> `teachers`**:

```text
content/teachers
  team[]             existing homepage profile list
    name
    role
    photo            /assets/design/... or /media/... URL path
  values[]           values cards used by the new directory page
    title
    text
    icon
  eyebrow            retained field; new directory hero does not read it
  title              retained field; new directory hero does not read it
  description        retained field; new directory hero does not read it
  image              retained field; new directory hero does not read it
```

| Visible information | Firestore document | Field |
| --- | --- | --- |
| Homepage responsive teacher names / roles / portraits | `content/teachers` | `team[].name`, `team[].role`, `team[].photo` |
| Homepage principal portrait | `content/teachers` | `team[0].photo` |
| Homepage and About-page principal name | `content/home` | `principal_name` |
| Homepage principal quotation | `content/home` | `principal_quote` |
| About-page principal quotation | `content/about` | `quote` |
| Homepage displayed teacher/staff total | `content/settings` | `teachers` (independent display text) |

Editing a teacher in `/admin/guru`, or changing `content/teachers.team`, does **not** update the new 30-person local directory. Changing `team[0].name` also does not change `content/home.principal_name`.

With the server `db` initialized as shown in section 4, this reads the existing homepage profiles, not the new directory:

```js
const snapshot = await db.doc('content/teachers').get();
if (!snapshot.exists) throw new Error('Missing content/teachers');
const team = snapshot.data().team;
if (!Array.isArray(team)) throw new Error('content/teachers.team must be an array');
const teacherCards = team.map(({ name, role, photo }) => ({ name, role, photo }));
```

The landing editor `/admin/konten/teachers` writes `content/teachers` directly. The separate dashboard editor `/admin/website/teachers/edit` saves a draft that must be published. Both currently edit the existing document, so neither manages the local `people`, `subjects`, or `structure` data. Editor labels such as `team.0.name` are form notation, not Firestore document IDs.

**If the new directory must become editable through Firebase — proposed work, not implemented**

A compatible extension would add `people`, `subjects`, and `structure` to the existing `content/teachers` document, preserving `team` and `values`. Copy the local schema above, update the page to read these fields through `SchoolContent`, provide fallback defaults, and update preview/editing as needed. Preserve the existing calculated counts and index relationships. The editors currently accept image paths under `/assets/design/` or `/media/`, so `/assets/team/` overrides require deliberate asset migration or editor validation changes. None of `people`, `subjects`, or `structure` exists on the inspected Firestore teacher document. Connecting them requires code/data changes; this guide does not perform that migration.

If using operational dashboard staff instead, explicitly map `data.position` / `data.assignment` to the chosen public role and publish approved public profile fields. Dashboard staff photos use private download routes; their `photo_path` is not a ready-to-use public portrait URL.

Source references: the remote `resources/content/staff-directory.php`, `resources/views/pages/teachers.blade.php`, `resources/views/components/staff-portrait.blade.php`, `public/js/team-directory.js`, `public/css/team-directory.css`; unchanged homepage `resources/views/components/team.blade.php` and `resources/views/pages/home.blade.php`; dashboard `app/Support/CloudData.php` and `resources/data/school.json`.

### 5.3 Other visible sections and their exact fields

| Website information | Firestore document | Field(s) |
| --- | --- | --- |
| Page hero label, heading, description, image | `content/{page}` where page is `home`, `about`, `program`, `facilities`, `gallery`, or `contact` | `eyebrow`, `title`, `description`, `image`; the new teacher directory has a custom local hero instead |
| Homepage responsive hero description | `content/home` | `tablet_description` |
| Homepage school introduction | `content/home` | `profile_title`, `profile_subtitle`, `profile_text`; responsive copy: `tablet.profile_text` |
| Homepage values cards | `content/home` | `values[].title`, `values[].text`, `values[].icon` |
| Homepage advantages | `content/home` | `advantages[].title`, `advantages[].text`; responsive cards: `tablet.advantages[]` |
| Homepage program preview | `content/home` | `programs[].title`, `programs[].text`; responsive cards: `tablet.programs[]` |
| Homepage testimonials | `content/home` | `testimonials[].text`, `testimonials[].name` |
| Homepage FAQ | `content/home` | `faq[].question`, `faq[].answer` |
| Homepage responsive admission steps | `content/home` | `tablet.admission[].title`, `tablet.admission[].text`, `tablet.admission[].icon` |
| Student count, teacher count, founding year, accreditation | `content/settings` | `students`, `teachers`, `founded`, `accreditation` |
| Homepage admission period, required age, fee | `content/settings` | `period`, `age`, `fee` |
| About-page story heading and text | `content/about` | `story_title`, `story_subtitle`, `story` |
| About-page vision/mission/values cards | `content/about` | `values[].title`, `values[].text`, `values[].icon` |
| Program-page teaching approach cards | `content/program` | `approach[].title`, `approach[].text`, `approach[].icon` |
| Program-page curriculum | `content/program` | `curriculum[].title`, `curriculum[].text` (text splits into lines) |
| Program-page activity cards | `content/program` | `activities[].title`, `activities[].text`, `activities[].icon` |
| Facility labels on the homepage and facilities page | `content/facilities` | `spaces[]` (array of strings) |
| Facility mosaic image on both pages | `content/settings` | `facilities_image` (one shared image, cropped into tiles) |
| Facility feature cards | `content/facilities` | `features[].title`, `features[].text`, `features[].icon` |
| Classroom image on About and the homepage responsive section | `content/settings` | `classroom_image` |
| News/gallery article cards | `content/gallery` | `news[].category`, `news[].title`, `news[].date`, `news[].text` |
| Contact-page visit availability text | `content/contact` | `visit_hours` |
| School name used by contact map and default HTML page title | `content/settings` | `school_name` |
| Site logo and favicon | `content/settings` | `logo` |
| Footer tagline | `content/settings` | `tagline` |
| Contact details, footer, floating WhatsApp button | `content/settings` | `email`, `whatsapp`, `address`, `hours` as applicable |
| Google Maps destination on Contact | `content/settings` | `map_url`, with fallback to `config('school.map_url')`; no longer generated from the address text |
| Privacy text | `content/settings` | `privacy` |
| Terms text | `content/settings` | `terms` |

The homepage program previews and the full program page use separate documents. Likewise, FAQ answers are their own text and do not automatically update when an admission fee or period changes in `content/settings`.

Some visible content is fixed in templates: header/footer school-name lettering, several page titles and section headings, button labels, the homepage desktop admission-step labels, and the first/third homepage hero slides. Only the second homepage hero slide reads `content/home.image`. The gallery cards use generated colored covers rather than individual image fields. These items need template changes if they must become database-driven.

### 5.4 Form submissions and admin screens

This table covers the admin panel inside the landing-page project. Section 5.6 covers the separate, larger dashboard application.

| Website action/screen | Data location |
| --- | --- |
| Registration steps `/pendaftaran/{step}` | Draft child/parent/file references are held in the user's session, backed by `runtime_sessions` in this app |
| Final registration submit, POST `/pendaftaran/kirim` | Creates `applications/{reference}` with `child`, `parent`, `documents`, status, and timestamps |
| Admin registration list `/admin/data/applications` | Reads `applications` |
| Admin registration detail `/admin/data/applications/{id}` | Reads/updates `applications/{id}`; review writes `status` and `admin_notes` |
| Contact visit form, POST `/kunjungan` | Creates `visits/{ulid}` |
| Admin visit list/detail `/admin/data/visits` | Reads `visits`; review writes `status` and `admin_notes` on the selected document |
| Admin accounts `/admin/akun` and login `/admin/login` | Uses `admins/{sha256(normalizedEmail)}` |
| Content editor `/admin/konten/{section}` | Reads and saves `content/{section}` |
| Public uploaded image `/media/{filename}` | Resolves `uploaded_files/{sha256('media/'+filename)}` and the matching chunk documents |
| Authorized registration attachment `/admin/berkas/{id}/{field}` | Reads `applications/{id}.documents[field].path`, then resolves file metadata/chunks |

Image fields contain URL paths. `/assets/design/...` requires the corresponding file under the target website's public assets. `/media/...` requires a backend handler that reads the Firestore file format. Connecting the database alone will not make either type of relative URL work on another website unless its assets/routes are present.

### 5.5 Why a correct database edit might not immediately appear

`SchoolContent::all()` loads the section keys from `resources/content/site.php`, reads matching `content/{section}` documents, and recursively merges saved fields over those defaults. The result is cached for **300 seconds** under the logical key `school-content-firestore`.

- Saving through this app's content editor clears that content-cache key for the current cache namespace.
- Editing directly in Firebase Console or from an arbitrary external writer does not invalidate the landing cache. The reviewed admin dashboard's **publish** workflow does invalidate it when `LANDING_CACHE_PREFIX` matches the landing application's cache prefix; saving a draft alone does not publish changes.
- Missing documents/fields can display local default content. A page showing sample text is not sufficient proof that the intended document was read.
- The recursive merge also applies to array indices. Shortening a saved array can leave default entries visible at remaining positions; a port should explicitly decide whether arrays should replace defaults wholesale.
- New top-level documents are not automatically rendered; the current loader iterates the known default section keys.
- Connection/authentication exceptions are not silently converted into SQLite reads by this integration.

### 5.6 Separate admin dashboard: screen-to-document mapping

This section describes `E:\Admin Dashboard SDCN` at `4b8114d` plus the inspected local changes, not the smaller admin panel inside the landing-page project. Both use the same Firestore project and database. Section 5.9 identifies account changes that remain uncommitted.

Most operational records share a collection and have this shape:

```text
dashboard_records/{recordId}
  kind           record category, for example staff or students
  code           human-readable employee/student/invoice/etc. code
  data           category-specific fields
  created_at
  updated_at
```

New operational IDs are ULIDs; imported IDs can be numeric strings. Use the actual document ID when following references. An employee number, NIS, or invoice `code` is not necessarily that document's ID.

| Dashboard screen | Collection / kind filter | Fields inside `data` and observed record count |
| --- | --- | --- |
| `/admin/guru` | `dashboard_records`, `kind = staff` | `name`, `employee_id`, `position`, `assignment`, `education`, `phone`, `email`, `gender`, `birth_date`, `joined`, `status`; optional `photo_path` after upload. **4** records |
| `/admin/siswa` | `dashboard_records`, `kind = students` | `name`, `nis`, `class`, `year`, `gender`, `birth_date`, `guardian`, `father`, `email`, `phone`, `address`, `previous_school`, `status`, optional `photo_path` and admission links. **5** records |
| `/admin/kelas` | `dashboard_records`, `kind = classes` | `name`, `level`, `year`, `teacher`, `count`, `status`. **3** records |
| `/admin/absensi` | `dashboard_records`, `kind = attendance` | `student_id`, `name`, `class`, `date`, `status`, `notes`. **4** records |
| `/admin/nilai` | `dashboard_records`, `kind = grades` | `student_id`, `name`, `class`, `subject`, `score`, `semester`, `status`, `notes`. **3** records |
| `/admin/tagihan` | `dashboard_records`, `kind = invoices` | `student_id`, `name`, `class`, `amount`, `period`, `due_date`, `notes`; paid/balance values are derived from verified payments. **3** records |
| `/admin/kas` | `dashboard_records`, `kind = transactions` | `name`, `date`, `type`, `category`, `amount`, `method`, `status`. **3** records |
| `/admin/pengumuman` | `dashboard_records`, `kind = announcements` | `name`, `body`, `audience`, `date`, `status`. **2** records |
| `/admin/agenda` | `dashboard_records`, `kind = events` | `name`, `date`, `time`, `location`, `audience`. **3** records |
| `/admin/aktivitas` | `dashboard_records`, `kind = activities` | `name`, `section`, `status`, `author`, `date`. **16** records |
| `/admin/alat` | `dashboard_records`, `kind = tools` | `name`, `purpose`, `proposal`, `status`. **6** records |
| `/admin/periode` | `dashboard_records`, `kind = period` | `year`, `period`, `age`, numeric `fee`, `capacity`, `reenrollment`, `status`. **1** record |
| `/admin/pengaturan` | `dashboard_records`, `kind = settings` | `name`, `email`, `phone`, `address`, `hours`, `founded`, `accreditation`, `year`. **1** record |
| `/admin/berita` | Redirects to `/admin/website/gallery/edit` | Published landing news is `content/gallery.news[]`; **3** older `dashboard_records` with `kind = news` also exist, but this route does not publish them automatically |
| `/admin/pendaftar`, `/admin/daftar-ulang` | Shared `applications/{id}` | Adapted into `kind = applicants` in memory; not a separate applicant collection |
| `/admin/pembayaran`, receipt views | `school_payments/{paymentId}` | Fields below; **3** payment records |
| `/admin/pengguna` | Shared `admins/{sha256(normalizedEmail)}` | Account name, email, role, active status, optional Firebase UID |
| `/admin/media` | `dashboard_records`, `kind = media`, plus upload collections | Uploaded catalog entry uses `data.name`, `data.image`, `data.alt`; none observed at inspection |

Other dashboard data:

- `school_payments`: `invoice_id`, `reference`, integer `amount`, `method`, `status`, `received_at`, `user_id`, optional `review_notes`, `verified_by`, `verified_at`, plus timestamps. `invoice_id` points to a `dashboard_records` invoice document, not the invoice display code. Verified payments have `status = "Terverifikasi"` in the reviewed implementation.
- `school_documents`: `record_id`, `name`, `path`, `mime`, `size`, timestamps. Its file bytes live in `uploaded_files` / `uploaded_file_chunks`. For admission attachments, the dashboard also synthesizes read-only document rows from `applications.documents`; an attachment can therefore be visible even when `school_documents` has zero documents.
- `admission_activations`: `applicant_id`, `student_id`, timestamps; connects an accepted application to the created student record.
- `dashboard_runtime/workflows`: numeric `revision`, used to coordinate dashboard transactional workflows. It is not public content.
- `dashboard_legacy_content`: imported historical content. The active publisher uses `content`, not these preserved draft/published snapshots.

Dashboard code often imports `App\Support\CloudData as DB`. Those `DB::table(...)` calls route through its Firestore adapter; they are not evidence of a separate SQL database.

Operational staff photos use `data.photo_path`, such as `staff-photos/{uuid}.jpg`, with private access through `/admin/foto-guru/{recordId}`. Student photos use `/admin/foto-siswa/{recordId}`. The public landing page cannot simply place those protected URLs into an anonymous public teacher card.

Student lifecycle operations also write status/deletion metadata under `dashboard_records/{studentId}.data`. The deletion service preserves financial/academic snapshots through fields such as `student_record_archived`, `student_archived_at`, and `student_snapshot`; it coordinates related applications, attachments, and activation links. Retain these fields when adapting record editors.

Evidence: dashboard `resources/data/school.json`, `app/Support/CloudData.php`, `app/Models/SchoolRecord.php`, `app/Http/Controllers/AdminController.php`, `WorkflowController.php`, and `app/Services/StudentDeletionService.php`; counts were read directly from Firestore.

### 5.7 Dashboard content publishing and shared settings

The actual publication flow is:

```text
Published page data: content/{section}
        |
        | edit and save in the separate dashboard
        v
Draft: dashboard_content_drafts/{section}
        base        published content when the draft was saved
        data        edited content
        author
        updated_at
        |
        | POST /admin/website/publish
        v
Merge changes into content/{section}, delete that draft,
and invalidate the landing-page content cache
```

| Dashboard action | Data effect |
| --- | --- |
| GET `/admin/website/{section}/edit` | Reads `content/{section}` and any matching `dashboard_content_drafts/{section}` |
| POST `/admin/website/{section}/edit` | Saves the draft; checks the editor's version fingerprint |
| GET `/admin/website/preview` | Displays draft-merged content in the dashboard preview |
| POST `/admin/website/publish` | Merges changes, rejects conflicting edits, removes published drafts, invalidates landing cache |

Known sections remain `settings`, `home`, `about`, `program`, `facilities`, `teachers`, `gallery`, `contact`. Dashboard aliases are `hero -> home`, `profil -> about`, `fasilitas -> facilities`, `guru -> teachers`, `kontak -> contact`, `ppdb -> settings`. These are routing aliases, not extra Firestore documents.

Saving dashboard operational settings or admission-period settings also prepares a `settings` content draft:

| Operational field | Published target after draft publication |
| --- | --- |
| `kind=settings`, `data.name` | `content/settings.school_name` |
| `kind=settings`, `data.phone` | `content/settings.whatsapp` |
| `kind=settings`, `data.email`, `address`, `hours`, `founded`, `accreditation` | Same-named fields in `content/settings` |
| `kind=period`, `data.period`, `data.age` | `content/settings.period`, `.age` |
| `kind=period`, numeric `data.fee` | `content/settings.fee`, formatted as a Rupiah display string |

The dashboard's `LANDING_CACHE_PREFIX` must match the landing application's actual cache prefix for immediate invalidation; its configured default is `sd-ceria-nusantara-cache-`. `LANDING_PAGE_URL` controls the destination link, not database selection. Each application should retain its own session/cache namespace except for deliberate invalidation of the landing content key.

The dashboard preview does not prove that the new local staff directory consumes those published fields. The new directory still requires the explicit integration described in section 5.2.

Evidence: dashboard `app/Services/WebsiteContent.php`, `app/Http/Controllers/WebsiteController.php`, `app/Http/Controllers/AdminController.php`, and `config/school.php`.

### 5.8 Registration additions and shared admission fields

The four-step landing registration writes these nested fields into `applications/{reference}` after final submission:

| Form information | Final Firestore field | Requirement in landing form |
| --- | --- | --- |
| Child's full name | `child.child_name` | Required |
| Nickname | `child.nickname` | Required |
| Birth date | `child.birth_date` | Required |
| Gender | `child.gender` | Required; `Laki-laki` or `Perempuan` |
| Previous school / PAUD | `child.previous_school` | Optional |
| Support needs | `child.support_needs` | Optional |
| Parent/guardian name | `parent.parent_name` | Required |
| Relationship | `parent.relationship` | Required; `Ayah`, `Ibu`, or `Wali` |
| WhatsApp/phone | `parent.phone` | Required |
| Email | `parent.email` | Required |
| Home address | `parent.address` | Optional in team update `2ca0c81` |
| Emergency contact | `parent.emergency_contact` | Optional in team update `2ca0c81` |
| Domicile latitude | `parent.domicile_latitude` | Optional, numeric -90..90; must be paired with longitude |
| Domicile longitude | `parent.domicile_longitude` | Optional, numeric -180..180; must be paired with latitude |
| Distance from domicile to school | `parent.domicile_distance_km` | Server-calculated float, rounded to 2 decimal places; not accepted as a client-supplied distance |
| Birth certificate | `documents.birth_certificate` | Required upload; metadata `path`, `name`, `size` |
| Family card / KK | `documents.family_card` | Required upload; metadata `path`, `name`, `size` |
| Child identity card / KIA | `documents.child_identity_card` | Fourth required upload in current landing version; metadata `path`, `name`, `size` |
| Child photo | `documents.photo` | Required upload; metadata `path`, `name`, `size` |
| Consent | `consent_at` | Timestamp written after accepted consent |
| Registration number | `reference` and document ID | Created by landing's draft initializer |

The file selection UI displays the selected name locally; the upload is stored when the step is submitted. During steps 1–3, draft data is stored in the session, and file bytes may already exist in the upload collections. The final `applications` document is created at POST `/pendaftaran/kirim`.

The landing admin displays each attachment's stored original `name`, its `size / 1024` in KB when available, and its download link; the current version includes `child_identity_card` in its download allowlist and detail view. This uses the existing upload collections. Certificates, KK, and KIA accept JPG/PNG/PDF; photos accept JPG/PNG; each file is limited to 1 MiB. The current launcher and `api/php.ini` set `post_max_size=6M` to accommodate the four-part upload, while keeping `upload_max_filesize=1M`. This PHP setting does not establish any external hosting platform's request-size limit.

Client image/PDF previews and selected-file names are transient browser UI; file bytes reach Firestore on step submission. Registration illustrations, hero/portal animations, progress indicators, and continue-button animations remain local template/CSS/JS behavior and add no database fields. Existing published FAQ/admission text can override newly updated local defaults and may still mention three files; update the published content separately if needed.

For optional domicile calculation, the browser requests device location after the user presses the location button and submits the coordinate pair. The server recomputes straight-line distance with a Haversine calculation using `school.latitude` and `school.longitude`; this is not road distance or geocoding of the typed address. Registration review hides raw coordinates and shows distance; the landing admin can display the distance and a map link to the saved point. CSV export adds address and `domicile_distance_km`. Existing submissions can legitimately lack these fields.

**How the separate dashboard reads the same application**

`CloudData::applicant()` adapts `applications/{id}` into an in-memory applicant record. It maps `child.child_name -> data.name`, `parent.parent_name -> data.guardian`, the shared contact fields to dashboard contact fields, and `admin_notes -> data.notes`. The dashboard's extended admission workflow is stored in `applications/{id}.dashboard`, including fields such as `status`, `source_status`, `payment`, `checks`, and later `student_id`.

Landing statuses include `baru`, `ditinjau`, `diterima`, `ditolak`. The dashboard maps `diterima` to `Lolos administrasi`, `ditolak` to `Perlu perbaikan`, and other statuses to `Pemeriksaan` unless a compatible dashboard workflow status exists. Do not rename the shared statuses or overwrite the entire application with a flattened dashboard form.

Activation can create a student in `dashboard_records`, an `admission_activations` link, an invoice, and a payment, then update the original application. These are distinct documents with references; a connection repair does not need to run activation or reseed them.

**Current cross-application differences:** the dashboard public `/ppdb` form still creates three attachments and does not collect domicile coordinates or KIA. Its applicant adapter does not promote the new domicile fields into the flattened dashboard form, although its shared-record merge preserves existing nested parent fields and attachment entries. The generic attachment adapter enumerates stored document keys, but the dashboard's admission checklist still names its existing checks. Do not assume all four-file/distance UI changes have been ported to that application. The live database contains a mixture of submissions; at inspection the union of document keys included KIA and the union of parent keys included all three domicile fields.

### 5.9 Account additions: Firestore data access versus Firebase login

Firestore server access still uses a service account. User login is separate and differs between the two reviewed applications:

| Application | Login implementation |
| --- | --- |
| Landing page | `admins/{sha256(trimmedLowercaseEmail)}` and Laravel `Hash::check` against `password`; requires an active account |
| Separate dashboard | Same `admins` collection, but can use Firebase Authentication when `FIREBASE_AUTH_ENABLED` is enabled or a record has `firebase_uid`; otherwise uses the Laravel hash |

The dashboard's Firebase login integration uses `FIREBASE_API_KEY` plus its server implementation and validates the associated Firebase account. Its account creation code can store an empty `password` alongside `firebase_uid`. A dashboard Firebase-authenticated account therefore may not work with the landing page's unchanged password-hash login even though both apps connect successfully to the same Firestore database. Treat this as an authentication compatibility issue, not a reason to replace database credentials.

Dashboard roles are `owner`, `admin`, `keuangan`, and `akademik`. Landing account creation uses `owner` and `editor`. The applications have different access-control logic; preserve their role checks when integrating them.

This section documents supported code paths, not a new Firebase Auth configuration or account migration. No account credentials, hashes, private keys, or session payloads are included in this guide.

**Additional dashboard account changes — local work in progress, 17 October**

These changes are present in modified/untracked files on this machine, beyond committed `4b8114d`. They have been inspected but not deployed or tested as part of this documentation task.

| Feature | Actual data source / effect |
| --- | --- |
| Email verification gate | Firebase Authentication account's `emailVerified` value, copied to an in-memory user property `email_verified`; no new Firestore verification collection |
| GET `/verify-email` | Verification screen for an unverified Firebase-authenticated user |
| POST `/verify-email` | Checks the supplied current password against Firebase, then sends a `VERIFY_EMAIL` action using an ID token held only for that request |
| Dashboard access after login | `VerifiedSchoolEmail` middleware redirects an unverified Firebase user to `/verify-email`; local-hash accounts bypass this Firebase-specific check |
| Account creation | Creates `admins/{sha256(normalizedEmail)}`; initially derives `name` from the email local part, with optional linked `firebase_uid` |
| Account manager edit | Updates `role`, `active`, timestamps; identity details are edited by the account holder |
| Own profile, POST `/admin/profil-admin` | Updates `admins/{id}.name`, optional `.phone`, optional `.photo_path`, `.updated_at`; name is also synchronized to Firebase Auth `displayName` when linked |
| Contact phone | Firestore `admins/{id}.phone`; it is not a Firebase verified phone-login credential |
| Admin portrait | File path `admin-photos/{uuid}.jpg`, bytes in upload collections, reference in `admins.photo_path`, private display via `/admin/foto-admin` |
| Account deletion in progress | `admins/{id}.deletion_pending = true`, `active = false`, cleared `remember_token` and `firebase_remember_version`, updated timestamp |
| Completed account deletion | Removes linked Firebase Auth account, profile-photo file, and Firestore admin record; records activity in `dashboard_records` |

The account-deletion service retains a disabled pending record if an external step fails, so a later retry can finish. This state is distinct from an ordinary inactive account. Its controller accepts up to 20 selected account IDs and requires confirmation; the service prevents deleting the current account or the last active owner, and prevents a non-owner from deleting an owner. No deletion was performed during this review.

The local manager check now allows active `owner` and `admin` roles to manage accounts; it is no longer just the old owner-only route wrapper. Preserve the actual controller/service checks rather than assuming the two applications share identical permissions. A redirect to verification, a disabled pending account, or a profile-name sync error does not establish a Firestore connection failure.

Evidence: dashboard local `EmailVerificationController.php`, `VerifiedSchoolEmail.php`, `AccountDeletion.php`, `AdminProfileController.php`, `UserManagementController.php`, `DocumentUserProvider.php`, `FirebaseAuth.php`, `User.php`, and `routes/web.php`.

### 5.10 Admission result lookup and WhatsApp group

The public landing `/pengumuman` page is a new read flow. It does **not** read the dashboard's `kind=announcements` records and does not require a `content/announcement` document.

| Input / displayed information | Source |
| --- | --- |
| Submitted registration number | POST `reference`, trimmed and uppercased, validated against `CN-` format |
| Submitted birth date | POST `birth_date`, `Y-m-d` |
| Applicant lookup | Exact document `applications/{reference}` |
| Verification against stored birth date | `applications/{reference}.child.birth_date`, exact comparison |
| Displayed applicant name | `.child.child_name` |
| Result decision | Top-level `.status` |
| Result heading, label, description | Fixed strings in `AnnouncementController.php` |
| WhatsApp group for accepted applicants | `config('school.whatsapp_group_url')`, supplied by server environment `WHATSAPP_GROUP_URL` or the source fallback |
| Help/contact WhatsApp | `content/settings.whatsapp`, distinct from the group invite |

Status mapping is `diterima -> LULUS`, `ditolak -> TIDAK LULUS`, and all other statuses -> `MASIH DIPROSES`. Only a matching lookup with `status=diterima` receives the group-link value. The lookup uses throttling (5/minute per normalized reference, 15/minute per IP), does not save results to a new collection, and returns private/no-store responses. Normal session/rate-limiter infrastructure can still use the runtime collections.

**Integration differences to preserve or resolve:**

- The current validator accepts `CN-...` numbers. Dashboard-created `PPDB-...` numbers fail validation even if their records exist in the same `applications` collection. Do not rename historical document IDs to make them fit.
- The dashboard maps `Perlu perbaikan` to top-level `ditolak`, while the public page displays `ditolak` as a failed selection. A correction workflow and a final rejection therefore currently share the same public outcome.
- Updating a display-only status in some other collection will not affect the public result. The page reads the shared application's top-level `status`.
- The group invite is server configuration, not `content/settings.whatsapp` and not a Firestore field. Configure the intended value privately on the target server; the invite itself is omitted from this handoff.

Evidence: landing `AnnouncementController.php`, `AppServiceProvider.php`, `routes/web.php`, `resources/views/pages/announcement.blade.php`; dashboard `CloudData::applicant()` and `CloudData::saveRecord()`.

### 5.11 School location and configuration precedence

| Setting / visible behavior | Current source |
| --- | --- |
| Contact-page address text | `content/settings.address`; local default is `config('school.address')` from `SCHOOL_ADDRESS` |
| Contact-page Google Maps link | `content/settings.map_url`; local default/fallback is `config('school.map_url')` from `SCHOOL_MAP_URL` |
| Registration location-widget school point | `config('school.latitude')`, `config('school.longitude')` from `SCHOOL_LATITUDE`, `SCHOOL_LONGITUDE` |
| Registration location-widget map link | `config('school.map_url')` directly, rather than the published content value |
| Accepted-applicant group link | `WHATSAPP_GROUP_URL` through `school.whatsapp_group_url` |

`map_url` was present in the live `content/settings` document during this review. The new domicile fields were also present on some applications. Changing the address text or published map link does not automatically change the coordinates used for distance calculation. Changing a default in `.env` or `resources/content/site.php` does not overwrite an existing published Firestore field because `SchoolContent` merges saved content over defaults.

The landing editor validates `map_url` as an HTTPS Google Maps URL. The separate dashboard's existing settings-to-content synchronization maps address/contact fields but does not automatically synchronize school coordinates or map URL; its content editor reads the actual published document fields. Keep the chosen address, map URL, and coordinate pair consistent when configuring the other website.

Source locations: `config/school.php`, `resources/content/site.php`, `RegistrationController.php`, `AdminController.php`, `resources/views/pages/contact.blade.php`, and `resources/views/registration/form.blade.php`.

## 6. Troubleshooting

| Symptom | Check / fix |
| --- | --- |
| Missing credentials or file not found | The configured path must exist on the target server. A Windows path from the source machine will not exist on Linux hosting. |
| JSON/Base64 parsing error or wrong project | Use the complete service account JSON. Check project IDs. Remove a stale `FIREBASE_CREDENTIALS_JSON_BASE64` because it overrides the file. |
| HTTP 401 / `UNAUTHENTICATED` | Check the service account key is valid, the server clock is accurate, and a fresh OAuth token is being used. |
| HTTP 403 / `PERMISSION_DENIED` from the server | Check the service account's Firestore IAM permissions and that the Firestore API is enabled in the selected project. |
| Permission denied from browser Firebase SDK | This app is designed for backend access. Its checked-in rules deny direct client access. Route requests through the backend and its authorization checks. |
| HTTP 404 / `NOT_FOUND` | Verify project ID, `(default)` database, exact collection/document path, and database existence. A missing document and a missing database are different failures. |
| Connection succeeds but document is missing | Check the selected project/database, exact document ID, and any emulator settings. `content/home` existed in the verified source database. |
| PHP cURL error 60 / certificate verification failure | Install a current trusted CA bundle. Set `FIREBASE_CA_BUNDLE` to a readable local path only if the PHP trust store needs it; keep TLS verification enabled. |
| Settings changed but error persists | Clear Laravel configuration cache, then restart or redeploy. Confirm the variables are set in the deployment environment being tested. |
| Unsupported Laravel `firestore` driver | Install/adapt this project's custom provider and services; environment variables alone do not add those drivers. |
| Database reads succeed but admin login fails | Check the Laravel account ID, `active` status, and password verification. Do not assume the issue is the Firebase connection. |
| New directory names do not change after editing Firebase | At landing commit `a68b4bf`, `/guru-staf` still reads local `staff-directory.php`; only its values cards read `content/teachers`. See section 5.2. |
| Editing dashboard staff does not update public teacher cards | `/admin/guru` writes `dashboard_records` with `kind=staff`; the homepage reads `content/teachers.team`, and the new directory reads a local file. No automatic sync exists. |
| Dashboard content was saved but public page did not change | Save creates `dashboard_content_drafts/{section}`. Publish it, confirm the landing cache prefix, and confirm that the page actually reads the field. |
| Dashboard login works but landing admin login fails | Check whether that account uses `firebase_uid` with an empty hash; the reviewed landing app does not implement the dashboard's Firebase Auth login path. |
| Registration works in dashboard but fails in result lookup | The landing lookup currently accepts `CN-` references, while dashboard submissions use `PPDB-`. See section 5.10. |
| New landing registration rejects an older three-file draft | Current landing submissions require KIA as a fourth file. Existing completed applications can still have three files. |
| School address changed but distance is wrong | Distance uses server `SCHOOL_LATITUDE` / `SCHOOL_LONGITUDE`, not the Firestore address or map-link text. |
| Dashboard login redirects to `/verify-email` | In local work in progress this is the Firebase email-verification gate; check Firebase Auth's verified state rather than inventing a Firestore field. |
| HTTP 429 / `RESOURCE_EXHAUSTED` | Check Firestore usage/quota and excessive retry loops. |

The original Windows installation uses `storage/app/private/firebase-ca-bundle.pem` via `FIREBASE_CA_BUNDLE`. On another host, use its valid system trust store or configure an appropriate local CA path.

The checked-in rules are:

```text
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /{document=**} {
      allow read, write: if false;
    }
  }
}
```

A connection repair should begin with the read-only `content/home` check. It does not require reseeding the database, changing collection names, replacing admin accounts, or deploying permissive rules.

## 7. Handoff prompt for the other website

> Inspect this website's actual framework, deployed branch/version, server runtime, environment loading, and Firebase initialization. Use this guide's 17 October 2026 snapshot for project `sd-ceria-nusantara`, Firestore database `(default)`. Configure server credentials privately and verify a read of `content/home`. Use sections 5.1–5.11 for exact sources: homepage teacher previews use `content/teachers.team`, the full staff directory/organization uses local `staff-directory.php`, and operational dashboard staff use `dashboard_records` filtered by `kind=staff`. Do not assume these lists are synchronized or that the proposed directory migration exists. Preserve unknown fields, role checks, and private file access. Account for four landing uploads versus three dashboard uploads, optional domicile fields, map/coordinate configuration precedence, and the result lookup's `CN-` restriction and top-level status semantics. Respect drafts/publication, cache invalidation, and the distinct login implementations. Confirm which local account-verification/deletion changes are actually deployed before relying on them. Identify the actual failure, adapt the integration to the existing architecture, then report changed files and verification results. If the target uses a different Firebase project, substitute its settings and credentials instead.
