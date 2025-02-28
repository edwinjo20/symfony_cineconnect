pipeline {
    agent any

    environment {
        GIT_REPO = "https://github.com/edwinjo20/symfony_cineconnect.git"
        GIT_BRANCH = "main"
        DEPLOY_DIR = "web005"
        DEPLOY_PATH = "/var/www/html/${DEPLOY_DIR}"
        DB_NAME = "web005"
        DB_USER = "root"
        DB_PASS = "routitop"
        DB_HOST = "127.0.0.1"
        DB_PORT = "3306"
    }

    stages {
        stage('Cloner le dépôt') {
            steps {
                script {
                    echo "🛠️ Nettoyage et clonage du dépôt..."
                    sh "rm -rf ${DEPLOY_DIR} || true"
                    sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${DEPLOY_DIR}"
                }
            }
        }

        stage('Installation des dépendances') {
            steps {
                dir("${DEPLOY_DIR}") {
                    echo "📦 Installation des dépendances..."
                    sh 'composer install --no-dev --optimize-autoloader'
                }
            }
        }

        stage('Configuration de l\'environnement') {
            steps {
                script {
                    echo "⚙️ Configuration des variables d'environnement..."
                    def envLocal = """
                    APP_ENV=prod
                    APP_DEBUG=0
                    DATABASE_URL=mysql://${DB_USER}:${DB_PASS}@${DB_HOST}:${DB_PORT}/${DB_NAME}?serverVersion=8.3.0&charset=utf8mb4
                    """.stripIndent()

                    writeFile file: "${DEPLOY_DIR}/.env.local", text: envLocal
                }
            }
        }

        stage('Migration de la base de données') {
            steps {
                dir("${DEPLOY_DIR}") {
                    echo "🔄 Mise à jour de la base de données..."
                    sh """
                        set -e
                        php bin/console doctrine:migrations:sync-metadata-storage --env=prod
                        php bin/console doctrine:database:create --if-not-exists --env=prod
                        php bin/console doctrine:migrations:migrate --no-interaction --env=prod
                    """
                }
            }
        }

        stage('Nettoyage du cache') {
            steps {
                dir("${DEPLOY_DIR}") {
                    echo "🧹 Nettoyage et optimisation du cache..."
                    sh 'php bin/console cache:clear --env=prod'
                    sh 'php bin/console cache:warmup'
                }
            }
        }

        stage('Déploiement') {
            steps {
                script {
                    echo "🚀 Déploiement en cours..."
                    sh """
                        set -e
                        sudo rm -rf ${DEPLOY_PATH} || true
                        sudo mkdir -p ${DEPLOY_PATH}
                        sudo cp -rT ${DEPLOY_DIR} ${DEPLOY_PATH}
                        sudo chmod -R 775 ${DEPLOY_PATH}/var ${DEPLOY_PATH}/public
                        sudo chown -R www-data:www-data ${DEPLOY_PATH}
                    """
                }
            }
        }
    }

    post {
        success {
            echo '✅ Déploiement réussi !'
        }
        failure {
            echo '❌ Erreur lors du déploiement. Vérifiez les logs Jenkins.'
        }
    }
}
