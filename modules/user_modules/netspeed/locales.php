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
    
	class lNetSpeed extends zLocales {
		function lNetSpeed() {
			$this->locales = array(
				"fr" => array(
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux, ou la page indisponible",
					"link-state" => "Etat des liens de",
					"main-map" => "Carte principale",
					"menu-title" => "Cartographie réseau",
					"net-map" => "Carte du réseau",
					"net-map-full" => "Carte complète du réseau",
					"precise-map" => "Carte détaillée",
					"title-bw" => "Analyse des Débits",
				),
				"en" => array(
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"link-state" => "Links state of",
					"main-map" => "Main map",
					"menu-title" => "Network maps",
					"net-map" => "CNetwork map",
					"net-map-full" => "Full network map",
					"precise-map" => "Precise map",
					"title-bw" => "Bandwidth analysis",
				)
			);
		}
	};
?>
