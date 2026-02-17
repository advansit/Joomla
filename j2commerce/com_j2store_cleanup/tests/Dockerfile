FROM joomla:5-php8.2-apache

# Install required packages
RUN apt-get update && apt-get install -y \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Configure MySQL client to skip SSL
RUN echo "[client]\nskip-ssl" > /etc/mysql/conf.d/no-ssl.cnf

# Copy extension package
COPY extension.zip /tmp/extension.zip

# Copy custom entrypoint
COPY scripts/docker-entrypoint.sh /usr/local/bin/custom-entrypoint.sh
RUN chmod +x /usr/local/bin/custom-entrypoint.sh

ENV EXTENSION_NAME="Cleanup Component"

ENTRYPOINT ["/usr/local/bin/custom-entrypoint.sh"]
CMD ["apache2-foreground"]
