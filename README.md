OmnomIRC
========
Installation
------------
1. upload all of omnomirc_www to your public html folder where you want it installed.
2. load dbinfo.sql into your mysql db (create db+user before)
3. change config.php so that it fits your config, for $signature_key put a random string
4. copy the correct checkLogin file to your server where the forum is located and change $encriptKeyToUse to the $signature_key you set in config.php
5. in Channels.php change line 20 to the ABSOLUTE path of your file
6. Modify the Bot File OmnomIRC.php: add the absolute file path to the root of your html document
7. Modify the bot File TopicBot.php: lines 23,24,29,107,112 and (if needed) 140,141
8. Make sure that TopicBot will get op in your irc chans for topic sync
9. change the paths in initscript.sh to match your paths + user + group and use absolute paths to the bot files


If you can't follow write me an email, the installation prozess is still pretty complicated, I can set it up for you, I am working on something to make it easier :) (mail [at] sorunome [dot] de)