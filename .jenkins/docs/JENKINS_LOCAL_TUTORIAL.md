# 🚀 Jenkins Local Tutorial - Untuk Pemula

Panduan ini akan membantu kamu setup dan run Jenkins di local menggunakan Docker, **tanpa perlu pengetahuan mendalam tentang Docker atau Jenkins**.

---

## 📋 **Prerequisites**

Yang kamu butuhkan:
- ✅ Docker Desktop sudah terinstall dan running
- ✅ Terminal/Command Prompt
- ✅ Project NusaHire ini

---

## 🎯 **Step-by-Step Setup (5 Menit!)**

### **STEP 1: Jalankan Setup Script**

Buka terminal di folder project ini, lalu:

```bash
# Berikan permission ke script
chmod +x jenkins-local-setup.sh

# Jalankan setup
./jenkins-local-setup.sh
```

Script ini akan otomatis:
1. ✅ Build Docker image untuk PHP + PCOV
2. ✅ Start Jenkins container
3. ✅ Show initial admin password
4. ✅ Test PCOV installation
5. ✅ Run tests dengan coverage

### **STEP 2: Login ke Jenkins**

Setelah script running, kamu akan dapat **Initial Admin Password**.

1. **Buka browser**: http://localhost:8080/jenkins

2. **Copy-paste password** yang ditampilkan di terminal

   ![Jenkins Login](https://i.imgur.com/jenkins-login.png)

3. **Klik "Continue"**

### **STEP 3: Install Plugins**

1. **Pilih**: "Install suggested plugins"
   
   ![Install Plugins](https://i.imgur.com/jenkins-plugins.png)

2. **Tunggu ~2-3 menit** sambil plugins ter-install

### **STEP 4: Create Admin User**

1. Isi form:
   - Username: `admin`
   - Password: `admin123` (atau terserah kamu)
   - Full name: `Admin`
   - Email: `admin@nusahire.local`

2. **Klik "Save and Continue"**

3. **Klik "Save and Finish"**

4. **Klik "Start using Jenkins"**

---

## 🔧 **Membuat Jenkins Job**

### **STEP 1: Create New Job**

1. Di Jenkins Dashboard, klik **"New Item"**

2. Isi:
   - **Name**: `nusahire-test`
   - **Type**: Pilih **"Pipeline"**
   - Klik **"OK"**

### **STEP 2: Configure Pipeline**

1. Scroll ke bawah ke section **"Pipeline"**

2. Di **"Definition"**, pilih: **"Pipeline script from SCM"**

3. Di **"SCM"**, pilih: **"Git"**

4. Di **"Repository URL"**, isi: `/var/jenkins_home/workspace/nusahire`

5. Di **"Branch Specifier"**, kosongkan atau isi: `*/main` atau `*/master`

6. Di **"Script Path"**, isi: `Jenkinsfile`

7. **Klik "Save"**

### **STEP 3: Run Job**

1. Di job page, klik **"Build Now"**

2. Lihat progress di **"Build History"**

3. Klik **#1** (build number) untuk lihat detail

4. Klik **"Console Output"** untuk lihat logs

---

## 📊 **Monitoring Tests & Coverage**

### **Lihat Test Results:**

1. Di build page, ada link **"Test Result"** (jika ada test failures)

2. Bisa lihat detail test mana yang failed

### **Lihat Coverage Report:**

Setelah build selesai:

```bash
# Di terminal, jalankan:
docker exec nusahire-php-agent ls -la coverage.xml

# Atau lihat coverage report HTML
open coverage-report/index.html
```

---

## 🛠️ **Troubleshooting**

### **1. Docker Tidak Jalan**

```bash
# Pastikan Docker Desktop running
docker ps

# Jika error, restart Docker Desktop
```

### **2. Port 8080 Sudah Dipakai**

Edit `docker-compose-jenkins.yml`, ubah port:

```yaml
ports:
  - "8888:8080"  # Ubah 8080 → 8888
```

Lalu akses: http://localhost:8888/jenkins

### **3. Jenkins Build Failed**

```bash
# Cek logs PHP Agent
docker logs nusahire-php-agent

# Atau exec ke container
docker exec -it nusahire-php-agent bash

# Test manual
php -d pcov.enabled=1 artisan test
```

### **4. PCOV Tidak Terdeteksi**

```bash
# Rebuild PHP Agent
cd .jenkins
docker build --no-cache -t nusahire-php-agent:latest .

# Restart containers
docker-compose -f docker-compose-jenkins.yml restart
```

### **5. Error: "intl PHP extension is required"**

**Symptom:**
```
The "intl" PHP extension is required to use the [currency] method.
Tests: 31 failed
```

**Solution:**

```bash
# Jalankan rebuild script
./jenkins-rebuild.sh
```

Script ini akan:
- ✅ Stop containers
- ✅ Rebuild image dengan intl extension
- ✅ Restart containers
- ✅ Verify extensions
- ✅ Run tests

**Manual Fix:**

```bash
# Edit .jenkins/Dockerfile, tambahkan:
# libicu-dev di system dependencies
# intl di PHP extensions

# Lalu rebuild:
cd .jenkins
docker build --no-cache -t nusahire-php-agent:latest .

# Restart
docker-compose -f docker-compose-jenkins.yml down
docker-compose -f docker-compose-jenkins.yml up -d
```

### **5. Coverage Report Kosong**

```bash
# Check if coverage.xml exists
docker exec nusahire-php-agent cat coverage.xml

# Regenerate
docker exec nusahire-php-agent php -d pcov.enabled=1 artisan test --coverage-clover=coverage.xml
```

---

## 🎮 **Perintah Berguna**

### **Start/Stop Jenkins:**

```bash
# Start
docker-compose -f docker-compose-jenkins.yml up -d

# Stop
docker-compose -f docker-compose-jenkins.yml down

# Restart
docker-compose -f docker-compose-jenkins.yml restart
```

### **Lihat Logs:**

```bash
# Jenkins logs
docker logs nusahire-jenkins -f

# PHP Agent logs
docker logs nusahire-php-agent -f
```

### **Exec ke Container:**

```bash
# Masuk ke Jenkins container
docker exec -it nusahire-jenkins bash

# Masuk ke PHP Agent
docker exec -it nusahire-php-agent bash
```

### **Run Tests Manual:**

```bash
# Di PHP Agent container
docker exec nusahire-php-agent php artisan test

# Dengan coverage
docker exec nusahire-php-agent php -d pcov.enabled=1 artisan test --coverage-clover=coverage.xml
```

---

## 📝 **FAQ**

### **Q: Kenapa perlu Docker?**
A: Supaya environment Jenkins sama persis di local dan production. Tidak ada "works on my machine" problem.

### **Q: Apakah data Jenkins hilang kalau restart?**
A: Tidak. Data tersimpan di Docker volume `jenkins_home`. Akan persistent meskipun restart.

### **Q: Bagaimana cara delete semua?**
A: 
```bash
# Stop dan remove containers
docker-compose -f docker-compose-jenkins.yml down

# Remove volume (hapus semua data Jenkins)
docker volume rm nusahire_jenkins_home

# Remove images
docker rmi nusahire-php-agent:latest
docker rmi jenkins/jenkins:lts
```

### **Q: Bisa pakai Jenkins ini untuk project lain?**
A: Bisa! Tinggal create new job dan configure repository URL-nya.

---

## 🎯 **Next Steps**

Setelah berhasil run Jenkins local:

1. **Experiment dengan Jenkinsfile**
   - Edit stages
   - Tambah notifications
   - Add deployment steps

2. **Setup SonarQube Local** (optional)
   ```bash
   docker run -d --name sonarqube -p 9000:9000 sonarqube:lts
   # Access: http://localhost:9000
   ```

3. **Integrate dengan GitHub/GitLab**
   - Install Git plugin
   - Configure webhooks
   - Auto-trigger builds on push

4. **Deploy to Production Jenkins**
   - Export job configuration
   - Use same Jenkinsfile
   - Configure production credentials

---

## ✅ **Checklist**

Pastikan semua ini sudah berjalan:

- [ ] Docker Desktop running
- [ ] Jenkins accessible di http://localhost:8080/jenkins
- [ ] PHP Agent container running
- [ ] PCOV terdeteksi (`php -m | grep pcov`)
- [ ] Tests berjalan sukses
- [ ] Coverage report generated (`coverage.xml` exists)
- [ ] Jenkins job berhasil build

---

## 🎉 **Selamat!**

Kamu sudah berhasil setup Jenkins local dengan:
- ✅ Automated testing
- ✅ Code coverage (PCOV)
- ✅ Docker containerization
- ✅ CI/CD pipeline ready

**Jenkins Dashboard**: http://localhost:8080/jenkins

**Need help?** Check troubleshooting section atau lihat logs!

---

## 📚 **Resources**

- [Jenkins Documentation](https://www.jenkins.io/doc/)
- [Docker Documentation](https://docs.docker.com/)
- [PCOV GitHub](https://github.com/krakjoe/pcov)
- [PHPUnit Coverage](https://phpunit.de/manual/10.0/en/code-coverage.html)
