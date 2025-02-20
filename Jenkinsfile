pipeline {
    agent any

    environment {
        WORKSPACE_DIR = "${env.WORKSPACE}"
        DEPLOY_DIR = "${env.WORKSPACE}/web005"
    }

    stages {
        stage('Check Composer Version') {
            steps {
                sh 'composer --version'
            }
        }

        stage('Cloner le dépôt') {
            steps {
                sh "rm -rf ${DEPLOY_DIR}" // Clean previous build
                sh "git clone -b main https://github.com/edwinjo20/symfony_cineconnect.git ${DEPLOY_DIR}"
            }
        }

        stage('Fix Symfony Flex') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'composer require symfony/flex --no-update --no-plugins --no-scripts'
                }
            }
        }

        stage('Installation des dépendances') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh 'composer install --optimize-autoloader --no-interaction'
                }
            }
        }

        stage('Debugging') {
            steps {
                sh 'composer diagnose'
                sh 'composer show symfony/flex'
            }
        }

        stage('Configuration de l\'environnement') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh '''
                    echo "APP_ENV=prod" > .env.local
                    echo "APP_DEBUG=0" >> .env.local
                    echo "DATABASE_URL=${DATABASE_URL}" >> .env.local
                    '''
                }
            }
        }

        stage('Migration de la base de données') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh '''
                    php bin/console doctrine:database:create --if-not-exists
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
                sh """
                mkdir -p /var/www/html/${DEPLOY_DIR}
                cp -rT ${DEPLOY_DIR} /var/www/html/${DEPLOY_DIR}
                chmod -R 775 /var/www/html/${DEPLOY_DIR}/var
                """
            }
        }
    }

    post {
        success {
            echo 'Deployment successful!'
        }
        failure {
            echo 'Deployment failed.'
        }
    }
}
