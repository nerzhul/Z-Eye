// JavaScript Document

function recordChange(obj) {
	type = document.getElementById(obj).value;
	zid = document.getElementById("zid").value;
	getPage('{ "at": "2", "mid":"36", "do":"3", "t":"' + type + '", "zid": "' + zid + '"}');
}

function deleteRecord(zid,rid) {
	if(!isNumeric(zid) || !isNumeric(rid))
		return false;

	$.post("index.php?at=3&mod=36&act=5&zid="+zid+"&rid="+rid+"", function(data) {
		getPage('{"at": "2", "mid": "36", "do":"2", "zid": "'+zid+'"}');
	});
}

function checkZone() {
	
	return true;	
}

function checkRecord() {
	type = document.getElementById("regtype").value;
	val = document.getElementById("value").value;
	if(document.getElementById("name").value == "") {
		alert("Le nom d'hôte est vide !");
		return false
	}
	
	txt = "";
	switch(type) {
		case "1":
			txt = "IPv4";
			break;
		case "2":
			txt = "IPv6";
			break;
		case "3":
			txt = "FQDN";
			break;
		case "4":
			txt = "Hôte";
			prio = document.getElementById("prio").value;
			if(prio == "") {
				alert("Le champ priorité est vide !");
				return false;	
			}
			
			if(!isNumeric(prio) || prio < 0 || prio > 100) {
				alert("Le champ priorité est invalide !");
				return false;	
			}
			break
		case "5":
			txt = "Service";
			pcl = document.getElementById("srvtype").value;
			if(pcl == "" || pcl != "TCP" && pcl != "UDP" && pcl != "TLS" && pcl != "SCTP") {
				alert("Le champ protocole n'est pas valide ou est vide !");
				return false;
			}
			
			prio = document.getElementById("prio").value;
			if(prio == "") {
				alert("Le champ priorité est vide !");
				return false;	
			}
			
			if(!isNumeric(prio) || prio < 0 || prio > 100) {
				alert("Le champ priorité est invalide !");
				return false;	
			}
			
			prt = document.getElementById("port").value;
			if(prt == "") {
				alert("Le champ port est vide !");
				return false;	
			}
			
			if(!isNumeric(prt) || prt < 1 || prt > 65535) {
				alert("Le champ port est invalide !");
				return false;	
			}
			
			if(!isNumeric(document.getElementById("wgt").value)) {
				alert("Le champ poids est invalide !");
				return false;	
			}
			break;
		default:
			alert("Le type d'enregistrement n'est pas valide !");
			return false;
	}
	
	if(val == "") {
		alert("Le champ " + txt + " est vide !");
		return false;
	}
	
	return true;
}