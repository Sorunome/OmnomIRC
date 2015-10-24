#!/bin/bash
UGLIFYOPTIONS="-m --comments -v"

echo "minifying omnomirc.js ..."
uglifyjs $UGLIFYOPTIONS src/omnomirc.js -o omnomirc_www/omnomirc.min.js
echo "minifying admin.js ..."
uglifyjs $UGLIFYOPTIONS src/admin.js -o omnomirc_www/admin.min.js
