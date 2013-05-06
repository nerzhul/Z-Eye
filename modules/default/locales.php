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

	final class lDefault extends FSLocales {
		function __construct() {
			parent::__construct();
			$locales = array(
				"fr" => array(
					"Attack" => "Attaques",
					"Availability" => "Disponibilité",
					"CRITICAL" => "CRITIQUE",
					"DOWN" => "INJOIGNABLE",
					"Duration" => "Durée",
					"err-detect-atk" => "Menace détectée !",
					"err-icinga" => "Erreur de services rapportées par Icinga",
					"err-icinga-off" => "Service de monitoring OFFLINE",
					"err-net" => "Problème(s) de bande passante",
					"err-security" => "Attaques des 60 dernières minutes",
					"Host" => "Hôte",
					"inc-bw" => "Débit Entrant",
					"ipaddr" => "Adresse IP",
					"Link" => "Lien",
					"menu-name" => "Supervision",
					"menu-title" => "Speed reporting",
					"out-bw" => "Débit Sortant",
					"Service" => "Service",
					"Since-icinga-start" => "Depuis le démarrage du processus Icinga",
					"State" => "Statut",
					"state-net" => "Etat du réseau",
					"state-security" => "Etat de la sécurité",
					"state-srv" => "Etat des services",
					"Status-information" => "Informations de statut",
					"WARN" => "ATTENTION",
				),
				"en" => array(
					"Attack" => "Attacks",
					"Availability" => "Availability",
					"CRITICAL" => "CRITICAL",
					"DOWN" => "DOWN",
					"Duration" => "Duration",
					"err-detect-atk" => "Threat detected !",
					"err-icinga" => "Icinga services report",
					"err-icinga-off" => "Service Monitor OFFLINE",
					"err-net" => "Bandwidth problems",
					"err-security" => "Last 60 minutes' attacks",
					"Host" => "Host",
					"inc-bw" => "Input Bandwidth",
					"ipaddr" => "IP Address",
					"Link" => "Link",
					"menu-name" => "Supervision",
					"menu-title" => "Speed reporting",
					"out-bw" => "Output bandwidth",
					"Service" => "Service",
					"Since-icinga-start" => "Depuis le démarrage du processus Icinga",
					"State" => "State",
					"state-net" => "Network state",
					"state-security" => "Security state",
					"state-srv" => "Services state",
					"Status-information" => "Status information",
					"WARN" => "WARNING",
				)
			);
			$this->concat($locales);
		}
	};
?>
