# CustomNPCs-Compiler

[CheatSheet](https://gist.github.com/RunonScripts/711b43d72ebd490fccdc92bb08800d4c)

A powerful CustomNPC scripting framework to create advanced scripts very easy and efficiently.
This compiler is custom tailored for Custom NPCs Scripting for 1.12.2    

---

**Feel free to create issues or pull requests.**

---

## Summary
##### Tired of...
 - Copying same pieces of code across multiple scripts?
 - Manually searching and deobfuscating Minecraft's Obfuscated methods?
 - Having to write in old ES5 JavaScript?
 - Having to use numerical gui ids in CustomNPC's Custom Gui feature?
 
##### Well now you can...
 - import files to keep the same code into one file.
 - write obfuscated Minecraft methods in a normal way.
 - write in ES6+ JavaScript, because this compiler has Babel integrated. This will get transpiled down to valid, runnable ES5 JavaScript.
 - use alphabetical names for gui ids.


This compiler is special made for Custom NPCs Scripting with JavaScript.    
It aims to take away alot of the frustrations we have to encounter, like manually deobfuscating functions. 


## Installation & Setup

### Requirements
In order to use the compiler, you need to have the following requirements:
1. Having NodeJS/NPM installed on your machine (To be able to run `npm` commands)
2. Having PHP installed on your machine, because the compiler is written in the PHP programming language.
3. Be able to run PHP from commmand like (`php` commands). You can check this [tutorial](https://www.youtube.com/watch?v=Ka44kcFSruk) on how to do so.
4. **Zero PHP-Programming knowledge, only the compiler is written with it.**

### Installing
Download this project as ZIP.    
If you fork it you cannot make your repo private, if you do make it private you cannot `git pull` updates from this repository anymore.    
To fix this, download as ZIP, make a new private repo and paste it in.

Run `install`*(Windows)* or `./install.sh`*(Mac/Linux)* inside your terminal of choice and it will ask you to stay updated, answer with `y`.    
Now you can do `git fetch compiler` or `git pull compiler` to update compiler. *(Not recommended, use update-command below)*

### Updating the compiler
Run `update`*(Windows)* or `./update.sh`*(Max/Linux)* inside your terminal of choice.    
This is the recommended way of updating compiler, as the compiler can include new npm/composer packages, these will get installed then too.



