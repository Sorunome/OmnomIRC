#!/bin/bash
echo "Generating SMF mod ..."
rm omnomirc_smf.tar.gz
sed 's/<file name=\"\$sourcedir\/Subs-Post.php\">/<file name=\"\$boarddir\/mobiquo\/include\/Subs-Post.php\" error=\"skip\">/' install.xml > install_tapatalk.xml
mkdir checkLogin
cp ../../html/checkLogin/index.php checkLogin
sed 's/generic/smf/' ../../html/checkLogin/config.json.php > checkLogin/config.json.php
cp ../../html/checkLogin/hook-smf.php checkLogin
tar -zcvf omnomirc_smf.tar.gz *.xml *.php checkLogin/* > /dev/null
rm install_tapatalk.xml
rm -r checkLogin
