pipeline {
    agent any

    environment {
        GIT_REPO = "https://github.com/hmicn/certif-bookapp.git"
        GIT_BRANCH = "main"
        DEPLOY_DIR = "$WORKSPACE/build/web022"
        DEPLOY_TARGET = "/var/www/html/web022" // Target directory for deployment
    }

    stages {
        stage('Clean Workspace') {
            steps {
                cleanWs() // Clean Jenkins workspace before build
            }
        }

        stage('Clone Repository') {
            steps {
                sh "rm -rf ${DEPLOY_DIR}" // Remove old builds
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${DEPLOY_DIR}" // Clone repo
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
                    APP_DEBUG=1
                    DATABASE_URL=mysql://root:routitop@127.0.0.1:3306/${DEPLOY_DIR}?serverVersion=8.3.0&charset=utf8mb4
                    """.stripIndent()

                    writeFile file: "${DEPLOY_DIR}/.env.local", text: envLocal
                }
            }
        }

        stage('Database Migration') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'php bin/console doctrine:database:create --if-not-exists --env=prod'
                    sh 'php bin/console doctrine:migrations:migrate --no-interaction --env=prod || php bin/console doctrine:schema:update --force --env=prod'
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
                script {
                    // Ensure Jenkins has permission to delete files
                    sh """
                    if [ -d ${DEPLOY_TARGET} ]; then
                        chmod -R 777 ${DEPLOY_TARGET} || true
                        rm -rf ${DEPLOY_TARGET} || true
                    fi
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
            echo '❌ Deployment Failed!'
        }
    }
}
