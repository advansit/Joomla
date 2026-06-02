FROM joomla:6.1-php8.3-apache

RUN apt-get update -qq && apt-get install -y --no-install-recommends \
    unzip curl default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Plugin ZIP copied in by CI
COPY tests-j2c6/extension.zip /tmp/extension.zip

# J2Commerce 6 ZIP: must be provided as a build secret or pre-copied file.
# The CI workflow writes it to tests-j2c6/j2commerce6.zip before building.
# If the file is absent the build fails with a clear message.
COPY tests-j2c6/j2commerce6.zip /tmp/j2commerce6.zip

COPY tests-j2c6/scripts/docker-entrypoint.sh /docker-entrypoint-custom.sh
RUN chmod +x /docker-entrypoint-custom.sh

ENTRYPOINT ["/docker-entrypoint-custom.sh"]
CMD ["apache2-foreground"]
