#!/bin/bash

echo "🚀 Setup Jenkins Local untuk NusaHire"
echo "===================================="
echo ""

# Step 1: Build PHP Agent Image
echo "📦 Step 1: Build PHP Agent dengan PCOV..."
cd .jenkins
docker build -t nusahire-php-agent:latest .
if [ $? -ne 0 ]; then
    echo "❌ Build failed!"
    exit 1
fi
echo "✅ PHP Agent image berhasil dibuild"
echo ""

# Step 2: Start Jenkins
cd ..
echo "🐳 Step 2: Starting Jenkins..."
docker-compose -f .jenkins/docker-compose-jenkins.yml up -d

if [ $? -ne 0 ]; then
    echo "❌ Failed to start Jenkins!"
    exit 1
fi
echo "✅ Jenkins berhasil dijalankan"
echo ""

# Step 3: Wait for Jenkins to start
echo "⏳ Step 3: Menunggu Jenkins siap (~30 detik)..."
sleep 30

# Step 4: Get initial admin password
echo "🔑 Step 4: Jenkins Initial Admin Password:"
echo "==========================================="
docker exec nusahire-jenkins cat /var/jenkins_home/secrets/initialAdminPassword
echo ""
echo ""

# Step 5: Instructions
echo "📝 NEXT STEPS:"
echo "=============="
echo ""
echo "1. Buka browser: http://localhost:8080/jenkins"
echo ""
echo "2. Login dengan password di atas"
echo ""
echo "3. Install suggested plugins (klik 'Install suggested plugins')"
echo ""
echo "4. Create admin user"
echo ""
echo "5. Lanjut ke terminal untuk test PCOV..."
echo ""
echo "✅ Setup selesai! Press Enter untuk lanjut test PCOV..."
read

# Step 6: Test PCOV di PHP Agent
echo ""
echo "🧪 Step 6: Testing PCOV di PHP Agent..."
echo "========================================"
docker exec nusahire-php-agent php -m | grep pcov
if [ $? -eq 0 ]; then
    echo "✅ PCOV terdeteksi!"
else
    echo "❌ PCOV tidak terdeteksi!"
    exit 1
fi
echo ""

# Step 7: Test Node.js di PHP Agent
echo "🧪 Step 7: Testing Node.js di PHP Agent..."
echo "==========================================="
docker exec nusahire-php-agent node --version
docker exec nusahire-php-agent npm --version
if [ $? -eq 0 ]; then
    echo "✅ Node.js terdeteksi!"
else
    echo "❌ Node.js tidak terdeteksi!"
    exit 1
fi
echo ""

# Step 8: Install dependencies
echo "📚 Step 8: Installing dependencies..."
docker exec nusahire-php-agent composer install --no-interaction
echo "✅ Dependencies installed"
echo "ℹ️  Tests akan menggunakan .env.testing (via phpunit.xml)"
echo ""

echo "🎉 Setup selesai!"
echo ""
echo "Jenkins Dashboard: http://localhost:8080/jenkins"
echo ""
echo "Untuk stop Jenkins: docker-compose -f .jenkins/docker-compose-jenkins.yml down"
