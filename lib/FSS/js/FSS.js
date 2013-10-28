/*
* Copyright (c) 2010-2013, Loïc BLOT, CNRS <http://www.unix-experience.fr>
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* 1. Redistributions of source code must retain the above copyright notice, this
*    list of conditions and the following disclaimer.
* 2. Redistributions in binary form must reproduce the above copyright notice,
*    this list of conditions and the following disclaimer in the documentation
*    and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
* ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
* ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
* The views and conclusions contained in the software and documentation are those
* of the authors and should not be interpreted as representing official policies,
* either expressed or implied, of the FreeBSD Project.
*/

/*
* global vars
*/

var forceLock = 0;
var tooltipShown = 0;
var lockTooltip = 0;
var notifCloseTicket = 0;
var noClosePopup = 0;
var idleTimer = 0;
var maxIdleTimer = 30;

/*
* Regexp
*/

function ReplaceNotNumeric(obj) {
    var str = obj.value;
    var reg = new RegExp("[^0-9]", "gi");
    var newstr = "";
    var found = false;
    if(str == "0")
        return;

    for(var i=0;i<str.length;i++)
        if(!str[i].match(reg))
        {
            if(!found && str[i] == "0")
                continue;

            if(!found && str[i] != "0")
                found = true;
            newstr += str[i];
        }
    if(newstr.length < 1)
        newstr = "0";
    obj.value = newstr;
}

function isNumeric(str) { expr = new RegExp("^([0-9]*)$", "gi"); if(expr.test(str)) return true; else return false; }
function isDNSName(str) { expr = new RegExp("^[a-z][a-z0-9.-]{1,}[a-z0-9]{2,}$", "gi"); if(expr.test(str))return true;return false;}
function isHostname(str) { expr = new RegExp("^[a-zA-Z]([a-zA-Z0-9_-]*)[a-zA-Z0-9]$", "gi"); if(expr.test(str))return true;return false;}
function isMacAddr(str) { expr = new RegExp("^([0-9A-F]{2}:){5}[0-9A-F]{2}$", "gi"); if(expr.test(str))return true;return false;}
function isIPv6(str) { expr = new RegExp("^([0-9A-F]{4}:){5}[0-9A-F]{4}$", "gi"); if(expr.test(str))return true;return false;}
// Package functions, full ok functions only
function isIP(str){expr=new RegExp("^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$");if(expr.test(str))return true;return false;}

function checkMAC(obj) {
        var str = obj.value;
        if(isMacAddr(str) || str == "")
                obj.style.backgroundColor = "#FFFFFF";
        else
                obj.style.backgroundColor = "#FFB56A";
}

function checkIP(obj) {
	var str = obj.value;
	if(isIP(str) || str == "")
		obj.style.backgroundColor = "#FFFFFF";
	else
		obj.style.backgroundColor = "#FFB56A";
}

function checkMask(obj) {
	var str = obj.value;
	var flag = 0;
	var correct_range = {128:1,192:1,224:1,240:1,248:1,252:1,254:1,255:1,0:1};
	var m = str.split('.');
	
	for (var i = 0; i <= 3; i ++) {
		if (!(m[i] in correct_range)) {
			flag = 1;
			break;
		}
	}

	if ((m[0] == 0) || (m[0] != 255 && m[1] != 0) || (m[1] != 255 && m[2] != 0) || (m[2] != 255 && m[3] != 0)) {
		flag = 2;
	}
	
	if(flag == 0)
		obj.style.backgroundColor = "#FFFFFF";
	else
		obj.style.backgroundColor = "#FFB56A";
	
	return flag;
}

/*
* Screen functions
*/

function lockScreen(callback) { if(!isset(callback)) callback = null; $('#lock').fadeIn('fast',callback); }
function unlockScreen(popup) { if(!isset(popup)) popup = false; if(popup) $('#subpop').dialog("close"); else $('#lock').fadeOut("fast"); hideTooltip(); }
function hideAndRemove(obj) { $(obj).hide('slow',function() { $(obj).remove(); }); }
function hideAndEmpty(obj) { $(obj).hide('slow',function() { $(obj).html(''); }); }

function isset(lvar) {
	if(typeof lvar == 'undefined') return false;
	return true; 
}

function loadWindowHead() {
        $.post('index.php?at=1', function(data) {
		$('#logform').html(data);
	});
}

function loadMainContainer(url,callback) {
	if(!isset(callback)) callback = null;

        $.post('index.php?at=2&at-conn=1'+url, function(data) {
		$('#main').html(data);
		if(callback != null)
			callback();
	});
}

function showNotification(options) {
	$('#subnotification').html(options['snotif']);
	$('#notification').slideDown();
	slideState = 1;
	setTimeout(function() {
		notifCloseTicket++;
		myTicket = notifCloseTicket;
		if(slideState == 1 && notifCloseTicket == myTicket)
			$('#notification').slideUp();
	},(isset(options['stimeout']) && options['stimeout'] > 1000 ? options['stimeout'] : 5000));
}

function callbackLink(cbklink,options) {
	// 0: nothing, 1: down, 2: lockUp
	var slideState = 0;
	if(isset(options)) {
		// Locking screen if needed
	        if(isset(options['lock']) && options['lock'] == true) {
			$('#subpop').css("text-align", "center");
			$('#subpop').html('<span class=\"loader\"></span>'); lockScreen();
			// Remove dialog buttons
			if(isset(options['dialog']) && options['dialog'] == true) {
				$('#subpop').dialog("option","buttons",{});
				$('#subpop').dialog("option","dialogClass","no-close");
				$('#subpop').dialog("option","draggable","false");
				// @TODO: remove cross button
			}
       		}
        	// Starting notification
		if(isset(options['snotif']) && options['snotif'].length > 0) {
			showNotification(options);
         	}
	}
        $.post(cbklink+'&at=3', function(data) {
        	$('#subnotification').html(data); 
	})
	.fail(function() {
        	$('#subnotification').html('Error 500, please check httpd-error.log'); 
	})
	.always(function() {
		if(slideState != 1) $('#notification').slideDown();
		// lock slideState for previous setTimeout
		slideState = 2;
         	setTimeout(function() {
                	$('#notification').slideUp();
                },isset(options) && isset(options['timeout']) && options['timeout'] > 1000 ? options['timeout'] : 5000);
                if(isset(options) && isset(options['lock']) && options['lock'] == true) {
			unlockScreen(true);
		}
	});
	return false;
}

function callbackForm(cbklink,obj,options) {
	// 0: nothing, 1: down, 2: lockUp
	var slideState = 0;
	// Bufferize form for noClosePopup
	var formBuffer = ''; 
	if(isset(options)) {
		formBuffer = $('#subpop').html();
		waitingPopup('');
        	// Starting notification
        	if(isset(options['snotif']) && options['snotif'].length > 0) {
			showNotification(options);
         	}
	}
	// 500ms because if form is too fast, there is a JS popup time mismatch
	setTimeout(function() {
        $.post(cbklink+'&at=3', $(obj).serialize(), function(data) {
        	$('#subnotification').html(data); 
			if(noClosePopup == 0) {
				if(slideState != 1) $('#notification').slideDown();
				// lock slideState for previous setTimeout
				slideState = 2;
				setTimeout(function() {
					$('#notification').slideUp();
				},isset(options) && isset(options['timeout']) && options['timeout'] > 1000 ? options['timeout'] : 5000);
				unlockScreen(true);
			}
			else {
				noClosePopup = 0;
				$('#subpop').parent().fadeOut('100',function() {
					setPopupContent(formBuffer);
				});
			}
		})
		.fail(function() {
				$('#subnotification').html('Error 500, please check httpd-error.log'); 
			setTimeout(function() {
				$('#notification').slideUp();
			},isset(options) && isset(options['timeout']) && options['timeout'] > 1000 ? options['timeout'] : 5000);
			unlockScreen(true);
		})
		.always(function() {
		})
	}, 200);
	return false;
}

function confirmPopup(text,textok,textno,cbklink,options) {
	cbklink = cbklink.replace(/\'/g,'\\\'');
	$('#subpop').css("text-align","left");
	$('#subpop').html(text);
	lockScreen();
	options['dialog'] = true;
	$('#subpop').dialog({
		modal: true,
		width: 'auto',
		height: 'auto',
		position: 'center',
		beforeClose: function(event,ui) { unlockScreen(); }});

	$('#subpop').dialog("option","buttons", [ 
		{ text: textok, click: function() { callbackLink(''+cbklink+'',options); } },
		{ text: textno, click: function() { $(this).dialog("close"); } }
	]);
	$('#subpop').dialog("option","height", "auto");
	return false;
}

function setPopupContent(data) {
	$('#subpop').dialog({
		dialogClass: "",
		modal: true,
		resizable: false,
		width: 'auto',
		height: 'auto',
		position: 'center',
		buttons: {},
		beforeClose: function(event,ui) { 
			if(forceLock == 0) 
				unlockScreen();
				$('#subpop').html('');
		}
	});

	$('#subpop').parent().fadeOut('100',function() {
		$('#subpop').html(data);
		$('#subpop').dialog("option","position", "center");
		$('#subpop').dialog("option","height", "auto");
		$('#subpop').parent().fadeIn('500');
	}); 

}

function formPopup(modid,callid,lnkadd) {
	if(!isset(lnkadd)) lnkadd = '';
	if($('#subpop').html() != '') {
		forceLock = 1;
		$('#subpop').dialog("close");
	}
	$('#subpop').css("text-align","left");
	waitingPopup('',function() {
	$('#subpop').dialog({
		dialogClass: "",
		modal: true,
		resizable: false,
		width: 'auto',
		height: 'auto',
		position: 'center',
		buttons: {},
		beforeClose: function(event,ui) { 
			if(forceLock == 0) 
				unlockScreen();
				$('#subpop').html('');
		}
	});

        $.post('index.php?mod='+modid+'&at=4&el='+callid+"&"+lnkadd, function(data) {
		$('#subpop').parent().fadeOut('100',function() {
			$('#subpop').html(data);
			$('#subpop').dialog("option","position", "center");
			$('#subpop').dialog("option","height", "auto");
			$('#subpop').dialog("option","width", "auto");
			$('#subpop').parent().fadeIn('500');
		}); 
	})
	.fail(function() {
		$('#subpop').parent().fadeOut('100',function() {
			$('#subpop').html("Error 500: please check httpd-error.log");
			$('#subpop').dialog("option","position", "center");
			$('#subpop').dialog("option","height", "auto");
			$('#subpop').dialog("option","width", "auto");
			$('#subpop').parent().fadeIn('500');
		}); 
	})
	});

	forceLock = 0;
	return false;
}

function waitingPopup(text,callback) {
	if(!isset(callback)) callback = null;

	$('#subpop').css("text-align", "center");
	$('#subpop').html('<span class=\"loader\"></span><br />'+text);
	lockScreen(function() {
		$('#subpop').dialog({
			dialogClass: "no-close",
			draggable: false,
			beforeClose: function(event,ui) { unlockScreen(); },
			buttons: {},
			width: "200px",	
			position: 'center'
		});
		if(callback != null)
			callback();
	});
}

function loadInterface(url) {
	waitingPopup('',function() {
		loadMainContainer(url,function() {
			unlockScreen(true);
		});
	});
}

function dontClosePopup() {
	noClosePopup = 1;
}

function showColorPicker(obj,defcolor) {
	$(obj).ColorPicker({
		color: '#'+defcolor,
		onSubmit: function(hsb, hex, rgb, el) {
			$(el).val(hex);
			$(el).ColorPickerHide();
		},
		onBeforeShow: function () {
			$(this).ColorPickerSetColor(this.value);
		}
	})
	.bind('keyup', function(){
		$(this).ColorPickerSetColor(this.value);
	});
}

function showTooltip(text) {
	lockTooltip = 1;
	tooltipShown += 1;
	$('#tooltip').html(text);
	//if(tooltipShown >= 1) {
		$('#tooltip').fadeIn('fast',function() {
			lockTooltip = 0;
		});
	//}
}

function hideTooltip() {
	tooltipShown -= 1;
	setTimeout(function() {
		if(/*tooltipShown == 0 && */lockTooltip == 0) {
			$('#tooltip').fadeOut('fast',function() { 
			});
		}
	},400);
}

function idleIncrement() {
    idleTimer = idleTimer + 1;
    if (idleTimer > maxIdleTimer) {
        disconnectUser();
    }
}

function openLogin () { $('#login').slideDown('slow'); }
// @TODO
function disconnectUser() {}

/*
* Main
*/

$(document).ready(function() {
	// Idle user 
	var idleInterval = setInterval(idleIncrement, 60000);
	$(this).mousemove(function (e) { idleTimer = 0; });
    $(this).keypress(function (e) { idleTimer = 0; });
});
