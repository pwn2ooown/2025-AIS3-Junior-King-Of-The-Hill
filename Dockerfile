FROM php:8.3-apache

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y net-tools build-essential vim sudo less curl htop

COPY www/ /var/www/html/

COPY s3cr3t.txt /opt/s3cr3t.txt

RUN chmod +s /usr/bin/find

RUN chown -R root:root /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

RUN echo "www-data ALL=(root) NOPASSWD: /usr/bin/less /opt/s3cr3t.txt" >> /etc/sudoers

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN printf "#!/bin/sh\nset -e\napachectl -k restart\necho 'Apache/PHP restarted.'\n" > /usr/local/bin/restart-php \
    && chmod 700 /usr/local/bin/restart-php

EXPOSE 80