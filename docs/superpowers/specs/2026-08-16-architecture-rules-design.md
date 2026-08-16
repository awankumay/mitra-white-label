# Design — Architecture Rules (TODO §1.3)

**Tanggal:** 2026-08-16
**Status:** Approved
**Sumber:** `docs/TODO.md` §1.3, `docs/PRD.md`, ADR-001 s.d. ADR-010, `docs/conventions/coding.md`, `docs/conventions/naming.md`, `docs/conventions/directory-structure.md`, `docs/superpowers/specs/2026-08-16-directory-structure-design.md`, `config/filament-shield.php`, source `bezhansalleh/filament-shield`
**Metode:** Brainstorming (sesi 2026-08-16) — konsultasi `laravel-patterns`, `laravel-best-practices`, verifikasi source Filament v5 & Filament Shield terpasang

## 1. Ringkasan

Milestone "Architecture Rules" (TODO.md §1.3) mendefinisikan aturan arsitektural
yang mengikat untuk `core/`, `app/`, dan `modules/`: aturan dependensi Core,
konvensi Model, Policy, Action, Service, dan Event/Listener. Sebagian besar
fondasi sudah direkam di ADR-001 s.d. ADR-010 dan konvensi yang ada; sesi ini
mempertegas, merekonsiliasi konflik (format permission), dan menetapkan
verifikasi otomatis.

Deliverable:

1. **Aturan dependensi Core** yang dipertegas + arch test baru.
2. **Konvensi Model, Policy, Action, Service, Event/Listener** yang konsisten di
   tiga lapisan, didokumentasikan di `docs/conventions/`.
3. **Rekonsiliasi format permission** PRD §19 / naming.md dengan konfigurasi
   Shield aktual.
4. Update `docs/conventions/coding.md`, `docs/conventions/naming.md`, dan
   checklist TODO.md §1.3.

## 2. Konteks

- ADR-005: `Core\` tidak mengimpor `App\`/`Modules\`; `Core\` non-UI tidak
  bergantung pada Filament.
- ADR-006: module-ready — `Modules\<Name>\` boleh mengimpor `Core\` dan `App\`.
- ADR-008: extension points — contracts, config, events, actions.
- PRD §19: "Core menggunakan Filament Shield / Spatie Permission sebagai
  authorization foundation."
- PRD §15: context dipakai oleh Models, Policies, Actions, Services, Jobs,
  Commands, Notifications.
- Konfigurasi Shield aktual (`config/filament-shield.php`): `permissions`
  (`separator => ':'`, `case => 'snake'`, `generate => true`), `policies`
  (`path => app_path('Policies')`, `merge => true`, 11 method), `super_admin`
  (`intercept_gate => 'before'`).
- Verifikasi source Shield: `defaultPermissionKeyBuilder()` =
  `format(case, affix) . separator . format(case, subject)`; subject resource =
  `class_basename(model)`; method policy dari `policies.methods`;
  `single_parameter_methods` menentukan stub (Single/Multi param), bukan daftar
  method.
- Filament v5 terpasang: enum `Filament\Support\Icons\Heroicon` tersedia.

## 3. Aturan Dependensi Core

### 3.1 Aturan (dari ADR-005, dipertegas)

1. `Core\` **tidak boleh** mengimpor `App\` atau `Modules\`.
2. `Core\` non-UI **tidak bergantung** pada Filament; komponen UI Core
   (`core/Filament/`) dikecualikan.
3. `App\` **boleh** mengimpor `Core\`; **tidak boleh** mengimpor `Modules\`
   (aplikasi tidak bergantung pada modul — modul boleh bergantung pada aplikasi).
4. `Modules\<Name>\` **boleh** mengimpor `Core\` dan `App\` public.
5. `Core\` **tidak mendengarkan event aplikasi** — Core melempar event, konsumen
   mendengarkan (ADR-008).

### 3.2 Verifikasi otomatis (arch test)

Perkuat `tests/Arch/CoreArchTest.php`:

- `Core` tidak menggunakan `App`/`Modules` (sudah ada).
- `Core` non-UI tidak menggunakan `Filament` (sudah ada).
- **Baru**: `App` tidak menggunakan `Modules`.
- **Baru**: tidak ada model Core di namespace `App\Models` (menjaga keputusan
  lokasi — model Core hidup di subfolder domain `core/`).

### 3.3 Dokumentasi

Aturan yang tidak bisa diverifikasi mesin (prinsip thin model, batas tanggung
jawab Action/Service, isi `Support/`) didokumentasikan di `coding.md` /
konvensi folder.

## 4. Konvensi Model

1. **Lokasi per lapisan** — model Core di `core/<Domain>/Models/`
   (`core/Organization/Models/Organization.php`), model aplikasi di
   `app/Models/`, model module di `modules/<Name>/Models/`.
2. **Thin model** — model tidak berisi logika bisnis yang seharusnya
   Action/Service. Model memegang: atribut, casts, relasi, scope sederhana.
   Query berat → named scope / Query Object; perilaku multi-langkah →
   Action/Service.
3. **Dependensi** — model Core tidak mengimpor `App\`/`Modules\` (ADR-005) dan
   tidak bergantung Filament; model App boleh mengimpor Core.
4. **Relasi lintas-batas** — model App/module yang butuh data model Core tidak
   mengimpor model Core langsung; relasi diekspresikan via contract
   (`OrganizationContext`, dll.) — menjaga batas Core tetap tegas dan
   memungkinkan ekstraksi package.
5. **Detail implementasi** (primary key UUID, timestamps, soft delete, audit
   columns, casts, penamaan tabel/pivot/FK) → tetap di `naming.md`.
6. **Global scope organisasi** — ditambahkan saat M4 (Data Scope) / M6
   (Authorization); §1.3 menetapkan prinsip: scope organisasi memakai global
   scope Eloquent, detail di milestone tersebut.

## 5. Konvensi Policy + Rekonsiliasi Shield

### 5.1 Temuan

- PRD §19 menetapkan Shield/Spatie Permission sebagai foundation.
- Konfigurasi Shield aktual: separator `:`, case `snake`, `generate => true`,
  policy path `app_path('Policies')`, `merge => true`.
- **Konflik**: PRD §19 & naming.md menulis pola permission `resource.action`
  (titik) — berbeda dengan Shield aktual (`:`).
- Format permission terverifikasi dari source Shield:
  `format(case, affix) . separator . format(case, subject)`.

### 5.2 Keputusan

1. **Shield adalah sumber policy utama.** Policy untuk resource Filament
   di-generate otomatis oleh Shield ke `app/Policies/` (`policies.path`).
   Developer tidak menulis policy manual untuk resource Filament.
2. **Method policy** = 11 method dari `policies.methods`: `viewAny`, `view`,
   `create`, `update`, `delete`, `restore`, `forceDelete`, `forceDeleteAny`,
   `restoreAny`, `replicate`, `reorder`. Setiap method memanggil
   `$user->can('{permission}')`.
3. **Stub method** — `single_parameter_methods` (viewAny, create, deleteAny,
   forceDeleteAny, restoreAny, reorder) memakai `SingleParamMethod.stub` (tanpa
   instance model); sisanya `MultiParamMethod.stub` (dengan instance model).
   `merge => true` menggabungkan method default dengan method per-resource.
4. **Format permission** (dengan `case => 'snake'`, `separator => ':'`,
   `subject => 'model'`): `format(case, affix) . ':' . format(case, subject)`
   — **affix (action) dulu, subject belakangan** (default Shield v4.3.1):

   ```text
   view_any:product
   view:product
   create:product
   update:product
   delete:product
   restore:product
   force_delete:product
   force_delete_any:product
   restore_any:product
   replicate:product
   reorder:product
   ```

   Untuk pages/widgets (`subject => 'class'`, prefix `view`): `view:some_page`.
   (Revisi 2026-08-16: format aktual Shield v4.3.1 adalah `action:subject`,
   bukan `resource:action` seperti keputusan awal — keputusan sesi: ikuti
   default tool.)
5. **Lokasi policy** — policy resource di-generate Shield. Untuk model di
   `app/Models/`, ke `app/Policies/`; untuk model Core (di luar `app/Models/`),
   Shield menurunkan path dari lokasi model → `core/<Domain>/Policies/`
   (revisi 2026-08-16: keputusan sesi — ikuti default Shield).
6. **Rekonsiliasi dokumen** — PRD §19 dan `naming.md` di-update ke format
   Shield `action:subject` (snake, separator `:`).
7. **Policy manual** — hanya untuk hal di luar resource Filament (mis. 2FA
   policy, kebijakan khusus domain), tetap di `app/Policies/`, mengikuti set
   method Shield. Policy yang di-generate boleh memanggil Action/Service untuk
   logika (jangan menumpuk logika di policy).
8. **Super admin** — di-handle Shield (`intercept_gate => 'before'`); aplikasi
   tidak perlu bypass manual. Scope bypass administrator organisasi ditangani di
   M6.
9. **Policy module** di `modules/<Name>/Policies/` (keputusan sesi struktur
   direktori).

## 6. Konvensi Action

### 6.1 Action class (logika bisnis)

1. **Lokasi per lapisan** — Core di `core/<Domain>/Actions/`
   (`core/Organization/Actions/CreateOrganizationAction.php`), aplikasi di
   `app/Actions/`, module di `modules/<Name>/Actions/`.
2. **Action Core = API publik per domain** — kontrak pemakaian stabil
   (ADR-008), dipanggil aplikasi/modul dan oleh Core sendiri; satu folder
   `Actions/` per domain, tanpa pemisahan publik/internal (YAGNI).
3. **Penamaan** — `VerbNoun` + suffix `Action` (naming.md):
   `CreateOrganizationAction`, `AssignUserToUnitAction`.
4. **Struktur** — class `final`, invokable (`__invoke()`) atau method
   `handle()`, constructor injection (bukan `app()`/`resolve()`).
5. **Batas tanggung jawab**:
   - Satu Action = satu operasi tunggal reusable.
   - Action **tidak memanggil Service** (hindari dependensi melingkar) —
     Service yang memanggil Action, bukan sebaliknya.
   - Action boleh komposisi Action lain yang lebih kecil.
   - Action Core tidak bergantung pada Filament (ADR-005) dan tidak mengimpor
     `App\`/`Modules\`.

### 6.2 Action UI (tombol Filament)

6. **Label bersumber dari lang** — label tombol aksi dari localization
   (Bahasa Indonesia default, English opsional — konsisten naming.md), bukan
   hardcode di resource.
7. **Label seminim mungkin** — judul aksi singkat: `Tambah`, `Edit`, `Hapus`,
   `Print`, `Export`, `Download`, `Import`, dst. (bukan kalimat panjang).
8. **Icon Heroicon enum** — pakai `Filament\Support\Icons\Heroicon` (enum,
   bukan string): `Heroicon::Plus` untuk Tambah, `Heroicon::PencilSquare` untuk
   Edit, `Heroicon::Trash` untuk Hapus, `Heroicon::Printer` untuk Print,
   `Heroicon::ArrowDownTray` untuk Export/Download, `Heroicon::ArrowLeft` untuk
   Back.
9. **Action general via Concerns (satu sumber)** — semua action general
   didefinisikan sebagai trait/concern reusable, dipakai semua resource —
   konsisten, tanpa recode:

   ```text
   app/Filament/Concerns/
   ├── HasCreateAction.php    # Tambah (Heroicon::Plus)
   ├── HasEditAction.php      # Edit   (Heroicon::PencilSquare)
   ├── HasDeleteAction.php    # Hapus  (Heroicon::Trash)
   ├── HasBackAction.php      # Kembali (Heroicon::ArrowLeft)
   └── ...                    # Export, Print, Download, Import, dst.
   ```

   Setiap concern berisi satu action general lengkap (label dari lang + icon
   enum + perilaku default), dapat di-extend per-resource via method
   `getAdditional...` (pola `getAdditionalHeaderActions()`).

   Contoh pola `HasBackAction` (terverifikasi kompatibel Filament v5):

   ```php
   <?php

   declare(strict_types=1);

   namespace App\Filament\Concerns;

   use Filament\Actions\Action;
   use Filament\Support\Icons\Heroicon;

   trait HasBackAction
   {
       protected function getHeaderActions(): array
       {
           return [
               Action::make('back')
                   ->label(__('action.action_back'))
                   ->icon(Heroicon::ArrowLeft)
                   ->url($this->getBackActionUrl()),
               ...$this->getAdditionalHeaderActions(),
           ];
       }

       /**
        * Extend header actions without overriding getHeaderActions().
        */
       protected function getAdditionalHeaderActions(): array
       {
           return [];
       }

       protected function getBackActionUrl(): string
       {
           return $this->getResource()::getUrl('index');
       }
   }
   ```

10. **Konsistensi** — action tipe sama memakai concern yang sama (label + icon
    + perilaku dari satu sumber lang dan satu pilihan icon enum).
11. **Lokasi concern per lapisan** — concern aplikasi di
    `app/Filament/Concerns/`, concern milik Core di `core/Filament/Concerns/`,
    concern module di `modules/<Name>/Filament/Concerns/` (struktur direktori
    yang sudah disepakati).

## 7. Konvensi Service

1. **Lokasi per lapisan** — Core di `core/<Domain>/Services/`
   (`core/Organization/Services/OrganizationService.php`), aplikasi di
   `app/Services/`, module di `modules/<Name>/Services/`.
2. **Peran: koordinator** — Service mengorkestrasi beberapa Action (dan/atau
   repository) dalam satu alur operasional. Service **memanggil Action**, bukan
   sebaliknya (Action tidak memanggil Service — mencegah dependensi melingkar).
3. **Penamaan** — `NounService` (naming.md): `OrganizationService`,
   `AuthService`, `SettingService`.
4. **Struktur** — class `final`, constructor injection, method publik yang
   mewakili alur (bukan satu `handle()`).
5. **Batas tanggung jawab**:
   - Service = alur multi-langkah; Action = unit kerja tunggal.
   - Service boleh memakai repository/contract langsung bila diperlukan
     (`SettingRepository`), selama logika bisnis tunggal tetap di Action.
   - Service Core tidak bergantung pada Filament (ADR-005) dan tidak mengimpor
     `App\`/`Modules\`.
   - Jika satu langkah dalam alur reusable, ekstrak jadi Action — Service tidak
     menumpuk logika.
6. **Transaction** — alur yang mengubah banyak record dibungkus
   `DB::transaction()` di Service (laravel-patterns).

## 8. Konvensi Event/Listener

1. **Lokasi per lapisan** — Event Core terkolokasi per-domain di
   `core/<Domain>/Events/` (`core/Organization/Events/OrganizationCreated.php`,
   `core/Audit/Events/AuditLogged.php`); Event aplikasi di `app/Events/`
   (Laravel default); Event module di `modules/<Name>/Events/`. Listener
   aplikasi di `app/Listeners/`; listener module di `modules/<Name>/Listeners/`.
2. **Aturan arah** — "Core melempar event, konsumen mendengarkan" (ADR-008):
   aplikasi/modul mendengarkan event Core; **Core tidak mendengarkan event
   aplikasi** (ADR-005).
3. **Penamaan** — event `NounVerbPastTense`: `OrganizationCreated`,
   `OrganizationUnitAssigned`, `AuditLogged`. Listener `NounVerbPastTense` +
   deskriptif: `SendWelcomeNotification` / `OrganizationCreatedListener`.
4. **Penggunaan** — event untuk integrasi yang tidak butuh return value (audit,
   notification, side effect) — konsisten ADR-008. Bukan untuk alur bisnis yang
   butuh hasil (itu Action/Service).
5. **Registrasi** — event/listener didaftarkan via provider domain Core
   (`config('core.providers')`) / `EventServiceProvider`, bukan hardcode di
   bootstrap.
6. **Verifikasi otomatis** — arch test memastikan event/listener Core tidak
   mengimpor Filament / App / Modules (tercakup aturan namespace Core global).

## 9. Dampak pada Dokumen

### 9.1 `docs/conventions/coding.md`

- Tambah sub-bagian "Architecture Rules" — ringkasan aturan dependensi §3,
  prinsip thin model, batas Action/Service, aturan event.
- Tambah aturan Action UI (label lang, icon Heroicon enum, concern).

### 9.2 `docs/conventions/naming.md`

- **Rekonsiliasi permission**: ubah pola `resource.action` menjadi
  `resource:action` (format Shield: separator `:`, snake).
- Tambah konvensi penamaan event (`NounVerbPastTense`) dan concern
  (`Has<Action>Action`).

### 9.3 PRD.md §19

- Update contoh pola permission dari `resource.action` ke `resource:action`.

### 9.4 `tests/Arch/CoreArchTest.php`

- Tambah 2 arch test baru: `App` tidak menggunakan `Modules`; tidak ada model
  Core di namespace `App\Models`.

### 9.5 TODO.md §1.3

Checklist yang terjawab:

- Define Core dependency rules → §3
- Prevent Core → Business Module dependency → §3.1 (aturan), §3.2 (arch test)
- Define Module → Core dependency rules → §3.1
- Define Model conventions → §4
- Define Policy conventions → §5
- Define Action conventions → §6
- Define Service conventions → §7
- Define Event/Listener conventions → §8

## 10. Non-Goals

- Tidak membuat policy manual untuk resource Filament (Shield yang generate).
- Tidak mengubah konfigurasi Shield (separator/case dibiarkan sesuai default —
  konvensi mengikuti alat).
- Tidak mengimplementasikan global scope organisasi (M4/M6).
- Tidak membuat contract/interface spekulatif (ADR-008).
- Tidak mengekstrak Core ke package terpisah (ADR-010).
- Tidak mengubah aturan dependensi ADR-005.

## 11. Verifikasi / Acceptance

- `composer check` lolos (Pint, Pest termasuk arch test baru, PHPStan).
- Arch test baru: `App` tidak menggunakan `Modules`; tidak ada model Core di
  `App\Models` — gagal jika dilanggar.
- Konvensi policy konsisten dengan konfigurasi Shield aktual dan source Shield
  (`defaultPermissionKeyBuilder`).
- PRD §19, naming.md, coding.md konsisten satu sama lain (format permission
  `resource:action`).
- Concern action general tersedia di `app/Filament/Concerns/` (dibuat saat
  resource Filament pertama dibangun di milestone selanjutnya).
