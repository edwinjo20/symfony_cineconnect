pipeline {
    agent any

    environment {
        GIT_REPO = "https://github.com/edwinjo20/symfony_cineconnect.git"
        GIT_BRANCH = "main"
        DEPLOY_DIR = "web005"
        DB_NAME = "web005"
        DB_USER = "root"
        DB_PASS = "routitop"
        DB_HOST = "127.0.0.1"
        DB_PORT = "3306"
        SERVER_VERSION = "8.3.0"
    }

    stages {
        stage('Clean Workspace') {
            steps {
                cleanWs() // Cleans the workspace before the build starts
            }
        }

        stage('Clone Repository') {
            steps {
                sh "rm -rf ${DEPLOY_DIR}" // Remove previous build
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${DEPLOY_DIR}" // Clone the repository
            }
        }

        stage('Install Dependencies') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'composer install --optimize-autoloader' // Install dependencies
                }
            }
        }

        stage('Configure Environment') {
            steps {
                script {
                    def envLocal = """
                    APP_ENV=prod
                    APP_DEBUG=0
                    DATABASE_URL=mysql://${DB_USER}:${DB_PASS}@${DB_HOST}:${DB_PORT}/${DB_NAME}?serverVersion=${SERVER_VERSION}&charset=utf8mb4
                    """.stripIndent()

                    writeFile file: "${DEPLOY_DIR}/.env.local", text: envLocal
                }
            }
        }

        stage('Check Database & Run Migrations') {
            steps {
                dir("${DEPLOY_DIR}") {
                    script {
                        def checkDB = sh(script: "mysql -u${DB_USER} -p${DB_PASS} -h ${DB_HOST} -P ${DB_PORT} -e 'SHOW DATABASES LIKE \"${DB_NAME}\";'", returnStdout: true).trim()
                        if (!checkDB.contains(DB_NAME)) {
                            sh "php bin/console doctrine:database:create --if-not-exists --env=prod"
                        }
                    }
                    // Apply migrations safely
                    sh 'php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod || php bin/console doctrine:schema:update --force --env=prod'
                }
            }
        }

        stage('Clear & Warmup Cache') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'php bin/console cache:clear --env=prod'
                    sh 'php bin/console cache:warmup'
                }
            }
        }

        stage('Deployment') {
            steps {
                sh "sudo rm -rf /var/www/html/${DEPLOY_DIR}" // Force remove old deployment
                sh "sudo mkdir -p /var/www/html/${DEPLOY_DIR}" // Ensure directory exists
                sh "sudo cp -rT ${DEPLOY_DIR} /var/www/html/${DEPLOY_DIR}" // Copy project files
                sh "sudo ln -s /var/www/html/${DEPLOY_DIR}/public /var/www/html/${DEPLOY_DIR}/www" // Fix Apache path
                sh "sudo chown -R www-data:www-data /var/www/html/${DEPLOY_DIR}" // Set proper ownership
                sh "sudo chmod -R 775 /var/www/html/${DEPLOY_DIR}/var"
            }
        }
    }

    post {
        success {
            echo '✅ Deployment Successful!'
        }
        failure {
            echo '❌ Deployment Failed!'
        }
    }
}
