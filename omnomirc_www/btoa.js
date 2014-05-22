/*
	This file is part of OmnomIRC. It is not created by OmnomIRC authors. It is believed that this is in agreement with
	all applicable licenses and restrictions.
*/
/*
 * Text replace stuff
 *
 * found in omnimaga themes js
 */

function replaceText(text, textarea) {
	if (typeof(textarea.caretPos) != "undefined" && textarea.createTextRange) {
		var caretPos = textarea.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
		caretPos.select();
	} else if (typeof(textarea.selectionStart) != "undefined") {
		var begin = textarea.value.substr(0, textarea.selectionStart);
		var end = textarea.value.substr(textarea.selectionEnd);
		var scrollPos = textarea.scrollTop;
		textarea.value = begin + text + end;
		if (textarea.setSelectionRange) {
			textarea.focus();
			textarea.setSelectionRange(begin.length + text.length, begin.length + text.length);
		}
		textarea.scrollTop = scrollPos;
	} else {
		textarea.value += text;
		textarea.focus(textarea.value.length - 1);
	}
}

function surroundText(text1, text2, textarea) {
	if (typeof(textarea.caretPos) != "undefined" && textarea.createTextRange) {
		var caretPos = textarea.caretPos,
			temp_length = caretPos.text.length;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text1 + caretPos.text + text2 + ' ' : text1 + caretPos.text + text2;
		if (temp_length === 0) {
			caretPos.moveStart("character", -text2.length);
			caretPos.moveEnd("character", -text2.length);
			caretPos.select();
		} else
			textarea.focus(caretPos);
	} else if (typeof(textarea.selectionStart) != "undefined") {
		var begin = textarea.value.substr(0, textarea.selectionStart);
		var selection = textarea.value.substr(textarea.selectionStart, textarea.selectionEnd - textarea.selectionStart);
		var end = textarea.value.substr(textarea.selectionEnd);
		var newCursorPos = textarea.selectionStart;
		var scrollPos = textarea.scrollTop;
		textarea.value = begin + text1 + selection + text2 + end;
		if (textarea.setSelectionRange) {
			if (selection.length === 0)
				textarea.setSelectionRange(newCursorPos + text1.length, newCursorPos + text1.length);
			else
				textarea.setSelectionRange(newCursorPos, newCursorPos + text1.length + selection.length + text2.length);
			textarea.focus();
		}
		textarea.scrollTop = scrollPos;
	} else {
		textarea.value += text1 + text2;
		textarea.focus(textarea.value.length - 1);
	}
}
/* Cookie functions
 * Copyright (c) by w3schools.com
 * yeah, i am a loser that i look there >.<
 *
 *
 */

function setCookie(c_name, value, exdays) {
	var exdate = new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value = escape(value) + ((exdays === null) ? "" : "; expires=" + exdate.toUTCString());
	document.cookie = c_name + "=" + c_value;
}

function getCookie(c_name) {
	var i, x, y, ARRcookies = document.cookie.split(";");
	for (i = 0; i < ARRcookies.length; i++) {
		x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
		y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
		x = x.replace(/^\s+|\s+$/g, "");
		if (x == c_name) {
			return unescape(y);
		}
	}
}

/* UTF8 encoding/decoding functions
 * Copyright (c) 2006 by Ali Farhadi.
 * released under the terms of the Gnu Public License.
 * see the GPL for details.
 *
 * Email: ali[at]farhadi[dot]ir
 * Website: http://farhadi.ir/
 */


//an alias of String.fromCharCode

function chr(code) {
	return String.fromCharCode(code);
}

//returns utf8 encoded charachter of a unicode value.
//code must be a number indicating the Unicode value.
//returned value is a string between 1 and 4 charachters.

function code2utf(code) {
	if (code < 128) return chr(code);
	if (code < 2048) return chr(192 + (code >> 6)) + chr(128 + (code & 63));
	if (code < 65536) return chr(224 + (code >> 12)) + chr(128 + ((code >> 6) & 63)) + chr(128 + (code & 63));
	if (code < 2097152) return chr(240 + (code >> 18)) + chr(128 + ((code >> 12) & 63)) + chr(128 + ((code >> 6) & 63)) + chr(128 + (code & 63));
}

//it is a private function for internal use in utf8Encode function 

function _utf8Encode(str) {
	var utf8str = [];
	for (var i = 0; i < str.length; i++) {
		utf8str[i] = code2utf(str.charCodeAt(i));
	}
	return utf8str.join('');
}

//Encodes a unicode string to UTF8 format.

function utf8Encode(str) {
	var utf8str = [];
	var pos, j = 0;
	var tmpStr = '';

	while ((pos = str.search(/[^\x00-\x7F]/)) != -1) {
		tmpStr = str.match(/([^\x00-\x7F]+[\x00-\x7F]{0,10})+/)[0];
		utf8str[j++] = str.substr(0, pos);
		utf8str[j++] = _utf8Encode(tmpStr);
		str = str.substr(pos + tmpStr.length);
	}

	utf8str[j++] = str;
	return utf8str.join('');
}

//it is a private function for internal use in utf8Decode function 

function _utf8Decode(utf8str) {
	var str = [];
	var code, code2, code3, code4, j = 0;
	for (var i = 0; i < utf8str.length;) {
		code = utf8str.charCodeAt(i++);
		if (code > 127) code2 = utf8str.charCodeAt(i++);
		if (code > 223) code3 = utf8str.charCodeAt(i++);
		if (code > 239) code4 = utf8str.charCodeAt(i++);

		if (code < 128) str[j++] = chr(code);
		else if (code < 224) str[j++] = chr(((code - 192) << 6) + (code2 - 128));
		else if (code < 240) str[j++] = chr(((code - 224) << 12) + ((code2 - 128) << 6) + (code3 - 128));
		else str[j++] = chr(((code - 240) << 18) + ((code2 - 128) << 12) + ((code3 - 128) << 6) + (code4 - 128));
	}
	return str.join('');
}

//Decodes a UTF8 formated string

function utf8Decode(utf8str) {
	var str = [];
	var pos = 0;
	var tmpStr = '';
	var j = 0;
	while ((pos = utf8str.search(/[^\x00-\x7F]/)) != -1) {
		tmpStr = utf8str.match(/([^\x00-\x7F]+[\x00-\x7F]{0,10})+/)[0];
		str[j++] = utf8str.substr(0, pos) + _utf8Decode(tmpStr);
		utf8str = utf8str.substr(pos + tmpStr.length);
	}

	str[j++] = utf8str;
	return str.join('');
}

/*
 * Copyright (c) 2010 Nick Galbreath
 * http://code.google.com/p/stringencoders/source/browse/#svn/trunk/javascript
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */


/* base64 encode/decode compatible with window.btoa/atob
 *
 * window.atob/btoa is a Firefox extension to convert binary data (the "b")
 * to base64 (ascii, the "a").
 *
 * It is also found in Safari and Chrome.  It is not available in IE.
 *
 * if (!window.btoa) window.btoa = base64.encode
 * if (!window.atob) window.atob = base64.decode
 *
 * The original spec's for atob/btoa are a bit lacking
 * https://developer.mozilla.org/en/DOM/window.atob
 * https://developer.mozilla.org/en/DOM/window.btoa
 *
 * window.btoa and base64.encode takes a string where charCodeAt is [0,255]
 * If any character is not [0,255], then an DOMException(5) is thrown.
 *
 * window.atob and base64.decode take a base64-encoded string
 * If the input length is not a multiple of 4, or contains invalid characters
 *   then an DOMException(5) is thrown.
 */
var base64 = {};
base64.PADCHAR = ',';
base64.ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

base64.makeDOMException = function() {
	// sadly in FF,Safari,Chrome you can't make a DOMException
	var e, tmp;


	try {
		return new DOMException(DOMException.INVALID_CHARACTER_ERR);
	} catch (tmp) {
		// not available, just passback a duck-typed equiv
		// https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Global_Objects/Error
		// https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Global_Objects/Error/prototype
		var ex = new Error("DOM Exception 5");


		// ex.number and ex.description is IE-specific.
		ex.code = ex.number = 5;
		ex.name = ex.description = "INVALID_CHARACTER_ERR";


		// Safari/Chrome output format
		ex.toString = function() {
			return 'Error: ' + ex.name + ': ' + ex.message;
		};
		return ex;
	}
};


base64.getbyte64 = function(s, i) {
	// This is oddly fast, except on Chrome/V8.
	//  Minimal or no improvement in performance by using a
	//   object with properties mapping chars to value (eg. 'A': 0)
	var idx = base64.ALPHA.indexOf(s.charAt(i));
	if (idx === -1) {
		//throw base64.makeDOMException();
	}
	return idx;
};


base64.decode = function(s) {

	// convert to string
	s = '' + s;
	s = s.replace("+", "-");
	s = s.replace("/", "_");
	s = s.replace("=", ",");
	var getbyte64 = base64.getbyte64,
		pads, i, b10,
		imax = s.length;
	if (imax === 0) {
		return s;
	}


	if (imax % 4 !== 0) {
		//throw base64.makeDOMException();
	}


	pads = 0;
	if (s.charAt(imax - 1) === base64.PADCHAR) {
		pads = 1;
		if (s.charAt(imax - 2) === base64.PADCHAR) {
			pads = 2;
		}
		// either way, we want to ignore this last block
		imax -= 4;
	}


	var x = [];
	for (i = 0; i < imax; i += 4) {
		b10 = (getbyte64(s, i) << 18) | (getbyte64(s, i + 1) << 12) |
			(getbyte64(s, i + 2) << 6) | getbyte64(s, i + 3);
		x.push(String.fromCharCode(b10 >> 16, (b10 >> 8) & 0xff, b10 & 0xff));
	}


	switch (pads) {
		case 1:
			b10 = (getbyte64(s, i) << 18) | (getbyte64(s, i + 1) << 12) | (getbyte64(s, i + 2) << 6);
			x.push(String.fromCharCode(b10 >> 16, (b10 >> 8) & 0xff));
			break;
		case 2:
			b10 = (getbyte64(s, i) << 18) | (getbyte64(s, i + 1) << 12);
			x.push(String.fromCharCode(b10 >> 16));
			break;
	}
	return s = utf8Decode(x.join(''));
};


base64.getbyte = function(s, i) {
	var x = s.charCodeAt(i);
	if (x > 255) {
		//throw base64.makeDOMException();
	}
	return x;
};


base64.encode = function(s) {
	s = unescape(encodeURIComponent(s));
	if (arguments.length !== 1) {
		throw new SyntaxError("Not enough arguments");
	}
	var padchar = base64.PADCHAR;
	var alpha = base64.ALPHA;
	var getbyte = base64.getbyte;


	var i, b10;
	var x = [];


	// convert to string
	s = '' + s;


	var imax = s.length - s.length % 3;


	if (s.length === 0) {
		return s;
	}
	for (i = 0; i < imax; i += 3) {
		b10 = (getbyte(s, i) << 16) | (getbyte(s, i + 1) << 8) | getbyte(s, i + 2);
		x.push(alpha.charAt(b10 >> 18));
		x.push(alpha.charAt((b10 >> 12) & 0x3F));
		x.push(alpha.charAt((b10 >> 6) & 0x3f));
		x.push(alpha.charAt(b10 & 0x3f));
	}
	switch (s.length - imax) {
		case 1:
			b10 = getbyte(s, i) << 16;
			x.push(alpha.charAt(b10 >> 18) + alpha.charAt((b10 >> 12) & 0x3F) +
				padchar + padchar);
			break;
		case 2:
			b10 = (getbyte(s, i) << 16) | (getbyte(s, i + 1) << 8);
			x.push(alpha.charAt(b10 >> 18) + alpha.charAt((b10 >> 12) & 0x3F) +
				alpha.charAt((b10 >> 6) & 0x3f) + padchar);
			break;
	}
	s = x.join('');
	s = s.replace("+", "-");
	s = s.replace("/", "_");
	s = s.replace("=", ",");
	return s;
};
