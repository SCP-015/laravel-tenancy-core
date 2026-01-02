# Dokumentasi API Digital Signature (Mobile App)

Dokumen ini menjelaskan endpoint API lengkap untuk fitur Digital Signature pada aplikasi mobile.
Base URL: `https://{tenant_slug}.nusahire.com/api` (atau sesuai env)

## 1. Authentication
Semua request harus menyertakan header Authorization:
```
Authorization: Bearer <access_token>
Accept: application/json
```

---

## 2. Dashboard Data
Mengambil semua data ringkasan (Status CA, Sertifikat, Dokumen Pending, History).

**Endpoint:** `GET /digital-signature/dashboard`

**Response (200 OK):**
```json
{
    "status": "success",
    "data": {
        "has_ca": true,
        "ca_info": { "name": "PT Maju Mundur", "common_name": "PT Maju Mundur Root CA" },
        "user": { "id": 123, "name": "Budi", "is_admin": true },
        "certificates": [...],
        "pending_signatures": [...],
        "signed_documents": [...],
        "available_signers": [
            { "id": 10, "name": "Ani", "email": "ani@example.com" },
            { "id": 11, "name": "Budi", "email": "budi@example.com" }
        ],
        "templates": [
            {
                "id": "HR", 
                "name": "HR",
                "description": "Default workflow for HR",
                "steps": [
                    { "user_id": 10, "name": "Ani", "role": "HR Manager", "step_order": 1 }
                ]
            }
        ]
    }
}
```

---

## 3. Create Root CA (Admin Only)
Membuat Root Certificate Authority baru untuk organisasi.

**Endpoint:** `POST /digital-signature/ca`

**Body (JSON):**
```json
{
    "common_name": "Nusawork Root CA",
    "organization": "PT Nusawork",
    "country": "ID",
    "province": "Jakarta",
    "locality": "Jakarta Selatan",
    "email": "admin@nusawork.com",
    "passphrase": "rahasia_root_ca",
    "valid_days": 3650
}
```

---

## 4. Issue Certificate
User membuat sertifikat digital personal untuk tanda tangan.

**Endpoint:** `POST /digital-signature/certificates`

**Body (JSON):**
```json
{
    "passphrase": "passphrase_user_aman",
    "label": "Sertifikat Pribadi Budi"
}
```

---

## 5. Create Signing Session (Upload Dokumen)
Upload dokumen PDF baru dan meminta tanda tangan dari user lain.

**Endpoint:** `POST /digital-signature/session`

**Header:** `Content-Type: multipart/form-data`

**Body Parameters:**
- `document`: File PDF (Max 10MB)
- `title`: Judul Dokumen (String)
- `mode`: `parallel` atau `sequential`
- `signers[0][email]`: email_signer1@example.com
- `signers[0][name]`: Nama Signer 1
- `signers[1][email]`: email_signer2@example.com
- `signers[1][name]`: Nama Signer 2

**Response:**
```json
{
    "status": "success",
    "message": "Document uploaded and signing session created.",
    "data": { "session_id": 55, "document_id": 102 }
}
```

---

## 6. Sign Document
Menandatangani dokumen yang pending.

**Endpoint:** `POST /digital-signature/sign/{signatureId}`

**Body (JSON):**
```json
{
    "certificate_id": 5,
    "passphrase": "passphrase_user_aman"
}
```

---

## 7. Verify File
Memverifikasi keaslian dokumen PDF yang sudah ditandatangani.

**Endpoint:** `POST /digital-signature/verify`

**Header:** `Content-Type: multipart/form-data`

**Body:**
- `document`: File PDF yang ingin dicek.

**Response:**
```json
{
    "status": "success",
    "data": {
        "isValid": true,
        "signatures": [
            {
                "signer": "Budi Santoso",
                "date": "2024-02-20",
                "isValid": true
            }
    }
}
```

---

## 8. Download Document
Mengunduh file PDF (Binary) untuk ditampilkan di mobile app. 
Endpoint ini bisa digunakan baik untuk dokumen yang belum ditandatangani (preview) maupun yang sudah ditandatangani.

**Endpoint:** `GET /digital-signature/download/{documentId}`

**Response:**
- `200 OK`: File Stream (application/pdf).
- `403 Forbidden`: Jika user tidak memiliki hak akses ke dokumen ini.
- `404 Not Found`: Jika file atau document ID tidak ditemukan.
