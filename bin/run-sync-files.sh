#!bin/bash
echo "\n"
echo 🐘🔍 '\033[01;33m RUNNING SYNC FILES TO MAGENTO 2 CONTAINER \033[0m'
echo "\n"

docker cp ./. magento-php:/var/www/html/magento2/app/code/MercadoPago/AdbPayment/

if [ $? -eq 0 ]; then
    echo ✅ "\033[01;32m SYNC EXECUTED SUCCESSFULLY \n \033[0m"
else
    echo 🚫 "\033[01;31m SYNC FAILED \n \033[0m"
fi
