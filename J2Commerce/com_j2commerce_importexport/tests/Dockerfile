FROM joomla:5-php8.2-apache

# Install required packages
RUN apt-get update && apt-get install -y \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Copy extension package
COPY extension.zip /tmp/extension.zip

# Copy HTTP-based installation script
COPY scripts/install-extension-http.php /usr/local/bin/install-extension-http.php
RUN chmod +x /usr/local/bin/install-extension-http.php

# Copy custom entrypoint
COPY scripts/docker-entrypoint.sh /usr/local/bin/custom-entrypoint.sh
RUN chmod +x /usr/local/bin/custom-entrypoint.sh

ENV EXTENSION_NAME="Import/Export Component"

ENTRYPOINT ["/usr/local/bin/custom-entrypoint.sh"]
CMD ["apache2-foreground"]
