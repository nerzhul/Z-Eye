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

        class rGroupMgmt extends FSRules {
                function rGroupMgmt() { $this->connectedstate = 1; }

                public function showMgmtInterface($activerules = array()) {
			$output = FS::$iMgr->ruleLines("Gestion des groupes",$activerules,array(
				array("Lire les données", 	"mrule_groupmgmt_read"),
				array("Modifier les données", 	"mrule_groupmgmt_write")
			));
                        return $output;
                }

                public function canAccessToModule() {
			if (FS::$sessMgr->isConnected() && FS::$sessMgr->hasRight("mrule_groumgmt_read"))
                                return true;
                        return false;
                }
        };
?>