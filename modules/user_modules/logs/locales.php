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
    
	class lLogs extends zLocales {
		function lLogs() {
			$this->locales = array(
				"fr" => array(
					"Collector" => "Collecteur Z-Eye",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux ou la page indisponible",
					"Stats" => "Statistiques",
				),
				"en" => array(
					"Collector" => "Z-Eye Collector",
					"fail-tab" => "Unable to load tab, link may be wrong or page is unavailable",
					"Stats" => "Statistics",
				)
			);
		}
	};
?>