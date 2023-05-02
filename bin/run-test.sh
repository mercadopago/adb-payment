#!/bin/bash
echo "\n"
echo ⭐ '\033[01;32m RUNNING UNIT TESTS \033[0m'
echo "\n"

docker exec magento-php [ -d "reports/" ] || docker exec magento-php mkdir reports/
docker exec magento-php magento2/vendor/phpunit/phpunit/phpunit --configuration magento2/app/code/MercadoPago/AdbPayment/phpunit.xml --coverage-clover clover.xml --coverage-text --coverage-html reports/ magento2/app/code/MercadoPago/AdbPayment/Tests
docker exec magento-php chmod 777 -Rf reports/

echo "\n"
echo ✅ "\033[01;32m SUCCESS - You can access the full report by accessing: http://localhost:8080/reports \n \033[0m"
