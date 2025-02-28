pipeline {
    agent any

    environment {
        GIT_REPO = "https://github.com/edwinjo20/symfony_cineconnect.git"
        GIT_BRANCH = "main"
        DEPLOY_DIR = "web005"
    }

    stages {
        stage('Clean Workspace') {
            steps {
                cleanWs() // Clean the Jenkins workspace before starting the build
            }
        }

        stage('Cloner le dépôt') {
            steps {
                sh "rm -rf ${DEPLOY_DIR}" // Nettoyage du précédent build
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${DEPLOY_DIR}"
            }
        }

        stage('Installation des dépendances') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'composer install --optimize-autoloader'
                }
            }
        }

        stage('Configuration de l\'environnement') {
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

        stage('Migration de la base de données') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'php bin/console doctrine:database:create --if-not-exists --env=prod'
                    sh 'php bin/console doctrine:migrations:migrate --no-interaction --env=prod'
                }
            }
        }

        stage('Nettoyage du cache') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'php bin/console cache:clear --env=prod'
                    sh 'php bin/console cache:warmup'
                }
            }
        }

            stage('Déploiement') {
                steps {
                    sh "rm -rf /var/www/html/${DEPLOY_DIR}" // Remove previous deployment
                    sh "mkdir -p /var/www/html/${DEPLOY_DIR}" // Ensure directory exists
                    sh "cp -rT ${DEPLOY_DIR} /var/www/html/${DEPLOY_DIR}" // Copy files
                    sh "ln -s /var/www/html/${DEPLOY_DIR}/public /var/www/html/${DEPLOY_DIR}/www" // Fix Apache path
                    sh "chmod -R 775 /var/www/html/${DEPLOY_DIR}/var"
                }
            }


    }

    post {
        success {
            echo 'Déploiement réussi !'
        }
        failure {
            echo 'Erreur lors du déploiement.'
        }
    }
}
