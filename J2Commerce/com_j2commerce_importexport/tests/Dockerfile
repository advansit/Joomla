FROM joomla:5.4-php8.3-apache

# Install required packages
RUN apt-get update && apt-get install -y \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache to pass environment variables to PHP
RUN echo "PassEnv J2COMMERCE_IMPORTEXPORT_TEST_MODE" >> /etc/apache2/apache2.conf

# Copy component package (must exist before docker build)
COPY extension.zip /tmp/extension.zip

# Copy HTTP-based installation script
COPY scripts/install-extension-http.php /usr/local/bin/install-extension-http.php
RUN chmod +x /usr/local/bin/install-extension-http.php

# Copy custom entrypoint
COPY scripts/docker-entrypoint.sh /usr/local/bin/custom-entrypoint.sh
RUN chmod +x /usr/local/bin/custom-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/custom-entrypoint.sh"]
CMD ["apache2-foreground"]
