<?php
	/*
	* Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
	*
	* This program is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program; if not, write to the Free Software
	* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
	*/

	final class lDNSManager extends FSLocales {
		function __construct() {
			parent::__construct();
			$locales = array(
				"fr" => array(
					"add-server" => "Ajouter un serveur",
					"Advanced-tools" => "Outils avancés",
					"algorithm" => "Algorithme",
					"Alone" => "Orphelin",
					"confirm-remove-dnssrc" => "Êtes vous sûr de vouloir supprimer la source de données suivante: ",
					"chroot-path" => "Chroot Path",
					"confirm-remove-tsig" => "Êtes vous sûr de vouloir supprimer la clef TSIG: ",
					"define-tsig-key" => "Définir une clef TSIG",
					"DNSSec-Mgmt" => "Gestion DNSSec",
					"DNS-zones" => "Zones DNS",
					"edit-server" => "Edition du serveur",
					"err-bad-login" => "Login au serveur incorrect",
					"err-bad-server" => "Aucun serveur enregistré avec ces coordonnées",
					"err-invalid-db" => "Aucune base de données avec ces informations en base",
					"err-invalid-req" => "Requête invalide !",
					"err-miss-bad-fields" => "Certains champs sont invalides ou vides",
					"err-no-rights" => "Vous n'avez pas le droit de faire cela !",
					"err-no-records" => "Aucun enregistrement DNS trouvé",
					"err-no-zone" => "Aucune zone DNS spécifiée !",
					"err-server-exist" => "Ce serveur est déjà référencé",
					"err-tsig-key-algo-invalid" => "L'algorithme choisi pour la clef est invalide",
					"err-tsig-key-already-exists" => "Cette clef TSIG existe déjà",
					"err-tsig-key-exactly-same" => "Une clef TSIG strictement identique existe déjà",
					"err-tsig-key-id-invalid" => "L'ID de la clef est invalide. Il ne peut contenir que des lettres, chiffres et tirets",
					"err-tsig-key-not-exists" => "Cette clef TSIG n'existe pas",
					"err-unable-conn" => "Impossible de se connecter au serveur spécifié",
					"expert-tools" => "Outils avancés",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"Filter" => "Filtrer",
					"found-records" => "Enregistrements obsolètes trouvés !",
					"ip-addr-dns" => "Adresse IP/DNS",
					"key-alias" => "Alias de la clef",
					"key-id" => "ID de la clef",
					"Login" => "Login",
					"menu-name" => "Supervision",
					"menu-title" => "Supervision DNS",
					"Modification" => "Modification",
					"modify-servers" => "Gérer la liste des serveurs", 
					"named-conf-path" => "Chemin named.conf",
					"no-data-found" => "Aucune donnée collectée",
					"no-found-records" => "Aucun enregistrement obsolète trouvé",
					"Others" => "Autres",
					"Password" => "Mot de passe",
					"Password-repeat" => "Mot de passe (répétition)",
					"Record" => "Enregistrement",
					"Return" => "Retour",
					"Save" => "Enregistrer",
					"Search" => "Rechercher",
					"serverlist" => "Liste des serveurs",
					"Server" => "Serveur",
					"Server-Mgmt" => "Gestion des serveurs",
					"Servers" => "Serveur(s)",
					"ssh-user" => "Utilisateur SSH",
					"Stats" => "Statistiques",
					"title-dns" => "Supervision DNS",
					"title-old-records" => "Recherche d'enregistrements obsolètes",
					"tooltip-chroot" => "Le chroot est une sécurité d'environnement DNS. Généralement il s'agit du répertoire /var/named. Si vous ne chrootez pas, indiquez '/'",
					"tooltip-rights" => "Attention l'utilisateur doit avoir des droits de lecture. Créer un groupe et y ajouter votre utilisateur SSH est une bonne idée",
					"Value" => "Valeur",
				),
				"en" => array(
					"add-server" => "Add server",
					"Advanced-tools" => "Advanced tools",
					"algorithm" => "Algorithm",
					"Alone" => "Alone",
					"confirm-remove-dnssrc" => "Are you sure to want to remove this DNS data source ",
					"confirm-remove-tsig" => "Are you sure you want to remove TSIG key ",
					"chroot-path" => "Chroot Path",
					"define-tsig-key" => "Define a new TSIG key",
					"DNSSec-Mgmt" => "DNSSec management",
					"DNS-zones" => "DNS zones",
					"edit-server" => "Server edit",
					"err-bad-login" => "Bad server login",
					"err-bad-server" => "No server found with this datas",
					"err-invalid-db" => "No database with those informations",
					"err-invalid-req" => "Invalid request !",
					"err-miss-bad-fields" => "Some fields are bad or missing",
					"err-no-rights" => "You don't have rights to do that !",
					"err-no-records" => "No record found",
					"err-no-zone" => "No DNS zone specified !",
					"err-server-exist" => "This server already exists",
					"err-tsig-key-algo-invalid" => "The choosen algorithm is invalid",
					"err-tsig-key-already-exists" => "This TSIG key already exists",
					"err-tsig-key-exactly-same" => "An existing TSIG key already exists with exactly same values",
					"err-tsig-key-id-invalid" => "Key ID is invalid. It can only contain letters, numbers and dashes",
					"err-tsig-key-not-exists" => "This TSIG key doesn't exist",
					"err-unable-conn" => "Unable to connect to specified server",
					"expert-tools" => "Expert tools",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"Filter" => "Filter",
					"found-records" => "Old records found !",
					"ip-addr-dns" => "IP/DNS address",
					"key-alias" => "Key alias",
					"key-id" => "Key ID",
					"Login" => "Login",
					"menu-name" => "Supervision",
					"menu-title" => "DNS supervision",
					"Modification" => "Modifying",
					"modify-servers" => "Manage server list",
					"named-conf-path" => "named.conf path",
					"no-data-found" => "No data collected",
					"no-found-records" => "No old records found",
					"Others" => "Others",
					"Password" => "Password",
					"Password-repeat" => "Password (repeat)",
					"Record" => "Record",
					"Return" => "Return",
					"Save" => "Save",
					"Search" => "Search",
					"Server" => "Server",
					"Server-Mgmt" => "Servers management",
					"serverlist" => "Liste des serveurs",
					"Servers" => "Server(s)",
					"ssh-user" => "SSH user",
					"Stats" => "Statistics",
					"title-dns" => "DNS supervision",
					"title-old-records" => "Search old records",
					"tooltip-chroot" => "Chroot is a DNS environment security. Often it's /var/named directory. If you don't chroot, write '/'",
					"tooltip-rights" => "Warning, your user need read rights. Use a special group (and add your SSH user into) may be a good idea",
					"Value" => "Value",
				)
			);
			$this->concat($locales);
		}
	};
?>
