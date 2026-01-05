FROM joomla:5-php8.2-apache

# Install additional tools
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Copy installation script
COPY scripts/install-via-http.php /usr/local/bin/install-via-http.php
RUN chmod +x /usr/local/bin/install-via-http.php

# Copy custom entrypoint that wraps original
COPY scripts/docker-entrypoint.sh /usr/local/bin/custom-entrypoint.sh
RUN chmod +x /usr/local/bin/custom-entrypoint.sh

# Extension package will be mounted at /tmp/extension.zip via volume

ENTRYPOINT ["/usr/local/bin/custom-entrypoint.sh"]
CMD ["apache2-foreground"]
