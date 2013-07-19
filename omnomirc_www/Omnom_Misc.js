/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45

    This file is part of OmnomIRC.

    OmnomIRC is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OmnomIRC is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with OmnomIRC.  If not, see <http://www.gnu.org/licenses/>.
*/ 

//******************************
// Start Indicator functions   *
//******************************
indicatorTimer = false;
function startIndicator() {
	if (!indicatorTimer) {
		indicatorTimer = setInterval(updateIndicator,50);
		indicatorPixels = Array(true,true,true,true,true,false,false,false);
	}
}
function updateIndicator() {
	var indicator = document.getElementById('indicator');
	indicator.innerHTML = "";
	for (var i=0;i<8;i++) {
		if (indicatorPixels[i])
			indicator.innerHTML+="<div style='padding:0;margin:0;width:3px;height:3px;background-color:black;'></div>";
		else
			indicator.innerHTML+="<div style='padding:0;margin:0;width:3px;height:3px;'></div>";
	}
	var temp = indicatorPixels[0];
	for (i=1;i<=7;i++) {
		indicatorPixels[(i-1)] = indicatorPixels[i];
	}
	indicatorPixels[7] = temp;
}
function stopIndicator() {
	clearInterval(indicatorTimer);
	document.getElementById('indicator').innerHTML = '';
	indicatorTimer = false;
}
//******************************
// End Indicator functions     *
//******************************

//******************************
// Start old messages functions*
//******************************
var oldMessages = new Array();
var messageCounter = 1;
var currentMessage;
window.addEventListener('keydown',function(e) {
	if (document.activeElement.id=="message") {
		var messageBoxElement = document.getElementById("message");
		if (messageCounter==oldMessages.length)currentMessage=messageBoxElement.value;
		if (oldMessages.length!=0) {
			if (e.keyCode==38) { //up
				if (messageCounter!=0)messageCounter--;
				messageBoxElement.value = oldMessages[messageCounter];
			} else if (e.keyCode==40) { //down
				if (messageCounter!=oldMessages.length)messageCounter++;
				if (messageCounter==oldMessages.length) {
					messageBoxElement.value = currentMessage;
				} else {
					messageBoxElement.value = oldMessages[messageCounter];
				}
			}
		}
	}
}, false);
function readOldMessagesCookies() {
	oldMessages = Array();
	var temp = getCookie("oldMessages-"+getChannelEn());
	if (temp!=null)
		oldMessages = temp.split("\n");
	messageCounter = oldMessages.length;
}
//******************************
// End old messages functions  *
//******************************