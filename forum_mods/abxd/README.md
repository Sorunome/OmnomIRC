ABXD-plugin-OmnomIRC
====================
This plugin adds an [OmnomIRC](https://github.com/Sorunome/OmnomIRC2) chatbox at the top of every page on your [AcmlmBoard XD](http://abxd.dirbaio.net/) forum.

Installation
------------
1. Install and configure ABXD and OmnomIRC like you usually would
2. Dump the checkLogin.php file from the OmnomIRC repo at the root of your ABXD installation (be sure to edit the key to be the same as the one in OmnomIRC's config.php)
3. Dump the files of this repo in a folder of the plugins folder
4. Enable the plugin in the admin panel and configure it to point to your OmnomIRC installation
5. Optionally write a irc.css in your theme folder to match your forum theme and tell OmnomIRC to use it

Features
--------
- Users can disable OmnomIRC in their profile
- OmnomIRC is disabled for unregistered and banned users
- /?page=irc where the box is higher
- /?page=irc&name=USER redirecting to the user profile (configure the username links in your OmnomIRC admin panel to point to this URL)

Credits
-------
Written for [Ponyville, Québec](http://ponyville.qc.to).

OmnomIRC originally written for [Omnimaga](http://www.omnimaga.org)

For any other info, check and ask on the thread on [Omnimaga](http://www.omnimaga.org/omnomirc-and-spybot45-development/omnomirc-for-abxd-and-phpbb/).
