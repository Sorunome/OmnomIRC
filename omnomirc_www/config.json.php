<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */
header("Location:index.php");
exit;
?>
{"info":{"version":"2.9.2.3","installed":false},"sql":{"server":"localhost","db":"omnomirc","user":"omnomirc","passwd":""},"security":{"sigKey":"","ircPwd":""},"settings":{"hostname":"omnomirc","defaultNetwork":1,"curidFilePath":"/usr/share/nginx/html/oirc/omnomirc_curid","useBot":false,"botPort":59619},"channels":[{"id":0,"alias":"main chan","enabled":true,"networks":[{"id":1,"name":"#omnimaga","hidden":false,"order":1}]}],"networks":[{"enabled":true,"id":0,"type":0,"normal":"<b>NICK</b>","userlist":"!NICK","irc":{"color":-2,"prefix":"(!)"},"name":"Server","config":false},{"enabled":true,"id":1,"type":1,"normal":"<a target=\"_top\" href=\"#NICKENCODE\">NICK</a>","userlist":"<a target=\"_top\" href=\"NICKENCODE\"><img src=\"omni.png\">NICK</a>","irc":{"color":12,"prefix":"(O)"},"name":"OmnomIRC","config":{"checkLogin":"link to checkLogin file","theme":-1,"defaults":"","guests":0}}],"websockets":{"use":false,"host":"localhost","port":61839,"ssl":false}}