# Design — Configuration & Environment (TODO §2)

**Tanggal:** 2026-08-16
**Status:** Approved
**Sumber:** `docs/TODO.md` §2, `docs/PRD.md` (§5, §27, §28, §29, §37-38, §53, §54), `docs/conventions/coding.md`, `core/Config/core.php`, `core/CoreServiceProvider.php`, `config/*.php` (15 file), `.env.example`
**Metode:** Brainstorming (sesi 2026-08-16) — inventaris `env()` di `config/`, review `.env.example`, konsultasi PRD

## 1. Ringkasan

Milestone "Configuration & Environment" (TODO.md §2) menetapkan fondasi
konfigurasi aplikasi: review dan sinkronisasi `.env.example`, dokumentasi
konvensi environment, dan pemetaan checklist §2. Fokus **fondasi** — tidak
membuat config baru untuk domain yang belum diimplementasikan
(organization/branding/feature — defer ke milestone masing-masing).

Deliverable:

1. **`docs/conventions/environment.md`** (baru) — aturan env vs config, daftar
   environment variables (required/optional/default/deskripsi), ringkasan tiap
   config file, kebijakan penambahan env var.
2. **`.env.example`** — sinkronisasi penuh dengan semua `env()` di `config/`
   aktual, dikelompokkan per kategori.
3. **`docs/conventions/coding.md`** — tambah referensi environment.md di header.
4. **`docs/TODO.md`** — checklist §2 tercentang sesuai pemetaan.

## 2. Konteks

- `.env.example` saat ini murni Laravel default (`APP_NAME=Laravel`,
  `APP_TIMEZONE=Asia/Makassar`, locale `id`), belum menyertakan env var untuk
  package terpasang (white-label, boost, dsb.).
- `config/` berisi 15 file: `app`, `auth`, `boost`, `cache`, `database`,
  `filament-logger`, `filament-shield`, `filament-white-label`, `filesystems`,
  `logging`, `mail`, `permission`, `queue`, `services`, `session`.
- `core/Config/core.php` sudah ada — berisi `providers` kosong; di-merge dan
  dipublish oleh `core/CoreServiceProvider.php`.
- Tidak ada dokumen konvensi environment (`docs/conventions/environment.md`).
- PRD §53: kompatibilitas MySQL/MariaDB/PostgreSQL/SQLite (SQLite untuk
  dev/testing). PRD §54: secrets tidak disimpan plaintext, tidak tampil di
  diagnostic. PRD §27: settings dibagi System/Organization/Unit/User. PRD §28:
  white label adalah Core capability. PRD §29: feature registry.
- `DEBUGBAR_ENABLED` hanya ada di `.env.example` (dikonsumsi debugbar via
  vendor, bukan lewat config lokal) — didokumentasikan sebagai env var package.

## 3. Aturan env vs config

### 3.1 Prinsip

1. **`.env` / environment variables** — nilai yang berbeda per lingkungan
   (development, production, on-premise). Hanya nilai primitif (string, int,
   bool); tidak berisi logika atau struktur.
2. **`config/*.php`** — komposisi, default, struktur, logika. Config file
   membaca `env()` dengan default aman. Untuk non-secret, `env()` di config
   **wajib punya default** kecuali nilai tersebut memang required dan
   divalidasi di bootstrap (mis. `APP_KEY`).
3. **`core/Config/*.php`** — config milik Core, di-merge via `mergeConfigFrom`
   dan dipublish via `publishes()` di `CoreServiceProvider`. File utama saat
   ini: `core/Config/core.php` (daftar `providers` domain).
4. **Secrets** (APP_KEY, DB_PASSWORD, MAIL_PASSWORD, API keys) — hanya di
   `.env`, **tidak pernah** hardcode di config, tidak tampil di log/diagnostic
   (PRD §54).
5. **Akses config di kode** — boleh via `config()` facade; prefer injeksi
   nilai config via constructor pada Action/Service Core (testable). Jangan
   memanggil `env()` langsung di kode aplikasi — selalu lewat config.
6. **Environment check** — gunakan `app()->environment()` /
   `App::environment()`; hindari `env('APP_ENV')` langsung di kode setelah
   config cached (PRD §54, Laravel best practice).

### 3.2 Kategori env var

- **Required** — harus ada di production; aplikasi gagal tanpa ini
  (APP_KEY, APP_ENV, APP_URL, DB_CONNECTION, dsb.).
- **Optional** — punya default aman di config (CACHE_STORE,
  QUEUE_CONNECTION, dsb.).

## 4. Dokumen `docs/conventions/environment.md`

### 4.1 Header

```markdown
# Konvensi Environment

**Status:** Accepted
**Tanggal:** 2026-08-16
**Referensi:** PRD §53, config/*, .env.example
```

### 4.2 Isi

1. **Aturan env vs config** — §3 spec ini.
2. **Daftar environment variables** — tabel per kategori, kolom: nama,
   required/optional, default, deskripsi, sumber config. Kategori:

   - Aplikasi & Debug: `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`,
     `APP_TIMEZONE`, `APP_URL`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`,
     `APP_FAKER_LOCALE`, `APP_MAINTENANCE_DRIVER`, `APP_MAINTENANCE_STORE`,
     `APP_PREVIOUS_KEYS`, `BCRYPT_ROUNDS`, `DEBUGBAR_ENABLED`,
     `PHP_CLI_SERVER_WORKERS`.
   - Database: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
     `DB_USERNAME`, `DB_PASSWORD`, `DB_URL`, `DB_FOREIGN_KEYS`, `DB_SOCKET`,
     `DB_CHARSET`, `DB_COLLATION`, `MYSQL_ATTR_SSL_CA`.
   - Redis: `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`,
     `REDIS_DB`, `REDIS_CACHE_DB`, `REDIS_CLUSTER`, `REDIS_PREFIX`,
     `REDIS_PERSISTENT`, `REDIS_URL`, `REDIS_USERNAME`.
   - Session: `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_EXPIRE_ON_CLOSE`,
     `SESSION_ENCRYPT`, `SESSION_CONNECTION`, `SESSION_TABLE`, `SESSION_STORE`,
     `SESSION_COOKIE` (dari `APP_NAME`), `SESSION_PATH`, `SESSION_DOMAIN`,
     `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`,
     `SESSION_PARTITIONED`.
   - Cache: `CACHE_STORE`, `CACHE_PREFIX`, `DB_CACHE_CONNECTION`,
     `DB_CACHE_TABLE`, `DB_CACHE_LOCK_CONNECTION`, `DB_CACHE_LOCK_TABLE`,
     `MEMCACHED_PERSISTENT_ID`, `MEMCACHED_USERNAME`, `MEMCACHED_PASSWORD`,
     `MEMCACHED_HOST`, `MEMCACHED_PORT`, `REDIS_CACHE_CONNECTION`,
     `REDIS_CACHE_LOCK_CONNECTION`, `DYNAMODB_CACHE_TABLE`, `DYNAMODB_ENDPOINT`.
   - Queue: `QUEUE_CONNECTION`, `QUEUE_FAILED_DRIVER`, `DB_QUEUE_CONNECTION`,
     `DB_QUEUE_TABLE`, `DB_QUEUE`, `DB_QUEUE_RETRY_AFTER`,
     `BEANSTALKD_QUEUE_HOST`, `BEANSTALKD_QUEUE`, `BEANSTALKD_QUEUE_RETRY_AFTER`,
     `SQS_PREFIX`, `SQS_QUEUE`, `SQS_SUFFIX`, `REDIS_QUEUE_CONNECTION`,
     `REDIS_QUEUE`, `REDIS_QUEUE_RETRY_AFTER`.
   - Filesystem & Storage: `FILESYSTEM_DISK`, `AWS_ACCESS_KEY_ID`,
     `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`,
     `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`.
   - Mail: `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_URL`, `MAIL_HOST`, `MAIL_PORT`,
     `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_EHLO_DOMAIN`,
     `MAIL_SENDMAIL_PATH`, `MAIL_LOG_CHANNEL`, `MAIL_FROM_ADDRESS`,
     `MAIL_FROM_NAME`.
   - Logging: `LOG_CHANNEL`, `LOG_STACK`, `LOG_DEPRECATIONS_CHANNEL`,
     `LOG_DEPRECATIONS_TRACE`, `LOG_LEVEL`, `LOG_DAILY_DAYS`,
     `LOG_SLACK_WEBHOOK_URL`, `LOG_SLACK_USERNAME`, `LOG_SLACK_EMOJI`,
     `LOG_PAPERTRAIL_HANDLER`, `PAPERTRAIL_URL`, `PAPERTRAIL_PORT`,
     `LOG_STDERR_FORMATTER`, `LOG_SYSLOG_FACILITY`.
   - Auth: `AUTH_GUARD`, `AUTH_PASSWORD_BROKER`, `AUTH_MODEL`,
     `AUTH_PASSWORD_RESET_TOKEN_TABLE`, `AUTH_PASSWORD_TIMEOUT`.
   - Services: `POSTMARK_TOKEN`, `RESEND_KEY`, `SLACK_BOT_USER_OAUTH_TOKEN`,
     `SLACK_BOT_USER_DEFAULT_CHANNEL`.
   - White Label: `FILAMENT_WHITE_LABEL_ENABLED`, `FILAMENT_WHITE_LABEL_CACHE_TTL`,
     `FILAMENT_WHITE_LABEL_DISK`, `FILAMENT_WHITE_LABEL_DISABLE_CSS`,
     `FILAMENT_WHITE_LABEL_PREVIEW`, `FILAMENT_WHITE_LABEL_LOGIN_ENABLED`,
     `GOOGLE_FONTS_API_KEY`.
   - Boost: `BOOST_ENABLED`, `BOOST_RULES_ENABLED`, `BOOST_RULES_SCOPED_GUIDELINES`,
     `BOOST_BROWSER_LOGS_WATCHER`, `BOOST_BROWSER_LOG_LEVELS`,
     `BOOST_PHP_EXECUTABLE_PATH`, `BOOST_COMPOSER_EXECUTABLE_PATH`,
     `BOOST_NPM_EXECUTABLE_PATH`, `BOOST_VENDOR_BIN_EXECUTABLE_PATH`,
     `BOOST_CURRENT_DIRECTORY_EXECUTABLE_PATH`.
   - Vite: `VITE_APP_NAME`.

3. **Ringkasan konfigurasi aplikasi** — satu paragraf tiap config file:
   `app.php`, `auth.php`, `database.php`, `cache.php`, `queue.php`,
   `session.php`, `filesystems.php`, `mail.php`, `logging.php`, `services.php`,
   `filament-shield.php`, `filament-white-label.php`, `filament-logger.php`,
   `permission.php`, `boost.php`, `core.php` (core/Config). Catatan:
   file dari package (filament-shield, white-label, dll.) mengikuti default
   package — konfigurasi kustom dicatat bila diubah.
4. **Kebijakan penambahan env var**:
   - Setiap `env()` baru di `config/` wajib: didokumentasikan di
     `environment.md` + ditambahkan ke `.env.example`.
   - Variabel yang hanya dipakai driver tertentu (AWS/SQS/Postmark/Slack/
     Resend/PaperTrail/Memcached) tetap didokumentasikan, tapi dikomentari di
     `.env.example` (opsional lanjutan).
   - Tidak menambah env var untuk fitur yang belum ada (YAGNI).

## 5. Update `.env.example`

Sinkronisasi penuh dengan `config/` aktual. Nilai default mengikuti config
(Laravel 12 default + kustomisasi lokal yang sudah ada). Struktur:

```bash
# --- Aplikasi ---
APP_NAME=Mitra White Label
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Makassar
APP_URL=http://localhost:8000
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID
APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database
APP_PREVIOUS_KEYS=
BCRYPT_ROUNDS=12
DEBUGBAR_ENABLED=true
PHP_CLI_SERVER_WORKERS=4

# --- Database ---
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel_filament_starter_kit
# DB_USERNAME=root
# DB_PASSWORD=
# DB_URL=
DB_FOREIGN_KEYS=true

# --- Redis (opsional) ---
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
REDIS_PORT=6379
# REDIS_DB=0
# REDIS_CACHE_DB=1

# --- Session ---
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
# SESSION_SECURE_COOKIE=
SESSION_HTTP_ONLY=true
SESSION_EXPIRE_ON_CLOSE=false
SESSION_SAME_SITE=lax

# --- Cache ---
CACHE_STORE=database
# CACHE_PREFIX=
# MEMCACHED_HOST=127.0.0.1
# MEMCACHED_PORT=11211

# --- Queue ---
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
# DB_QUEUE_TABLE=jobs
# DB_QUEUE_RETRY_AFTER=90

# --- Filesystem ---
FILESYSTEM_DISK=local

# --- Mail ---
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
# MAIL_USERNAME=
# MAIL_PASSWORD=
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="${APP_NAME}"

# --- Logging ---
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# --- Auth ---
AUTH_MODEL=App\Models\User
AUTH_PASSWORD_RESET_TOKEN_TABLE=password_reset_tokens
AUTH_PASSWORD_BROKER=users
AUTH_PASSWORD_TIMEOUT=10800

# --- White Label ---
FILAMENT_WHITE_LABEL_ENABLED=true
FILAMENT_WHITE_LABEL_CACHE_TTL=300
FILAMENT_WHITE_LABEL_DISK=public
FILAMENT_WHITE_LABEL_DISABLE_CSS=false
FILAMENT_WHITE_LABEL_PREVIEW=false
FILAMENT_WHITE_LABEL_LOGIN_ENABLED=true
# GOOGLE_FONTS_API_KEY=

# --- Boost ---
BOOST_ENABLED=true
BOOST_RULES_ENABLED=true
BOOST_RULES_SCOPED_GUIDELINES=false
BOOST_BROWSER_LOGS_WATCHER=true
# BOOST_BROWSER_LOG_LEVELS=error,warning,info,debug
# BOOST_PHP_EXECUTABLE_PATH=
# BOOST_COMPOSER_EXECUTABLE_PATH=
# BOOST_NPM_EXECUTABLE_PATH=
# BOOST_VENDOR_BIN_EXECUTABLE_PATH=
# BOOST_CURRENT_DIRECTORY_EXECUTABLE_PATH=

# --- Vite ---
VITE_APP_NAME="${APP_NAME}"
```

Catatan:

- Variabel driver-lanjutan (AWS/SQS/Postmark/Slack/Resend/PaperTrail/
  Memcached credentials, dst.) **tidak** diaktifkan di `.env.example` —
  didokumentasikan di environment.md sebagai optional lanjutan.
- `APP_NAME` diubah ke "Mitra White Label".
- Komentar `#` untuk variabel yang jarang diubah/opsional lanjutan.

## 6. Dampak pada Dokumen

### 6.1 `docs/conventions/coding.md`

- Update header referensi: tambah `environment.md`.
- Tambah satu baris di bagian Quality Gate / catatan: "Lihat
  `docs/conventions/environment.md` untuk konvensi env var & config."

### 6.2 `docs/TODO.md` — pemetaan checklist §2

| Item TODO | Penanganan |
|---|---|
| Review `.env.example` | §5 — update sinkron |
| Define required environment variables | environment.md — tabel required |
| Define optional environment variables | environment.md — tabel optional |
| Define application configuration | environment.md — ringkasan config app.php + env APP_* |
| Define Core configuration | environment.md — core/Config/core.php + CoreServiceProvider |
| Define security configuration | environment.md — env auth/session/shield + PRD §54 |
| Define organization configuration | **defer** ke milestone Organization |
| Define branding configuration | **defer** ke milestone White Label (package config sudah ada) |
| Define feature configuration | **defer** ke milestone Feature Registry |
| Define localization configuration | environment.md — APP_LOCALE/FALLBACK/FAKER |
| Define database configuration | environment.md — DB_*, REDIS_*, config/database.php |
| Define cache configuration | environment.md — CACHE_*, MEMCACHED_* |
| Define queue configuration | environment.md — QUEUE_*, DB_QUEUE_* |
| Define filesystem configuration | environment.md — FILESYSTEM_DISK, AWS_* |

Item yang **defer** tetap `[ ]` (belum selesai) dengan catatan "defer ke
milestone X" — pekerjaannya belum dilakukan, hanya dicatat di environment.md
sebagai referensi fondasi. Item non-defer yang tercakup milestone ini ditandai
`[x]`.

## 7. Non-Goals

- Tidak membuat `config/organization.php`, `config/branding.php`,
  `config/features.php` baru (milestone masing-masing).
- Tidak menambah env var untuk fitur yang belum ada.
- Tidak mengubah perilaku config yang ada (nilai default tetap).
- Tidak implementasi validasi env runtime (boot-time check) — dokumentasi
  cukup untuk milestone ini (validasi masuk milestone Installer §37-38).
- Tidak mengubah `core/Config/core.php` selain dokumentasi.

## 8. Verifikasi / Acceptance

- Grep check: setiap `env()` di `config/` tercantum di `environment.md`.
- `.env.example` mencerminkan semua `env()` di `config/` (perbandingan grep).
- Tidak ada `env()` langsung di kode aplikasi baru (hanya di config/).
- `composer check` hijau (tidak ada perubahan kode PHP — arch test tetap lolos).
- TODO §2 item terkait `[x]`; item deferred punya referensi milestone.
- `environment.md` konsisten dengan `coding.md` (referensi silang).
