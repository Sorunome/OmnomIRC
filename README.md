OmnomIRC
========

Changelog:
17/1/2013
 - Added /ignore
 - Added /unignore
 - Added /ignorelist

1/2/2013
 - Added /reload
 - /ignore and /unignore now also reload omnomirc to hide/unhide the previous messages
 - Added up/down to scroll through previous messages

2/2/2013
 - Added topic feature.
 - Fixed a url parsing bug that was in the version before, allowing this to be a url: wwwthegame

3/2/2013
 - Added all text-decoration support I know
 - Known bug: not-clean HTML (<b>text<span>text</b></span>)
 - Known bug: Inverting inverted text gives same bg color as text color.
 - Fixed URL bug where already parsed urls got re-parsed

5/2/2013
 - Fixed URL parsing bug when there are color codes after the url without the space, when there is now a color code it sees the url to be finished.

10/2/2013
 - The hitting-up chronic is now stored in cookies, 20 max, cookie storage is 30 days (renews every time you post something) and that per channel.
 - Added smileys, you can disable them, look into the options
 - Made the options page look better

11/2/2013
 - Added more smileys
 - Added smiley selection menu

12/2/2013
 - Added a indicator while loading

23/2/2013
 - Fixed a major bug parsing www.something urls
 - Fixed an indicator bug
 - Added real server messages

24/2/2013
 - Fixed a chrome notification bug with server type
 - Hovering over channel tabs now gives color

25/2/2013
 - Arrows now also change color when hovering over them

26/2/2013
 - Fixed the bug with sometimes lines not alternating

20/3/2013
 - Topic changing on efnet now also auto-changes topic on omninet and nice versa
 - all smileys that are not :smiley: now need a space or the beginning of the message in front of them

21/3/2013
 - added textbased browser support
