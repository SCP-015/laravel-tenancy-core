def shouldStageRun() {
    // Jika bukan branch master, selalu jalankan
    if (env.BRANCH_NAME != 'master') {
        return true
    }

    // --- Jika branch adalah master, periksa pemicunya ---
    // Pemicu 1: Dijalankan oleh timer (cron)
    def isScheduled = currentBuild.getBuildCauses('hudson.triggers.TimerTrigger$TimerTriggerCause').size() > 0

    // Pemicu 2: Dijalankan manual oleh pengguna
    def isManual = currentBuild.getBuildCauses('hudson.model.Cause$UserIdCause').size() > 0

    // Pemicu 3 (CARA AMAN): Dijalankan oleh pemicu dari Git dengan mendeteksi adanya commit
    def isFromGit = (currentBuild.changeSets.size() > 0)

    // Jalankan hanya jika salah satu pemicu yang diizinkan terpenuhi
    return isScheduled || isManual || isFromGit
}

pipeline {
    agent any
    
    triggers {
        // Server jenkins zona waktunya UTC. 
        // Jadi, jam yang diset mesti dalam zona waktu UTC.
        cron('H 5 * * 0')
    }
    
    stages {
		stage ('Preparation') {
            when {
                expression { return shouldStageRun() }
            }
		    steps {
		        // Ambil source code projek
		        checkout scm
		    }
		}
		stage ('Verify PHP Extensions') {
            when {
                expression { return shouldStageRun() }
            }
		    steps {
		        script {
		            echo '🔍 Verifying required PHP extensions...'
		            
		            // Cek intl extension
		            def intlExists = sh(script: 'php -m | grep -i intl || echo "not_found"', returnStdout: true).trim()
		            
		            if (intlExists == 'not_found') {
		                echo '⚠️  WARNING: PHP intl extension is NOT installed!'
		                echo '⚠️  Some tests may fail. Please contact Jenkins admin to install php-intl extension.'
		                // Set environment variable untuk skip tests yang butuh intl (jika diperlukan)
		                env.SKIP_INTL_TESTS = 'true'
		            } else {
		                echo '✅ PHP intl extension: OK'
		                env.SKIP_INTL_TESTS = 'false'
		            }
		            
		            // Cek curl extension (required by Sentry)
		            def curlExists = sh(script: 'php -m | grep -i curl || echo "not_found"', returnStdout: true).trim()
		            
		            if (curlExists == 'not_found') {
		                echo '⚠️  WARNING: PHP curl extension is NOT installed!'
		                echo '⚠️  This is REQUIRED by Sentry package.'
		                echo '⚠️  Please contact Jenkins admin to install php-curl extension.'
		                echo '⚠️  Command: sudo apt-get install php8.2-curl (for Ubuntu/Debian)'
		                env.SKIP_CURL_CHECK = 'true'
		            } else {
		                echo '✅ PHP curl extension: OK'
		                env.SKIP_CURL_CHECK = 'false'
		            }
		            
		            // Tampilkan versi PHP
		            sh 'php --version'
		        }
		    }
		}
		stage ('Setup Node.js') {
            when {
                expression { return shouldStageRun() }
            }
		    steps {
		        script {
		            // Cek apakah npm sudah terinstall
		            def npmExists = sh(script: 'which npm || echo "not_found"', returnStdout: true).trim()
		            
		            if (npmExists == 'not_found') {
		                echo '📦 Installing Node.js via nvm...'
		                sh '''
		                    # Install nvm jika belum ada
		                    if [ ! -d "$HOME/.nvm" ]; then
		                        curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
		                    fi
		                    
		                    # Load nvm dan install Node.js LTS
		                    export NVM_DIR="$HOME/.nvm"
		                    [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
		                    
		                    nvm install --lts
		                    nvm use --lts
		                    
		                    # Verify installation
		                    node --version
		                    npm --version
		                '''
		            } else {
		                echo '✅ Node.js already installed'
		                sh 'node --version && npm --version'
		            }
		        }
		    }
		}
		stage ('Install Dependencies') {
            when {
                expression { return shouldStageRun() }
            }
			steps {
				script {
					// Install PHP dependencies
					// Jika curl extension tidak ada, gunakan --ignore-platform-req=ext-curl
					if (env.SKIP_CURL_CHECK == 'true') {
						echo '⚠️  Installing with --ignore-platform-req=ext-curl (curl extension not found)'
						sh 'composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-req=ext-curl'
					} else {
						sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
					}
				}
				
				// Install Node dependencies (for frontend build)
				sh '''
					export NVM_DIR="$HOME/.nvm"
					[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
					npm ci
				'''
				
				// Setup environment - gunakan .env.testing untuk testing
				sh 'cp .env.testing .env'
				sh 'php artisan key:generate'
				
				// Generate OAuth keys for Passport (needed for tests)
				sh 'php artisan passport:keys --force'
			}
		}
		stage ('Build Frontend') {
            when {
                expression { return shouldStageRun() }
            }
		    steps {
		        // Build Vite assets (creates public/build/manifest.json)
		        sh '''
		            export NVM_DIR="$HOME/.nvm"
		            [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
		            npm run build
		        '''
		    }
		}
		stage ('Run Tests') {
            when {
                expression { return shouldStageRun() }
            }
	    steps {
                catchError(buildResult: 'UNSTABLE', stageResult: 'FAILURE') {
                    // Buat folder build/logs
                    sh 'mkdir -p build/logs'

                    // Jalankan unit test dengan PCOV dan memory limit yang cukup
                    // HANYA generate clover XML (untuk SonarQube), skip HTML report (lambat)
                    // Parallel testing untuk speed (auto-detect processor cores)
                    sh 'php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app artisan test --parallel --coverage-clover=build/logs/coverage-clover.xml --log-junit=build/logs/report-unit-test.xml'
                }
	    }
		    post {
		        always {
		            // Cleanup test databases
		            sh './cleanup-test-db.sh || true'
		        }
		    }
		}
		stage ('Run Sonar') {
		    // Definisikan nama sonar scanner
		    environment {
		        scannerHome = tool 'SonarScanner'
		    }
            when {
                expression { return shouldStageRun() }
            }
		    steps {
                // Jalankan sonar scanner
		        sh "${scannerHome}/bin/sonar-scanner"
		    }
		}
		stage ('Check Status Sonarqube') {
            when {
                expression { return shouldStageRun() }
            }
            steps {
		        script {
		            // env.BRANCH_NAME bisa bernilai nama branch, id pull request atau git tag
                    if (env.BRANCH_NAME.startsWith('PR-')) {
                        // Jika BRANCH_NAME dimulai dengan PR-. Ini berarti, pull request
                        def pullRequestNumber = env.BRANCH_NAME.replace('PR-', '')
                        echo "pull request number: $pullRequestNumber"

                        // Waiting selama 10 detik agar data sonar-nya masuk terlebih dahulu ke server
                        sleep 10

                        // Cek status dari sonarqube terhadap pull request yang bersangkutan
                        sh "curl -sb --request GET \
                                --url 'https://sonar.nusa.work/api/qualitygates/project_status?pullRequest=$pullRequestNumber&projectKey=$PROJECT_KEY_SONAR_NUSAHIRE' \
                                --header 'Authorization: Basic $AUTHORIZATION_SONAR_NUSAWORK' \
                                --header 'Accept: application/json' > status_sonar_response.json"
                        def statusSonarResponseJson = readJSON file:'status_sonar_response.json'
                        def sonarStatus = statusSonarResponseJson.projectStatus.status
                        echo "sonar status: $sonarStatus"
                        if (sonarStatus == 'OK') {
                            // TODO: Di sini nanti bakalan ada script pull request auto merge
                        } else {
                            currentBuild.result = 'FAILURE'
                            error('SonarQube quality gate status of a project is failure.')
                        }
                    } else {
                        def branchName = env.BRANCH_NAME
                        echo "branch name: $branchName"

                        // Waiting selama 10 detik agar data sonar-nya masuk terlebih dahulu
                        sleep 10

                        // Cek status dari sonarqube terhadap branch yang bersangkutan
                        sh "curl -sb --request GET \
                          --url 'https://sonar.nusa.work/api/qualitygates/project_status?branch=$branchName&projectKey=$PROJECT_KEY_SONAR_NUSAHIRE' \
                          --header 'Authorization: Basic $AUTHORIZATION_SONAR_NUSAWORK' \
                          --header 'Accept: application/json' > status.json"
                        def json = readJSON file:'status.json'
                        def sonarStatus = json.projectStatus.status
                        echo "sonar status: $sonarStatus"
                        if (sonarStatus != "OK") {
                            currentBuild.result = 'FAILURE'
                            error('SonarQube quality gate status of a project is failure.')
                        }
                    }
		        }
		    }
        }
    }
    post {
        always {
            // HTML coverage report tidak di-generate lagi (untuk speed)
            // Coverage report bisa dilihat di SonarQube
            echo 'Pipeline completed. Check SonarQube for coverage report.'
        }
    }
}