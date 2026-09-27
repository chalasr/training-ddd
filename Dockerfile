FROM php:8.4-cli

# git + unzip : requis par composer pour télécharger les paquets
RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Fuseau horaire métier : les créneaux et horaires d'ouverture de Popote sont en heure de Paris
RUN echo 'date.timezone = Europe/Paris' > /usr/local/etc/php/conf.d/popote.ini \
    && echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/popote.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
