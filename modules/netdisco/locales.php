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

    require_once(dirname(__FILE__)."/../../lib/FSS/objects/Locales.FS.class.php");
    
	class lNetdisco extends zLocales {
		function lNetdisco() {
			parent::zLocales();
			$locales = array(
				"fr" => array(
					"Add-community" => "Ajouter une communauté SNMP",
					"database" => "Base de données",
					"device-expiration" => "Expiration des périphériques",
					"dns-suffix" => "Suffixe DNS",
					"err-already-exist" => "Cette communauté SNMP existe déjà",
					"err-invalid-data" => "Les données que vous avez entré ne sont pas valides !",
					"err-not-exist" => "Cette communauté SNMP n'existe pas",
					"err-no-snmp-community" => "Aucune communauté SNMP renseignée. Veuillez les configurer",
					"err-read-fail" => "Impossible de lire le fichier /usr/local/etc/netdisco/netdisco.conf",
					"err-readorwrite" => "Il faut choisir lecture et/ou écriture",
					"err-unable-read" => "Impossible de lire le fichier",
					"err-write-fail" => "Impossible d'écrire la configuration de netdisco",
					"General" => "Général",
					"Go" => "Aller",
					"global-conf" => "Configuration globale",
					"main-node" => "Noeud principal",
					"menu-title" => "Moteur de collecte Netdisco",
					"mod-ok" => "Modification prise en compte",
					"node-expiration" => "Expiration des noeuds",
					"pg-db" => "Nom de la base de données",
					"pg-host" => "Hôte PostGreSQL",
					"pg-pwd" => "Mot de passe",
					"pg-user" => "Utilisateur PostGreSQL",
					"Read" => "Lecture",
					"Save" => "Enregistrer",
					"snmp-community" => "Communauté SNMP",
					"SNMP-communities" => "Communautés SNMP",
					"snmp-conf" => "Configuration SNMP",
					"snmp-read" => "Communautés en lecture",
					"snmp-timeout" => "Timeout des requêtes",
					"snmp-try" => "Tentatives maximales",
					"snmp-version" => "Version SNMP",
					"snmp-write" => "Communautés en écriture",
					"timer-conf" => "Configuration des timers",
					"title-netdisco" => "Management du service de découverte Netdisco",
					"tooltip-devicetimeout" => "Durée en jours après laquelle expirent les équipements", 
					"tooltip-dnssuffix" => "Suffixe DNS de recherche lorsqu'un nom court DNS est découvert sur un équipement",
					"tooltip-firstnode" => "Premier noeud. Sert de référence à netdisco pour découvrir l'ensemble des équipements",
					"tooltip-nodetimeout" => "Durée en jour après laquelle expirent les noeuds (adresses IP, MAC, Netbios) découvertes",
					"tooltip-read" => "Spécifie si la communauté permet la lecture",
					"tooltip-snmptimeout" => "Durée d'expiration des requêtes SNMP (en secondes)",		
					"tooltip-snmptry" => "Nombre de tentatives pour contacter un équipement",
					"tooltip-write" => "Spécifie si la communauté permet l'écriture",
					"Write" => "Ecriture",
				),
				"en" => array(
					"Add-community" => "Add SNMP community",
					"database" => "Database",
					"device-expiration" => "Device expiration",
					"dns-suffix" => "DNS suffix",
					"err-already-exist" => "This SNMP community already exists",
					"err-read-fail" => "Unable to read /usr/local/etc/netdisco/netdisco.conf",
					"err-readorwrite" => "You must choose read and/or write",
					"err-invalid-data" => "Some sent datas are wrong !",
					"err-not-exist" => "This SNMP community doesn't exist",
					"err-no-snmp-community" => "No SNMP community found. Please configure them before use",
					"err-unable-read" => "Unable to read",
					"err-write-fail" => "Unable to write netdisco configuration",
					"General" => "General",
					"Go" => "Go",
					"global-conf" => "Global configuration",
					"main-node" => "Main node",
					"menu-title" => "netdisco collect engine",
					"mod-ok" => "Modification saved",
					"node-expiration" => "Node expiration",
					"pg-db" => "Database name",
					"pg-host" => "PostGreSQL host",
					"pg-pwd" => "Password",
					"pg-user" => "PostGreSQL user",
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
					"timer-conf" => "Timer configuration",
					"title-netdisco" => "Netdisco discovering service management",
					"tooltip-devicetimeout" => "Expiration time for devices (in days)",
					"tooltip-dnssuffix" => "DNS suffix when searching DNS short name discovered by netdisco",
					"tooltip-firstnode" => "First node. Reference for discovering all devices",
					"tooltip-nodetimeout" => "Expiration time for nodes (IP/MAC addresses, Netdisco), in days",
					"tooltip-read" => "Specify is community allow reading",
					"tooltip-snmptimeout" => "Expiration time for SNMP requests (in seconds)",
					"tooltip-snmptry" => "Try number for contacting device",
					"tooltip-write" => "Specify is community allow writing",
					"Write" => "Write",
				)
			);
			$this->concat($locales);
		}
	};
?>
