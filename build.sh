#!/bin/bash
UGLIFYOPTIONS="-m --comments -v"

echo "minifying omnomirc.js ..."
sed 's/admin\.js/admin\.min\.js/' src/omnomirc.js > src/omnomirc.js.tmp
uglifyjs $UGLIFYOPTIONS src/omnomirc.js.tmp -o omnomirc_www/omnomirc.min.js
rm src/omnomirc.js.tmp
echo "minifying admin.js ..."
uglifyjs $UGLIFYOPTIONS src/admin.js -o omnomirc_www/admin.min.js

cd smf_mod
./build.sh
cd ..

shopt -s extglob
wc *.sh *.py omnomirc_www/*!([.min.js,.min.map,smileys]) smf_mod/*[.xml,.php,.css,.html] src/* checkLogin/* irc/* | awk {'print $4" Lines:"$1" Bytes:"$3'}|grep total
shopt -u extglob