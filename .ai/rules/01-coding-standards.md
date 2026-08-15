# Coding & Technical Standards

- **PHP / Laravel:**
    - Gunakan strict typing (`declare(strict_types=1);`) pada setiap file PHP baru.
    - Namespace custom CLI command wajib menggunakan prefix `mitra:*` (contoh: `php artisan mitra:make-module`)[cite: 5].
    - Selalu validasi data di level Request/Form Validation sebelum masuk ke Business Logic / Service Layer.
- **Database:**
    - Gunakan konvensi `snake_case` jamak untuk nama tabel dan `snake_case` tunggal untuk nama kolom.
    - Sertakan `foreignId` dengan penanganan constraint yang jelas (`cascadeOnDelete` / `restrictOnDelete`).
