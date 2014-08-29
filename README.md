OmnomIRC
========
Installation
------------
Just dump all the files in omnomirc_www into a public directory, visit it via your web-browser and follow the instructions there.

Plugins
-------
* [OIRC Plugin for ABXD-Forums](https://github.com/juju2143/ABXD-plugin-OmnomIRC)

Regex
-----
Because why not?

On IRC:
Matches a normal line:  
/^(?:\x03[0-9]{1,2}\([OC]\)\x0F|\(#\))<([^>]+)> (.*)/  
The first result is the nick, the second one the message.