#!/bin/bash
echo "Generating ABXD mod ..."
mkdir -p OmnomIRC/checkLogin
cp *.php OmnomIRC
cp plugin.settings OmnomIRC

cp ../../checkLogin/index.php OmnomIRC/checkLogin
sed 's/generic/abxd/' ../../checkLogin/config.json.php > OmnomIRC/checkLogin/config.json.php
cp ../../checkLogin/hook-abxd.php OmnomIRC/checkLogin

tar -zcvf omnomirc_abxd.tar.gz OmnomIRC/* > /dev/null
zip -r omnomirc_abxd.zip OmnomIRC/* > /dev/null
rm -r OmnomIRC