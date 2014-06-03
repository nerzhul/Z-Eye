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
    
	final class lSnort extends FSLocales {
		function __construct() { 
			parent::__construct();	
			$locales = array(
				"fr" => array(
					"Activate" => "Activer",
					"bad-data" => "Donnée(s) entrée(s) invalide(s)",
					"Database" => "Base de données",
					"data-storage" => "Stockage des données",
					"en-smtp-sensor" => "Activer les sondes SMTP",
					"en-ssh-sensor" => "Activer les sondes SSH",
					"en-telnet-sensor" => "Activer les sondes Telnet",
					"en-tse-sensor" => "Activer les sondes TSE",
					"fail-cron-wr" => "Impossible d'écrire le fichier de configuration ".dirname(__FILE__)."/../../datas/system/snort.crontab",
					"fail-snort-conf-wr" => "Impossible d'écrire le fichier de configuration snort.z_eye.conf",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux, ou la page indisponible",
					"General" => "Général",
					"lan-list" => "Liste des LANs",
					"menu-name" => "Moteur Z-Eye",
					"menu-title" => "Moteur IDS SNORT",
					"mod-in-progress" => "Modification en cours...",
					"page-title" => "Management de l'IDS SNORT",
					"Password" => "Mot de passe",
					"pg-host" => "Hôte PgSQL",
					"port-ftp" => "Ports FTP",
					"port-http" => "Ports HTTP",
					"port-imap" => "Ports IMAP",
					"port-oracle" => "Ports Oracle",
					"port-pop" => "Ports POP",
					"port-sip" => "Ports SIP",
					"port-smtp" => "Ports SMTP",
					"port-ssh" => "Ports SSH",
					"prev-hour" => "Durée antécédente",
					"Register" => "Enregistrer",
					"Remote" => "Accès distant",
					"Reports" => "Rapports",
					"sent-hour" => "Heure d'envoi",
					"sql-oracle" => "Serveurs Oracle",
					"srv-dns" => "Serveurs DNS",
					"srv-ftp" => "Serveurs FTP",
					"srv-http" => "Serveurs HTTP",
					"srv-imap" => "Serveurs IMAP",
					"srv-oracle" => "Serveurs Oracle",
					"srv-pop" => "Serveurs POP",
					"srv-sip" => "Serveurs SIP",
					"srv-smtp" => "Serveurs SMTP",
					"srv-snmp" => "Serveurs SNMP",
					"srv-sql" => "Serveurs SQL",
					"srv-ssh" => "Serveurs SSH",
					"srv-telnet" => "Serveurs Telnet",
					"srv-tse" => "Serveurs TSE",
					"title-nightreport" => "Rapports nocturnes",
					"title-we" => "Rapports de fin de semaine",
					"tooltip-ipv4" => "<b>Requis: </b>Adresse IPv4, CIDR IPv4.<br /><b>Séparateur:</b> virgule",
					"tooltip-port" => "<b>Requis: </b>Numéro de port (1-65535).<br /><b>Séparateur:</b> virgule",
					"tooltip-prev-hour" => "Nombres d'heures précédant l'heure d'envoi sur lesquelles collecter des données",
					"User" => "Utilisateur",
				),
				"en" => array(
					"Activate" => "Activate",
					"bad-data" => "Invalid data(s) sent",
					"data-storage" => "Data storage",
					"Database" => "Database",
					"en-smtp-sensor" => "Enable SMTP sensors",
					"en-ssh-sensor" => "Enable SSH sensors",
					"en-telnet-sensor" => "Enable Telnet sensors",
					"en-tse-sensor" => "Enable TSE sensors",
					"fail-cron-wr" => "Unable to write to ".dirname(__FILE__)."/../../datas/system/snort.crontab",
					"fail-snort-conf-wr" => "Unable to write to snort.z_eye.conf",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"lan-list" => "LANs list",
					"menu-name" => "Z-Eye Engine",
					"menu-title" => "SNORT IDS engine",
					"General" => "General",
					"mod-in-progress" => "Modification in progress...",
					"page-title" => "SNORT IDS Management",
					"Password" => "Password",
					"pg-host" => "PgSQL host",
					"port-ftp" => "FTP ports",
					"port-http" => "HTTP ports",
					"port-imap" => "IMAP ports",
					"port-oracle" => "Oracle ports",
					"port-pop" => "POP ports",
					"port-sip" => "SIP ports",
					"port-smtp" => "SMTP ports",
					"port-ssh" => "SSH ports",
					"prev-hour" => "Prev. Hours to begin",
					"Register" => "Register",
					"Remote" => "Remote",
					"Reports" => "Reports",
					"sent-hour" => "Sent time",
					"sql-oracle" => "Oracle servers",
					"srv-dns" => "DNS servers",
					"srv-ftp" => "FTP servers",
					"srv-http" => "HTTP servers",
					"srv-imap" => "IMAP servers",
					"srv-oracle" => "Oracle servers",
					"srv-pop" => "POP servers",
					"srv-sip" => "SIP servers",
					"srv-smtp" => "SMTP servers",
					"srv-snmp" => "SNMP servers",
					"srv-sql" => "SQL servers",
					"srv-ssh" => "SSH servers",
					"srv-telnet" => "Telnet servers",
					"srv-tse" => "TSE servers",
					"title-nightreport" => "Nightly reports",
					"title-we" => "Week-end reports",
					"tooltip-ipv4" => "<b>Require: </b>IPv4 address, IPv4 CIDR.<br /><b>Separator:</b> decimal point",
					"tooltip-port" => "<b>Require: </b>Port number (1-65535).<br /><b>Separator:</b> decimal point",
					"tooltip-prev-hour" => "This is the number of hours before the report date to collect datas",
					"User" => "User",
				)
			);
			$this->concat($locales);
		}
	};
?>
