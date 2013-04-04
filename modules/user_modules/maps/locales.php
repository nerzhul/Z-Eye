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
    
	class lMaps extends zLocales {
		function lMaps() {
			parent::zLocales();
			$locales = array(
				"fr" => array(
					"err-no-tab" => "Onglet invalide",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux, ou la page indisponible",
					"icinga-map" => "Etat des systèmes",
					"link-state" => "Etat des liens de",
					"menu-title" => "Cartes",
					"net-map" => "Réseau",
					"net-map-full" => "Réseau (complet)",
					"title-maps" => "Cartes",
				),
				"en" => array(
					"err-no-tab" => "Bad tab",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"icinga-map" => "System states",
					"link-state" => "Links state of",
					"menu-title" => "Maps",
					"net-map" => "Network",
					"net-map-full" => "Network (full)",
					"title-maps" => "Maps",
				)
			);
			$this->concat($locales);
		}
	};
?>
