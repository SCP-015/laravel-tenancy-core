# 🚀 Jenkins Local - Quick Start

## ⚡ Super Cepat (2 Menit!)

### 1. Jalankan Setup

```bash
./jenkins-local-setup.sh
```

### 2. Buka Browser

```
http://localhost:8080/jenkins
```

### 3. Login

- Copy password dari terminal
- Paste di Jenkins login page
- Klik "Install suggested plugins"
- Create admin user
- Done! ✅

---

## 📖 Tutorial Lengkap

Lihat: [JENKINS_LOCAL_TUTORIAL.md](docs/JENKINS_LOCAL_TUTORIAL.md)

---

## 🛠️ Commands

```bash
# Start Jenkins
docker-compose -f docker-compose-jenkins.yml up -d

# Stop Jenkins  
docker-compose -f docker-compose-jenkins.yml down

# Lihat logs
docker logs nusahire-jenkins -f

# Test manual
docker exec nusahire-php-agent php artisan test
```

---

## ❓ Troubleshooting

### Port 8080 sudah dipakai?

Edit `docker-compose-jenkins.yml`:
```yaml
ports:
  - "8888:8080"  # Ganti ke port lain
```

### Docker error?

```bash
# Restart Docker Desktop
# Lalu jalankan ulang setup
./jenkins-local-setup.sh
```

### Build failed?

```bash
# Lihat logs detail
docker exec nusahire-php-agent php artisan test -v
```

---

## ✅ Success Checklist

- [ ] Jenkins running di http://localhost:8080/jenkins
- [ ] PCOV detected: `docker exec nusahire-php-agent php -m | grep pcov`
- [ ] Tests passing: 339 tests ✅
- [ ] Coverage report: `coverage.xml` exists

---

🎉 **Happy Testing!**
