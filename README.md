OmnomIRC
========
Installation
------------
Just poke me. Or try to figure it out yourself :P
By default you'll be in the admin pannel until you hit 'install'. Make sure the script can write to the curid file and to the config file.


If you can't follow write me an email, the installation prozess is still pretty complicated, I can set it up for you, I am working on something to make it easier :) (mail [at] sorunome [dot] de)

Regex
-----
Because why not?

On IRC:
Matches a normal line:  
/^(?:\x03[0-9]{1,2}\([OC]\)\x0F|\(#\))<([^>]+)> (.*)/  
The first result is the nick, the second one the message.