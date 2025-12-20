# 📁 Testing Database Folder

Folder ini digunakan untuk menyimpan file database SQLite yang dibuat saat menjalankan automated tests.

## 🎯 Tujuan

- **Isolasi**: Database testing terisolasi dari file production
- **Organisasi**: Semua file testing terkumpul di satu tempat
- **Cleanup Mudah**: Hapus folder ini untuk membersihkan semua database testing sekaligus

## 📝 File yang Dibuat

Saat menjalankan test, file database akan dibuat dengan pattern:
```
nusahire_[tenant_id]
```

Contoh:
```
nusahire_68e09b2e957e4
nusahire_68e09b3646f3a
nusahire_68e09b3cbccad
```

## 🧹 Cara Membersihkan

### Menggunakan Composer Script (Recommended)
```bash
composer test:cleanup
```

### Manual
```bash
# Menggunakan cleanup script
./cleanup-test-db.sh

# Atau manual dengan rm
rm -f database/testing/nusahire_*
```

### Otomatis
File database akan dihapus otomatis setelah test selesai oleh Laravel testing framework.

## ⚙️ Konfigurasi

Database testing ini dikonfigurasi **HANYA** dengan 1 baris perubahan di:

**`config/tenancy.php`** (line 54):
```php
'prefix' => env('APP_ENV') === 'testing' ? 'testing/nusahire_' : 'nusahire_',
```

Ini akan membuat:
- ✅ Testing: Database dibuat di `database/testing/nusahire_[id]`
- ✅ Production/Dev: Database dibuat di `database/nusahire_[id]`

**File Pendukung:**
- **Git Ignore**: `.gitignore` (line 82-84)
- **Cleanup Script**: `cleanup-test-db.sh`

## 🔒 Git Ignore

Semua file di folder ini sudah di-ignore oleh Git, kecuali:
- `.gitkeep` - Untuk track folder kosong
- `README.md` - Dokumentasi ini

Konfigurasi di `.gitignore`:
```gitignore
/database/testing/*
!/database/testing/.gitkeep
!/database/testing/README.md
```

## 📊 Verifikasi

Untuk memastikan file database dibuat di folder yang benar:

```bash
# Jalankan test
php artisan test tests/Feature/Tenant/HasPermissionTraitTest.php --filter test_super_admin_always

# Cek file di folder testing (should have files)
ls -lah database/testing/

# Cek file di root database (should be empty)
ls -1 database/ | grep "^nusahire"
```

## 🚀 Status

✅ **Aktif** - Folder ini otomatis digunakan saat `APP_ENV=testing`

Untuk production/development, database tenant akan menggunakan lokasi default `database/` sesuai konfigurasi driver.
