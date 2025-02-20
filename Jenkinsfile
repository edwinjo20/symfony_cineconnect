pipeline {
    agent any

    environment {
        DEPLOY_DIR = "cineconnect_edwin" // ✅ No spaces or special characters
    }

    stages {
        // Stage 1: Check Composer Version
        stage('Check Composer Version') {
            steps {
                sh 'composer --version'
            }
        }

        // Stage 2: Clone the Repository
        stage('Clone Repository') {
            steps {
                sh "rm -rf \"${DEPLOY_DIR}\"" // Cleanup
                sh "git clone -b main https://github.com/edwinjo20/symfony_cineconnect.git \"${DEPLOY_DIR}\""
                sh "ls -lah \"${DEPLOY_DIR}\"" // ✅ Verify cloning
            }
        }

        // Stage 3: Fix Symfony Flex (if needed)
        stage('Fix Symfony Flex') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh '''
                    composer clear-cache
                    composer require symfony/flex --no-plugins --no-scripts --no-update || true
                    composer update symfony/flex --no-scripts || true
                    '''
                }
            }
        }

        // Stage 4: Install Dependencies
        stage('Install Dependencies') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'composer install --no-interaction --optimize-autoloader --no-scripts'
                }
            }
        }

        // Stage 5: Configure Environment
        stage('Configure Environment') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh '''
                    echo "APP_ENV=prod" > .env.local
                    echo "APP_DEBUG=0" >> .env.local
                    echo "DATABASE_URL=mysql://root:@mysql_container:3306/cinemacineconnect" >> .env.local
                    cat .env.local # Debug: Print the contents of .env.local
                    '''
                }
            }
        }

        // Stage 6: Run Database Migrations
        stage('Run Database Migrations') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh '''
                    php bin/console doctrine:database:create --if-not-exists || true
                    php bin/console doctrine:migrations:migrate --no-interaction
                    '''
                }
            }
        }

        // Stage 7: Clear and Warmup Cache
        stage('Clear and Warmup Cache') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'php bin/console cache:clear --env=prod'
                    sh 'php bin/console cache:warmup'
                }
            }
        }

        // Stage 8: Deploy Application
        stage('Deploy Application') {
            steps {
                sh '''
                mkdir -p /var/www/html/"${DEPLOY_DIR}"
                cp -rT "${DEPLOY_DIR}" /var/www/html/"${DEPLOY_DIR}"
                chown -R jenkins:jenkins /var/www/html/"${DEPLOY_DIR}"
                chmod -R 775 /var/www/html/"${DEPLOY_DIR}/var"
                '''
            }
        }
    }

    post {
        success {
            echo '✅ Deployment successful!'
        }
        failure {
            echo '❌ Deployment failed.'
        }
    }
}