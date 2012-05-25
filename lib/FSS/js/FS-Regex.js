/** This code is Property of Frost Sapphire Studios, all rights reserved.
*	All modification is stricty forbidden without Frost Sapphire Studios Agreement
**/

function isNumeric(str) {
	expr = new RegExp("^([0-9]*)$", "gi");	
	if(expr.test(str))
		return true;
		
	return false;	
}

function isDNSName(str) {
	expr = new RegExp("^[a-z][a-z0-9.-]{1,}[a-z0-9]{2,}$", "gi");	
	if(expr.test(str))
		return true;
		
	return false;
}

function isHostname(str) {
	expr = new RegExp("^[a-zA-Z]([a-zA-Z0-9_-]*)[a-zA-Z0-9]$", "gi");	
	if(expr.test(str))
		return true;
		
	return false;
}

function isMacAddr(str) {
	expr = new RegExp("^([0-9A-F]{2}:){5}[0-9A-F]{2}$", "gi");	
	if(expr.test(str))
		return true;
		
	return false;
}

function isIPv6(str) {
	expr = new RegExp("^([0-9A-F]{4}:){5}[0-9A-F]{4}$", "gi");	
	if(expr.test(str))
		return true;
		
	return false;
	
}
// Package functions, full ok functions only
function isIP(str){expr=new RegExp("^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$");if(expr.test(str))return true;return false;}