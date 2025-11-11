FROM php:8.2-apache

# Activer mod_rewrite et mod_headers pour Apache
RUN a2enmod rewrite headers

# Configurer Apache pour écouter sur le port défini par Render (variable PORT)
RUN echo 'Listen 8080' > /etc/apache2/ports.conf
RUN echo '<VirtualHost *:8080>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html/>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Copier les fichiers de l'application
COPY . /var/www/html/

# Créer un répertoire pour les sessions PHP
RUN mkdir -p /var/lib/php/sessions
RUN chown -R www-data:www-data /var/lib/php/sessions
RUN chmod -R 755 /var/lib/php/sessions

# Configurer PHP pour utiliser ce répertoire de session
RUN echo 'session.save_path = "/var/lib/php/sessions"' >> /usr/local/etc/php/conf.d/sessions.ini

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Exposer le port 8080 (Render mappera automatiquement la variable PORT)
EXPOSE 8080

# Script de démarrage qui configure le port depuis la variable d'environnement PORT
RUN echo '#!/bin/bash\n\
PORT=${PORT:-8080}\n\
sed -i "s/Listen 8080/Listen $PORT/" /etc/apache2/ports.conf\n\
sed -i "s/*:8080/*:$PORT/" /etc/apache2/sites-available/000-default.conf\n\
exec apache2-foreground' > /start.sh
RUN chmod +x /start.sh

# Démarrer Apache avec le script personnalisé
CMD ["/start.sh"]

