# Mitra White Label

## Product Requirements Document

**Document:** `PRD.md`
**Product:** Mitra White Label
**Version:** 1.0.0
**Status:** Final — Architecture Baseline
**Framework:** Laravel 13
**Admin UI:** Filament 5
**Application Type:** Standalone Enterprise Application Starterkit

---

# 1. Product Overview

**Mitra White Label** adalah Starterkit dan Core Application Foundation berbasis Laravel 13 dan Filament 5 untuk membangun berbagai aplikasi enterprise standalone.

Mitra White Label menyediakan fondasi umum yang dibutuhkan oleh aplikasi bisnis tanpa mengunci project ke domain tertentu.

Starterkit dapat menjadi foundation untuk:

- ERP
- HMS / Hotel Management System
- POS
- HRIS
- Accounting
- Inventory Management
- Procurement
- Project Management
- Hospital / Clinic Management
- Education Management
- Custom Enterprise Application

Mitra White Label **bukan ERP, HMS, POS, HRIS, atau SaaS platform**.

Produk ini menyediakan **Core System** yang nantinya dapat menjadi basis berbagai aplikasi tersebut.

---

# 2. Product Vision

> **Build once, extend for any standalone enterprise application.**

Developer dapat memulai project baru dari Mitra White Label dan langsung mendapatkan:

```text
Mitra White Label
│
├── Authentication
├── Account Security
├── Authorization
├── Organization
├── Organizational Units
├── Settings
├── Branding
├── Audit
├── Notifications
├── Feature Registry
├── Developer Tools
└── Installer
```

Kemudian application-specific functionality dibangun di atas Core System.

Contoh:

```text
Mitra White Label
        │
        ├── ERP
        ├── HMS
        ├── POS
        ├── HRIS
        ├── Inventory
        └── Custom Application
```

Core System harus tetap independent terhadap domain bisnis tersebut.

---

# 3. Primary Goals

Mitra White Label harus:

1. Menjadi foundation Laravel + Filament untuk standalone enterprise applications.
2. Mendukung deployment individual untuk setiap client.
3. Mendukung On-Premise deployment.
4. Mendukung deployment melalui local network/LAN tanpa internet.
5. Tidak memiliki mandatory runtime dependency terhadap internet.
6. Mendukung satu organization dengan struktur organizational unit yang hierarkis.
7. Menyediakan modern authentication security.
8. Menyediakan RBAC dan permission system.
9. Menyediakan organizational data scope.
10. Menyediakan centralized settings architecture.
11. Menyediakan white-label branding.
12. Menyediakan audit dan security event architecture.
13. Menyediakan feature/module registry.
14. Menyediakan application installer.
15. Menyediakan system diagnostic commands.
16. Menyediakan enterprise-aware developer generators.
17. Mempertahankan compatibility dengan Laravel dan Filament generators.
18. Memiliki architecture yang dapat dikembangkan menjadi berbagai jenis application tanpa redesign Core.

---

# 4. Non-Goals

## 4.1 SaaS Platform

Mitra White Label Core **bukan SaaS platform**.

Core tidak menyediakan:

- SaaS tenant billing
- Subscription management
- Tenant provisioning
- Central tenant administration
- Tenant database isolation
- SaaS onboarding
- SaaS usage metering

`Tenant` bukan bagian dari Core domain.

Package yang secara khusus menyediakan SaaS tenant-membership architecture tidak menjadi dependency Core.

---

## 4.2 Business Domain

Core tidak menyediakan business modules seperti:

- Accounting
- Inventory
- Purchasing
- Sales
- Payroll
- Hotel Reservation
- POS
- Warehouse
- Hospital Management

Fitur tersebut merupakan responsibility dari application/module layer.

---

## 4.3 Offline-First Client Synchronization

Core mendukung:

> **Application server yang dapat berjalan tanpa koneksi internet.**

Core tidak menjanjikan:

```text
Client Device
      ↓
Offline Database
      ↓
Synchronization
      ↓
Central Server
```

Disconnected-device synchronization merupakan future capability di luar Core v1.

---

# 5. Deployment Model

Mitra White Label harus mendukung:

```text
Development
     ↓
Standalone Production
     ↓
On-Premise LAN
     ↓
On-Premise + Internet
     ↓
Cloud VPS
     ↓
Hybrid Environment
```

## 5.1 Standalone Deployment

Default deployment model:

```text
One Installation
        │
        └── One Organization
```

Setiap client dapat memiliki application instance sendiri.

Contoh:

```text
Client A
└── ERP Instance

Client B
└── HMS Instance

Client C
└── POS Instance
```

Tidak ada kebutuhan untuk shared SaaS infrastructure.

---

# 6. Internet Independence

Core functionality harus dapat berjalan tanpa koneksi internet.

Fitur berikut tidak boleh membutuhkan external internet service:

- Login
- Logout
- Authentication
- Authorization
- RBAC
- Organization management
- Organizational unit management
- CRUD
- Settings
- Branding
- Audit
- Notifications berbasis database
- Core application modules

Internet hanya menjadi optional dependency untuk external integrations seperti:

- Cloud backup
- External API
- Email provider
- SMS
- WhatsApp
- Payment gateway
- Cloud storage
- Remote monitoring
- Online update

---

# 7. Core Architecture

Architecture utama:

```text
                    MITRA WHITE LABEL
                           │
                     CORE SYSTEM
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
     Security        Organization        Platform
        │                  │                  │
        │                  │                  │
 Authentication       Organization       Settings
 2FA / Passkey        Org Units          Branding
 Sessions             Assignment         Features
 Security Events      Context            Audit
        │                  │                  │
        └──────────────────┼──────────────────┘
                           │
                     Authorization
                           │
                      Shield / RBAC
                           │
                     Filament 5
                           │
                     Laravel 13
```

Application-specific modules berada di atas Core System.

---

# 8. Single Organization Architecture

Core menggunakan:

> **Single Organization as the default application model.**

Satu installation merepresentasikan satu client/company.

Contoh:

```text
Installation
│
└── PT ABC Indonesia
```

Schema dapat dibuat future-compatible untuk kemungkinan multiple organizations, tetapi:

- UX Core v1 berorientasi single organization.
- User tidak perlu memilih organization saat login.
- Organization tidak diperlakukan sebagai SaaS tenant.
- Organization switching bukan Core workflow.

---

# 9. Organization

`Organization` merepresentasikan perusahaan atau institusi yang menggunakan application instance.

Contoh:

```text
PT ABC Indonesia
PT XYZ Hospitality
RS ABC
ABC Construction
```

Organization menjadi owner dari:

- Organizational Units
- Organization settings
- Organization branding
- User assignments
- Application configuration

---

# 10. Organizational Unit

Core menggunakan konsep:

> **Organizational Unit**

dan tidak menggunakan `Branch` sebagai abstraction utama.

Organizational Unit dapat merepresentasikan:

- Head Office
- Branch
- Sub Office
- Site
- Warehouse
- Outlet
- Hotel
- Clinic
- Project
- Office

Contoh:

```text
PT ABC Indonesia
│
└── Head Office
    │
    ├── Sub Office Jakarta
    │   ├── Site A
    │   └── Site B
    │
    └── Sub Office Surabaya
```

---

# 11. Organizational Unit Hierarchy

Organizational Units mendukung hierarchy menggunakan:

```text
parent_id
```

Relationship:

```text
Organization
    │
    └── Organizational Unit
            │
            ├── Organizational Unit
            │      └── Organizational Unit
            │
            └── Organizational Unit
```

Hierarchy harus mendukung:

- Parent-child relationship
- Root unit
- Nested units
- Unit tree navigation

---

# 12. Organizational Unit Type

Default type:

```text
HEAD_OFFICE
BRANCH
SUB_OFFICE
SITE
```

Application layer dapat memperkenalkan type tambahan:

```text
WAREHOUSE
OUTLET
HOTEL
CLINIC
PROJECT
```

Core tidak boleh bergantung pada business-specific unit types.

Unit type harus dapat diperluas.

---

# 13. User Assignment

User dapat memiliki akses ke lebih dari satu organizational unit.

Contoh:

```text
Andika
├── Head Office
├── Branch Bandung
└── Branch Surabaya
```

Core menggunakan assignment relationship:

```text
User
    │
    └── Organizational Unit Assignments
```

User tidak memiliki single:

```text
organization_unit_id
```

sebagai sumber authorization utama.

---

# 14. Primary Organizational Unit

User dapat memiliki satu primary organizational unit.

Contoh:

```text
Andika

Primary:
Head Office

Accessible:
Head Office
Branch Bandung
Branch Surabaya
```

Primary unit digunakan sebagai default context ketika user masuk ke application.

---

# 15. Organizational Context

Core menyediakan abstraction:

```text
OrganizationContext
OrganizationalUnitContext
```

Context harus independent dari Filament.

Context dapat digunakan oleh:

- Models
- Policies
- Actions
- Services
- Jobs
- Commands
- Notifications
- Application modules
- Filament UI

Contoh conceptual usage:

```php
app(OrganizationalUnitContext::class)->current();
```

---

# 16. Context Lifecycle

Default resolution:

```text
User Login
     ↓
Organization Context
     ↓
Primary Organizational Unit
     ↓
Current Unit
```

Jika user memiliki beberapa unit:

```text
Current Unit
│
├── Head Office
├── Branch Bandung
└── Branch Surabaya
```

User dapat memilih unit yang dapat diakses.

Context switching harus:

- Validate user access.
- Persist current context appropriately.
- Prevent unauthorized unit selection.
- Work independently from Filament.

---

# 17. Data Scope

Core mengenal tiga kategori data scope:

```text
Global
Organization-scoped
Organizational-unit-scoped
```

### Global

Contoh:

```text
System configuration
Application metadata
Feature definitions
```

### Organization-scoped

Contoh:

```text
Organization profile
Organization settings
Organization branding
```

### Organizational-unit-scoped

Contoh:

```text
Operational transactions
Branch settings
Site-specific records
```

Application developer harus menentukan scope setiap domain entity.

Tidak semua model otomatis organizational-unit-scoped.

---

# 18. Authorization Model

Authorization terdiri dari:

```text
Authentication
       ↓
Identity
       ↓
Role
       ↓
Permission
       ↓
Organizational Scope
```

Permission menentukan:

> Apa yang boleh dilakukan?

Organizational scope menentukan:

> Di area mana user boleh melakukannya?

---

# 19. RBAC

Core menggunakan Filament Shield / Spatie Permission sebagai authorization foundation.

Role default dapat mencakup:

```text
Super Admin
Administrator
Manager
Supervisor
Staff
Viewer
```

Role harus dapat ditambah atau dimodifikasi oleh application.

Permission menggunakan pola `action:subject` (format Filament Shield v4.3.1):

```text
action:subject
```

Contoh:

```text
view:users
create:users
update:users
delete:users

view:organizational_units
create:organizational_units
update:organizational_units
delete:organizational_units
```

---

# 20. Organizational Scope Authorization

Authorization harus mendukung kombinasi:

```text
Permission
+
Organization
+
Organizational Unit
```

Contoh:

```text
Finance Manager

Permissions:
    invoice.view
    invoice.approve
    report.view

Scope:
    Head Office
    Bandung
    Surabaya
```

Cashier:

```text
Cashier

Permissions:
    sales.create
    sales.view

Scope:
    Bandung
```

---

# 21. Authentication

Core menyediakan:

- Login
- Logout
- Password authentication
- Password reset
- Email verification
- Session management

Authentication tidak boleh membutuhkan external identity provider.

---

# 22. Account Security

Account Security terdiri dari:

```text
Account Security
│
├── Password
├── Two-Factor Authentication
├── Recovery Codes
├── Passkeys
├── Active Sessions
└── Security Events
```

Breezy digunakan sebagai salah satu foundation untuk account/security UX.

---

# 23. Two-Factor Authentication

Core mendukung:

- TOTP
- Recovery codes
- Enable / disable
- Recovery code regeneration
- 2FA policy

Policy minimal:

```text
2FA Optional
2FA Required
```

Application dapat menentukan requirement sesuai kebutuhan.

---

# 24. Passkeys

Core mendukung passkeys sebagai modern authentication mechanism.

User dapat:

- Register passkey
- Name passkey
- View registered passkeys
- Revoke passkey

Passkey implementation harus menggunakan established authentication/security implementation dan tidak membuat custom cryptography.

---

# 25. Session Security

User dapat melihat active sessions.

Minimal information:

```text
Device
Browser
Platform
IP
Last Active
Current Session
```

User dapat:

```text
Revoke Session
Revoke Other Sessions
```

---

# 26. Security Events

Core menyediakan security event architecture.

Event examples:

```text
LoginSucceeded
LoginFailed
PasswordChanged
TwoFactorEnabled
TwoFactorDisabled
PasskeyRegistered
PasskeyRevoked
SessionRevoked
```

Security events dapat digunakan oleh:

- Security monitoring
- Audit
- Notifications
- Reporting

---

# 27. Settings Architecture

Settings dibagi menjadi:

```text
System
Organization
Organizational Unit
User
```

## System

```text
Application
Security
Mail
Queue
Storage
Localization
```

## Organization

```text
Company Information
Fiscal Configuration
Currency
Branding
Notification Defaults
```

## Organizational Unit

```text
Address
Timezone
Operational Settings
Numbering
Contact
```

## User

```text
Language
Timezone
Theme
Notifications
```

Settings architecture harus mendukung extension dari application modules.

---

# 28. White Label

White Label merupakan Core Capability.

Minimal:

```text
Application Name
Company Name
Logo
Dark Logo
Favicon
Primary Color
Secondary Color
Login Branding
Email Branding
Footer
```

Branding harus dapat dikembangkan menjadi:

```text
System Branding
        ↓
Organization Branding
        ↓
Organizational Unit Branding
```

Organization-level branding menjadi primary requirement.

Unit-level branding bersifat extensible.

---

# 29. Feature Registry

Core menyediakan Feature Registry untuk mengetahui capability yang aktif.

Feature examples:

```text
User Management
Organization
Organizational Units
Audit
Notifications
```

Application modules dapat mendaftarkan feature mereka sendiri:

```text
Accounting
Inventory
Purchasing
HR
POS
HMS
```

Feature Registry bukan SaaS feature billing system.

---

# 30. Module Architecture

Core harus **module-ready**, tetapi tidak memaksakan Modular Monolith penuh.

Application modules harus memiliki predictable structure.

Contoh:

```text
modules/
└── Inventory/
    ├── Module.php
    ├── Config/
    ├── Database/
    ├── Domain/
    ├── Filament/
    ├── Models/
    ├── Policies/
    └── ...
```

Module dapat menggunakan Core:

```text
Core
 ↑
Module
```

Core tidak boleh bergantung kepada business modules:

```text
Core
 X
Inventory
Accounting
HMS
```

---

# 31. Module Contract

Module harus dapat mendefinisikan:

- Identity
- Name
- Version
- Configuration
- Service provider / registration
- Routes
- Migrations
- Models
- Policies
- Filament resources
- Permissions
- Features

Module registration harus predictable dan discoverable.

---

# 32. Audit System

Core menyediakan Audit System untuk business/application actions.

Contoh:

```text
User created
User updated
Role changed
Organization updated
Unit created
Unit updated
Settings changed
Record deleted
```

Audit record minimal:

```text
actor
action
subject
timestamp
IP
metadata
```

Audit Log berbeda dari application log.

---

# 33. Application Logging

Laravel logging digunakan untuk:

```text
Exception
Warning
Application Error
Infrastructure Error
Queue Error
Debug Information
```

Application log tidak menggantikan:

```text
Audit Log
Security Events
```

---

# 34. Notifications

Core menyediakan notification abstraction.

Minimum channels:

```text
Database
Filament Notification
Mail
```

Architecture harus memungkinkan future adapters:

```text
SMS
WhatsApp
Push
Webhook
```

External channels bersifat optional.

---

# 35. Console Command Philosophy

Console commands merupakan **first-class Developer Experience feature**.

Laravel dan Filament native generators harus tetap tersedia.

Mitra White Label tidak menggantikan:

```text
php artisan make:model
php artisan make:controller
php artisan make:migration
php artisan make:request
php artisan make:policy
php artisan make:test
```

dan Filament generators:

```text
php artisan make:filament-resource
php artisan make:filament-page
php artisan make:filament-widget
```

Mitra menyediakan higher-level generators.

---

# 36. Mitra Console Namespace

Semua custom command menggunakan:

```text
mitra:
```

Contoh:

```bash
php artisan mitra:install
php artisan mitra:doctor
php artisan mitra:health
php artisan mitra:about
```

Generator:

```bash
php artisan mitra:make:module
php artisan mitra:make:crud
php artisan mitra:make:action
php artisan mitra:make:service
php artisan mitra:make:contract
php artisan mitra:make:enum
```

---

# 37. Installer

Primary installer:

```bash
php artisan mitra:install
```

Installer lifecycle:

```text
Preflight
    ↓
Environment
    ↓
Database
    ↓
Application
    ↓
Organization
    ↓
Initial Organizational Unit
    ↓
Administrator
    ↓
Features
    ↓
Branding
    ↓
Migration
    ↓
Seed
    ↓
Finalize
```

---

# 38. Installer Requirements

Installer harus:

- Interactive.
- Non-interactive capable.
- Idempotent where possible.
- Safe against accidental destructive operations.
- Validating prerequisites.
- Providing clear progress.
- Providing actionable errors.

Support:

```bash
php artisan mitra:install --no-interaction
```

Installer tidak boleh secara default melakukan destructive database operation.

Perintah seperti database reset/fresh harus tetap menjadi explicit developer/admin operation.

---

# 39. Doctor Command

Command:

```bash
php artisan mitra:doctor
```

Memeriksa:

```text
PHP
Laravel
Filament
Database
Cache
Queue
Storage
APP_KEY
Encryption
Filesystem
Permissions
Mail
Configuration
```

Output harus human-readable.

Future capability:

```bash
php artisan mitra:doctor --json
```

untuk automation.

Diagnostic command tidak boleh menampilkan secrets:

```text
APP_KEY
DB_PASSWORD
MAIL_PASSWORD
API_SECRET
```

---

# 40. Health Command

Command:

```bash
php artisan mitra:health
```

Tujuan:

> Runtime/system health verification.

Berbeda dari `mitra:doctor`.

`doctor` berfokus pada deployment/configuration diagnosis.

`health` berfokus pada application runtime health.

Future implementation dapat menyediakan:

```text
GET /health
```

untuk monitoring.

---

# 41. About Command

Command:

```bash
php artisan mitra:about
```

Contoh informasi:

```text
Mitra White Label

Version
Laravel
Filament
PHP

Application
Environment
Organization

Enabled Features
Enabled Modules
```

Tidak boleh menampilkan sensitive configuration.

---

# 42. Developer Generator Architecture

Mitra Generator Engine harus memiliki architecture yang konsisten:

```text
Mitra Generator Engine
│
├── Input
├── Options
├── Validation
├── Template
├── Filesystem Operations
├── Preview
└── Result
```

Generator harus mendukung:

```text
--dry-run
--force
--no-interaction
```

---

# 43. CRUD Generator

Command:

```bash
php artisan mitra:make:crud Product
```

Generator dapat menghasilkan:

```text
Model
Migration
Factory
Seeder
Policy
Filament Resource
Tests
```

Developer dapat memilih component yang dihasilkan.

Example:

```text
[x] Model
[x] Migration
[x] Factory
[x] Policy
[x] Filament Resource
[x] Tests
[ ] API Controller
[ ] Form Request
```

---

# 44. Organizationally Scoped CRUD

Generator harus dapat mendukung:

```bash
php artisan mitra:make:crud Product --unit-aware
```

Jika dipilih, generator dapat menghasilkan organizational-unit-aware architecture.

Namun:

> **Tidak semua CRUD otomatis scoped ke Organizational Unit.**

Developer harus secara eksplisit memilih scope.

---

# 45. Generator Dry Run

Semua generator yang menghasilkan multiple files harus mendukung:

```bash
php artisan mitra:make:crud Product --dry-run
```

Output:

```text
Would create:

✓ app/Models/Product.php
✓ app/Policies/ProductPolicy.php
✓ app/Filament/Resources/Products/ProductResource.php
✓ database/migrations/xxxx_create_products_table.php
✓ tests/Feature/ProductTest.php
```

Tidak ada filesystem modification saat `--dry-run`.

---

# 46. Module Generator

Command:

```bash
php artisan mitra:make:module Inventory
```

Membuat baseline module structure:

```text
modules/
└── Inventory/
    ├── Module.php
    ├── Config/
    ├── Database/
    ├── Domain/
    ├── Filament/
    ├── Models/
    └── Policies/
```

Module generator tidak boleh mengasumsikan business functionality.

---

# 47. Action Generator

Command:

```bash
php artisan mitra:make:action CreateProduct
```

Tujuan:

> Mendorong application logic berada pada reusable Action layer daripada menumpuk logic pada controller/resource.

---

# 48. Service Generator

Command:

```bash
php artisan mitra:make:service ProductService
```

Service digunakan ketika application membutuhkan reusable orchestration/service layer.

Tidak semua business logic wajib menggunakan Service.

---

# 49. Contract Generator

Command:

```bash
php artisan mitra:make:contract ProductRepository
```

Digunakan untuk menghasilkan application contracts ketika abstraction memang diperlukan.

Core tidak memaksakan repository pattern untuk seluruh model.

---

# 50. Enum Generator

Command:

```bash
php artisan mitra:make:enum ProductStatus
```

Membantu standardisasi PHP Enum usage.

---

# 51. Testing

Core harus menyediakan testing foundation:

```text
Unit Tests
Feature Tests
Authorization Tests
Authentication Tests
Console Command Tests
Installer Tests
```

Critical flows:

```text
Login
2FA
Passkey
Role
Permission
Organization access
Unit access
Context switching
Settings
Branding
Installer
Doctor
Health
Generators
```

---

# 52. Code Quality

Core menggunakan:

```text
Pest
PHPStan / Larastan
Laravel Pint
```

Quality command:

```bash
composer check
```

harus menjadi standard quality gate.

Target:

```text
Tests      → Pass
Static     → Pass
Formatting → Pass
```

---

# 53. Database Compatibility

Core harus menargetkan database yang umum digunakan oleh Laravel enterprise deployments:

```text
MySQL / MariaDB
PostgreSQL
SQLite
```

SQLite terutama digunakan untuk:

- Development
- Testing
- Lightweight deployments

Production deployment dapat menggunakan MySQL/MariaDB/PostgreSQL sesuai kebutuhan application.

---

# 54. Security Principles

Core harus:

- Menggunakan Laravel password hashing.
- Menggunakan CSRF protection.
- Menggunakan authorization policies.
- Menggunakan rate limiting.
- Mendukung 2FA.
- Mendukung passkeys.
- Mendukung session revocation.
- Tidak menyimpan password secara plaintext.
- Tidak menyimpan security secrets secara plaintext.
- Tidak menampilkan secrets pada diagnostic output.
- Tidak membutuhkan external internet untuk authentication core.

---

# 55. Upgradeability

Core harus meminimalkan framework/vendor modification.

Prefer:

```text
Contracts
Composition
Actions
Services
Policies
Extension points
```

daripada:

```text
Vendor modification
Framework modification
Heavy monkey patching
```

Tujuannya:

> Laravel dan Filament dapat di-upgrade tanpa membongkar Core Architecture.

---

# 56. Package Philosophy

Package hanya menjadi Core dependency jika memberikan functionality yang:

- Generic.
- Stabil.
- Reusable.
- Tidak mengunci business domain.
- Tidak mengunci SaaS architecture.

Core package categories:

```text
Authentication / Security
Authorization
White Label
Filament UX
```

Package yang memiliki opinionated SaaS tenancy architecture tidak menjadi Core dependency.

---

# 57. Recommended Core Models

Minimal Core models:

```text
User
Organization
OrganizationalUnit
```

Supporting models:

```text
AuditLog
SecurityEvent
Setting
```

Assignment:

```text
OrganizationUser
OrganizationalUnitUser
```

Tidak ada `Tenant` model pada Core.

---

# 58. Core Relationship

Conceptual relationship:

```text
Organization
│
├── Users
│
└── Organizational Units
       │
       └── Users
```

User:

```text
User
│
├── Roles
├── Permissions
├── Organizations
└── Organizational Units
```

Default application behavior:

```text
1 Installation
└── 1 Organization
```

---

# 59. Application Layer

Application-specific domain berada di atas Core.

Contoh ERP:

```text
Core
+
Accounting
+
Finance
+
Inventory
+
Purchasing
```

Contoh HMS:

```text
Core
+
Rooms
+
Reservations
+
Housekeeping
+
F&B
```

Contoh POS:

```text
Core
+
Products
+
Sales
+
Payments
+
Inventory
```

Core tidak boleh mengetahui detail domain tersebut.

---

# 60. Documentation Requirements

Starterkit harus menyediakan documentation untuk:

```text
Getting Started
Installation
Architecture
Authentication
Security
Organization
Organizational Units
Authorization
Settings
White Label
Modules
Console Commands
Generators
Testing
Deployment
On-Premise
Upgrade Guide
```

Developer documentation harus menjelaskan:

> bagaimana membangun application baru di atas Core.

Bukan hanya menjelaskan bagaimana menjalankan Starterkit.

---

# 61. Acceptance Criteria

Core System dianggap memenuhi PRD apabila:

## Installation

```text
✓ Fresh installation berhasil
✓ mitra:install berhasil
✓ Administrator dapat dibuat
✓ Organization tersedia
✓ Initial Organizational Unit tersedia
```

## Authentication

```text
✓ Login
✓ Logout
✓ Password reset
✓ Email verification
✓ Session management
```

## Security

```text
✓ 2FA
✓ Recovery codes
✓ Passkeys
✓ Session revocation
✓ Security events
```

## Authorization

```text
✓ Roles
✓ Permissions
✓ Policies
✓ Organizational scope
```

## Organization

```text
✓ Organization
✓ Organizational Unit
✓ Hierarchy
✓ User assignment
✓ Primary unit
✓ Context switching
```

## Platform

```text
✓ Settings
✓ Branding
✓ Feature Registry
✓ Audit
✓ Notifications
```

## Developer Experience

```text
✓ Laravel generators tetap tersedia
✓ Filament generators tetap tersedia
✓ Mitra generators tersedia
✓ CRUD generator
✓ Module generator
✓ Action generator
✓ Service generator
✓ Contract generator
✓ Enum generator
✓ Dry-run
```

## Deployment

```text
✓ On-Premise
✓ LAN
✓ Internet optional untuk Core
✓ Doctor
✓ Health
✓ About
```

---

# 62. Definition of Done

Mitra White Label Core System dianggap selesai apabila developer dapat melakukan:

```bash
php artisan mitra:install
```

kemudian memperoleh application foundation yang menyediakan:

```text
Authentication
Security
RBAC
Organization
Organizational Units
Settings
Branding
Audit
Notifications
Features
Installer
Diagnostics
Developer Generators
```

Developer kemudian dapat langsung menjalankan:

```bash
php artisan mitra:make:crud Product
```

dan mulai membangun domain application tanpa perlu mengubah Core Architecture.

---

# 63. MVP Implementation Phases

## Phase 1 — Foundation

```text
Core architecture
Configuration
Settings
Branding
Plugin organization
Base application conventions
```

## Phase 2 — Authentication & Security

```text
Breezy integration
2FA
Recovery codes
Passkeys
Sessions
Security events
```

## Phase 3 — Organization

```text
Organization
Organizational Unit
Hierarchy
User assignments
Primary unit
Context
```

## Phase 4 — Authorization

```text
Shield
Roles
Permissions
Policies
Organizational scopes
Context-aware authorization
```

## Phase 5 — Platform

```text
Feature Registry
Audit
Notifications
Health
```

## Phase 6 — Developer Experience

```text
mitra:install
mitra:doctor
mitra:health
mitra:about

mitra:make:module
mitra:make:crud
mitra:make:action
mitra:make:service
mitra:make:contract
mitra:make:enum
```

## Phase 7 — Documentation & Quality

```text
Architecture documentation
Developer guide
Deployment guide
On-Premise guide
Testing
CI / quality checks
```

---

# 64. Architectural Constraints

Beberapa keputusan berikut dianggap **locked decisions** untuk Core v1:

### Standalone

```text
Core = Standalone-first
```

### SaaS

```text
SaaS tenancy = Out of Scope
```

### Organization

```text
Default = Single Organization
```

### Branch

```text
Branch = Organizational Unit Type
```

### Hierarchy

```text
Organizational Unit supports parent-child hierarchy
```

### User Assignment

```text
User dapat memiliki multiple Organizational Units
```

### Context

```text
Context independent from Filament
```

### Data Scope

```text
Global
Organization
Organizational Unit
```

### Modules

```text
Module-ready
Modular Monolith not mandatory for Core
```

### Internet

```text
Core tidak membutuhkan internet saat runtime
```

### Offline

```text
Offline server/LAN supported
Disconnected client synchronization = Future
```

### Generator

```text
Laravel/Filament generators remain available
Mitra generators provide higher-level capabilities
```

### Installer

```text
mitra:install is the official application bootstrap command
```

---

# 65. Product North Star

Mitra White Label harus selalu dapat menjawab pertanyaan berikut:

> **"Jika besok saya ingin membuat ERP, HMS, POS, HRIS, atau aplikasi enterprise baru, apakah saya dapat mengambil Starterkit ini, menjalankan installer, lalu langsung membangun domain application tanpa membongkar architecture Core?"**

Jika jawabannya **ya**, maka Core System telah memenuhi tujuan utamanya.

---

# 66. Final Architecture Summary

```text
                         MITRA WHITE LABEL
                    Standalone Enterprise Core
                               │
       ┌───────────────────────┼───────────────────────┐
       │                       │                       │
   SECURITY               ORGANIZATION              PLATFORM
       │                       │                       │
 Authentication          Organization              Settings
 Password                Org Units                 Branding
 2FA                     Hierarchy                 Features
 Passkeys                Assignments               Audit
 Sessions                Context                   Notifications
 Security Events
       │                       │                       │
       └───────────────────────┼───────────────────────┘
                               │
                         AUTHORIZATION
                               │
                         Shield / RBAC
                               │
                         Developer Tools
                               │
                  ┌────────────┴────────────┐
                  │                         │
            Laravel Native            Mitra Generators
             Generators
                  │                         │
                  └────────────┬────────────┘
                               │
                         APPLICATION LAYER
                               │
          ┌────────────┬───────┼────────┬────────────┐
          │            │       │        │            │
         ERP          HMS     POS      HRIS       Custom
```
