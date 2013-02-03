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
    
	class lDNSManager extends zLocales {
		function lDNSManager() {
			$this->locales = array(
				"fr" => array(
					"Alone" => "Orphelin",
					"err-invalid-req" => "Requête invalide !",
					"err-no-zone" => "Aucune zone DNS spécifiée !",
					"expert-tools" => "Outils avancés",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"Filter" => "Filtrer",
					"found-records" => "Enregistrements obsolètes trouvés !",
					"menu-title" => "Supervision DNS",
					"no-data-found" => "Aucune donnée collectée",
					"no-found-records" => "Aucun enregistrement obsolète trouvé",
					"Others" => "Autres",
					"Record" => "Enregistrement",
					"Search" => "Rechercher",
					"Servers" => "Serveur(s)",
					"Stats" => "Statistiques",
					"title-dns" => "Supervision DNS",
					"title-old-records" => "Recherche d'enregistrements obsolètes",
					"Value" => "Valeur",
				),
				"en" => array(
					"Alone" => "Alone",
					"err-invalid-req" => "Invalid request !",
					"err-no-zone" => "No DNS zone specified !",
					"expert-tools" => "Expert tools",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"Filter" => "Filter",
					"found-records" => "Old records found !",
					"menu-title" => "DNS supervision",
					"no-data-found" => "No data collected",
					"no-found-records" => "No old records found",
					"Others" => "Others",
					"Record" => "Record",
					"Search" => "Search",
					"Servers" => "Server(s)",
					"Stats" => "Statistics",
					"title-dns" => "DNS supervision",
					"title-old-records" => "Search old records",
					"Value" => "Value",
				)
			);
		}
	};
?>
