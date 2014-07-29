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

	//final class lDefault extends FSLocales {
	new lDefault();
	final class lDefault {
		function __construct() {
			//parent::__construct();
			$locales = array(
				"fr" => array(
					"menu-name" => "Supervision",
					"menu-title" => "Speed reporting",
				),
				"en" => array(
					"menu-name" => "Supervision",
					"menu-title" => "Speed reporting",
				)
			);
			$msgidbuf = array();
			foreach ($locales["en"] as $locname => $value) {
				echo sprintf("msgid \"%s\"\nmsgstr \"%s\"\n\n",
					$locname,
					$value);
				if (!in_array($locname,$msgidbuf)) {
					$msgidbuf[] = $locname;
				}

			}
			echo "\n\n==================================\n\n";
			foreach ($locales["fr"] as $locname => $value) {
				echo sprintf("msgid \"%s\"\nmsgstr \"%s\"\n\n",
					$locname,
					$value);
				if (!in_array($locname,$msgidbuf)) {
					$msgidbuf[] = $locname;
				}
			}
			echo "\n\n==================================\n\n";
			for ($i=0;$i<count($msgidbuf);$i++) {
				echo sprintf("_('%s')\n",$msgidbuf[$i]);
			}
			$this->concat($locales);
		}
	};
?>
