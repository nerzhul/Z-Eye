<?php
    /*
    * Copyright (C) 2010-2012 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

    require_once(dirname(__FILE__)."/../../../lib/FSS/objects/Locales.FS.class.php");
    
	class lServerMgmt extends zLocales {
		function lServerMgmt() {
			$this->locales = array(
				"fr" => array(
					"add-server" => "Ajouter un serveur au moteur",
					"Alias" => "Alias",
					"chroot-path" => "Chroot Path",
					"db-name" => "Nom de la base",
					"dhcpd-conf-path" => "Chemin dhcpd.conf",
					"dhcpd-lease-path" => "Chemin dhcpd.leases",
					"edit-server" => "Edition du serveur",
					"err-bad-login" => "Login au serveur incorrect",
					"err-bad-server" => "Aucun serveur enregistré avec ces coordonnées",
					"err-invalid-db" => "Aucune base de données avec ces informations en base",
					"err-miss-bad-fields" => "Certains champs sont invalides ou vides",
					"err-no-db" => "Aucune base de données à éditer spécifiée",
					"err-no-db-given" => "Aucune base renseignée",
					"err-no-server-found" => "Aucun serveur trouvé",
					"err-no-server-get" => "Aucun serveur à éditer spécifié",
					"err-server-exist" => "Ce serveur est déjà référencé",
					"err-unable-conn" => "Impossible de se connecter au serveur spécifié",
					"Host" => "Hôte",
					"ip-addr" => "Adresse IP",
					"ip-addr-dns" => "Adresse IP/DNS",
					"Login" => "Login",
					"named-conf-path" => "Chemin named.conf",
					"New-base" => "Nouvelle base",
					"New-server" => "Nouveau Serveur",
					"Password" => "Mot de passe",
					"Password-repeat" => "Mot de passe (répétition)",
					"Port" => "Port",
					"Remove" => "Supprimer",
					"Return" => "Retour",
					"Save" => "Enregistrer",
					"Server" => "Serveur",
					"server-path" => "Chemin sur le serveur",
					"srv-type" => "Type de service",
					"ssh-user" => "Utilisateur SSH",
					"title-analysismgmt" => "Gestion du moteur d'analyse des serveurs",
					"title-add-backup-switch-server" => "Ajouter un serveur de sauvegarde (configuration des équipements réseau)",
					"title-add-radius" => "Ajouter une base de données Radius au moteur",
					"title-backup-switch" => "Liste des serveurs de sauvegarde (configurations des équipements réseau)",
					"title-edit-backup-switch-server" => "Edition des informations du serveur de sauvegarde (configuration des équipements réseau)",
					"title-edit-radius" => "Edition des informations de la base de données Radius",
					"title-radius-db" => "Liste des bases Radius",
					"title-server-list" => "Liste des serveurs",
					"tooltip-rights" => "Attention l'utilisateur doit avoir des droits de lecture. Créer un groupe et y ajouter votre utilisateur SSH est une bonne idée",
					"Type" => "Type",
					"User" => "Utilisateur",
				),
				"en" => array(
					"add-server" => "Add server",
					"Alias" => "Alias",
					"chroot-path" => "Chroot Path",
					"db-name" => "Database name",
					"dhcpd-conf-path" => "dhcpd.conf path",
					"dhcpd-lease-path" => "dhcpd.leases path",
					"edit-server" => "Server edit",
					"err-bad-login" => "Bad server login",
					"err-bad-server" => "No server found with this datas",
					"err-invalid-db" => "No database with those informations",
					"err-miss-bad-fields" => "Some fields are bad or missing",
					"err-no-db" => "No database to edit field",
					"err-no-db-given" => "No database",
					"err-no-server-found" => "No server found",
					"err-no-server-get" => "No server to edit found",
					"err-server-exist" => "This server already exists",
					"err-unable-conn" => "Unable to connect to specified server",
					"Host" => "Host",
					"ip-addr" => "IP address",
					"ip-addr-dns" => "IP/DNS address",
					"Login" => "Login",
					"named-conf-path" => "named.conf path",
					"New-base" => "New base",
					"New-server" => "New server",
					"Password" => "Password",
					"Password-repeat" => "Password (repeat)",
					"Port" => "Port",
					"Remove" => "Remove",
					"Return" => "Return",
					"Save" => "Save",
					"Server" => "Server",
					"server-path" => "Server path",
					"srv-type" => "Type of service",
					"ssh-user" => "SSH user",
					"title-analysismgmt" => "Server analysis management",
					"title-add-backup-switch-server" => "Add backup server (network device config)",
					"title-add-radius" => "Add Radius DB",
					"title-backup-switch" => "Backup server list (network device config)",
					"title-edit-backup-switch-server" => "Edit backup server (network device config)",
					"title-edit-radius" => "Edit Radius DB",
					"title-radius-db" => "Radius DB list",
					"title-server-list" => "Server list",
					"tooltip-rights" => "Warning, your user need read rights. Use a special group (and add your SSH user into) may be a good idea",
					"Type" => "Type",
					"User" => "User",
				)
			);
		}
	};
?>
