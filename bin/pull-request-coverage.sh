#!/bin/bash

echo "Getting pull request head branch..."
export PHPUNIT_HEAD_BRANCH=$(curl -H "Accept: application/vnd.github.v3+json" https://api.github.com/repos/mercadopago/adb-payment-plugin/pulls/${PR_NUMBER} \
| jq ".head.ref" \
| xargs)

echo "Getting pull request files..."
export PHPUNIT_FILES=$(curl -H "Accept: application/vnd.github.v3+json" https://api.github.com/repos/mercadopago/adb-payment-plugin/pulls/${PR_NUMBER}/files \
| jq ".[].filename" \
| grep -E  'php"$' \
| xargs)

php magento2/app/code/MercadoPago/AdbPayment/Tests/pull-request-coverage-checker.php clover.xml 40 $PHPUNIT_HEAD_BRANCH $PHPUNIT_FILES
