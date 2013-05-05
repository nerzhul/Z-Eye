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
    
	class lLogs extends FSLocales {
		function lLogs() {
			parent::FSLocales();
			$locales = array(
				"fr" => array(
					"Date" => "Date",
					"Entry" => "Entrée",
					"err-no-logs" => "Aucun log collecté",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux ou la page indisponible",
					"Filter" => "Filtrer",
					"Level" => "Niveau",
					"menu-name" => "Moteur Z-Eye",
					"menu-title" => "Admin Z-Eye",
					"Module" => "Module",
					"Service" => "Service Z-Eye",
					"Stats" => "Statistiques",
					"User" => "Utilisateur",
					"webapp" => "Application Web",
				),
				"en" => array(
					"Date" => "Date",
					"Entry" => "Entry",
					"err-no-logs" => "No log found",
					"fail-tab" => "Unable to load tab, link may be wrong or page is unavailable",
					"Filter" => "Filter",
					"Level" => "Level",
					"menu-name" => "Z-Eye Engine",
					"menu-title" => "Z-Eye Admin",
					"Module" => "Module",
					"Service" => "Z-Eye Service",
					"Stats" => "Statistics",
					"User" => "User",
					"webapp" => "Web application",
				)
			);
			$this->concat($locales);
		}
	};
?>
