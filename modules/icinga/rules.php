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

	class rIcinga extends FSRules {
		function rIcinga() {}

		public function showMgmtInterface($activerules = array()) {
			$output = FS::$iMgr->ruleLines("Monitoring de services",$activerules,array(
				array("Lire les données",			"mrule_icinga_read"),
				array("Modifier les données",			"mrule_icinga_write"),
				array("Modifier les commandes",				"mrule_icinga_cmd_write"),
				array("Modifier les groupes de contacts",		"mrule_icinga_ctg_write"),
				array("Modifier les contacts",				"mrule_icinga_ctg_write"),
				array("Modifier les périodes temporelles",	"mrule_icinga_tp_write"),
				array("Modifier les services",				"mrule_icinga_srv_write"),
				array("Modifier les groupes d'hôtes",		"mrule_icinga_hg_write"),
				array("Modifier les hôtes",			"mrule_icinga_host_write")
			));
                        return $output;
		}

		public function canAccessToModule() {
			if(FS::$sessMgr->isConnected() && FS::$sessMgr->hasRight("mrule_icinga_read"))
				return true;
			return false;
		}
	};
?>
