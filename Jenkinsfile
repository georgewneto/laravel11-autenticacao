pipeline {
    agent any

    environment {
        APP_NAME = "laravel11-autenticacao"
        IMAGE_NAME = "georgewneto/laravel11-autenticacao:latest"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build Image') {
            steps {
                script {
                    // Constrói a imagem usando o Dockerfile do projeto
                    sh "docker build -t ${IMAGE_NAME} ."
                }
            }
        }

        stage('Deploy (Replace Container)') {
            steps {
                script {
                    // 1. Para e remove o container antigo (se existir)
                    sh "docker stop ${APP_NAME} || true && docker rm ${APP_NAME} || true"
                    
                    // 2. Sobe o novo container
                    // Ajuste as portas e o link com o banco de dados conforme necessário
                    sh """
                        docker run -d \
                        --name ${APP_NAME} \
                        --restart unless-stopped \
                        -p 8008:443 \
                        --env-file .env \
                        ${IMAGE_NAME}
                    """
                }
            }
        }

        stage('Post-Deploy Tasks') {
            steps {
                // Roda as migrações dentro do novo container
                sh "docker exec ${APP_NAME} php artisan migrate --force"
                sh "docker exec ${APP_NAME} php artisan config:cache"
            }
        }
        
        stage('Cleanup') {
            steps {
                // Remove imagens antigas "soltas" para não lotar o disco
                sh "docker image prune -f"
            }
        }
    }
}
