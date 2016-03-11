#!/bin/bash
echo "Generating phpBB mod ..."
mkdir -p omnimaga/OmnomIRC/checkLogin
cp -r {acp,adm,config,event,language,migrations,styles,ucp,*.php,*.json} omnimaga/OmnomIRC

cp ../../html/checkLogin/index.php omnimaga/OmnomIRC/checkLogin
sed 's/generic/phpbb3/' ../../html/checkLogin/config.json.php > omnimaga/OmnomIRC/checkLogin/config.json.php
cp ../../html/checkLogin/hook-phpbb3.php omnimaga/OmnomIRC/checkLogin

tar -zcvf omnomirc_phpbb.tar.gz omnimaga/* > /dev/null
zip -r omnomirc_phpbb.zip omnimaga/* > /dev/null

rm -r omnimaga