@echo off
:: Compiler updater for windows
:: Execute using: update

set prefix=[33m[CNPC-Compiler][0m
echo %prefix% Starting update
echo %prefix% Updating compiler
git pull compiler master
echo %prefix% Compiler updated
echo %prefix% Updating composer packages
php composer.phar install
php composer.phar dump-autoload
echo %prefix% Updating npm packages
npm install
