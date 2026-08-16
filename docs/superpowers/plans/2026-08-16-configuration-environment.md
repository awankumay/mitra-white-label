# Configuration & Environment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menetapkan fondasi konfigurasi & environment — dokumentasi konvensi env var, sinkronisasi `.env.example` dengan `config/`, dan pemetaan checklist TODO §2.

**Architecture:** Dua bagian: (1) dokumen konvensi `environment.md` (aturan env vs config, daftar env var per kategori, ringkasan config file, kebijakan penambahan), (2) `.env.example` tersinkron penuh dengan semua `env()` di `config/` aktual. Item organization/branding/feature config di-defer ke milestone masing-masing (tetap `[ ]` + referensi).

**Tech Stack:** Markdown (dokumen), bash/grep (verifikasi). Tidak ada perubahan kode PHP — arch test tidak terpengaruh.

## Global Constraints

- Bahasa dokumen: Bahasa Indonesia (konsisten dokumen existing).
- `.env.example` mencerminkan semua `env()` di `config/` aktual — tidak ada env var untuk fitur yang belum ada (YAGNI).
- Variabel driver-lanjutan (AWS/SQS/Postmark/Slack/Resend/PaperTrail/Memcached credentials) tidak diaktifkan di `.env.example` — dikomentari `#` atau didokumentasikan di environment.md sebagai optional lanjutan.
- Secrets tidak pernah hardcode di config; hanya di `.env`.
- Item TODO defer (organization/branding/feature) tetap `[ ]` dengan catatan referensi milestone — bukan `[x]`.
- Tidak membuat config file baru (`config/organization.php` dll).
- Tidak mengubah perilaku config yang ada (nilai default tetap).
- Commit message: conventional commits (`docs:` untuk dokumen), satu task = satu commit.

---

### Task 1: Buat `docs/conventions/environment.md`

**Files:**
- Create: `docs/conventions/environment.md`

**Interfaces:**
- Consumes: Spec §3 (aturan env vs config), §4 (struktur dokumen), inventaris `env()` di `config/` (grep), `.env.example` aktual.
- Produces: Dokumen konvensi environment yang menjadi acuan developer + input Task 2 (kebijakan sinkronisasi).

- [ ] **Step 1: Tulis dokumen environment.md**

Buat file `docs/conventions/environment.md` dengan isi berikut:

```markdown
# Konvensi Environment

**Status:** Accepted
**Tanggal:** 2026-08-16
**Referensi:** PRD §53, config/*, .env.example

## Aturan env vs config

- `.env` / environment variables berisi nilai yang berbeda per lingkungan
  (development, production, on-premise) — hanya nilai primitif, tanpa logika.
- `config/*.php` berisi komposisi, default, struktur, dan logika; membaca
  `env()` dengan default aman.
- `core/Config/*.php` adalah config milik Core — di-merge via
  `mergeConfigFrom` dan dipublish via `publishes()` di `CoreServiceProvider`.
- Secrets (APP_KEY, DB_PASSWORD, MAIL_PASSWORD, API keys) hanya di `.env`,
  tidak pernah di config, tidak tampil di log/diagnostic (PRD §54).
- Kode aplikasi memakai `config()` facade, bukan `env()` langsung — `env()`
  hanya dipakai di file config.
- Gunakan `app()->environment()` untuk cek lingkungan, bukan `env('APP_ENV')`
  di kode (Laravel config cache).

### Kategori env var

- **Required** — harus ada di production; aplikasi gagal tanpa ini
  (APP_KEY, APP_ENV, APP_URL, DB_CONNECTION, dsb.).
- **Optional** — punya default aman di config (CACHE_STORE,
  QUEUE_CONNECTION, dsb.).

## Daftar Environment Variables

### Aplikasi & Debug

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `APP_NAME` | required | Laravel | Nama aplikasi | config/app.php |
| `APP_ENV` | required | production | Lingkungan (local/production) | config/app.php |
| `APP_KEY` | required | — | Enkripsi key (artisan key:generate) | config/app.php |
| `APP_DEBUG` | optional | false | Tampilkan debug error | config/app.php |
| `APP_TIMEZONE` | optional | UTC | Zona waktu aplikasi | config/app.php |
| `APP_URL` | required | http://localhost | URL aplikasi | config/app.php |
| `APP_LOCALE` | optional | en | Lokalisasi utama | config/app.php |
| `APP_FALLBACK_LOCALE` | optional | en | Fallback lokalisasi | config/app.php |
| `APP_FAKER_LOCALE` | optional | en_US | Locale faker | config/app.php |
| `APP_MAINTENANCE_DRIVER` | optional | file | Driver maintenance mode | config/app.php |
| `APP_MAINTENANCE_STORE` | optional | database | Store maintenance (bila database) | config/app.php |
| `APP_PREVIOUS_KEYS` | optional | — | Key lama (rotasi APP_KEY) | config/app.php |
| `BCRYPT_ROUNDS` | optional | 12 | Cost hashing bcrypt | config/hashing (via app) |
| `DEBUGBAR_ENABLED` | optional | true | Aktifkan debugbar | package debugbar |
| `PHP_CLI_SERVER_WORKERS` | optional | 4 | Worker server CLI | php artisan serve |

### Database

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `DB_CONNECTION` | required | sqlite | Driver database | config/database.php |
| `DB_HOST` | optional | 127.0.0.1 | Host DB (non-sqlite) | config/database.php |
| `DB_PORT` | optional | 3306/5432 | Port DB | config/database.php |
| `DB_DATABASE` | optional | laravel | Nama database | config/database.php |
| `DB_USERNAME` | optional | root | Username DB | config/database.php |
| `DB_PASSWORD` | optional | — | Password DB | config/database.php |
| `DB_URL` | optional | — | URL koneksi lengkap | config/database.php |
| `DB_FOREIGN_KEYS` | optional | true | Aktifkan FK constraint | config/database.php |
| `DB_SOCKET` | optional | — | Unix socket | config/database.php |
| `DB_CHARSET` | optional | utf8mb4 | Charset | config/database.php |
| `DB_COLLATION` | optional | utf8mb4_unicode_ci | Collation | config/database.php |
| `MYSQL_ATTR_SSL_CA` | optional | — | SSL CA untuk MySQL | config/database.php |

### Redis (opsional)

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `REDIS_CLIENT` | optional | phpredis | Client Redis | config/database.php |
| `REDIS_HOST` | optional | 127.0.0.1 | Host Redis | config/database.php |
| `REDIS_PASSWORD` | optional | null | Password Redis | config/database.php |
| `REDIS_PORT` | optional | 6379 | Port Redis | config/database.php |
| `REDIS_DB` | optional | 0 | DB index | config/database.php |
| `REDIS_CACHE_DB` | optional | 1 | DB cache index | config/database.php |
| `REDIS_CLUSTER` | optional | redis | Mode cluster | config/database.php |
| `REDIS_PREFIX` | optional | — | Prefix key | config/database.php |
| `REDIS_PERSISTENT` | optional | false | Koneksi persistent | config/database.php |
| `REDIS_URL` | optional | — | URL koneksi Redis | config/database.php |
| `REDIS_USERNAME` | optional | — | Username Redis | config/database.php |

### Session

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `SESSION_DRIVER` | required | database | Driver session | config/session.php |
| `SESSION_LIFETIME` | optional | 120 | Lifetime (menit) | config/session.php |
| `SESSION_EXPIRE_ON_CLOSE` | optional | false | Expire saat browser ditutup | config/session.php |
| `SESSION_ENCRYPT` | optional | false | Enkripsi session | config/session.php |
| `SESSION_CONNECTION` | optional | — | Koneksi DB session | config/session.php |
| `SESSION_TABLE` | optional | sessions | Tabel session | config/session.php |
| `SESSION_STORE` | optional | — | Store session | config/session.php |
| `SESSION_PATH` | optional | / | Path cookie | config/session.php |
| `SESSION_DOMAIN` | optional | null | Domain cookie | config/session.php |
| `SESSION_SECURE_COOKIE` | optional | — | Hanya HTTPS | config/session.php |
| `SESSION_HTTP_ONLY` | optional | true | Cookie httpOnly | config/session.php |
| `SESSION_SAME_SITE` | optional | lax | SameSite policy | config/session.php |
| `SESSION_PARTITIONED` | optional | false | Partitioned cookie | config/session.php |

### Cache

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `CACHE_STORE` | required | database | Store cache default | config/cache.php |
| `CACHE_PREFIX` | optional | — | Prefix key cache | config/cache.php |
| `DB_CACHE_CONNECTION` | optional | — | Koneksi DB cache | config/cache.php |
| `DB_CACHE_TABLE` | optional | cache | Tabel cache | config/cache.php |
| `DB_CACHE_LOCK_CONNECTION` | optional | — | Koneksi lock | config/cache.php |
| `DB_CACHE_LOCK_TABLE` | optional | cache_locks | Tabel lock | config/cache.php |
| `MEMCACHED_PERSISTENT_ID` | optional | — | Persistent ID | config/cache.php |
| `MEMCACHED_USERNAME` | optional | — | Username Memcached | config/cache.php |
| `MEMCACHED_PASSWORD` | optional | — | Password Memcached | config/cache.php |
| `MEMCACHED_HOST` | optional | 127.0.0.1 | Host Memcached | config/cache.php |
| `MEMCACHED_PORT` | optional | 11211 | Port Memcached | config/cache.php |
| `REDIS_CACHE_CONNECTION` | optional | cache | Koneksi Redis cache | config/cache.php |
| `REDIS_CACHE_LOCK_CONNECTION` | optional | default | Koneksi lock | config/cache.php |
| `DYNAMODB_CACHE_TABLE` | optional | cache | Tabel DynamoDB | config/cache.php |
| `DYNAMODB_ENDPOINT` | optional | — | Endpoint DynamoDB | config/cache.php |

### Queue

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `QUEUE_CONNECTION` | required | database | Driver queue | config/queue.php |
| `QUEUE_FAILED_DRIVER` | optional | database-uuids | Driver failed jobs | config/queue.php |
| `DB_QUEUE_CONNECTION` | optional | — | Koneksi DB queue | config/queue.php |
| `DB_QUEUE_TABLE` | optional | jobs | Tabel jobs | config/queue.php |
| `DB_QUEUE` | optional | default | Nama queue | config/queue.php |
| `DB_QUEUE_RETRY_AFTER` | optional | 90 | Retry setelah (detik) | config/queue.php |
| `BEANSTALKD_QUEUE_HOST` | optional | localhost | Host Beanstalkd | config/queue.php |
| `BEANSTALKD_QUEUE` | optional | default | Nama queue | config/queue.php |
| `BEANSTALKD_QUEUE_RETRY_AFTER` | optional | 90 | Retry setelah | config/queue.php |
| `SQS_PREFIX` | optional | — | Prefix SQS | config/queue.php |
| `SQS_QUEUE` | optional | default | Nama queue | config/queue.php |
| `SQS_SUFFIX` | optional | — | Suffix SQS | config/queue.php |
| `REDIS_QUEUE_CONNECTION` | optional | default | Koneksi Redis queue | config/queue.php |
| `REDIS_QUEUE` | optional | default | Nama queue | config/queue.php |
| `REDIS_QUEUE_RETRY_AFTER` | optional | 90 | Retry setelah | config/queue.php |

### Filesystem & Storage

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `FILESYSTEM_DISK` | required | local | Disk default | config/filesystems.php |
| `AWS_ACCESS_KEY_ID` | optional | — | AWS access key | config/filesystems.php |
| `AWS_SECRET_ACCESS_KEY` | optional | — | AWS secret | config/filesystems.php |
| `AWS_DEFAULT_REGION` | optional | us-east-1 | Region AWS | config/filesystems.php |
| `AWS_BUCKET` | optional | — | Bucket S3 | config/filesystems.php |
| `AWS_URL` | optional | — | URL S3 | config/filesystems.php |
| `AWS_ENDPOINT` | optional | — | Endpoint S3 | config/filesystems.php |
| `AWS_USE_PATH_STYLE_ENDPOINT` | optional | false | Path-style endpoint | config/filesystems.php |

### Mail

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `MAIL_MAILER` | required | log | Driver mail | config/mail.php |
| `MAIL_SCHEME` | optional | — | Scheme SMTP | config/mail.php |
| `MAIL_URL` | optional | — | URL SMTP | config/mail.php |
| `MAIL_HOST` | optional | 127.0.0.1 | Host SMTP | config/mail.php |
| `MAIL_PORT` | optional | 2525 | Port SMTP | config/mail.php |
| `MAIL_USERNAME` | optional | — | Username SMTP | config/mail.php |
| `MAIL_PASSWORD` | optional | — | Password SMTP | config/mail.php |
| `MAIL_EHLO_DOMAIN` | optional | — | EHLO domain | config/mail.php |
| `MAIL_SENDMAIL_PATH` | optional | /usr/sbin/sendmail | Path sendmail | config/mail.php |
| `MAIL_LOG_CHANNEL` | optional | — | Channel log mail | config/mail.php |
| `MAIL_FROM_ADDRESS` | required | hello@example.com | Alamat pengirim | config/mail.php |
| `MAIL_FROM_NAME` | optional | Example | Nama pengirim | config/mail.php |

### Logging

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `LOG_CHANNEL` | required | stack | Channel log default | config/logging.php |
| `LOG_STACK` | optional | single | Channel stack | config/logging.php |
| `LOG_DEPRECATIONS_CHANNEL` | optional | null | Channel deprecations | config/logging.php |
| `LOG_DEPRECATIONS_TRACE` | optional | false | Trace deprecations | config/logging.php |
| `LOG_LEVEL` | optional | debug | Level log | config/logging.php |
| `LOG_DAILY_DAYS` | optional | 14 | Retensi harian | config/logging.php |
| `LOG_SLACK_WEBHOOK_URL` | optional | — | Webhook Slack | config/logging.php |
| `LOG_SLACK_USERNAME` | optional | Laravel Log | Username Slack | config/logging.php |
| `LOG_SLACK_EMOJI` | optional | :boom: | Emoji Slack | config/logging.php |
| `LOG_PAPERTRAIL_HANDLER` | optional | SyslogUdpHandler | Handler PaperTrail | config/logging.php |
| `PAPERTRAIL_URL` | optional | — | URL PaperTrail | config/logging.php |
| `PAPERTRAIL_PORT` | optional | — | Port PaperTrail | config/logging.php |
| `LOG_STDERR_FORMATTER` | optional | — | Formatter stderr | config/logging.php |
| `LOG_SYSLOG_FACILITY` | optional | LOG_USER | Facility syslog | config/logging.php |

### Auth

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `AUTH_GUARD` | optional | web | Guard default | config/auth.php |
| `AUTH_PASSWORD_BROKER` | optional | users | Password broker | config/auth.php |
| `AUTH_MODEL` | optional | App\Models\User | Model user | config/auth.php |
| `AUTH_PASSWORD_RESET_TOKEN_TABLE` | optional | password_reset_tokens | Tabel reset token | config/auth.php |
| `AUTH_PASSWORD_TIMEOUT` | optional | 10800 | Timeout reset (detik) | config/auth.php |

### Services

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `POSTMARK_TOKEN` | optional | — | Token Postmark | config/services.php |
| `RESEND_KEY` | optional | — | API key Resend | config/services.php |
| `SLACK_BOT_USER_OAUTH_TOKEN` | optional | — | Token Slack | config/services.php |
| `SLACK_BOT_USER_DEFAULT_CHANNEL` | optional | — | Channel default Slack | config/services.php |

### White Label

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `FILAMENT_WHITE_LABEL_ENABLED` | optional | true | Aktifkan white label | config/filament-white-label.php |
| `FILAMENT_WHITE_LABEL_CACHE_TTL` | optional | 300 | TTL cache (detik) | config/filament-white-label.php |
| `FILAMENT_WHITE_LABEL_DISK` | optional | public | Disk penyimpanan branding | config/filament-white-label.php |
| `FILAMENT_WHITE_LABEL_DISABLE_CSS` | optional | false | Nonaktifkan custom CSS | config/filament-white-label.php |
| `FILAMENT_WHITE_LABEL_PREVIEW` | optional | false | Tampilkan preview | config/filament-white-label.php |
| `FILAMENT_WHITE_LABEL_LOGIN_ENABLED` | optional | true | Login branding aktif | config/filament-white-label.php |
| `GOOGLE_FONTS_API_KEY` | optional | — | API key Google Fonts | config/filament-white-label.php |

### Boost

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `BOOST_ENABLED` | optional | true | Master switch Boost | config/boost.php |
| `BOOST_RULES_ENABLED` | optional | true | Aktifkan project rules | config/boost.php |
| `BOOST_RULES_SCOPED_GUIDELINES` | optional | false | Guideline path-scoped | config/boost.php |
| `BOOST_BROWSER_LOGS_WATCHER` | optional | true | Browser log watcher | config/boost.php |
| `BOOST_BROWSER_LOG_LEVELS` | optional | error,warning,info,debug | Level browser log | config/boost.php |
| `BOOST_PHP_EXECUTABLE_PATH` | optional | — | Path PHP custom | config/boost.php |
| `BOOST_COMPOSER_EXECUTABLE_PATH` | optional | — | Path Composer custom | config/boost.php |
| `BOOST_NPM_EXECUTABLE_PATH` | optional | — | Path npm custom | config/boost.php |
| `BOOST_VENDOR_BIN_EXECUTABLE_PATH` | optional | — | Path vendor/bin custom | config/boost.php |
| `BOOST_CURRENT_DIRECTORY_EXECUTABLE_PATH` | optional | — | Path cwd executable | config/boost.php |

### Vite

| Variabel | Tipe | Default | Deskripsi | Sumber |
|---|---|---|---|---|
| `VITE_APP_NAME` | optional | ${APP_NAME} | Nama app frontend | vite.config.js |

## Ringkasan Konfigurasi Aplikasi

- **`config/app.php`** — identitas aplikasi (nama, env, debug, url, locale,
  timezone, maintenance). Default Laravel 12; locale/timezone dikustomisasi
  via `.env`.
- **`config/auth.php`** — guard, provider, password reset, timeout.
  Model user `App\Models\User`; integrasi dengan Filament Shield/Spatie
  Permission (lihat `config/permission.php`).
- **`config/database.php`** — koneksi default `sqlite` (PRD §53: dev/testing),
  MySQL/PostgreSQL tersedia untuk production; Redis cache/queue.
- **`config/cache.php`** — store default `database`; Redis/Memcached/DynamoDB
  untuk scale.
- **`config/queue.php`** — driver default `database`; Redis/SQS/Beanstalkd
  untuk production.
- **`config/session.php`** — driver default `database` (tabel sessions).
- **`config/filesystems.php`** — disk default `local`; S3 untuk storage cloud.
- **`config/mail.php`** — mailer default `log`; SMTP untuk production.
- **`config/logging.php`** — channel default `stack` (single); PaperTrail/Slack
  opsional.
- **`config/services.php`** — kredensial layanan pihak ketiga
  (Postmark, Resend, Slack, AWS).
- **`config/filament-shield.php`** — konfigurasi Filament Shield (RBAC);
  separator `:`, case snake, super admin via gate `before`.
- **`config/filament-white-label.php`** — branding (nama, logo, warna, login);
  white label adalah Core capability (PRD §28).
- **`config/filament-logger.php`** — audit logging Filament (PRD §32).
- **`config/permission.php`** — konfigurasi Spatie Permission (dipakai Shield).
- **`config/boost.php`** — tooling development (rules, skills, browser logs).
- **`core/Config/core.php`** — config Core (daftar `providers` domain);
  di-merge + dipublish via `CoreServiceProvider`.

## Kebijakan Penambahan Env Var

- Setiap `env()` baru di `config/` wajib didokumentasikan di dokumen ini dan
  ditambahkan ke `.env.example`.
- Variabel driver-lanjutan (AWS/SQS/Postmark/Slack/Resend/PaperTrail/
  Memcached) didokumentasikan di tabel, tapi dikomentari di `.env.example`
  (opsional lanjutan).
- Tidak menambah env var untuk fitur yang belum ada (YAGNI).
```

- [ ] **Step 2: Verifikasi isi**

Jalankan:

```bash
grep -c "^| `" docs/conventions/environment.md
```

Expected: jumlah baris tabel env var > 100 (semua kategori tercakup).

- [ ] **Step 3: Commit**

```bash
git add docs/conventions/environment.md
git commit -m "docs: add environment conventions (TODO 2)"
```

---

### Task 2: Update `.env.example` — sinkronisasi penuh

**Files:**
- Modify: `.env.example`

**Interfaces:**
- Consumes: Spec §5 (struktur .env.example), inventaris `env()` di `config/`, Task 1 (daftar env var).
- Produces: `.env.example` tersinkron — developer baru bisa lihat semua variabel yang tersedia.

- [ ] **Step 1: Ganti seluruh isi .env.example**

Baca dulu `.env.example` aktual (via git show bila perlu), lalu ganti dengan struktur berikut (nilai mengikuti config/ + kustomisasi lokal):

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

- [ ] **Step 2: Verifikasi sinkronisasi**

Jalankan:

```bash
grep -ohrE "env\('[A-Z_]+" config/ | sed "s/env('//" | sort -u > /tmp/config_envs.txt
grep -oE "^[A-Z_]+" .env.example | sort -u > /tmp/example_envs.txt
comm -23 /tmp/config_envs.txt /tmp/example_envs.txt
```

Expected: tidak ada env var dari config/ yang hilang di `.env.example` (output kosong untuk yang wajib diaktifkan; driver-lanjutan seperti AWS/SQS/Postmark boleh tidak aktif tapi tetap terdokumentasi di environment.md).

Catatan: di Windows gunakan PowerShell setara:
```powershell
(Select-String -Path config\*.php -Pattern "env\('([A-Z_]+)" | ForEach-Object { $_.Matches[0].Groups[1].Value }) | Sort-Object -Unique
```

- [ ] **Step 3: Commit**

```bash
git add .env.example
git commit -m "docs: sync .env.example with config (TODO 2)"
```

---

### Task 3: Update `docs/conventions/coding.md` — referensi environment

**Files:**
- Modify: `docs/conventions/coding.md`

**Interfaces:**
- Consumes: Task 1 (environment.md final).
- Produces: coding.md merujuk environment.md — developer tahu dokumen konvensi env.

- [ ] **Step 1: Tambah referensi environment.md**

Update header referensi di `docs/conventions/coding.md` dari:

```markdown
**Referensi:** ADR-001 s.d. ADR-010, naming.md, directory-structure.md,
spec `docs/superpowers/specs/2026-08-16-architecture-rules-design.md`
```

menjadi:

```markdown
**Referensi:** ADR-001 s.d. ADR-010, naming.md, directory-structure.md,
environment.md, spec `docs/superpowers/specs/2026-08-16-architecture-rules-design.md`
```

- [ ] **Step 2: Verifikasi**

Jalankan:

```bash
grep -n "environment.md" docs/conventions/coding.md
```

Expected: `environment.md` muncul di header referensi.

- [ ] **Step 3: Commit**

```bash
git add docs/conventions/coding.md
git commit -m "docs: reference environment conventions in coding (TODO 2)"
```

---

### Task 4: Update `docs/TODO.md` — checklist §2

**Files:**
- Modify: `docs/TODO.md` (bagian §2, baris 71-87)

**Interfaces:**
- Consumes: Spec §6.2 (pemetaan checklist), Task 1-3 (deliverable final).
- Produces: Checklist §2 tercentang sesuai pemetaan; item defer punya referensi.

- [ ] **Step 1: Update checklist §2**

Ganti blok §2 dari:

```markdown
# 2. Configuration & Environment

- [ ] Review `.env.example`
- [ ] Define required environment variables
- [ ] Define optional environment variables
- [ ] Define application configuration
- [ ] Define Core configuration
- [ ] Define security configuration
- [ ] Define organization configuration
- [ ] Define branding configuration
- [ ] Define feature configuration
- [ ] Define localization configuration
- [ ] Define database configuration
- [ ] Define cache configuration
- [ ] Define queue configuration
- [ ] Define filesystem configuration
```

menjadi:

```markdown
# 2. Configuration & Environment

- [x] Review `.env.example` — `.env.example`, spec §5
- [x] Define required environment variables — `docs/conventions/environment.md`, spec §3.2
- [x] Define optional environment variables — `docs/conventions/environment.md`, spec §3.2
- [x] Define application configuration — `docs/conventions/environment.md`, spec §4
- [x] Define Core configuration — `docs/conventions/environment.md`, spec §4
- [x] Define security configuration — `docs/conventions/environment.md`, spec §4
- [ ] Define organization configuration — defer ke milestone Organization
- [ ] Define branding configuration — defer ke milestone White Label
- [ ] Define feature configuration — defer ke milestone Feature Registry
- [x] Define localization configuration — `docs/conventions/environment.md`, spec §4
- [x] Define database configuration — `docs/conventions/environment.md`, spec §4
- [x] Define cache configuration — `docs/conventions/environment.md`, spec §4
- [x] Define queue configuration — `docs/conventions/environment.md`, spec §4
- [x] Define filesystem configuration — `docs/conventions/environment.md`, spec §4
```

- [ ] **Step 2: Verifikasi**

Jalankan:

```bash
grep -n "Define.*configuration\|Review.*env" docs/TODO.md | sed -n '1,20p'
```

Periksa: 11 item `[x]` + 3 item defer `[ ]` dengan referensi milestone; item lain di TODO tidak berubah.

- [ ] **Step 3: Commit**

```bash
git add docs/TODO.md
git commit -m "docs: mark configuration checklist done, defer domain configs (TODO 2)"
```

---

### Task 5: Verifikasi akhir

**Files:**
- Verify only: `docs/conventions/environment.md`, `.env.example`, `docs/conventions/coding.md`, `docs/TODO.md`

**Interfaces:**
- Consumes: Task 1-4.
- Produces: Jaminan sinkronisasi env var + konsistensi dokumen.

- [ ] **Step 1: Grep check — semua env() di config/ tercantum di environment.md**

Jalankan:

```bash
grep -ohrE "env\('[A-Z_]+" config/ | sed "s/env('//" | sort -u > /tmp/config_envs.txt
grep -oE "`[A-Z_]+`" docs/conventions/environment.md | sed 's/`//g' | sort -u > /tmp/env_doc_envs.txt
comm -23 /tmp/config_envs.txt /tmp/env_doc_envs.txt
```

Expected: output kosong (semua env var config terdokumentasi). Beberapa env var driver (AWS/SQS/dll) mungkin hanya di tabel — pastikan ada di environment.md.

Catatan Windows (PowerShell):

```powershell
# env var di config/
$configEnvs = (Select-String -Path config\*.php -Pattern "env\('([A-Z_]+)" | ForEach-Object { $_.Matches[0].Groups[1].Value }) | Sort-Object -Unique

# env var terdokumentasi di environment.md
$docEnvs = (Select-String -Path docs\conventions\environment.md -Pattern '`([A-Z_]+)`' | ForEach-Object { $_.Matches[0].Groups[1].Value }) | Sort-Object -Unique

# yang ada di config tapi tidak di doc
Compare-Object $configEnvs $docEnvs | Where-Object { $_.SideIndicator -eq '<=' }
```

- [ ] **Step 2: Cek .env.example vs environment.md konsisten**

Jalankan:

```bash
grep -oE "^[A-Z_]+" .env.example | sort -u > /tmp/example_envs.txt
grep -oE "`[A-Z_]+`" docs/conventions/environment.md | sed 's/`//g' | sort -u > /tmp/env_doc_envs.txt
comm -13 /tmp/env_doc_envs.txt /tmp/example_envs.txt
```

Expected: tidak ada env var di `.env.example` yang tidak terdokumentasi di environment.md.

- [ ] **Step 3: Jalankan test**

Jalankan:

```bash
php artisan test
```

Expected: semua test hijau (tidak ada perubahan kode PHP, hanya dokumen — arch test tetap lolos).

- [ ] **Step 4: Pastikan hanya file yang dimaksud berubah**

Jalankan:

```bash
git diff --stat HEAD~4
```

Expected: hanya `docs/conventions/environment.md` (baru), `.env.example`, `docs/conventions/coding.md`, `docs/TODO.md` yang berubah dalam 4 commit terakhir.

- [ ] **Step 5: Commit perbaikan (jika ada)**

Jika Step 1-4 menemukan masalah, perbaiki inline lalu:

```bash
git add -A
git commit -m "docs: fix environment conventions consistency"
```

Jika bersih, tidak ada commit tambahan — milestone selesai.
