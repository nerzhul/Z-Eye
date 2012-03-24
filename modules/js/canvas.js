// JavaScript Document
var orange = 0;
function _initcanvas(obj) {
	var canvas = document.getElementById(obj);
	var context = canvas.getContext("2d");
	context.fillStyle = "rgba(0,118,176,0)";
	context.fillRect(0, 0, context.canvas.width, context.canvas.height);
	context.beginPath();
	context.moveTo(-2,-2);
	context.lineTo(1082,-2);
	context.lineTo(1082,202);
	context.lineTo(-2,202);
	context.lineTo(-2,-2);	
	context.closePath(); // complete custom shape
	
	var grd = context.createRadialGradient(10,190,35,150,100,800);
	grd.addColorStop(0,"#9ed2ec");
	grd.addColorStop(0.08,"#55a6cd"); 
	grd.addColorStop(0.13,"#1f85b6");
	grd.addColorStop(0.2,"#005d8a");
	grd.addColorStop(0.5,"#55a6cd"); 
	grd.addColorStop(0.66,"#1f85b6");
	grd.addColorStop(0.8,"#005d8a"); 
	grd.addColorStop(1,"#0076b0");
	context.fillStyle = grd;
	context.fill();
	context.save();
	context.moveTo(0,0);

	var img = new Image();
	img.onload = function() {
		context.shadowColor = (orange == 0) ? "orange" : "red";
		context.shadowBlur = 15;
		//context.drawImage(img, 680,120,350,40);
	}
	
	var img2 = new Image();
	img2.src = 'http://www.frostsapphirestudios.com/styles/images/FSS.png';
	img2.onload = function() {
		context.shadowColor = (orange == 0) ? "orange" : "red";
		context.shadowBlur = 30;
		//context.drawImage(img2, 680,35,350,90);
	}
	
	orange = (orange == 0) ? 1 : 0;
}
