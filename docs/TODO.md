# Mitra White Label — TODO

> Implementation roadmap for the Mitra White Label Core System.
>
> The `PRD.md` is the source of truth for architecture and product requirements.
> Tasks in this document must not introduce architectural decisions that conflict
> with the PRD.

---

# 0. Project Baseline

- [x] Review current repository structure against `PRD.md` — `docs/architecture/baseline-audit.md`
- [x] Review installed Composer packages — `docs/architecture/baseline-audit.md`
- [x] Review installed NPM packages — `docs/architecture/baseline-audit.md`
- [ ] Remove unused / redundant packages — **deferred ke M7 (Settings/Branding) & M8 (Audit)**, lihat `docs/architecture/adr-007-package-retention.md`
- [x] Verify Laravel 13 compatibility — `docs/architecture/baseline-audit.md`
- [x] Verify Filament 5 compatibility — `docs/architecture/baseline-audit.md`
- [x] Verify PHP 8.3+ compatibility — `docs/architecture/baseline-audit.md`
- [x] Establish coding conventions — `docs/conventions/coding.md`
- [x] Establish naming conventions — `docs/conventions/naming.md`
- [x] Establish namespace conventions — `docs/architecture/adr-001-namespace-core.md`, `docs/architecture/adr-002-struktur-app.md`
- [x] Establish Core vs Application boundaries — `docs/architecture/adr-005-batas-core-application.md`
- [x] Document architectural decisions — `docs/architecture/adr-001-namespace-core.md` s.d. `adr-007-package-retention.md`

---

# 1. Core Architecture

## 1.1 Application Foundation

- [x] Define Core application architecture — ADR-001, ADR-002, ADR-005
- [x] Define Core namespaces — ADR-001
- [x] Define application layer boundaries — ADR-005
- [x] Define extension points — ADR-008 (mekanisme; kontrak nyata per-kebutuhan)
- [x] Define service provider strategy — ADR-009
- [x] Define configuration strategy — ADR-010
- [x] Define bootstrap strategy — ADR-010
- [ ] Define Core contracts — per-kebutuhan (M4: context, M5: auth, dst.)
- [ ] Define Core interfaces — per-kebutuhan
- [ ] Define Core events — per-kebutuhan (M8: audit, notification)
- [ ] Define Core actions — per-kebutuhan (M3: CreateOrganization, dst.)
- [x] Define Core exceptions — `CoreException` dibuat; sisanya per-kebutuhan

## 1.2 Directory Structure

- [x] Define final `app/` structure — `docs/conventions/directory-structure.md`, spec §3.2
- [x] Define `Core` structure — `docs/conventions/directory-structure.md`, spec §3.1
- [x] Define `Domain` conventions — `docs/conventions/directory-structure.md`, spec §4.1
- [x] Define `Actions` conventions — `docs/conventions/directory-structure.md`, spec §4.2
- [x] Define `Services` conventions — `docs/conventions/directory-structure.md`, spec §4.3
- [x] Define `Contracts` conventions — `docs/conventions/directory-structure.md`, spec §4.4
- [x] Define `Enums` conventions — `docs/conventions/directory-structure.md`, spec §4.5
- [x] Define `Support` conventions — `docs/conventions/directory-structure.md`, spec §4.7
- [x] Define `Filament` conventions — `docs/conventions/directory-structure.md`, spec §3.2, §4.8
- [x] Define `modules/` conventions — `docs/conventions/directory-structure.md`, spec §3.3

## 1.3 Architecture Rules

- [x] Define Core dependency rules — `docs/conventions/coding.md`, spec §3
- [x] Prevent Core → Business Module dependency — `docs/conventions/coding.md`, spec §3.1, arch test §3.2
- [x] Define Module → Core dependency rules — `docs/conventions/coding.md`, spec §3.1
- [x] Define Model conventions — `docs/conventions/coding.md`, spec §4
- [x] Define Policy conventions — `docs/conventions/coding.md`, spec §5
- [x] Define Action conventions — `docs/conventions/coding.md`, spec §6
- [x] Define Service conventions — `docs/conventions/coding.md`, spec §7
- [x] Define Event/Listener conventions — `docs/conventions/coding.md`, spec §8

---

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

---

# 3. Database Foundation

## 3.1 Database Conventions

- [x] Define primary key strategy — `docs/conventions/database.md` (ADR-004)
- [x] Define UUID/ULID strategy — `docs/conventions/database.md` (UUIDv7, ADR-011)
- [x] Define timestamps convention — `docs/conventions/database.md`
- [x] Define soft-delete convention — `docs/conventions/database.md`
- [x] Define foreign key convention — `docs/conventions/database.md`
- [x] Define indexing convention — `docs/conventions/database.md`
- [x] Define naming convention — `docs/conventions/database.md`
- [x] Define audit columns convention — `docs/conventions/database.md`

## 3.2 Core Tables

- [x] Design `organizations` — `core/Database/Migrations/2026_08_16_000001_create_organizations_table.php`
- [x] Design `organizational_units` — `core/Database/Migrations/2026_08_16_000002_create_organizational_units_table.php`
- [x] Design `organizational_unit_user` — `core/Database/Migrations/2026_08_16_000003_create_organizational_unit_user_table.php`
- [x] Design `organization_user` if required by implementation — `core/Database/Migrations/2026_08_16_000004_create_organization_user_table.php`
- [x] Design `settings` — `core/Database/Migrations/2026_08_16_000006_create_settings_table.php`
- [x] Design `audit_logs` — `core/Database/Migrations/2026_08_16_000007_create_audit_logs_table.php`
- [x] Design `security_events` — `core/Database/Migrations/2026_08_16_000008_create_security_events_table.php`

## 3.3 Database Constraints

- [x] Add foreign keys — `core/Database/Migrations/` (onDelete hybrid, ADR-011)
- [x] Add indexes — `core/Database/Migrations/` (index di tiap tabel Core)
- [x] Add unique constraints — `organizations.name`, `settings_scope_unique`, composite PK pivot
- [ ] Validate organizational hierarchy constraints — validasi aplikasi (cycle, parent ≠ self, se-organization, depth) di M3 (`docs/conventions/database.md`)
- [ ] Validate user/unit assignment constraints — validasi aplikasi (primary unit harus unit yang di-assign) di M3 (`docs/conventions/database.md`)

---

# 4. Organization Core

## 4.1 Organization

- [ ] Create Organization model
- [ ] Create Organization migration
- [ ] Create Organization factory
- [ ] Create Organization policy
- [ ] Create Organization service/action layer
- [ ] Create Organization Filament resource/page

## 4.2 Organizational Unit

- [ ] Create OrganizationalUnit model
- [ ] Create migration
- [ ] Create factory
- [ ] Create policy
- [ ] Implement parent/child relationship
- [ ] Implement hierarchy queries
- [ ] Implement root unit support
- [ ] Implement unit types
- [ ] Create Filament management UI

## 4.3 User Assignment

- [ ] Implement user → organizational unit assignment
- [ ] Implement multiple unit access
- [ ] Implement primary organizational unit
- [ ] Validate assignment permissions
- [ ] Create assignment management UI

---

# 5. Organizational Context

## 5.1 Context Contracts

- [ ] Define `OrganizationContext`
- [ ] Define `OrganizationalUnitContext`
- [ ] Define context contracts
- [ ] Define context lifecycle

## 5.2 Context Resolution

- [ ] Resolve organization context
- [ ] Resolve primary organizational unit
- [ ] Resolve current organizational unit
- [ ] Handle users with multiple units
- [ ] Handle users without assigned units
- [ ] Validate context authorization

## 5.3 Context Switching

- [ ] Implement unit switcher
- [ ] Implement current unit persistence
- [ ] Prevent unauthorized switching
- [ ] Add context switching tests
- [ ] Integrate context with Filament

## 5.4 Non-Filament Usage

- [ ] Make context available to Services
- [ ] Make context available to Actions
- [ ] Make context available to Policies
- [ ] Make context available to Jobs
- [ ] Make context available to Console Commands

---

# 6. Data Scope Architecture

- [ ] Define Global scope convention
- [ ] Define Organization scope convention
- [ ] Define Organizational Unit scope convention
- [ ] Define scoped model conventions
- [ ] Define scope-aware query patterns
- [ ] Define scope-aware policies
- [ ] Define scope-aware resource patterns
- [ ] Define scope bypass rules for administrators
- [ ] Add scope tests

---

# 7. Authentication

## 7.1 Base Authentication

- [ ] Review current authentication implementation
- [ ] Configure login
- [ ] Configure logout
- [ ] Configure password reset
- [ ] Configure email verification
- [ ] Configure password confirmation
- [ ] Configure session handling

## 7.2 Account Security

- [ ] Install/configure selected security package(s)
- [ ] Implement security settings page
- [ ] Implement password change
- [ ] Implement active session management
- [ ] Implement session revocation
- [ ] Implement revoke-all-other-sessions

## 7.3 Two-Factor Authentication

- [ ] Implement TOTP
- [ ] Implement QR enrollment
- [ ] Implement recovery codes
- [ ] Implement recovery code regeneration
- [ ] Implement enable/disable flow
- [ ] Define 2FA policy
- [ ] Add 2FA tests

## 7.4 Passkeys

- [ ] Select passkey implementation
- [ ] Implement passkey registration
- [ ] Implement passkey authentication
- [ ] Implement passkey management
- [ ] Implement passkey revocation
- [ ] Add passkey tests

## 7.5 Security Events

- [ ] Define security event model
- [ ] Record login success
- [ ] Record login failure
- [ ] Record password change
- [ ] Record 2FA changes
- [ ] Record passkey changes
- [ ] Record session revocation

---

# 8. Authorization

## 8.1 RBAC

- [ ] Configure Filament Shield
- [ ] Configure Spatie Permission
- [ ] Define default roles
- [ ] Define permission naming convention
- [ ] Configure role management
- [ ] Configure permission management

## 8.2 Policies

- [ ] Define policy conventions
- [ ] Implement OrganizationPolicy
- [ ] Implement OrganizationalUnitPolicy
- [ ] Implement UserPolicy
- [ ] Implement scoped authorization

## 8.3 Organizational Authorization

- [ ] Implement permission + organizational scope
- [ ] Validate current context against permissions
- [ ] Prevent cross-unit unauthorized access
- [ ] Test manager-level access
- [ ] Test unit-level access
- [ ] Test administrator bypass rules

---

# 9. Settings System

## 9.1 Settings Architecture

- [ ] Define settings contract
- [ ] Define settings repository/storage
- [ ] Define typed settings
- [ ] Define settings scopes
- [ ] Define default values
- [ ] Define fallback behavior

## 9.2 System Settings

- [ ] Application settings
- [ ] Security settings
- [ ] Localization settings
- [ ] Mail settings
- [ ] Storage settings

## 9.3 Organization Settings

- [ ] Company information
- [ ] Contact information
- [ ] Currency
- [ ] Fiscal configuration
- [ ] Default preferences

## 9.4 Organizational Unit Settings

- [ ] Address
- [ ] Contact
- [ ] Timezone
- [ ] Operational settings
- [ ] Numbering settings

## 9.5 User Settings

- [ ] Language
- [ ] Timezone
- [ ] Theme
- [ ] Notification preferences

---

# 10. White Label / Branding

- [ ] Define branding model
- [ ] Define branding configuration
- [ ] Implement application name
- [ ] Implement company name
- [ ] Implement logo
- [ ] Implement dark logo
- [ ] Implement favicon
- [ ] Implement primary color
- [ ] Implement secondary color
- [ ] Implement login branding
- [ ] Implement email branding
- [ ] Implement footer branding

## Organization Branding

- [ ] Implement organization branding
- [ ] Implement branding fallback
- [ ] Implement branding cache

## Future Extension

- [ ] Define organizational-unit branding extension point

---

# 11. Feature Registry

- [ ] Define Feature contract
- [ ] Define Feature Registry
- [ ] Define feature identifier convention
- [ ] Register Core features
- [ ] Register module features
- [ ] Implement feature discovery
- [ ] Implement feature status
- [ ] Implement feature configuration
- [ ] Add feature tests

---

# 12. Module Architecture

- [ ] Define module contract
- [ ] Define module metadata
- [ ] Define module registration
- [ ] Define module discovery
- [ ] Define module configuration
- [ ] Define module migrations
- [ ] Define module routes
- [ ] Define module permissions
- [ ] Define module features
- [ ] Define module Filament integration

## Module Rules

- [ ] Document Core → Module restriction
- [ ] Document Module → Core usage
- [ ] Document module dependency rules
- [ ] Document module naming conventions
- [ ] Document module lifecycle

---

# 13. Audit System

- [ ] Define AuditLog model
- [ ] Define audit schema
- [ ] Define actor
- [ ] Define subject
- [ ] Define action
- [ ] Define metadata
- [ ] Define IP tracking
- [ ] Define timestamp
- [ ] Implement audit events
- [ ] Implement audit viewer
- [ ] Implement audit filtering
- [ ] Implement audit authorization

---

# 14. Notifications

- [ ] Configure database notifications
- [ ] Configure Filament notifications
- [ ] Configure mail notifications
- [ ] Define notification conventions
- [ ] Define notification events
- [ ] Define notification preferences
- [ ] Add notification tests

## Future

- [ ] SMS adapter
- [ ] WhatsApp adapter
- [ ] Push notification adapter
- [ ] Webhook adapter

---

# 15. Console Installer

## 15.1 `mitra:install`

- [ ] Create installer command
- [ ] Implement preflight checks
- [ ] Validate environment
- [ ] Validate PHP version
- [ ] Validate Laravel version
- [ ] Validate database connection
- [ ] Validate filesystem
- [ ] Validate required configuration

## 15.2 Installation Flow

- [ ] Database setup
- [ ] Run migrations
- [ ] Seed Core data
- [ ] Create Organization
- [ ] Create initial Organizational Unit
- [ ] Create administrator
- [ ] Configure initial settings
- [ ] Configure branding
- [ ] Configure features
- [ ] Finalize installation

## 15.3 Installer Options

- [ ] `--no-interaction`
- [ ] `--force` where appropriate
- [ ] `--skip-*` options where justified
- [ ] Avoid destructive defaults
- [ ] Add installer progress output
- [ ] Add installer error handling

## 15.4 Installer Tests

- [ ] Fresh installation test
- [ ] Re-run installation test
- [ ] Invalid environment test
- [ ] Invalid database test
- [ ] Non-interactive test

---

# 16. Diagnostic Commands

## 16.1 `mitra:doctor`

- [ ] Create command
- [ ] Check PHP
- [ ] Check Laravel
- [ ] Check Filament
- [ ] Check database
- [ ] Check cache
- [ ] Check queue
- [ ] Check storage
- [ ] Check filesystem permissions
- [ ] Check configuration
- [ ] Check encryption
- [ ] Hide sensitive values

## 16.2 `mitra:health`

- [ ] Create command
- [ ] Check database health
- [ ] Check cache health
- [ ] Check filesystem health
- [ ] Check application health
- [ ] Return meaningful exit codes

## 16.3 `mitra:about`

- [ ] Create command
- [ ] Display Mitra version
- [ ] Display Laravel version
- [ ] Display Filament version
- [ ] Display PHP version
- [ ] Display environment
- [ ] Display enabled features
- [ ] Display enabled modules
- [ ] Hide sensitive information

---

# 17. Developer Generators

## 17.1 Generator Engine

- [ ] Define generator contract
- [ ] Define generator input
- [ ] Define generator options
- [ ] Define template strategy
- [ ] Define filesystem strategy
- [ ] Implement validation
- [ ] Implement `--dry-run`
- [ ] Implement `--force`
- [ ] Implement `--no-interaction`
- [ ] Implement consistent output

## 17.2 `mitra:make:module`

- [ ] Create module generator
- [ ] Generate module structure
- [ ] Generate module metadata
- [ ] Generate module provider/registration
- [ ] Generate module tests

## 17.3 `mitra:make:crud`

- [ ] Create CRUD generator
- [ ] Generate Model
- [ ] Generate Migration
- [ ] Generate Factory
- [ ] Generate Seeder
- [ ] Generate Policy
- [ ] Generate Filament Resource
- [ ] Generate Tests
- [ ] Support optional API layer
- [ ] Support `--unit-aware`
- [ ] Support `--organization-aware`

## 17.4 `mitra:make:action`

- [ ] Create Action generator
- [ ] Generate class
- [ ] Generate optional test

## 17.5 `mitra:make:service`

- [ ] Create Service generator
- [ ] Generate class
- [ ] Generate optional test

## 17.6 `mitra:make:contract`

- [ ] Create Contract generator
- [ ] Generate interface
- [ ] Generate optional implementation

## 17.7 `mitra:make:enum`

- [ ] Create Enum generator
- [ ] Generate backed enum
- [ ] Generate optional test

---

# 18. Laravel / Filament Generator Compatibility

- [ ] Verify Laravel native generators continue working
- [ ] Verify Filament generators continue working
- [ ] Document when to use Laravel generators
- [ ] Document when to use Filament generators
- [ ] Document when to use Mitra generators
- [ ] Avoid overriding native commands unnecessarily

---

# 19. Filament Administration

- [ ] Define panel architecture
- [ ] Define navigation structure
- [ ] Define user menu
- [ ] Define account/security menu
- [ ] Define organization navigation
- [ ] Define organizational unit navigation
- [ ] Define settings navigation
- [ ] Define audit navigation
- [ ] Define system diagnostics navigation

## UX

- [ ] Consistent form conventions
- [ ] Consistent table conventions
- [ ] Consistent filters
- [ ] Consistent actions
- [ ] Consistent notifications
- [ ] Consistent empty states
- [ ] Consistent authorization behavior

---

# 20. Testing Foundation

## Unit

- [ ] Core services
- [ ] Actions
- [ ] Context
- [ ] Settings
- [ ] Feature Registry
- [ ] Generators

## Feature

- [ ] Authentication
- [ ] Authorization
- [ ] Organization
- [ ] Organizational Units
- [ ] Context switching
- [ ] Settings
- [ ] Branding
- [ ] Audit
- [ ] Notifications

## Security

- [ ] Login
- [ ] Failed login
- [ ] 2FA
- [ ] Passkeys
- [ ] Session revocation
- [ ] Authorization boundaries
- [ ] Organizational scope

## Console

- [ ] Installer
- [ ] Doctor
- [ ] Health
- [ ] About
- [ ] Generators

---

# 21. Code Quality

- [ ] Configure Laravel Pint
- [ ] Configure PHPStan/Larastan
- [ ] Configure Pest
- [ ] Define quality baseline
- [ ] Create `composer check`
- [ ] Run formatting checks
- [ ] Run static analysis
- [ ] Run tests
- [ ] Configure CI
- [ ] Add minimum quality gate

---

# 22. CI/CD

- [ ] Configure GitHub Actions
- [ ] Test supported PHP versions
- [ ] Test supported database drivers
- [ ] Run Pint
- [ ] Run PHPStan/Larastan
- [ ] Run Pest
- [ ] Validate Composer dependencies
- [ ] Validate NPM build
- [ ] Validate migrations
- [ ] Validate installation flow

---

# 23. Documentation

## Developer Documentation

- [ ] Getting Started
- [ ] Installation
- [ ] Architecture
- [ ] Directory Structure
- [ ] Authentication
- [ ] Security
- [ ] Organization
- [ ] Organizational Units
- [ ] Context
- [ ] Authorization
- [ ] Settings
- [ ] White Label
- [ ] Features
- [ ] Modules
- [ ] Console Commands
- [ ] Generators
- [ ] Testing

## Deployment Documentation

- [ ] Local Development
- [ ] Production
- [ ] VPS
- [ ] On-Premise
- [ ] LAN deployment
- [ ] Offline environment
- [ ] Database setup
- [ ] Backup
- [ ] Restore
- [ ] Upgrade

---

# 24. On-Premise Readiness

- [ ] Document minimum server requirements
- [ ] Document supported OS
- [ ] Document PHP requirements
- [ ] Document database requirements
- [ ] Document web server requirements
- [ ] Document storage requirements
- [ ] Document LAN deployment
- [ ] Document backup strategy
- [ ] Document restore strategy
- [ ] Document update strategy
- [ ] Document offline operation

---

# 25. Backup & Recovery Foundation

> Core does not implement a complete enterprise backup platform,
> but must provide a foundation that can support one.

- [ ] Document database backup strategy
- [ ] Document file storage backup
- [ ] Document configuration backup
- [ ] Document restore process
- [ ] Document backup verification
- [ ] Define future backup extension point

---

# 26. Upgrade Strategy

- [ ] Define Core versioning strategy
- [ ] Define application versioning strategy
- [ ] Define database migration strategy
- [ ] Define configuration migration strategy
- [ ] Define package upgrade process
- [ ] Document Laravel upgrade strategy
- [ ] Document Filament upgrade strategy
- [ ] Document Mitra Core upgrade strategy

---

# 27. Security Hardening

- [ ] Review authentication configuration
- [ ] Review authorization policies
- [ ] Review session security
- [ ] Review CSRF protection
- [ ] Review rate limiting
- [ ] Review file upload security
- [ ] Review storage permissions
- [ ] Review production configuration
- [ ] Review debug exposure
- [ ] Review secret exposure
- [ ] Review diagnostic output
- [ ] Review audit access

---

# 28. Performance Foundation

- [ ] Review database indexes
- [ ] Review N+1 queries
- [ ] Review Filament query patterns
- [ ] Configure caching
- [ ] Configure configuration caching
- [ ] Configure route caching where applicable
- [ ] Review queue architecture
- [ ] Review large dataset handling
- [ ] Review organizational scope query performance

---

# 29. Final Core Validation

Before starting business-specific modules:

- [ ] Fresh installation works
- [ ] Administrator can login
- [ ] 2FA works
- [ ] Passkey works
- [ ] Sessions work
- [ ] Roles work
- [ ] Permissions work
- [ ] Organization works
- [ ] Organizational Units work
- [ ] Hierarchy works
- [ ] User assignment works
- [ ] Context switching works
- [ ] Scoped authorization works
- [ ] Settings work
- [ ] Branding works
- [ ] Audit works
- [ ] Notifications work
- [ ] Feature Registry works
- [ ] Module registration works
- [ ] `mitra:install` works
- [ ] `mitra:doctor` works
- [ ] `mitra:health` works
- [ ] `mitra:about` works
- [ ] CRUD generator works
- [ ] Module generator works
- [ ] Action generator works
- [ ] Service generator works
- [ ] Contract generator works
- [ ] Enum generator works
- [ ] Native Laravel generators still work
- [ ] Native Filament generators still work
- [ ] Test suite passes
- [ ] Static analysis passes
- [ ] Formatting passes
- [ ] CI passes

---

# 30. Definition of Core Complete

Core System is considered complete when:

1. A new standalone application can be installed using:

    `php artisan mitra:install`

2. An administrator can securely authenticate.

3. Organization and Organizational Units can be configured.

4. Users can be assigned to one or more Organizational Units.

5. Current organizational context is available outside Filament.

6. Authorization respects both permissions and organizational scope.

7. Settings and branding can be configured.

8. Audit and security events are available.

9. Features and modules can be registered.

10. Developers can generate application components using Mitra generators.

11. Laravel and Filament native generators remain usable.

12. The application can operate without an internet connection.

13. The application can be deployed independently for each client.

14. Core has automated tests and quality gates.

---

# 31. Post-Core / Future Scope

The following should NOT block Core v1:

- [ ] SaaS tenancy
- [ ] Tenant billing
- [ ] Tenant provisioning
- [ ] Cloud synchronization
- [ ] Offline device synchronization
- [ ] Multi-installation management
- [ ] Centralized remote administration
- [ ] Online license server
- [ ] Marketplace
- [ ] Module marketplace
- [ ] Cloud backup service
- [ ] Remote monitoring platform
- [ ] Multi-organization UX

````

## Urutan pengerjaan yang saya rekomendasikan

Walaupun `TODO.md` di atas cukup panjang, **jangan dikerjakan berdasarkan urutan file tersebut secara mentah**. Untuk repository ini saya akan membaginya menjadi milestone:

```text
M0  Project Audit
 ↓
M1  Core Architecture
 ↓
M2  Database Foundation
 ↓
M3  Organization + Organizational Unit
 ↓
M4  Context + Data Scope
 ↓
M5  Authentication + Security
 ↓
M6  Authorization
 ↓
M7  Settings + White Label
 ↓
M8  Audit + Notifications + Feature Registry
 ↓
M9  Installer + Doctor + Health
 ↓
M10 Developer Generators
 ↓
M11 Testing + CI + Quality
 ↓
M12 Documentation + On-Premise
 ↓
M13 Core Validation
````
