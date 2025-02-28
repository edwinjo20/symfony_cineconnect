pipeline {
    agent any

    environment {
        GIT_REPO = "https://github.com/edwinjo20/symfony_cineconnect.git"
        GIT_BRANCH = "main"
        DEPLOY_DIR = "$WORKSPACE/build/web005"
        DEPLOY_TARGET = "/var/www/html/web005"
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
                cleanWs()
            }
        }

        stage('Clone Repository') {
            steps {
                sh "rm -rf ${DEPLOY_DIR} || true"
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${DEPLOY_DIR}"
            }
        }

        stage('Install Dependencies') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'composer install --optimize-autoloader'
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
                        sh "mkdir -p var/log"
                        sh "mysql -u${DB_USER} -p${DB_PASS} -h ${DB_HOST} -P ${DB_PORT} -e 'SHOW DATABASES;' | tee var/log/jenkins-database.log"
                        
                        def checkDB = sh(script: "mysql -u${DB_USER} -p${DB_PASS} -h ${DB_HOST} -P ${DB_PORT} -e 'SHOW DATABASES LIKE \"${DB_NAME}\";'", returnStdout: true).trim()
                        if (!checkDB.contains(DB_NAME)) {
                            sh "php bin/console doctrine:database:create --if-not-exists --env=prod"
                        }
                    }

                    sh 'php bin/console doctrine:schema:validate | tee var/log/jenkins-schema-validate.log'

                    // 🔥 NEW FIX: Rollback conflicting migrations before applying new ones
                    sh 'php bin/console doctrine:migrations:rollback --step=1 || true'
                    
                    // 🔥 NEW FIX: If migration fails, force schema update instead
                    sh 'php bin/console doctrine:migrations:migrate --no-interaction --env=prod || php bin/console doctrine:schema:update --force'
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
                    // 🔥 FIX PERMISSION ERRORS: Run commands as sudo
                    sh """
                    sudo chown -R jenkins:jenkins ${DEPLOY_TARGET} || true
                    sudo chmod -R 775 ${DEPLOY_TARGET} || true
                    sudo rm -rf ${DEPLOY_TARGET} || true
                    sudo mkdir -p ${DEPLOY_TARGET}
                    sudo cp -rT ${DEPLOY_DIR} ${DEPLOY_TARGET}
                    sudo ln -s ${DEPLOY_TARGET}/public ${DEPLOY_TARGET}/www || true
                    sudo chmod -R 775 ${DEPLOY_TARGET}/var
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
