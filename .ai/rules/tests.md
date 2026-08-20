---
paths:
  - 'tests/**'
---

# Tests

## Run tests with APP_ENV=testing explicitly (shell env leaks into phpunit.xml)
In this sandbox the shell process has APP_ENV=local pre-exported. PHPUnit's `<env name="APP_ENV" value="testing"/>` in phpunit.xml does NOT override an already-set OS env var (no `force="true"`), so `php artisan test` / `vendor/bin/pest` silently run with APP_ENV=local instead of testing.

This breaks `app()->runningUnitTests()` (returns false), which in turn breaks Filament's `fillFormDataForTesting()` (used by `Livewire::test(...)->fillForm([...])`) — it becomes a silent no-op, so `getState()`/`save()` see only mount-time defaults, not the values you filled in. Feature tests report wrong/default persisted values with no error.

Always prefix test runs in this environment: `APP_ENV=testing php artisan test ...` (or `APP_ENV=testing vendor/bin/pest ...`). Verify with `php artisan tinker --execute='var_dump(app()->runningUnitTests());'` — must print `true` under APP_ENV=testing.
