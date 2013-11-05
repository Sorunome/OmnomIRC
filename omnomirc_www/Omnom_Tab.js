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

message = document.getElementById("message");
if(message.addEventListener ){
	message.addEventListener("keydown",keyHandler,false);
}else if(message.attachEvent ){
	message.attachEvent("onkeydown",keyHandler);
}
isInTab = false;
tabWord = "";
tabCount = 0;
startPos = 0;
endPos = 0;
endPosO = 0;
tabAppendStr = ' ';
	function keyHandler(e){
		if (getCurrentWord() == "")
			return true;
		var TABKEY = 9;
		if(e.keyCode == TABKEY){
			if(e.preventDefault){
				e.preventDefault();
			}
			tabWord = getCurrentWord();
			getTabComplete();
			tabCount++;
			isInTab = true;
			setTimeout(1,1); //Who woulda thought that a bogus call makes it not parse it in FF4?
			return false;
		}else{
			tabWord = "";
			tabCount = 0;
			isInTab = false;
		}
	}
	function getCurrentWord(){
		if (isInTab)
			return tabWord;
		startPos = message.selectionStart;
		endPos = message.selectionStart;
		
		startChar = message.value.charAt(startPos);
		while (startChar != ' ' && --startPos > 0)
			startChar = message.value.charAt(startPos);
		if (startChar == ' ') startPos++;
		endChar = message.value.charAt(endPos);
		while (endChar != ' ' && ++endPos <= message.value.length)
			endChar = message.value.charAt(endPos);
		endPosO = endPos;		
		return message.value.substr(startPos,endPos - startPos).trim();
	}
	function getTabComplete(){
		if(!isInTab){
			tabAppendStr = ' ';
			startPos = message.selectionStart;
			startChar = message.value.charAt(startPos);
			while(startChar != ' ' && --startPos > 0)
				startChar = message.value.charAt(startPos);
			if(startChar == ' ')
				startChar+=2;
			if(startPos==0){
				tabAppendStr = ': ';
			}
			endPos = message.selectionStart;
			endChar = message.value.charAt(endPos);
			while (endChar != ' ' && ++endPos <= message.value.length)
				endChar = message.value.charAt(endPos);
			if (endChar == ' ') endChar-=2;
		}
		name = searchUser(getCurrentWord(),tabCount);
		if (name == getCurrentWord()){
			tabCount = 0;
			name = searchUser(getCurrentWord(),tabCount);
		}
			
		message.value = message.value.substr(0,startPos)+name+tabAppendStr+message.value.substr(endPos+1);
		endPos = endPosO+name.length;
	}