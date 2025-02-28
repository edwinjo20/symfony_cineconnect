pipeline {
    agent any

    environment {
        GIT_REPO = "https://github.com/edwinjo20/symfony_cineconnect.git"
        GIT_BRANCH = "main"
        DEPLOY_DIR = "$WORKSPACE/build/web005"  // Build directory
        DEPLOY_TARGET = "/var/www/html/web005"  // Deployment directory
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
                cleanWs() // Clean workspace before build
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
                        // Log existing databases
                        sh "mysql -u${DB_USER} -p${DB_PASS} -h ${DB_HOST} -P ${DB_PORT} -e 'SHOW DATABASES;' | tee var/log/jenkins-database.log"

                        // Check if database exists
                        def checkDB = sh(script: "mysql -u${DB_USER} -p${DB_PASS} -h ${DB_HOST} -P ${DB_PORT} -e 'SHOW DATABASES LIKE \"${DB_NAME}\";'", returnStdout: true).trim()
                        if (!checkDB.contains(DB_NAME)) {
                            sh "php bin/console doctrine:database:create --if-not-exists --env=prod"
                        }
                    }
                    // Log schema validation to check for missing fields
                    sh 'php bin/console doctrine:schema:validate | tee var/log/jenkins-schema-validate.log'
                    
                    // Run migrations or force schema update
                    sh 'php bin/console doctrine:migrations:migrate --no-interaction --env=prod || php bin/console doctrine:schema:update --force --env=prod'
                }
            }
        }

        stage('Clear & Warmup Cache') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'php bin/console cache:clear --env=prod | tee var/log/jenkins-cache-clear.log'
                    sh 'php bin/console cache:warmup | tee var/log/jenkins-cache-warmup.log'
                }
            }
        }

        stage('Deployment') {
            steps {
                script {
                    sh """
                    rm -rf ${DEPLOY_TARGET} || true
                    mkdir -p ${DEPLOY_TARGET}
                    cp -rT ${DEPLOY_DIR} ${DEPLOY_TARGET}
                    ln -s ${DEPLOY_TARGET}/public ${DEPLOY_TARGET}/www || true
                    chmod -R 775 ${DEPLOY_TARGET}/var
                    """
                }
            }
        }
    }

    post {
        success {
            echo '✅ Deployment Successful!'
        }
        failure {
            echo '❌ Deployment Failed! Checking logs...'
            sh "cat ${DEPLOY_TARGET}/var/log/prod.log || echo 'No logs found'"
        }
    }
}
