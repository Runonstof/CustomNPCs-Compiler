#!/bin/bash
# Compiler installation for Mac and Linux
# execute using: ./install.sh
PREFIX=\033[0;33m[Runon-Compiler]\033[0m
echo -e "${PREFIX} Starting installation"
php composer.phar install
php composer.phar dumpautoload
echo -e "${PREFIX} Composer done."
echo -e "Is this a Github Repository"
read -p "And want to stay updated with compiler? (y/n): " useGit
if [ "$setGit" = "y" ]; then
    echo -e "${PREFIX} Adding compiler as new Github upstream under name 'compiler'"
    git remote add compiler https://github.com/Runonstof/CustomNPCs-Compiler.git
    echo -e "${PREFIX} Disabling pushing to upstream 'compiler'"
    git remote set-url --push compiler DISABLE
    echo -e "${PREFIX} Done! You can update the compiler anytime by doing \033[93mgit fetch compiler\033[0m or just \033[93mupdate\033[0m";

fi
npm install
echo -e "${PREFIX} Custom NPCs Compiler successfully installed."