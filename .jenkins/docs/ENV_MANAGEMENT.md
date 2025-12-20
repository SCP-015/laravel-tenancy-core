# 🔒 Environment Management untuk Jenkins CI

## 🚨 **PROBLEM YANG SOLVED:**

### **Masalah Lama:**
```bash
# Jenkins overwrite .env lokal developer
docker exec ... cp .env.testing .env  ❌
# Result: PostgreSQL config hilang, development terganggu!
```

---

## ✅ **SOLUTION: Dedicated `.env.jenkins`**

### **File Hierarchy:**
```
.env              ← Development lokal (PostgreSQL, dll) - NEVER TOUCHED!
.env.testing      ← Template untuk testing (SQLite)
.env.jenkins      ← Jenkins CI config (generated from .env.testing)
.env.ci           ← Temporary runtime env (auto-deleted after test)
```

---

## 🔄 **WORKFLOW:**

### **1. First Time Setup:**
```bash
# Create .env.jenkins from template (once)
cp .env.testing .env.jenkins
```

### **2. Jenkins Pipeline Run:**
```bash
# Step 1: Check if .env.jenkins exists, create if not
test -f .env.jenkins || cp .env.testing .env.jenkins

# Step 2: Copy .env.jenkins → .env.ci (temporary)
cp .env.jenkins .env.ci

# Step 3: Generate key untuk .env.ci
php artisan key:generate --env=ci

# Step 4: Run tests dengan .env.ci
php artisan test --env=ci

# Step 5: Cleanup (delete .env.ci)
rm -f .env.ci
```

### **3. Local `.env` Status:**
```
✅ NEVER MODIFIED
✅ NEVER BACKED UP
✅ NEVER TOUCHED
```

---

## 📋 **FILES & PURPOSE:**

| File | Purpose | Gitignore? | Touched by Jenkins? |
|------|---------|------------|---------------------|
| `.env` | **Development lokal** | ✅ Yes | ❌ **NEVER** |
| `.env.testing` | Template testing | ❌ No (committed) | Read only |
| `.env.jenkins` | CI config cache | ✅ Yes | Create if missing |
| `.env.ci` | Runtime temporary | ✅ Yes | Create & delete |

---

## 🛡️ **SAFETY FEATURES:**

### **1. No `.env` Overwrite**
```bash
# OLD (DANGEROUS):
cp .env.testing .env  ❌

# NEW (SAFE):
cp .env.jenkins .env.ci  ✅
# .env tidak pernah di-touch!
```

### **2. Auto-Create `.env.jenkins`**
```bash
test -f .env.jenkins || cp .env.testing .env.jenkins
```
Jika developer lupa create, Jenkins auto-generate.

### **3. Cleanup After Test**
```bash
# post: always
rm -f .env.ci
```
Temporary file selalu di-cleanup.

---

## 🔧 **CUSTOMIZATION:**

### **Update Jenkins Test Config:**

Edit `.env.jenkins` jika butuh custom config untuk CI:

```env
# .env.jenkins
APP_ENV=testing
APP_DEBUG=true

# Database - SQLite for testing
DB_CONNECTION=sqlite

# Cache - Array driver (no external service)
CACHE_STORE=array
QUEUE_CONNECTION=sync

# Mail - Array driver (no email sending)
MAIL_MAILER=array

# Custom for CI
SOME_CI_SPECIFIC_CONFIG=value
```

**JANGAN** commit `.env.jenkins` ke Git!

---

## 📝 **DEVELOPER GUIDELINES:**

### **✅ DO:**
- Use `.env` untuk development lokal
- Customize `.env.jenkins` jika butuh CI-specific config
- Commit `.env.testing` sebagai template

### **❌ DON'T:**
- Jangan commit `.env` atau `.env.jenkins`
- Jangan manual create `.env.ci` (Jenkins auto-manage)
- Jangan rely on `.env` untuk Jenkins testing

---

## 🚀 **BENEFITS:**

| Benefit | Description |
|---------|-------------|
| **🔒 Safe** | Local `.env` tidak pernah di-touch |
| **⚡ Fast** | `.env.jenkins` di-reuse, tidak recreate tiap run |
| **🧹 Clean** | Auto-cleanup `.env.ci` setelah test |
| **🎯 Isolated** | CI config terpisah dari dev config |
| **👥 Team-friendly** | Setiap developer bisa punya `.env` sendiri |

---

## 📊 **COMPARISON:**

### **Before (BAD):**
```
Developer Local:
.env (PostgreSQL) 
    ↓ Jenkins run
.env (SQLite) ❌ OVERWRITTEN!
    ↓ Developer confusion
"Kenapa database saya berubah?!" 😱
```

### **After (GOOD):**
```
Developer Local:
.env (PostgreSQL) ✅ SAFE, never touched
    
Jenkins CI:
.env.jenkins → .env.ci (temporary)
    ↓ Test finished
.env.ci deleted
    
Developer:
.env (PostgreSQL) ✅ Still intact!
```

---

## 🎯 **SUMMARY:**

**Rule #1:** `.env` adalah **SACRED** - Jenkins NEVER TOUCH IT!  
**Rule #2:** Use `.env.jenkins` untuk CI  
**Rule #3:** `.env.ci` adalah temporary, always cleaned up

---

**Created:** 2025-10-06  
**Status:** ✅ Production Ready  
**Safety:** 🔒 100% Safe for Local Development
