
function checkMainConfig() {
	if(document.getElementsByName('gate')[0].value == "") {
		alert("Le champ 'Routeur par défaut' est vide !");
		return false;
	}
	
	if(!isIP(document.getElementsByName('gate')[0].value)) {
		alert("Le champ 'Routeur par défaut' doit contenir une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('dns1')[0].value == "") {
		alert("Le champ 'DNS primaire' est vide !");
		return false;
	}
	
	if(document.getElementsByName('domain')[0].value != "" && !isDNSName(document.getElementsByName('domain')[0].value)) {
		alert("Le champ 'Domaine' n'est pas valide !");
		return false;
	}
	
	if(!isIP(document.getElementsByName('dns1')[0].value)) {
		alert("Le champ 'DNS primaire' doit contenir une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('dns2')[0].value != "" && !isIP(document.getElementsByName('dns2')[0].value)) {
		alert("Le champ 'DNS secondaire' doit être une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('maxlease')[0].value == "") {
		alert("Le champ 'Durée de bail maximale' est vide !");
		return false;
	}
	
	if(!isNumeric(document.getElementsByName('maxlease')[0].value)) {
		alert("Le champ 'Durée de bail Maximale' doit être une valeur numérique");
		return false;	
	}
	
	if(document.getElementsByName('maxlease')[0].value < 300) {
		alert("Le champ 'Durée de bail maximale' doit avoir une valeur supérieure à 300");
		return false;
	}
	
	if(document.getElementsByName('suglease')[0].value == "") {
		alert("Le champ 'Durée de bail suggérée' est vide !");
		return false;
	}
	
	if(!isNumeric(document.getElementsByName('suglease')[0].value)) {
		alert("Le champ 'Durée de bail suggérée' doit être une valeur numérique");
		return false;	
	}
	
	if(document.getElementsByName('suglease')[0].value < 300) {
		alert("Le champ 'Durée de bail suggérée' doit avoir une valeur supérieure à 300");
		return false;
	}
	
	if(document.getElementsByName('suglease')[0].value >= document.getElementsByName('maxlease')[0].value) {
		alert("Le champ 'Durée de bail suggérée' doit être inférieur au champ 'Durée de bail maximale'");
		return false;
	}
	
	if(document.getElementsByName('tftp')[0].value != "" && !isIP(document.getElementsByName('tftp')[0].value)) {
		alert("Le champ 'Serveur TFTP' doit être une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('pxe')[0].value != "" && !isIP(document.getElementsByName('pxe')[0].value)) {
		alert("Le champ 'Serveur de Boot PXE' doit être une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('pxe')[0].value == "" && document.getElementsByName('tftp')[0].value != "" ||
		document.getElementsByName('pxe')[0].value != "" && document.getElementsByName('tftp')[0].value == "") {
		alert("Les champs 'Serveur TFTP' et 'Serveur de Boot PXE' sont complémentaires");
		return false;	
	}
	
	if(document.getElementsByName('gwpxe')[0].value != "" && !isIP(document.getElementsByName('gwpxe')[0].value)) {
		alert("Le champ 'Passerelle par défaut du PXE' doit être une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('gwpxe')[0].value != "" && (document.getElementsByName('pxe')[0].value == "" || document.getElementsByName('tftp')[0].value == "")) {
		alert("Le champ 'Passerelle par défaut du PXE' requiert les champs 'Serveur TFTP' et 'Serveur de boot PXE'");
		return false;	
	}
	
	return true;
}

function checkFailover() {
	if(document.getElementsByName('name')[0].value == "") {
		alert("Le champ 'Identifiant failover' est vide !");
		return false;
	}
	
	if(document.getElementsByName('saddr')[0].value == "") {
		alert("Le champ 'Adresse IP source' est vide !");
		return false;
	}
	
	if(document.getElementsByName('sport')[0].value == "") {
		alert("Le champ 'Port Source' est vide !");
		return false;
	}
	
	if(document.getElementsByName('daddr')[0].value == "") {
		alert("Le champ 'Adresse IP de destination' est vide !");
		return false;
	}
	
	if(document.getElementsByName('dport')[0].value == "") {
		alert("Le champ 'Port de destination' est vide !");
		return false;
	}
	
	if(document.getElementsByName('ansdelay')[0].value == "") {
		alert("Le champ 'Délai de réponse' est vide !");
		return false;
	}
	
	if(document.getElementsByName('maxack')[0].value == "") {
		alert("Le champ 'Nombre de requêtes maximum non acquitées' est vide !");
		return false;
	}
	
	if(document.getElementsByName('loadbalance')[0].value == "") {
		alert("Le champ 'Durée de surcharge maximum' est vide !");
		return false;
	}
	
	if(!isNumeric(document.getElementsByName('sport')[0].value)) {
		alert("Le champ 'Port source' doit être une valeur numérique");
		return false;	
	}
	
	if(!isNumeric(document.getElementsByName('dport')[0].value)) {
		alert("Le champ 'Port de Destination' doit être une valeur numérique");
		return false;	
	}
	if(!isNumeric(document.getElementsByName('ansdelay')[0].value)) {
		alert("Le champ 'Délai de réponse' doit être une valeur numérique");
		return false;	
	}
	if(!isNumeric(document.getElementsByName('maxack')[0].value)) {
		alert("Le champ 'Nombre de requêtes maximum non acquitées' doit être une valeur numérique");
		return false;	
	}
	if(!isNumeric(document.getElementsByName('loadbalance')[0].value)) {
		alert("Le champ 'Durée de surcharge maximum' doit être une valeur numérique");
		return false;	
	}
	
	if(document.getElementsByName('sport')[0].value > 65535 || document.getElementsByName('sport')[0].value < 1) {
		alert("Le champ 'Port source' n'est pas valide !");
		return false;
	}
	
	if(document.getElementsByName('dport')[0].value > 65535 || document.getElementsByName('dport')[0].value < 1) {
		alert("Le champ 'Port de destination' n'est pas valide !");
		return false;
	}
	
	if(document.getElementsByName('loadbalance')[0].value < 2) {
		alert("Le champ 'Durée de surcharge maximum' n'est pas valide !");
		return false;
	}
		
	if(document.getElementsByName('maxack')[0].value < 2) {
		alert("Le champ 'Nombre de requêtes maximum non acquitées' n'est pas valide !");
		return false;
	}
	
	if(document.getElementsByName('split')[0].value != "") {
		
		if(!isNumeric(document.getElementsByName('split')[0].value)) {
			alert("Le champ 'Pourcentage de baux délivrés par le serveur primaire' doit être une valeur numérique");
			return false;
		}
		
		if(document.getElementsByName('split')[0].value > 100 || document.getElementsByName('split')[0].value < 1) {
			alert("Le champ 'Pourcentage de baux délivrés par le serveur primaire' n'est pas valide !");
			return false;
		}
	}
	
	
	if(document.getElementsByName('mclt')[0].value != "") {
		
		if(!isNumeric(document.getElementsByName('mclt')[0].value)) {
			alert("Le champ 'Temps maximum de réponse du pair en cas d'échec' doit être une valeur numérique");
			return false;	
		}
		
		if(document.getElementsByName('mclt')[0].value < 2) {
			alert("Le champ 'Temps maximum de réponse du pair en cas d'échec' n'est pas valide !");
			return false;
		}
	}
	
	return true;
}

function checkSubnet() {
	if(document.getElementsByName('net')[0].value == "") {
		alert("Le champ 'Adresse Réseau' est vide !");
		return false;
	}
	
	if(!isIP(document.getElementsByName('net')[0].value)) {
		alert("Le champ 'Adresse Réseau' doit contenir une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('mask')[0].value == "") {
		alert("Le champ 'Masque réseau' est vide !");
		return false;
	}
	
	if(!isIP(document.getElementsByName('mask')[0].value) || !checkMask(document.getElementsByName('mask')[0].value)) {
		alert("Le champ 'Masque Réseau' est d'une forme incorrecte");
		return false;	
	}
	
	if(document.getElementsByName('gate')[0].value == "") {
		alert("Le champ 'Routeur par défaut' est vide !");
		return false;
	}
	
	if(!isIP(document.getElementsByName('gate')[0].value)) {
		alert("Le champ 'Routeur par défaut' doit contenir une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('dns1')[0].value == "") {
		alert("Le champ 'DNS primaire' est vide !");
		return false;
	}
	
	if(document.getElementsByName('domain')[0].value != "" && !isDNSName(document.getElementsByName('domain')[0].value)) {
		alert("Le champ 'Domaine' n'est pas valide !");
		return false;
	}
	
	if(!isIP(document.getElementsByName('dns1')[0].value)) {
		alert("Le champ 'DNS primaire' doit contenir une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('dns2')[0].value != "" && !isIP(document.getElementsByName('dns2')[0].value)) {
		alert("Le champ 'DNS secondaire' doit être une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('maxlease')[0].value == "") {
		alert("Le champ 'Durée de bail maximale' est vide !");
		return false;
	}
	
	if(!isNumeric(document.getElementsByName('maxlease')[0].value)) {
		alert("Le champ 'Durée de bail Maximale' doit être une valeur numérique");
		return false;	
	}
	
	if(document.getElementsByName('maxlease')[0].value < 300) {
		alert("Le champ 'Durée de bail maximale' doit avoir une valeur supérieure à 300");
		return false;
	}
	
	if(document.getElementsByName('suglease')[0].value == "") {
		alert("Le champ 'Durée de bail suggérée' est vide !");
		return false;
	}
	
	if(!isNumeric(document.getElementsByName('suglease')[0].value)) {
		alert("Le champ 'Durée de bail suggérée' doit être une valeur numérique");
		return false;	
	}
	
	if(document.getElementsByName('suglease')[0].value < 300) {
		alert("Le champ 'Durée de bail suggérée' doit avoir une valeur supérieure à 300");
		return false;
	}
	
	if(document.getElementsByName('suglease')[0].value >= document.getElementsByName('maxlease')[0].value) {
		alert("Le champ 'Durée de bail suggérée' doit être inférieur au champ 'Durée de bail maximale'");
		return false;
	}
	
	if(document.getElementsByName('tftp')[0].value != "" && !isIP(document.getElementsByName('tftp')[0].value)) {
		alert("Le champ 'Routeur par défaut' doit être une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('pxe')[0].value != "" && !isIP(document.getElementsByName('pxe')[0].value)) {
		alert("Le champ 'Routeur par défaut' doit être une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('pxe')[0].value == "" && document.getElementsByName('tftp')[0].value != "" ||
		document.getElementsByName('pxe')[0].value != "" && document.getElementsByName('tftp')[0].value == "") {
		alert("Les champs 'Serveur TFTP' et 'Serveur de Boot PXE' sont complémentaires");
		return false;	
	}
	
	if(document.getElementsByName('gwpxe')[0].value != "" && !isIP(document.getElementsByName('gwpxe')[0].value)) {
		alert("Le champ 'Passerelle par défaut du PXE' doit être une adresse IP");
		return false;	
	}
	
	if(document.getElementsByName('gwpxe')[0].value != "" && (document.getElementsByName('pxe')[0].value == "" || document.getElementsByName('tftp')[0].value == "")) {
		alert("Le champ 'Passerelle par défaut du PXE' requiert les champs 'Serveur TFTP' et 'Serveur de boot PXE'");
		return false;	
	}
	
	return true;
}

function checkDistribRange() {
	if(document.getElementsByName('fip')[0].value == "") {
		alert("Le champ 'Première IP du range' est vide !");
		return false;
	}
	
	if(document.getElementsByName('lip')[0].value == "") {
		alert("Le champ 'Dernière IP du range' est vide !");
		return false;
	}
	
	if(!isIP(document.getElementsByName('fip')[0].value)) {
		alert("Le champ 'Première IP du range' n'est pas une adresse IP valide !");
		return false;
	}
	
	if(!isIP(document.getElementsByName('lip')[0].value)) {
		alert("Le champ 'Dernière IP du range' n'est pas une adresse IP valide !");
		return false;
	}
	
	return true;	
}

function checkReserv() {
	if(document.getElementsByName('host')[0].value == "") {
		alert("Le champ 'Nom d'hôte est vide' est vide !");
		return false;
	}
	
	if(document.getElementsByName('hw')[0].value == "") {
		alert("Le champ 'Adresse MAC' est vide");
		return false;	
	}

	if(!isMacAddr(document.getElementsByName('hw')[0].value)) {
		alert("Le champ 'Adresse MAC' doit contenir une adresse MAC valide");
		return false;	
	}
	
	if(!isHostname(document.getElementsByName('host')[0].value)) {
		alert("Le champ 'Adresse MAC' doit contenir un nom d'hôte valide");
		return false;	
	}
	
	$.post("index.php?at=3&mod=27&act=8", $("#resform").serialize(), function(data) {
		switch(data) {
			case "-1":
				alert("Le champ 'Adresse MAC' doit contenir une adresse MAC valide"); break;
			case "-2":
				alert("Le champ 'Adresse MAC' doit contenir un nom d'hôte valide"); break;
			case "-3":
				alert("L'adresse MAC entrée correspond déjà à une autre adresse IP"); break;
			case "-4":
				alert("Le nom d'hôte entré correspond déjà à une autre adresse IP"); break;
			default:
				getPage('{"at": "2", "mid": "27", "do": "2", "net": "' + data + '"}');
				break;
	}});
	
	return false;	
}

function isMaskElem(num) {
	mask = 0;
	add = 256;
	
	if(num == 255)
		return true;
		
	while(add != 1) {
		if(num == mask)
			return true;
			
		add /= 2;
		mask += add;
	}
	
	return false;
}

function checkMask(mask) {
	arr = mask.split('.');	
	if(arr[0] == 255) {
		if(arr[1] == 255) {
			if(arr[2] == 255) {
				if(!isMaskElem(arr[3]))
					return false;
			}
			else if(arr[3] != 0)
				return false;
				
			if(!isMaskElem(arr[2]))
				return false;
		}
		else if(arr[2] != 0 || arr[3] != 0)
			return false;
			
		if(!isMaskElem(arr[1]))
			return false;
	}
	else if(arr[1] != 0 || arr[2] != 0 || arr[3] != 0) 
		return false;
	
	if(!isMaskElem(arr[0]))
		return false;
		
	return true;
}