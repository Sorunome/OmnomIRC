#!/bin/bash
rm omnomirc_smf.tar.gz
sed 's/<file name=\"\$sourcedir\/Subs-Post.php\">/<file name=\"\$boarddir\/mobiquo\/include\/Subs-Post.php\" error=\"skip\">/' install.xml > install_tapatalk.xml
tar -zcvf omnomirc_smf.tar.gz *.xml *.php
rm install_tapatalk.xml
