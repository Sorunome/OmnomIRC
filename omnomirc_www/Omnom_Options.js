
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
(function(window,undefined){
    var Options = "----------------------------------------|", //40 for future expansion!(and 40 bytes isn't much.) Pipe is a terminator.
        cookieLoad = window.cookieLoad = function() {
            if (document.cookie.indexOf("OmnomIRC") >= 0) {
                Options = document.cookie.replace(/^.*OmnomIRC=(.+?)|.*/, "\$1");
            }else{
                document.cookie = "OmnomIRC=" + Options + ";expires=Sat, 20 Nov 2286 17:46:39 GMT;";
            }
        },
        getOption = window.getOption = function(Option,def) { //Returns what 'Option' is. Option must be a number 1-40. def is what to return if it is not set(equal to -)
            if (Option < 1 || Option > 40){
                return 0;
            }
            var result = Options.charAt(Option - 1);
            if (result == '-'){
                return def;
            }
            return result;
                
        },
        setOption = window.setOption = function(Option, value,noRefresh) { //Sets 'Option' to 'value'. Value must be a single char. Option must be a number 1-40.
            if (Option < 1 || Option > 40){
                return;
            }
            Options = Options.substring(0, Option - 1) + value + Options.substring(Option);
            document.cookie = "OmnomIRC=" + Options + ";expires=Sat, 20 Nov 2286 17:46:39 GMT;";
            if (!noRefresh){
                document.location.reload();
            }
        };
    function getHTMLToggle(State, StateOn, StateOff,StateOnFunc,StateOffFunc){
        var result = "";
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
        if(!State){
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
// Chrome Notification Start   *
//******************************
	function showNotification(message){
		if (window.webkitNotifications === undefined || window.webkitNotifications === null || !window.webkitNotifications){
			return 0;
		}
		if (window.webkitNotifications.checkPermission() !== 0){
			return 0;
		}
		var n;
		n = window.webkitNotifications.createNotification('http://www.omnimaga.org/favicon.ico', 'OmnomIRC Highlight', message);
		n.show();
	}
	function setAllowNotification(){
		if (window.webkitNotifications === undefined || window.webkitNotifications === null || !window.webkitNotifications){
			alert("This feature only works in chrome.");
			return;
		}
		window.webkitNotifications.requestPermission(permissionGranted);
	}
	function permissionGranted(){
		if (window.webkitNotifications.checkPermission() === 0){
			showNotification("Notifications Enabled!");
			setOption(7,'T');
			window.location.refresh(true);
		}
	}
//******************************
// Chrome Notification End     *
//******************************
})(window);