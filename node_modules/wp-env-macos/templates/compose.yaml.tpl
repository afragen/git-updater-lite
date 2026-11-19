# Template rendered by `mac-env init` into a project's compose.yaml.
# Placeholders @@PLUGIN_SLUG@@, @@PHP_VERSION@@, @@DEV_THEME@@,
# @@FIXTURE_PLUGIN_MOUNTS@@, @@FIXTURE_THEME_MOUNTS@@ are substituted at init time.
#
# Bring up with:   mac-env up        Tear down:   mac-env down -v
# Run tests with:  mac-env test
#
# Values are interpolated from .env. WordPress core + PHPUnit libs come from
# the git-seeded cache (WP_ENV_CACHE_DIR, populated by `mac-env install-wp-tests`).
# The container PHP version comes from "phpVersion" in .wp-env.json (default 8.2).
#
# Database reachability uses container-name DNS: run
# `sudo container system dns create opossum` ONCE (mac-env does this
# automatically on first `up` if missing). After that, the app containers
# reach the database by its bare service name (mysql / tests-mysql) on the
# internal port 3306 — no host LAN IP and no published DB ports needed.

x-php-version: &php-version '@@PHP_VERSION@@'

services:
  mysql:
    image: 'mariadb:lts'
    environment:
      MYSQL_ROOT_HOST: '%'
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: wordpress
    volumes:
      - 'mysql:/var/lib/mysql'
    healthcheck:
      test: ['CMD', 'healthcheck.sh', '--connect', '--innodb_initialized']
      interval: 5s
      timeout: 5s
      retries: 20
      start_period: 30s

  tests-mysql:
    image: 'mariadb:lts'
    environment:
      MYSQL_ROOT_HOST: '%'
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: tests-wordpress
    volumes:
      - 'mysql-test:/var/lib/mysql'
    healthcheck:
      test: ['CMD', 'healthcheck.sh', '--connect', '--innodb_initialized']
      interval: 5s
      timeout: 5s
      retries: 20
      start_period: 30s

  wordpress:
    depends_on:
      - mysql
    build:
      context: .
      dockerfile: docker/WordPress.Dockerfile
      args:
        HOST_USERNAME: ${HOST_USERNAME}
        HOST_UID: '${HOST_UID}'
        HOST_GID: '${HOST_GID}'
        XDEBUG_HOST: '${XDEBUG_HOST}'
        PHP_VERSION: *php-version
    ports:
      - '${WP_ENV_PORT:-8888}:80'
    environment:
      APACHE_RUN_USER: '#${HOST_UID}'
      APACHE_RUN_GROUP: '#${HOST_GID}'
      WORDPRESS_DB_USER: root
      WORDPRESS_DB_PASSWORD: password
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_HOST: 'mysql'
      WP_TESTS_DIR: /wordpress-phpunit
    volumes:
      - '${WP_ENV_CACHE_DIR}/WordPress:/var/www/html'
      - '${WP_ENV_CACHE_DIR}/WordPress-PHPUnit/tests/phpunit:/wordpress-phpunit'
      - ${PWD}/.opossum-home/user:/home/${HOST_USERNAME}
      - '${PWD}:/var/www/html/wp-content/plugins/@@PLUGIN_SLUG@@'
@@FIXTURE_PLUGIN_MOUNTS@@
@@FIXTURE_THEME_MOUNTS@@

  tests-wordpress:
    depends_on:
      - tests-mysql
    build:
      context: .
      dockerfile: docker/Tests-WordPress.Dockerfile
      args:
        HOST_USERNAME: ${HOST_USERNAME}
        HOST_UID: '${HOST_UID}'
        HOST_GID: '${HOST_GID}'
        XDEBUG_HOST: '${XDEBUG_HOST}'
        PHP_VERSION: *php-version
    ports:
      - '${WP_ENV_TESTS_PORT:-8889}:80'
    environment:
      APACHE_RUN_USER: '#${HOST_UID}'
      APACHE_RUN_GROUP: '#${HOST_GID}'
      WORDPRESS_DB_USER: root
      WORDPRESS_DB_PASSWORD: password
      WORDPRESS_DB_NAME: tests-wordpress
      WORDPRESS_DB_HOST: 'tests-mysql'
      WP_TESTS_DIR: /wordpress-phpunit
    volumes:
      - '${WP_ENV_CACHE_DIR}/tests-WordPress:/var/www/html'
      - '${WP_ENV_CACHE_DIR}/tests-WordPress-PHPUnit/tests/phpunit:/wordpress-phpunit'
      - ${PWD}/.opossum-home/tests-user:/home/${HOST_USERNAME}
      - '${PWD}:/var/www/html/wp-content/plugins/@@PLUGIN_SLUG@@'
@@FIXTURE_PLUGIN_MOUNTS@@
@@FIXTURE_THEME_MOUNTS@@

  cli:
    depends_on:
      - wordpress
    build:
      context: .
      dockerfile: docker/CLI.Dockerfile
      args:
        HOST_USERNAME: ${HOST_USERNAME}
        HOST_UID: '${HOST_UID}'
        HOST_GID: '${HOST_GID}'
        XDEBUG_HOST: '${XDEBUG_HOST}'
        PHP_VERSION: *php-version
    volumes:
      - '${WP_ENV_CACHE_DIR}/WordPress:/var/www/html'
      - '${WP_ENV_CACHE_DIR}/WordPress-PHPUnit/tests/phpunit:/wordpress-phpunit'
      - ${PWD}/.opossum-home/user:/home/${HOST_USERNAME}
      - '${PWD}:/var/www/html/wp-content/plugins/@@PLUGIN_SLUG@@'
@@FIXTURE_PLUGIN_MOUNTS@@
@@FIXTURE_THEME_MOUNTS@@
    user: '${HOST_UID}:${HOST_GID}'
    environment:
      WORDPRESS_DB_USER: root
      WORDPRESS_DB_PASSWORD: password
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_HOST: 'mysql'
      WP_TESTS_DIR: /wordpress-phpunit

  tests-cli:
    depends_on:
      - tests-wordpress
    build:
      context: .
      dockerfile: docker/Tests-CLI.Dockerfile
      args:
        HOST_USERNAME: ${HOST_USERNAME}
        HOST_UID: '${HOST_UID}'
        HOST_GID: '${HOST_GID}'
        XDEBUG_HOST: '${XDEBUG_HOST}'
        PHP_VERSION: *php-version
    volumes:
      - '${WP_ENV_CACHE_DIR}/tests-WordPress:/var/www/html'
      - '${WP_ENV_CACHE_DIR}/tests-WordPress-PHPUnit/tests/phpunit:/wordpress-phpunit'
      - ${PWD}/.opossum-home/tests-user:/home/${HOST_USERNAME}
      - '${PWD}:/var/www/html/wp-content/plugins/@@PLUGIN_SLUG@@'
@@FIXTURE_PLUGIN_MOUNTS@@
@@FIXTURE_THEME_MOUNTS@@
    user: '${HOST_UID}:${HOST_GID}'
    environment:
      WORDPRESS_DB_USER: root
      WORDPRESS_DB_PASSWORD: password
      WORDPRESS_DB_NAME: tests-wordpress
      WORDPRESS_DB_HOST: 'tests-mysql'
      WP_TESTS_DIR: /wordpress-phpunit

volumes:
  mysql: {}
  mysql-test: {}
