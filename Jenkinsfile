pipeline {
    agent any

    environment {
        DEPLOY_DIR = "cineconnect - Edwin" // ✅ Corrected path (no web005)
    }

    stages {
        stage('Check Composer Version') {
            steps {
                sh 'composer --version'
            }
        }

        stage('Cloner le dépôt') {
            steps {
                sh "rm -rf \"${DEPLOY_DIR}\"" // Cleanup
                sh "git clone -b main https://github.com/edwinjo20/symfony_cineconnect.git \"${DEPLOY_DIR}\""

                // ✅ Verification after cloning
                sh "ls -lah \"${DEPLOY_DIR}\""
            }
        }

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

        stage('Installation des dépendances') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh '''
                    composer install --no-interaction --optimize-autoloader --no-scripts
                    '''
                }
            }
        }

        stage('Debugging') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh '''
                    composer diagnose
                    composer show symfony/flex
                    php bin/console about
                    '''
                }
            }
        }

        stage('Configuration de l\'environnement') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh '''
                    echo "APP_ENV=prod" > .env.local
                    echo "APP_DEBUG=0" >> .env.local
                    echo "DATABASE_URL=mysql://root:@mysql_container:3306/cinemacineconnect" >> .env.local
                    '''
                }
            }
        }

        stage('Migration de la base de données') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh '''
                    php bin/console doctrine:database:create --if-not-exists || true
                    php bin/console doctrine:migrations:migrate --no-interaction
                    '''
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
