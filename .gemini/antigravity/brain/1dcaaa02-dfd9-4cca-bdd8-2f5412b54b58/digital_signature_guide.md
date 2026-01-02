# Digital Signature Module - User Guide

## 📋 Flow Lengkap Penggunaan Digital Signature

### 1️⃣ **Setup Root CA** (One-time, Admin Only)
**Status**: ✅ SELESAI
- Admin membuat Root Certificate Authority (CA)
- CA ini akan digunakan untuk menandatangani semua user certificates
- Data yang dibuat:
  - Root CA Certificate
  - Root CA Private Key (encrypted)
  - Disimpan di: `storage/app/tenants/{tenant_id}/ca/`

---

### 2️⃣ **Generate User Certificate** (Per User)
**Status**: ✅ SELESAI
- Setiap user yang ingin menandatangani dokumen harus punya certificate
- User klik **"My Certificate"** → masukkan passphrase
- Passphrase di-hash (SHA-256) sebelum dikirim ke server
- System membuat:
  - User Certificate (signed by Root CA)
  - User Private Key (encrypted dengan passphrase hash)
  - Disimpan di: `storage/app/tenants/{tenant_id}/certs/{user_id}/`

**⚠️ PENTING**: User HARUS INGAT passphrase ini untuk signing nanti!

---

### 3️⃣ **Create Signing Session** (Next Step)
**Status**: 🔄 BELUM DITEST

User yang ingin dokumen ditandatangani:
1. Klik **"New Signing Session"**
2. Upload dokumen (PDF)
3. Pilih signing workflow:
   - **Sequential**: Signer 1 → Signer 2 → Signer 3 (berurutan)
   - **Parallel**: Semua signer bisa sign bersamaan
   - **Hybrid**: Kombinasi sequential dan parallel
4. Pilih signers (users yang sudah punya certificate)
5. Submit

**Output**:
- Document tersimpan di database
- Signing session dibuat
- Signature records dibuat untuk setiap signer (status: pending)

---

### 4️⃣ **Sign Document** (Per Signer)
**Status**: 🔄 BELUM DITEST

Setiap signer yang ditunjuk:
1. Lihat "Pending Action" di dashboard (counter kuning)
2. Klik document yang perlu di-sign
3. Masukkan passphrase (untuk decrypt private key)
4. System:
   - Hash dokumen (SHA-256)
   - Sign hash dengan private key user
   - Simpan signature
   - Update status signature menjadi "signed"

**Sequential Workflow**:
- Signer berikutnya baru bisa sign setelah signer sebelumnya selesai

**Parallel Workflow**:
- Semua signer bisa sign kapan saja

---

### 5️⃣ **Verify Signature** (Anyone)
**Status**: 🔄 BELUM DITEST

Siapa saja bisa verify dokumen yang sudah ditandatangani:
1. Upload dokumen yang sudah di-sign
2. System verify:
   - Hash dokumen cocok dengan yang di-sign?
   - Signature valid dengan public key signer?
   - Certificate signer masih valid (belum expired/revoked)?
3. Tampilkan hasil:
   - ✅ Valid: Dokumen asli, tidak diubah
   - ❌ Invalid: Dokumen sudah dimodifikasi atau signature palsu

---

## 🔐 Keamanan yang Sudah Diimplementasi

✅ **Passphrase hashing**: SHA-256 di frontend sebelum dikirim
✅ **Private key encryption**: Encrypted dengan passphrase hash
✅ **Response sanitization**: Tidak ada paths, serial numbers, atau passphrase di response
✅ **Model $hidden**: Sensitive fields tidak ter-serialize ke JSON
✅ **CSRF protection**: Token validation untuk semua POST requests

---

## 📝 Testing Checklist

### ✅ Sudah Ditest:
- [x] Create Root CA
- [x] Generate User Certificate
- [x] Passphrase hashing
- [x] Response security (no sensitive data)

### 🔄 Perlu Ditest:
- [ ] Create Signing Session (upload PDF, pilih signers)
- [ ] Sign Document (sequential workflow)
- [ ] Sign Document (parallel workflow)
- [ ] Verify Signature
- [ ] Revoke Certificate
- [ ] Handle expired certificates

---

## 🚀 Next Steps

1. **Test Create Signing Session**:
   - Klik "New Signing Session"
   - Upload sample PDF
   - Pilih workflow type
   - Pilih signers

2. **Test Signing**:
   - Login sebagai signer
   - Lihat pending signatures
   - Sign dengan passphrase

3. **Test Verification**:
   - Download signed document
   - Verify signature

4. **Production Readiness**:
   - Implement proper CA private key encryption
   - Add audit logging
   - Add email notifications
   - Add certificate expiry warnings
   - Implement certificate revocation list (CRL)

---

## 🐛 Known Issues

1. ✅ **FIXED**: Redirect error (missing tenant parameter) - menggunakan `Inertia::location()`
2. ⚠️ **TODO**: CA private key belum di-encrypt (masih plaintext di storage)
3. ⚠️ **TODO**: Belum ada email notification untuk pending signatures
4. ⚠️ **TODO**: Belum ada auto-cleanup untuk expired certificates

---

## 📞 Support

Jika ada error atau pertanyaan, cek:
1. `storage/logs/laravel.log` untuk backend errors
2. Browser console untuk frontend errors
3. Network tab untuk request/response issues
