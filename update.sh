#!/bin/bash
# Compiler updater for Mac and Linux
# Execute using: ./update.sh
PREFIX="\033[0;33m[Runon-Compiler]\033[0m"
echo -e "${PREFIX} Starting update"
echo -e "${PREFIX} Updating compiler"
git fetch compiler
echo -e "${PREFIX} Compiler updated"
echo -e "${PREFIX} Updating composer packages"
php composer.phar install
echo -e "${PREFIX} Updating npm packages"
npm install
