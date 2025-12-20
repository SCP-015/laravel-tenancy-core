# Jenkins Agent untuk NusaHire

Docker image untuk Jenkins agent dengan PHP 8.4 + PCOV pre-installed.

## 🚀 Quick Start

### Build Image

```bash
docker build -t nusahire-jenkins-agent:latest .
```

### Test Locally

```bash
# Start container
docker-compose up -d

# Exec ke container
docker exec -it nusahire-jenkins-agent bash

# Verify PCOV
php -m | grep pcov

# Install dependencies
composer install

# Run tests
php -d pcov.enabled=1 -d pcov.directory=app artisan test --coverage-clover=coverage.xml

# Stop container
docker-compose down
```

## 📦 Included

- PHP 8.4 CLI
- Composer 2.x
- PCOV extension
- Common PHP extensions (pdo_mysql, mbstring, bcmath, gd, zip, dll)

## 🔧 Usage di Jenkins

### Option 1: Docker Agent

```groovy
pipeline {
    agent {
        docker {
            image 'nusahire-jenkins-agent:latest'
        }
    }
    stages {
        stage('Test') {
            steps {
                sh 'composer install'
                sh 'php -d pcov.enabled=1 artisan test --coverage-clover=coverage.xml'
            }
        }
    }
}
```

### Option 2: Kubernetes Pod

```groovy
pipeline {
    agent {
        kubernetes {
            yaml '''
apiVersion: v1
kind: Pod
spec:
  containers:
  - name: php
    image: nusahire-jenkins-agent:latest
    command: ['cat']
    tty: true
'''
        }
    }
    stages {
        stage('Test') {
            steps {
                container('php') {
                    sh 'composer install'
                    sh 'php -d pcov.enabled=1 artisan test --coverage-clover=coverage.xml'
                }
            }
        }
    }
}
```

## 🔍 Troubleshooting

### PCOV tidak terdeteksi

```bash
# Check extensions
docker exec -it nusahire-jenkins-agent php -m | grep pcov

# Rebuild image
docker-compose down
docker build --no-cache -t nusahire-jenkins-agent:latest .
docker-compose up -d
```

### Test gagal

```bash
# Check logs
docker exec -it nusahire-jenkins-agent tail -f storage/logs/laravel.log

# Debug interaktif
docker exec -it nusahire-jenkins-agent bash
php artisan test --filter=TestName
```
