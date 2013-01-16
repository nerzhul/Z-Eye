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
					"err-already-exist" => "Cette règle existe déjà", 
					"err-bad-datas" => "Erreur sur la requête",
					"err-not-found" => "Cette règle n'existe pas ou plus",
					"err-snmpgid-not-found" => "La communauté ou le groupe n'existe pas ou plus",
					"Groups" => "Groupes",
					"None" => "Aucun",
					"Reading" => "Lecture",
					"Right" => "Droit",
					"Save" => "Enregistrer",
					"snmp-community" => "Communauté SNMP",
					"title-rightsbysnmp" => "Par communauté SNMP", 
					"title-switchrightsmgmt" => "Gestion des droits sur les équipements réseau",
					"Users" => "Utilisateurs",
					"Writing" => "Ecriture",
				),
				"en" => array(
					"Add" => "Add",
					"err-already-exist" => "This rule already exists",
					"err-bad-datas" => "Bad request",
					"err-not-found" => "This rule doesn't exist (anymore)",
					"err-snmpgid-not-found" => "This community/group doesn't exist (anymore)",
					"Groups" => "Groups",
					"None" => "Aucun",
					"Reading" => "Lecture",
					"Right" => "Droit",
					"Save" => "Save",
					"snmp-community" => "SNMP community",
					"title-rightsbysnmp" => "By SNMP community",
					"title-switchrightsmgmt" => "Network devices rights management",
					"Users" => "Users",
					"Writing" => "Ecriture",
				)
			);
		}
	};
?>
