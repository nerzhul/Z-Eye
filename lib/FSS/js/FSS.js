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
var maxIdleTimer = -1;
var historyID = 0;

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
function unlockScreen(popup) { $('#lock').fadeOut("fast"); hideTooltip(); }
function hideAndRemove(obj) { $(obj).hide('slow',function() { $(obj).remove(); }); }
function hideAndEmpty(obj) { $(obj).hide('slow',function() { $(obj).html(''); }); }

function isset(lvar) {
	if(typeof lvar == 'undefined') return false;
	return true; 
}

function loadWindowHead() {
        $.post('?at=1', function(data) {
		$('#logform').html(data);
	});
}

function loadMainContainer(url) {
	$.post('?at=2&at-conn=1'+url, function(data) {
		$('#main').html(data);
		closeWaitingPopup();
		unlockScreen();
	});
}

function showNotification(options) {
	$('#subnotification').html(options['snotif']);
	$('#notification').slideDown();
	slideState = 1;
	setTimeout(function() {
		notifCloseTicket++;
		myTicket = notifCloseTicket;
		if (slideState == 1 && notifCloseTicket == myTicket) {
			$('#notification').slideUp();
		}
	},(isset(options['stimeout']) && options['stimeout'] > 1000 ? options['stimeout'] : 5000));
}

function callbackLink(cbklink,options) {
	// 0: nothing, 1: down, 2: lockUp
	var slideState = 0;
	if(isset(options)) {
		// Locking screen if needed
		if(isset(options['lock']) && options['lock'] == true) {
			waitingPopup('');
		}
		// Starting notification
		if(isset(options['snotif']) && options['snotif'].length > 0) {
			showNotification(options);
         }
	}
	$.post(cbklink+'&at=3', function(data) {
		$('#subnotification').html(data);
		closePopup();
		/*
		 * If waiting popup is and callback fast, 
		 * waiting popup isn't closed.
		 * Then we wait 500ms before closing this popup
		 */
		setTimeout(function() {
			closeWaitingPopup();
		},500);
	})
	.fail(function() {
        $('#subnotification').html('Error 500, please check httpd-error.log'); 
	})
	.always(function() {
		if(slideState != 1) {
			$('#notification').slideDown();
		}
		// lock slideState for previous setTimeout
		slideState = 2;
		setTimeout(function() {
			$('#notification').slideUp();
		},isset(options) && isset(options['timeout']) && options['timeout'] > 1000 ? options['timeout'] : 5000);
		if(isset(options) && isset(options['lock']) && options['lock'] == true) {
			//unlockScreen();
		}
		
	});
	return false;
}

function callbackForm(cbklink,obj,options) {
	// 0: nothing, 1: down, 2: lockUp
	var slideState = 0;
	if(isset(options)) {
		// Starting notification
		if(isset(options['snotif']) && options['snotif'].length > 0) {
			showNotification(options);
		}
	}
	
	waitingPopup('',function() {
		/*
		 * We launch a timer before showing the loader. If timer expires
		 * no loaded is shown
		 */
		
		popLoaded = 0;
		setTimeout(function() {
			if (popLoaded == 0) {
				popLoaded = 1;
			}
        },500);
        
		$.post(cbklink+'&at=3', $(obj).serialize(), function(data) {
			$('#subnotification').html(data);
			if(noClosePopup == 0) {
				if(slideState != 1) { 
					$('#notification').slideDown();
				}
				// lock slideState for previous setTimeout
				slideState = 2;
				setTimeout(function() {
					$('#notification').slideUp();
				},isset(options) && isset(options['timeout']) && options['timeout'] > 1000 ? options['timeout'] : 5000);
				
				popLoaded = 1;
				closePopup();
				closeWaitingPopup();
				unlockScreen();
			}
			else {
				noClosePopup = 0;

				// If loader is shown, we must do an animation
				if (popLoaded == 1) {
					closeWaitingPopup();
					$('#subpop').dialog("open");
				}
				// Else, show the popup now
				else {
					popLoaded = 1;
					closeWaitingPopup();
					$('#subpop').dialog("open");
				}
			}
		})
		.fail(function() {
			// If loader is shown, we must do an animation
			if (popLoaded == 1) {
				showPopup('Error 500, please check httpd-error.log',2);
			}
			// Else, show the popup now
			else {
				popLoaded = 1;
				showPopup('Error 500, please check httpd-error.log',0);
			}
		});
	});
	return false;
}

function confirmPopup(text,textok,textno,cbklink,options) {
	cbklink = cbklink.replace(/\'/g,'\\\'');
	$('#subpop').css("text-align","left");
	$('#subpop').html(text);
	
	options['dialog'] = true;
	
	createPopup("no-close",true,"auto","auto","subpop");

	$('#subpop').dialog("option","buttons", [ 
		{ text: textok, click: function() { callbackLink(''+cbklink+'',options); } },
		{ text: textno, click: function() { $(this).dialog("close"); } }
	]);

	return false;
}

function formPopup(modid,callid,lnkadd) {
	if(!isset(lnkadd)) lnkadd = '';
	if($('#subpop').html() != '') {
		forceLock = 1;
		closePopup();
	}
	$('#subpop').css("text-align","left");
	waitingPopup('',function() {
		
		/*
		 * We launch a timer before showing the loader. If timer expires
		 * no loaded is shown
		 */
		
		popLoaded = 0;
		setTimeout(function() {
			if (popLoaded == 0) {
				$('#subpop').dialog("open");
				popLoaded = 1;
			}
        },500);

        $.post('?mod='+modid+'&at=4&el='+callid+"&"+lnkadd, function(data) {
			// If loader is shown, we must do an animation
			if (popLoaded == 1) {
				showPopup(data,2);
			}
			// Else, show the popup now
			else {
				popLoaded = 1;
				showPopup(data,0);
			}
		})
		.fail(function() {
			$('#subpop').parent().fadeOut('100',function() {
				showPopup("Error 500: please check httpd-error.log",0);
			}); 
		});
	});

	forceLock = 0;
	return false;
}

function waitingPopup(text,callback,autoOp) {
	if(!isset(callback)) {
		callback = null;
	}
	
	if(!isset(autoOp)) {
		autoOp = false;
	}
	
	closePopup();

	$('#loaderpop').css("text-align", "center");
	lockScreen(function() {
		createPopup("no-close",autoOp,"200px","auto","loaderpop");
		$('#loaderpop').html('<span class=\"loader\"></span><br />'+text);
		$('#loaderpop').dialog("open");
		if(callback != null) {
			callback();
		}
	});
}

function closeWaitingPopup() {
	if ($('#loaderpop').hasClass('ui-dialog-content')) {
		$('#loaderpop').dialog("close");
	}
}

function createPopup(dClass,autoOp,w,h,divName) {
	$('#'+divName).dialog({
		dialogClass: dClass,
		draggable: false,
		buttons: {},
		width: w,
		height: h,
		position: 'center',
		autoOpen: autoOp,
		modal: true,
		resizable: false,
		beforeClose: function(event,ui) {
			unlockScreen();
		},
		// Override maxHeight and maxWidth where there is so many content
		create: function (event,ui) {
			// If there is a footer we need to move up the container
			upHeight = 0;
			if ($('#footer')) {
					upHeight = $('#footer').height();
			}
			$(this).css("maxHeight", 0.95*$(window).height()-upHeight*2);
			$(this).css("maxWidth", 0.95*$(window).width());
		}
	});
}

function closePopup() {
	if ($('#subpop').hasClass('ui-dialog-content')) {
		$('#subpop').dialog("close");
	}
}

/*
 * doFade permit to fade the content
 * value 0: no fade + set content
 * value 1: set content + fadeIn
 * value 2: only fade out
 */
function showPopup(data,doFade) {
	if (doFade == 2) {
		$('#subpop').parent().fadeOut('100',function() {
			showPopup(data, 1);
		});
	}
	else {
		closeWaitingPopup();
		createPopup("no-close",true,"auto","auto","subpop");
		$('#subpop').html(data);
		$('#subpop').dialog("open");
		$('#subpop').dialog("option","dialogClass", "dialog-shown");
		$('#subpop').dialog("option","height", "auto");
		$('#subpop').dialog("option","position", "center");
		// Because of footer, centerize height
		topPosition = ($('#subpop').parent().position().top/2);
		$('#subpop').parent().css('top',topPosition);
	}
	
	if (doFade == 1) {
		$('#subpop').parent().fadeIn('500');
	}
}

function loadInterface(url) {
	waitingPopup('',function() {
		loadMainContainer(url);
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
			$('#tooltip').fadeOut('fast');
		}
	},400);
}

/*
 * Footer plugins
 */

function reloadFooterPlugins() {
	$.get('?at=6',
		function(data) {
			$('#footer #content').html(data);
		}
	);
}

function clearFooterPlugins() {
	$('#footer #content').html('');
}

/*
 * Login related
 */

function setMaxIdleTimer(value) { maxIdleTimer = value; }
function idleIncrement() {
	// maxIdleTimer == -1 => disabled
	if (maxIdleTimer != -1) {
		idleTimer = idleTimer + 1;
		if (idleTimer > maxIdleTimer) {
			disconnectUser();
		}
	}
}

function openLogin () {
	clearPasswordValue();
	$('#login').slideDown('slow');	
}
function closeLogin() { 
	$('#login').slideUp('slow');
	var obj = document.getElementsByName('loginupwd')[0];
	if (isset(obj)) { obj.value = ''; }
}
function clearPasswordValue() {
	$('#loginupwd').val('');
}

function setLoginCbkMsg(value) { clearPasswordValue(); $('#loginCbk').html(value); }
function disconnectUser() { $.get('?at=5',function(data) { $('#subnotification').html(data); }); }

/*
 * History
 */
 
function addHistoryState(title,link,clink) {
	if (window.history.pushState) {
		window.history.pushState({"id":historyID,"title":title,"url":clink}, title, link);
		historyID++;
	}
}
/*
* Main
*/

$(document).ready(function() {
	// Idle user 
	var idleInterval = setInterval(idleIncrement, 60000);
	$(this).click(function (e) { idleTimer = 0; });
    $(this).keypress(function (e) { idleTimer = 0; });
    
    window.addEventListener('popstate', function(event) {
		if (event.state) {
			loadInterface(event.state.url);
		}
	});

	// Every 15 sec reload all footer plugins
	var footerIntervalIdx = setInterval(reloadFooterPlugins, 15000);
});
