#!/usr/bin/env bash

set -e
set -x

if [ "$CODE_COVERAGE" = "true" ]; then
    mv /tmp/magento/public/.modman/project/app/code/community/TIG/Buckaroo3Extended/Test/build/ ${TRAVIS_BUILD_DIR}

    sed -i -e "s|/tmp/magento/public/.modman/project/|${TRAVIS_BUILD_DIR}/|g" ${TRAVIS_BUILD_DIR}/build/logs/clover-*.xml

    coveralls -vvv --config app/code/community/TIG/Buckaroo3Extended/Test/.coveralls.yml
fi
