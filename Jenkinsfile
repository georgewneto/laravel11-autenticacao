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
                        docker run -d --name ${APP_NAME} --restart unless-stopped -p 8008:443 --env-file /home/georgewneto/Projetos/autenticacao/.env ${IMAGE_NAME}
                    """
                }
            }
        }

        stage('Post-Deploy Tasks') {
            steps {
                // Cria a pasta jwt com permissões corretas
                sh "docker exec ${APP_NAME} mkdir -p /app/storage/jwt"

                // Copia as chaves JWT para o container
                sh "docker cp /home/georgewneto/Projetos/autenticacao/storage/jwt/jwt-private.key ${APP_NAME}:/app/storage/jwt/"
                sh "docker cp /home/georgewneto/Projetos/autenticacao/storage/jwt/jwt-public.key ${APP_NAME}:/app/storage/jwt/"

                // Define permissões corretas nos arquivos JWT
                sh "docker exec ${APP_NAME} chmod 644 /app/storage/jwt/jwt-private.key"
                sh "docker exec ${APP_NAME} chmod 644 /app/storage/jwt/jwt-public.key"
                sh "docker exec ${APP_NAME} chown -R www-data:www-data /app/storage/jwt"

                // Instala as dependências do Laravel
                sh "docker exec ${APP_NAME} composer install --no-interaction"

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
