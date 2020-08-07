@echo off
php composer.phar install
php composer.phar dumpautoload
npm install
echo Custom NPCs Compiler successfully installed.