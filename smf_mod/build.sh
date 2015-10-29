#!/bin/bash
rm omnomirc_smf.tar.gz
sed 's/<file name=\"\$sourcedir\/Subs-Post.php\">/<file name=\"\$boarddir\/mobiquo\/include\/Subs-Post.php\" error=\"skip\">/' install.xml > install_tapatalk.xml
mkdir checkLogin
cp ../checkLogin/index.php checkLogin
cp ../checkLogin/config.json.php checkLogin
cp ../checkLogin/hook-smf.php checkLogin
tar -zcvf omnomirc_smf.tar.gz *.xml *.php checkLogin/*
rm install_tapatalk.xml
rm -r checkLogin
