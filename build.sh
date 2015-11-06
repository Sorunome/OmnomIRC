#!/bin/bash
UGLIFYOPTIONS="-m --comments -v"

echo "minifying omnomirc.js ..."
sed 's/admin\.js/admin\.min\.js/' src/omnomirc.js > src/omnomirc.js.tmp
uglifyjs $UGLIFYOPTIONS src/omnomirc.js.tmp -o omnomirc_www/omnomirc.min.js
rm src/omnomirc.js.tmp
echo "minifying admin.js ..."
uglifyjs $UGLIFYOPTIONS src/admin.js -o omnomirc_www/admin.min.js

cd forum_mods
./build.sh
cd ..

find . \( -name '*.php' -o -name '*.xml' -o -name '*.css' -o -name '*.html' -o -name '*.py' -o -name '*.sh' -o -name '*.js' -o -name '*.sql' \) \! \( -name '*.min.*' -o -name '\.*' \) -exec wc {} \+ | awk {'print $4" Lines:"$1" Bytes:"$3'} | grep total
