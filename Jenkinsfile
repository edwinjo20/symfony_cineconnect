pipeline {
    agent any

    environment {
        GIT_REPO = "https://github.com/edwinjo20/symfony_cineconnect.git"
        GIT_BRANCH = "main"
        DEPLOY_DIR = "symfony_app"
        DB_HOST = "mysql"  // Change if MySQL is running elsewhere
        DB_NAME = "cinemacineconnect"
        DB_USER = "root"
        DB_PASS = "" // Change if your MySQL requires a password
    }

    stages {
        stage('Clone Repository') {
            steps {
                script {
                    sh "rm -rf ${DEPLOY_DIR}"
                    sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${DEPLOY_DIR}"
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'composer install --no-dev --optimize-autoloader'
                }
            }
        }

        stage('Setup Environment') {
            steps {
                script {
                    def envLocal = """
                    APP_ENV=prod
                    APP_DEBUG=0
                    DATABASE_URL=mysql://${DB_USER}:${DB_PASS}@${DB_HOST}:3306/${DB_NAME}?serverVersion=8.0&charset=utf8mb4
                    """
                    writeFile file: "${DEPLOY_DIR}/.env.local", text: envLocal
                }
            }
        }

        stage('Migrate Database') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'php bin/console doctrine:database:create --if-not-exists || true'
                    sh 'php bin/console doctrine:migrations:migrate --no-interaction --env=prod || true'
                }
            }
        }

        stage('Clear Cache & Set Permissions') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'rm -rf var/cache/*'  // Manually clear cache before running Symfony commands
                    sh 'APP_ENV=prod php bin/console cache:clear --no-debug || true'
                    sh 'APP_ENV=prod php bin/console cache:warmup || true'
                    sh 'chmod -R 775 var/'
                }
            }
        }

        stage('Deploy to Server') {
            steps {
                script {
                    sh "mkdir -p /var/www/html/${DEPLOY_DIR}"
                    sh "rsync -av --delete ${DEPLOY_DIR}/ /var/www/html/${DEPLOY_DIR}/"
                    sh "chmod -R 775 /var/www/html/${DEPLOY_DIR}/var"
                }
            }
        }
    }

    post {
        success {
            echo "✅ Symfony application deployed successfully!"
        }
        failure {
            echo "❌ Deployment failed. Check Jenkins logs for errors."
        }
    }
}
