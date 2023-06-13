#!bin/bash
echo "\n"
echo 🐘🔍 '\033[01;33m RUNNING PHPCS \033[0m'
echo "\n"

docker exec magento-php magento2/vendor/bin/phpcs -q --report=full --standard=Magento2 magento2/app/code/MercadoPago/AdbPayment/

echo ✅ "\033[01;32m PHPCS EXECUTED SUCCESSFULLY \n \033[0m"
