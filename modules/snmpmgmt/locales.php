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
    
	class lSNMPMgmt extends FSLocales {
		function lSNMPMgmt() {
			parent::FSLocales();
			$locales = array(
				"fr" => array(
					"Add-community" => "Ajouter une communauté SNMP",
					"confirm-remove-community" => "Êtes vous sûr de vouloir supprimer la communauté ",
					"err-already-exist" => "Cette communauté SNMP existe déjà",
					"err-invalid-data" => "Les données que vous avez entré ne sont pas valides !",
					"err-not-exist" => "Cette communauté SNMP n'existe pas",
					"err-no-snmp-community" => "Aucune communauté SNMP renseignée. Veuillez les configurer",
					"err-read-fail" => "Impossible de lire le fichier /usr/local/etc/netdisco/netdisco.conf",
					"err-readorwrite" => "Il faut choisir lecture et/ou écriture",
					"err-unable-read" => "Impossible de lire le fichier",
					"err-write-fail" => "Impossible d'écrire la configuration de netdisco",
					"menu-title" => "Communautés SNMP",
					"OK" => "Confirmer",
					"Read" => "Lecture",
					"Save" => "Enregistrer",
					"snmp-community" => "Communauté SNMP",
					"snmp-communities" => "Communautés SNMP",
					"snmp-conf" => "Configuration SNMP",
					"snmp-read" => "Communautés en lecture",
					"snmp-timeout" => "Timeout des requêtes",
					"snmp-try" => "Tentatives maximales",
					"snmp-version" => "Version SNMP",
					"snmp-write" => "Communautés en écriture",
					"tooltip-read" => "Spécifie si la communauté permet la lecture",
					"tooltip-write" => "Spécifie si la communauté permet l'écriture",
					"Write" => "Ecriture",
				),
				"en" => array(
					"Add-community" => "Add SNMP community",
					"confirm-remove-community" => "Are you sure you want to remove community ",
					"err-already-exist" => "This SNMP community already exists",
					"err-read-fail" => "Unable to read /usr/local/etc/netdisco/netdisco.conf",
					"err-readorwrite" => "You must choose read and/or write",
					"err-invalid-data" => "Some sent datas are wrong !",
					"err-not-exist" => "This SNMP community doesn't exist",
					"err-no-snmp-community" => "No SNMP community found. Please configure them before use",
					"err-unable-read" => "Unable to read",
					"err-write-fail" => "Unable to write netdisco configuration",
					"Go" => "Go",
					"menu-title" => "SNMP communities",
					"OK" => "Confirm",
					"Read" => "Read",
					"Save" => "Save",
					"snmp-community" => "SNMP community",
					"SNMP-communities" => "SNMP communities",
					"snmp-conf" => "SNMP configuration ",
					"snmp-read" => "Reading communities",
					"snmp-timeout" => "Requests timeout",
					"snmp-try" => "Maximum try",
					"snmp-version" => "SNMP version",
					"snmp-write" => "Writing communities",
					"tooltip-read" => "Specify is community allow reading",
					"tooltip-write" => "Specify is community allow writing",
					"Write" => "Write",
				)
			);
			$this->concat($locales);
		}
	};
?>
