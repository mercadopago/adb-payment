#!/bin/bash
echo "\n"
echo 🐘🔍 '\033[01;35m RUNNING PHPSTAN \033[0m'
echo "\n"

docker exec magento-php php -d memory_limit=1G magento2/vendor/bin/phpstan analyse --error-format=table --level 0 magento2/app/code/MercadoPago/AdbPayment/

echo ✅ "\033[01;32m PHPSTAN EXECUTED SUCCESSFULLY \n \033[0m"
