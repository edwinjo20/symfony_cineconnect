pipeline {
    agent any

    environment {
        GIT_REPO = "https://github.com/edwinjo20/symfony_cineconnect.git" // Replace with your repo
        GIT_BRANCH = "main"
        DEPLOY_DIR = "symfony_app" // Adjust to your server path
    }

    stages {
        stage('Clone Repository') {
            steps {
                script {
                    sh "rm -rf ${DEPLOY_DIR}" // Deletes old deployment folder
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
                    DATABASE_URL=mysql://root:@127.0.0.1:3306/${DB_NAME}?serverVersion=8.0&charset=utf8mb4
                    """
                    writeFile file: "${DEPLOY_DIR}/.env.local", text: envLocal
                }
            }
        }

        stage('Migrate Database') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'php bin/console doctrine:database:create --if-not-exists'
                    sh 'php bin/console doctrine:migrations:migrate --no-interaction --env=prod'
                }
            }
        }

        stage('Clear Cache & Set Permissions') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'php bin/console cache:clear --env=prod'
                    sh 'php bin/console cache:warmup --env=prod'
                }
            }
        }

        stage('Deploy to Server') {
            steps {
                script {
                    sh "rm -rf /var/www/html/${DEPLOY_DIR}/*" // Deletes old deployment folder
                    sh "cp -r ${DEPLOY_DIR}/* /var/www/html/${DEPLOY_DIR}"
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
