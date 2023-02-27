#!/bin/bash

sh bin/run-sync-files.sh
sh bin/run-phpcs.sh
sh bin/run-phpstan.sh
sh bin/run-phpmd.sh
