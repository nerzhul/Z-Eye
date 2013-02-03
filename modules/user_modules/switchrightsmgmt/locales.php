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

	require_once(dirname(__FILE__)."/../../../lib/FSS/objects/Locales.FS.class.php");
    
	class lSwitchRightsMgmt extends zLocales {
		function lSwitchRightsMgmt() {
			$this->locales = array(
				"fr" => array(
					"Add" => "Ajouter",
					"All" => "Tous",
					"device" => "Equipement",
					"err-already-exist" => "Cette règle existe déjà", 
					"err-bad-datas" => "Erreur sur la requête",
					"err-no-snmp-community" => "Aucune communaut\xc3\xa9 SNMP renseign\xc3\xa9e. Veuillez les configurer",
					"err-not-found" => "Cette règle n'existe pas ou plus",
					"err-snmpgid-not-found" => "La communauté ou le groupe n'existe pas ou plus",
					"Filter" => "Filtrer",
					"Go" => "Aller",
					"group-rights" => "Droits des groupes",
					"Groups" => "Groupes",
					"menu-title" => "Equipements réseau (droits)",
					"None" => "Aucun",
					"Reading" => "Consultation (global)",
					"Read-port-stats" => "Statistiques des ports<br />(Consultation)",
					"Read-switch-details" => "Caractéristiques de l'équipement<br />(Consultation)",
					"Read-switch-modules" => "Liste des modules<br />(Consultation)",
					"Read-switch-vlan" => "Liste des vlans d'un équipement<br />(Consultation)",
					"Right" => "Droit",
					"Save" => "Enregistrer",
					"snmp-community" => "Communauté SNMP",
					"title-rightsbysnmp" => "Par communauté SNMP", 
					"title-rightsbyswitch" => "Par équipement",
					"title-switchrightsmgmt" => "Gestion des droits sur les équipements réseau",
					"user-rights" => "Droits des utilisateurs",
					"Users" => "Utilisateurs",
					"Writing" => "Modification (global)",
					"Write-port-mon" => "Monitoring des ports<br />(Modification)",
				),
				"en" => array(
					"Add" => "Add",
					"All" => "All",
					"device" => "Device",
					"err-already-exist" => "This rule already exists",
					"err-bad-datas" => "Bad request",
					"err-no-snmp-community" => "No SNMP community found. Please configure them before use",
					"err-not-found" => "This rule doesn't exist (anymore)",
					"err-snmpgid-not-found" => "This community/group doesn't exist (anymore)",
					"Filter" => "Filter",
					"Go" => "Go",
					"group-rights" => "Group rights",
					"Groups" => "Groups",
					"menu-title" => "Network devices (rights)",
					"None" => "Aucun",
					"Reading" => "Reading (global)",
					"Read-port-stats" => "Reading<br />(port stats)",
					"Read-switch-details" => "Reading<br />(device features)",
					"Read-switch-modules" => "Reading<br />(device modules list)",
					"Read-switch-vlan" => "Reading<br />(device vlan list)",
					"Right" => "Right",
					"Save" => "Save",
					"snmp-community" => "SNMP community",
					"title-rightsbysnmp" => "By SNMP community",
					"title-rightsbyswitch" => "By device",
					"title-switchrightsmgmt" => "Network devices rights management",
					"user-rights" => "User rights",
					"Users" => "Users",
					"Writing" => "Modification",
					"Write-port-mon" => "Modification (port monitoring)",
				)
			);
		}
	};
?>
