/** This code is Property of Frost Sapphire Studios, all rights reserved.
*	All modification is stricty forbidden without Frost Sapphire Studios Agreement
**/

var mouseX;
var mouseY;
document.onmousemove = getMouseXY;

function getMouseXY(event) {
	if(!event) event = window.event;	
	if(event) {
		mouseX = document.all ? (event.clientX + document.body.scrollLeft) : event.pageX;
		mouseY = document.all ? (event.clientY + document.body.scrollTop) : event.pageY;
	}
}

function getMouseX(){return mouseX;}

function getMouseY() {return mouseY;}

function setDivOnMouse(obj,paddingW) {
	if(!paddingW)
		paddingW = 0;
	if(document.getElementById(obj)) {
		document.getElementById(obj).style.left = getMouseX() + 20 + paddingW;
		document.getElementById(obj).style.top = getMouseY() - 50;
	}
}

function fadeIn(obj) {
	$(obj).hide();
		setTimeout(function()
		{
			$(obj).fadeIn(1000);
		}, 350);
}

function fadeOut(obj) {
	setTimeout(function()
		{
			$(obj).fadeOut(1000);
		}, 300);
}

function checkIP(obj) {
	var str = document.getElementById(obj).value;
	if(isIP(str) || str == "")
		document.getElementById(obj).style.backgroundColor = "#FFFFFF";
	else
		document.getElementById(obj).style.backgroundColor = "#FFB56A";
}

function checkMask(obj) {
	var str = document.getElementById(obj).value;
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
		document.getElementById(obj).style.backgroundColor = "#FFFFFF";
	else
		document.getElementById(obj).style.backgroundColor = "#FFB56A";
	
	return flag;
}

function getMenu(obj,id) {
	$('#mainContent').html("<br /><br /><center><img src=\"lib/FSS/images/loader.gif\" alt=\"Chargement...\" /></center>");
	$.get("index.php", { at: 1, mid: id }, function(data) {
		$(obj).html(data);
	});
}

function getModule(id){$('#mainContent').html("<br /><br /><center><img src=\"lib/FSS/images/loader.gif\" alt=\"Chargement...\" /></center>");$.get("index.php",{ at: 2, mid: id },function(data){$('#mainContent').html(data);});}

function getPage(jsontab) {
	$('#mainContent').html("<br /><br /><center><img src=\"lib/FSS/images/loader.gif\" alt=\"Chargement...\" /></center>");
	$.get("index.php", eval("(" + jsontab + ")"), function(data) {
		$('#mainContent').html(data);
	});
}

$(document).ready(function() {
	// Expand Panel
	$("#loginopen").click(function(){
		$("div#logpanel").slideDown("slow");
	
	});	
	
	// Collapse Panel
	$("#loginclose").click(function(){
		$("div#logpanel").slideUp("slow");	
	});		
	
	$("#logintoggle a").click(function () {
		$("#logintoggle a").toggle();
	});		
	
	// Expand Panel
	$("#searchopen").click(function(){
			$("div#searchpanel").animate({width: 'toggle'});

	});

	// Collapse Panel
	$("#searchclose").click(function(){
			$("div#searchpanel").animate({width: 'toggle'});
	});

	$("#searchtoggle a").click(function () {
			$("#searchtoggle a").toggle();
	});
});
