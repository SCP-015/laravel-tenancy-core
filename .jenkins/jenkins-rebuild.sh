#!/bin/bash

echo "🔄 Rebuilding Jenkins PHP Agent"
echo "================================"
echo ""

# Step 1: Stop containers
echo "🛑 Step 1: Stopping containers..."
docker-compose -f .jenkins/docker-compose-jenkins.yml down
echo "✅ Containers stopped"
echo ""

# Step 2: Rebuild PHP Agent
echo "🔨 Step 2: Rebuilding PHP Agent (with intl extension)..."
cd .jenkins
docker build --no-cache -t nusahire-php-agent:latest .
if [ $? -ne 0 ]; then
    echo "❌ Build failed!"
    exit 1
fi
echo "✅ PHP Agent rebuilt successfully"
echo ""

# Step 3: Restart containers
cd ..
echo "🚀 Step 3: Restarting containers..."
docker-compose -f .jenkins/docker-compose-jenkins.yml up -d --force-recreate
echo "✅ Containers restarted"
echo ""

# Step 4: Wait for containers to be ready
echo "⏳ Step 4: Waiting for containers to be ready..."
sleep 10

# Step 5: Verify intl extension
echo "🔍 Step 5: Verifying intl extension..."
docker exec nusahire-php-agent php -m | grep intl
if [ $? -eq 0 ]; then
    echo "✅ intl extension installed!"
else
    echo "❌ intl extension NOT found!"
    exit 1
fi
echo ""

# Step 6: Verify PCOV
echo "🔍 Step 6: Verifying PCOV..."
docker exec nusahire-php-agent php -m | grep pcov
if [ $? -eq 0 ]; then
    echo "✅ PCOV installed!"
else
    echo "❌ PCOV NOT found!"
    exit 1
fi
echo ""

# Step 7: Verify Node.js
echo "🔍 Step 7: Verifying Node.js..."
docker exec nusahire-php-agent node --version
docker exec nusahire-php-agent npm --version
if [ $? -eq 0 ]; then
    echo "✅ Node.js installed!"
else
    echo "❌ Node.js NOT found!"
    exit 1
fi
echo ""

# Step 8: Install dependencies
echo "📚 Step 8: Installing dependencies..."
docker exec nusahire-php-agent composer install --no-interaction
echo "✅ Dependencies installed"
echo "ℹ️  Tests akan menggunakan .env.testing (via phpunit.xml)"
echo ""

echo "🎉 Rebuild complete!"
echo ""
echo "Jenkins Dashboard: http://localhost:8080/jenkins"
echo ""
echo "Untuk stop Jenkins: docker-compose -f .jenkins/docker-compose-jenkins.yml down"
