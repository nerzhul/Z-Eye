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

	class lDefault extends zLocales {
		function lDefault() {
			$this->locales = array(
				"fr" => array(
					"Attack" => "Attaques",
					"err-detect-atk" => "Menace détectée !",
					"err-icinga" => "Erreur de services rapportées par Icinga",
					"err-icinga-off" => "Service de monitoring OFFLINE",
					"err-net" => "Problème(s) de bande passante",
					"err-security" => "Attaques des 60 dernières minutes",
					"inc-bw" => "Débit Entrant",
					"ipaddr" => "Adresse IP",
					"Link" => "Lien",
					"out-bw" => "Débit Sortant",
					"state-net" => "Etat du réseau",
					"state-security" => "Etat de la sécurité",
					"state-srv" => "Etat des services",
				),
				"en" => array(
					"Attack" => "Attacks",
					"err-detect-atk" => "Threat detected !",
					"err-icinga" => "Icinga services report",
					"err-icinga-off" => "Service Monitor OFFLINE",
					"err-net" => "Bandwidth problems",
					"err-security" => "Last 60 minutes' attacks",
					"inc-bw" => "Input Bandwidth",
					"ipaddr" => "IP Address",
					"Link" => "Link",
					"out-bw" => "Output bandwidth",
					"state-net" => "Network state",
					"state-security" => "Security state",
					"state-srv" => "Services state",
				)
			);
		}
	};
?>
