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

	class lDNSManager extends FSLocales {
		function lDNSManager() {
			parent::FSLocales();
			$locales = array(
				"fr" => array(
					"add-server" => "Ajouter un serveur",
					"Alone" => "Orphelin",
					"confirm-remove-dnssrc" => "Êtes vous sûr de vouloir supprimer la source de données suivante: ",
					"chroot-path" => "Chroot Path",
					"edit-server" => "Edition du serveur",
					"err-bad-login" => "Login au serveur incorrect",
					"err-bad-server" => "Aucun serveur enregistré avec ces coordonnées",
					"err-invalid-db" => "Aucune base de données avec ces informations en base",
					"err-invalid-req" => "Requête invalide !",
					"err-miss-bad-fields" => "Certains champs sont invalides ou vides",
					"err-no-rights" => "Vous n'avez pas le droit de faire cela !",
					"err-no-zone" => "Aucune zone DNS spécifiée !",
					"err-server-exist" => "Ce serveur est déjà référencé",
					"err-unable-conn" => "Impossible de se connecter au serveur spécifié",
					"expert-tools" => "Outils avancés",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"Filter" => "Filtrer",
					"found-records" => "Enregistrements obsolètes trouvés !",
					"ip-addr-dns" => "Adresse IP/DNS",
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
					"Alone" => "Alone",
					"confirm-remove-dnssrc" => "Are you sure to want to remove this DNS data source ",
					"chroot-path" => "Chroot Path",
					"edit-server" => "Server edit",
					"err-bad-login" => "Bad server login",
					"err-bad-server" => "No server found with this datas",
					"err-invalid-db" => "No database with those informations",
					"err-invalid-req" => "Invalid request !",
					"err-miss-bad-fields" => "Some fields are bad or missing",
					"err-no-rights" => "You don't have rights to do that !",
					"err-no-zone" => "No DNS zone specified !",
					"err-server-exist" => "This server already exists",
					"err-unable-conn" => "Unable to connect to specified server",
					"expert-tools" => "Expert tools",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"Filter" => "Filter",
					"found-records" => "Old records found !",
					"ip-addr-dns" => "IP/DNS address",
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
