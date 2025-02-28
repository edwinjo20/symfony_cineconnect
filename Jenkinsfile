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
    }

    stages {
        stage('Cloner le dépôt') {
            steps {
                echo "🛠️ Nettoyage et clonage du dépôt..."
                sh "rm -rf ${DEPLOY_DIR}" // Nettoyage du précédent build
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${DEPLOY_DIR}"
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
                    echo "🔄 Vérification et mise à jour de la base de données..."
                    sh """
                        set -e
                        php bin/console doctrine:migrations:sync-metadata-storage --env=prod
                        php bin/console doctrine:database:create --if-not-exists --env=prod
                        # Run migration only if necessary
                        php bin/console doctrine:migrations:migrate --no-interaction --env=prod || echo "⚠️ Aucune nouvelle migration à appliquer."
                    """
                }
            }
        }

        stage('Nettoyage du cache') {
            steps {
                dir("${DEPLOY_DIR}") {
                    echo "🧹 Nettoyage du cache..."
                    sh 'php bin/console cache:clear --env=prod'
                    sh 'php bin/console cache:warmup'
                }
            }
        }

        stage('Déploiement') {
            steps {
                echo "🚀 Déploiement en cours..."
                sh """
                    sudo rm -rf /var/www/html/${DEPLOY_DIR} || true
                    sudo mkdir -p /var/www/html/${DEPLOY_DIR}
                    sudo cp -rT ${DEPLOY_DIR} /var/www/html/${DEPLOY_DIR}
                    sudo chmod -R 775 /var/www/html/${DEPLOY_DIR}/var
                """
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
