OmnomIRC
========
Supported Browsers
------------------
Full:
* IE 9+
* Firefox 3.6+
* Safari 4+
* Chrome 14+
* Opera 10.6+

Textmode:
* Elinks

Partial (touch events not working (yet)):
* Mobile Safari
* Android defualt browser
* Mobile Firefox
* Mobile Chrome

Not Tested:
* Opera Mini
* Any browser not listed here

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

Protocol
--------
You want to write a bot for OmnomIRC or just want to know how it works, because you enjoy learning things?
You can check out the protocol information [here](http://ourl.ca/20700)