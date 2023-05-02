#!/bin/bash
echo "\n"
echo 🐘🔍 '\033[01;36m RUNNING PHPMD \033[0m'
echo "\n"

docker exec magento-php phpmd/src/bin/phpmd magento2/app/code/MercadoPago/AdbPayment/ --ignore-violations-on-exit ansi codesize,unusedcode,naming,cleancode

echo "\n"
echo ✅ "\033[01;32m PHPMD EXECUTED SUCCESSFULLY \n \033[0m"
