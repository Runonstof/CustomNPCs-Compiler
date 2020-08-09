@echo off
:: Compiler installation for Windows
:: Execute using: install
set useGit=
set prefix=[33m[CNPC-Compiler][0m
echo %prefix% Starting installation
php composer.phar install
php composer.phar dumpautoload
echo %prefix% Composer done.
:git
echo [95mIs this a Github Repository and[0m
set /p useGit=[95mdo you want to stay updated with compiler? (y/n):[0m 
if %useGit%==y goto gity
if %useGit%==n goto gitn
goto git
:gity
echo %prefix% Adding compiler as new Github upstream under name 'compiler'
git remote add compiler https://github.com/Runonstof/CustomNPCs-Compiler.git/master
echo %prefix% Disabling pushing to upstream 'compiler'
git remote set-url --push compiler DISABLE
echo %prefix% Done! You can update the compiler anytime by doing [93mgit fetch compiler[0m or just [93mupdate[0m
goto gitafter
:gitn
echo %prefix% Skipped git upstream initialization
:gitafter
npm install
echo %prefix% Custom NPCs Compiler successfully installed.