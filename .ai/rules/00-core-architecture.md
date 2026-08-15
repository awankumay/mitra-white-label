# Core Architecture Guidelines

- **Standalone-First:** Selalu asumsikan aplikasi berjalan sebagai _single-organization default_. Jangan tambahkan dependensi wajib ke internet atau infrastruktur SaaS (seperti multi-tenant billing, isolated DB, dll.)[cite: 3, 5].
- **Language Policy & Localization:**
    - Aplikasi menggunakan `FilamentLanguageSwitcherPlugin`.
    - Bahasa bawaan / default aplikasi adalah **Bahasa Indonesia (`id`)**.
    - Bahasa Inggris (`en`) disediakan sebagai opsional melalui Language Switcher.
    - Semua string UI / label wajib disiapkan dengan dukungan lokalisasi (`trans('translation.key')` atau file lang PHP/JSON bawaan Laravel) dengan fallback default Bahasa Indonesia.
    - Penamaan kode (class, method, variable, DB column, file path, CLI commands) tetap **wajib menggunakan Bahasa Inggris**[cite: 3].
- **Source of Truth Hierarchy:**
    1. `PRD.md` — Kebutuhan produk dan arsitektur[cite: 3].
    2. `CLAUDE.md` — Konteks proyek dan batasan arsitektur[cite: 3].
    3. `TODO.md` — Roadmap dan urutan pengerjaan[cite: 3].
