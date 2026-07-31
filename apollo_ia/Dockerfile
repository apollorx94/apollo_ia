# Imagem base: PHP 8.4 com CLI (inclui o servidor embutido do PHP)
FROM php:8.4-cli

# Instala dependências do sistema necessárias para compilar extensões PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Instala o Composer dentro da imagem, copiando do binário oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho dentro do container
WORKDIR /app

# Copia primeiro só os arquivos de dependência (otimização de cache de build)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Agora copia o restante do código da aplicação
COPY . .

# Porta que a aplicação vai escutar dentro do container
EXPOSE 10000

# Comando executado quando o container inicia
CMD php -S 0.0.0.0:10000 -t public
