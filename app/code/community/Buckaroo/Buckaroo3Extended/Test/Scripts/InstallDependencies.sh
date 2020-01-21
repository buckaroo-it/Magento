#!/bin/bash

#set -e
set -x

COMPOSER_HOME="$HOME/.composer/vendor/bin/";
COMPOSER_REQUIRE="";

which n98-magerun
if [ $? != "0" ]; then
    COMPOSER_REQUIRE="${COMPOSER_REQUIRE} n98/magerun"
fi

which modman
if [ $? != "0" ]; then
    COMPOSER_REQUIRE="${COMPOSER_REQUIRE} colinmollenhour/modman"
fi

which coveralls
if [ $? != "0" ] && [ "${CODE_COVERAGE}" = "true" ]; then
    COMPOSER_REQUIRE="${COMPOSER_REQUIRE} satooshi/php-coveralls ^1.0"
fi

if [ ! -f "${COMPOSER_HOME}phpunit" ]; then
    PHPUNIT_VERSION="4.8.35"

    if [ ${TRAVIS_PHP_VERSION} == "5.6" ]  || [ ${TRAVIS_PHP_VERSION} == "7.0" ] || [ ${TRAVIS_PHP_VERSION} == "7.1" ]; then
        PHPUNIT_VERSION="5.7.15"
    fi

    COMPOSER_REQUIRE="${COMPOSER_REQUIRE} phpunit/phpunit ${PHPUNIT_VERSION}"
fi

if [ ! -z "${COMPOSER_REQUIRE}" ]; then
    composer global require ${COMPOSER_REQUIRE}
else
    echo "All dependencies installed"
fi;
