FROM joomla:6.1-php8.3-apache

RUN apt-get update -qq && apt-get install -y --no-install-recommends \
    unzip curl default-mysql-client git \
    && rm -rf /var/lib/apt/lists/*

# Plugin ZIP copied in by CI
COPY plg_ajax_joomlaajaxforms/tests-j2c6/extension.zip /tmp/extension.zip

# J2Commerce 6 ZIP built from source by CI and placed at tests-j2c6/j2commerce6.zip
COPY plg_ajax_joomlaajaxforms/tests-j2c6/j2commerce6.zip /tmp/j2commerce6.zip

# Test scripts must be inside the container so docker exec can run them
COPY plg_ajax_joomlaajaxforms/tests-j2c6/scripts/ /tests-j2c6/scripts/

COPY plg_ajax_joomlaajaxforms/tests-j2c6/scripts/docker-entrypoint.sh /docker-entrypoint-custom.sh
RUN chmod +x /docker-entrypoint-custom.sh

ENTRYPOINT ["/docker-entrypoint-custom.sh"]
CMD ["apache2-foreground"]
