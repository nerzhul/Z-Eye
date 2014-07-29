<?php
	/*
	* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

	final class lIPMRightsMgmt extends FSLocales {
		function __construct() {
			parent::__construct();	
			$locales = array(
				"fr" => array(
					"Add" => "Ajouter",
					"All" => "Tous",
					"confirm-remove-groupright" => "Êtes vous sûr de vouloir supprimer ce droit pour le groupe '%s' ?",
					"confirm-remove-userright" => "Êtes vous sûr de vouloir supprimer ce droit pour l'utilisateur '%s' ?",
					"err-already-exist" => "Cet élément existe déjà", 
					"err-bad-datas" => "Erreur sur la requête: certains champs sont manquants ou invalides",
					"err-no-subnet" => "Aucun réseau référencé",
					"err-not-found" => "Cet élément n'existe pas ou plus",
					"Filter" => "Filtrer",
					"Go" => "Aller",
					"group-rights" => "Droits des groupes",
					"Groups" => "Groupes",
					"ip-addr" => "Adresse IP",
					"Login" => "Identifiant",
					"menu-name" => "Utilisateurs et droits",
					"menu-title" => "Gestionnaire IP",
					"Modification" => "Modification",
					"None" => "Aucun",
					"Return" => "Retour",
					"Right" => "Droit",
					"right-advancedtools" => "Outils avancés",
					"right-history" => "Historique IP",
					"right-ipmgmt" => "Gestion des adresses IP",
					"right-optionsgrpmgmt" => "Gestion des groupes d'options DHCP",
					"right-optionsmgmt" => "Gestion des options DHCP",
					"right-rangemgmt" => "Gestion des ranges IP",
					"right-read" => "Lecture (global)",
					"right-servermgmt" => "Gestion des serveurs DHCP",
					"right-subnetmgmt" => "Gestion des réseaux DHCP",
					"Save" => "Enregistrer",
					"Server" => "Serveur",
					"title-bysubnet" => "Par réseau",
					"title-globalrights" => "Droits globaux",
					"title-ipmrightsmgmt" => "Gestion des droits sur le gestionnaire IP",
					"Type" => "Type",
					"User" => "Utilisateur",
					"user-rights" => "Droits des utilisateurs",
					"Users" => "Utilisateurs",
					"Writing" => "Modification (global)",
				),
				"en" => array(
					"Add" => "Add",
					"All" => "All",
					"confirm-remove-groupright" => "Are you sure you want to remove right for group '%s' ?",
					"confirm-remove-userright" => "Are you sure you want to remove right for user '%s' ?",
					"err-already-exist" => "This element already exists",
					"err-bad-datas" => "Bad request: some fields are missing or wrong",
					"err-no-subnet" => "No subnet found",
					"err-not-found" => "This element doesn't exist (anymore)",
					"Filter" => "Filter",
					"Go" => "Go",
					"group-rights" => "Group rights",
					"Groups" => "Groups",
					"ip-addr" => "Adresse IP",
					"Login" => "Login",
					"menu-name" => "Users and rights",
					"menu-title" => "IP manager",
					"Modification" => "Modifying",
					"None" => "Aucun",
					"Return" => "Return",
					"Right" => "Right",
					"right-advancedtools" => "Advanced tools",
					"right-history" => "IP history",
					"right-ipmgmt" => "IP address management",
					"right-optionsgrpmgmt" => "DHCP option group management",
					"right-optionsmgmt" => "DHCP options management",
					"right-rangemgmt" => "IP range management",
					"right-read" => "Reading (global)",
					"right-servermgmt" => "DHCP server management",
					"right-subnetmgmt" => "Subnet management",
					"Save" => "Save",
					"Server" => "Server",
					"title-bysubnet" => "By subnet",
					"title-globalrights" => "Global rights",
					"title-ipmrightsmgmt" => "IP manager rights management",
					"Type" => "Type",
					"User" => "User",
					"user-rights" => "User rights",
					"Users" => "Users",
					"Writing" => "Modification",
				)
			);
			/*
			$msgidbuf = array();
			foreach ($locales["en"] as $locname => $value) {
				echo sprintf("msgid \"%s\"\nmsgstr \"%s\"\n\n",
					$locname,
					$value);
				if (!in_array($locname,$msgidbuf)) {
					$msgidbuf[] = $locname;
				}

			}
			echo "\n\n==================================\n\n";
			foreach ($locales["fr"] as $locname => $value) {
				echo sprintf("msgid \"%s\"\nmsgstr \"%s\"\n\n",
					$locname,
					$value);
				if (!in_array($locname,$msgidbuf)) {
					$msgidbuf[] = $locname;
				}
			}
			echo "\n\n==================================\n\n";
			for ($i=0;$i<count($msgidbuf);$i++) {
				echo sprintf("_('%s')\n",$msgidbuf[$i]);
			}
			*/
			$this->concat($locales);
		}
	};
?>
