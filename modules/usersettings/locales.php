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

	final class lUserSettings extends FSLocales {
		function __construct() {
			parent::__construct();
			$locales = array(
				"fr" => array(
					"Account-parameters" => "Paramètres du compte",
					"Android-options" => "Options Android",
					"API-Key" => "Clef d'API",
					"App-Lang" => "Langage de l'interface",
					"Disconnect-after" => "Déconnexion après",
					"Enable-Monitoring" => "Activer l'onglet monitoring",
					"err-bad-lang" => "Langue non supportée",
					"menu-name" => "",
					"tooltip-disconnect-after" => "Cette valeur spécifie une durée maximale d'inactivité avant que Z-Eye ne déconnecte votre compte",
				),
				"en" => array(
					"Account-parameters" => "Account parameters",
					"Android-options" => "Android options",
					"API-Key" => "API key",
					"App-Lang" => "Interface language",
					"Disconnect-after" => "Disconnect after",
					"Enable-Monitoring" => "Enable monitoring tab",
					"err-bad-lang" => "Unsupported language",
					"menu-name" => "",
					"tooltip-disconnect-after" => "This value set a maximum inactivity time before Z-Eye disconnect your account",
				)
			);
			$this->concat($locales);
		}
	};
?>
