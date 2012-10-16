<?php
	/*
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
    
	class lNetdisco extends zLocales {
		function lNetdisco() {
			$this->locales = array(
				"fr" => array(
					"database" => "Base de données",
					"device-expiration" => "Expiration des périphériques",
					"dns-suffix" => "Suffixe DNS",
					"err-invalid-data" => "Les données que vous avez entré ne sont pas valides !",
					"err-unable-read" => "Impossible de lire le fichier",
					"global-conf" => "Configuration globale",
					"main-node" => "Noeud principal",
					"mod-ok" => "Modification prise en compte",
					"node-expiration" => "Expiration des noeuds",
					"pg-db" => "Nom de la base de données",
					"pg-host" => "Hôte PostGreSQL",
					"pg-pwd" => "Mot de passe",
					"pg-user" => "Utilisateur PostGreSQL",
					"Save" => "Enregistrer",
					"snmp-conf" => "Configuration SNMP",
					"snmp-read" => "Communautés en lecture",
					"snmp-timeout" => "Timeout des requêtes",
					"snmp-try" => "Tentatives maximales",
					"snmp-version" => "Version SNMP",
					"snmp-write" => "Communautés en écriture",
					"timer-conf" => "Configuration des timers",
					"title-netdisco" => "Management du service de découverte Netdisco",
				),
				"en" => array(
					"database" => "Database",
					"device-expiration" => "Device expiration",
					"dns-suffix" => "DNS suffix",
					"err-invalid-data" => "Some sent datas are wrong !",
					"err-unable-read" => "Unable to read",
					"global-conf" => "Global configuration",
					"main-node" => "Main node",
					"mod-ok" => "Modification saved",
					"node-expiration" => "Node expiration",
					"pg-db" => "Database name",
					"pg-host" => "PostGreSQL host",
					"pg-pwd" => "Password",
					"pg-user" => "PostGreSQL user",
					"Save" => "Save",
					"snmp-conf" => "SNMP configuration ",
					"snmp-read" => "Reading communities",
					"snmp-timeout" => "Requests timeout",
					"snmp-try" => "Maximum try",
					"snmp-version" => "SNMP version",
					"snmp-write" => "Writing communities",
					"timer-conf" => "Timer configuration",
					"title-netdisco" => "Netdisco discovering service management",
				)
			);
		}
	};
?>
