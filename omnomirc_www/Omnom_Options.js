
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45

    This file is part of OmnomIRC.

    OmnomIRC is free software: you can redistribute it and/or modifys
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
// Option Engine Start         *
//******************************
	var Options = "----------------------------------------|"; //40 for future expansion!(and 40 bytes isn't much.) Pipe is a terminator.
	function cookieLoad(){
		if(document.cookie.indexOf("OmnomIRC") >= 0){
			Options = document.cookie.replace(/^.*OmnomIRC=(.+?)|.*/, "\$1");
		}else{
			document.cookie = "OmnomIRC=" + Options + ";expires=Sat, 20 Nov 2286 17:46:39 GMT;";
		}
	}
	
	function getOption(Option,def){ //Returns what 'Option' is. Option must be a number 1-40. def is what to return if it is not set(equal to -)
		if (Option < 1 || Option > 40)
			return 0;
		result = Options.charAt(Option - 1);
		result;
		if (result == '-')
			return def;
		return result;
			
	}
	
	function setOption(Option,value,noRefresh){ //Sets 'Option' to 'value'. Value must be a single char. Option must be a number 1-40.
		if (Option < 1 || Option > 40)
			return;
		Options = Options.substring(0, Option - 1) + value + Options.substring(Option);
		document.cookie = "OmnomIRC=" + Options + ";expires=Sat, 20 Nov 2286 17:46:39 GMT;";
		if (!noRefresh)
			document.location.reload();
	}
	
	function getHTMLToggle(State,StateOn,StateOff,StateOnFunc,StateOffFunc){
		result = "";
		if (State){
			result += "<b>";
			result += StateOn;
			result += "</b>";
		}else{
			result += '<a href="#" onclick="'+StateOnFunc+'">';
			result += StateOn;
			result += '</a>';
		}
		result += "</td><td>";
		if (!State){
			result += "<b>";
			result += StateOff;
			result += "</b>";
		}else{
			result += '<a href="#" onclick="'+StateOffFunc+'">';
			result += StateOff;
			result += '</a>';
		}
		return result;
	}
	function clearCookies(){
		document.cookie = "OmnomIRC=a;expires=Thu, 01-Jan-1970 00:00:01 GMT;";
		document.cookie = "OmnomChannels=a;expires=Thu, 01-Jan-1970 00:00:01 GMT;";
		document.location.reload();
	}
	window.onLoad=cookieLoad();
//******************************
// Option Engine End           *
//******************************

//******************************
// Browser Notification Start   *
//******************************

	function showNotification(message){
		if(window.webkitNotifications!=undefined && window.webkitNotifications!=null && window.webkitNotifications && window.webkitNotifications.checkPermission() == 0){
			var n = window.webkitNotifications.createNotification('http://www.omnimaga.org/favicon.ico', 'OmnomIRC Highlight', message);
			n.show();
		}else if(typeof Notification!=='undefined' && Notification && Notification.permission==='granted'){
			var n = new Notification('OmnomIRC Highlight',{
				icon:'http://www.omnimaga.org/favicon.ico',
				body:message
			});
			n.onshow = function(){ 
				setTimeout(n.close,30000); 
			}
		}
	}
	
	function setAllowNotification(){
		if (window.webkitNotifications!=undefined && window.webkitNotifications!=null && window.webkitNotifications){
			window.webkitNotifications.requestPermission(permissionGranted);
		}else if(typeof Notification!=='undefined' && Notification && Notification.permission!=='denied'){
			Notification.requestPermission(function(status){
				if (Notification.permission !== status){
					Notification.permission = status;
				}
				if(status==='granted'){
					showNotification('Notifications Enabled!');
					setOption(7,'T');
					window.location.refresh(true);
				}
			});
		}else{
			alert('Your browser doesn\'t support notifications');
		}
	}
	
	function permissionGranted(){
		if (window.webkitNotifications.checkPermission() == 0){
			showNotification("Notifications Enabled!");
			setOption(7,'T');
			window.location.refresh(true);
		}
	}
//******************************
// Browser Notification End     *
//******************************