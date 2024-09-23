#!/bin/bash

if [ -z "$1" ]
then
   echo "USAGE: install-mg2.sh [version] ";
   exit 0;
fi

sudo pecl install -f xdebug-2.9.8

echo Downloading Magento $1
curl -LO https://github.com/magento/magento2/archive/refs/tags/$1.zip
unzip -qq $1.zip
mv magento2-$1 magento2

cd magento2

echo "Installing SDK..."
composer require mp-plugins/php-sdk:1.12.0
composer update
composer install

bin/magento --version
sudo chmod -Rf 777 var/ pub/ generated/ app/etc/env.php
php -d memory_limit=5G bin/magento
bin/magento setup:upgrade
bin/magento module:enable --all --clear-static-content
php -d memory_limit=5G bin/magento setup:di:compile

rm -rf ../$1.zip
