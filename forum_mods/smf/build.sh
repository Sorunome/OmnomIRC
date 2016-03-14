#!/bin/bash
echo "Generating SMF mod ..."
sed 's/<file name=\"\$sourcedir\/Subs-Post.php\">/<file name=\"\$boarddir\/mobiquo\/include\/Subs-Post.php\">/' forum/install.xml > tapatalk/install_tapatalk.xml
mkdir forum/checkLogin
cp ../../html/checkLogin/index.php forum/checkLogin
sed 's/generic/smf/' ../../html/checkLogin/config.json.php > forum/checkLogin/config.json.php
cp ../../html/checkLogin/hook-smf.php forum/checkLogin
cd forum
tar -zcvf omnomirc_smf.tar.gz * > /dev/null
cd ..
mv forum/omnomirc_smf.tar.gz .
cd tapatalk
tar -zcvf omnomirc_smf_tapatalk.tar.gz * > /dev/null
cd ..
mv tapatalk/omnomirc_smf_tapatalk.tar.gz .
rm tapatalk/install_tapatalk.xml
rm -r forum/checkLogin
