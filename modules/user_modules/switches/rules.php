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

        require_once(dirname(__FILE__)."/../../../lib/FSS/objects/Rules.FS.class.php");
        class rSwitchMgmt extends zRules {
                function rSwitchMgmt() { $this->connectedstate = 1; }

                public function showMgmtInterface($activerules = array()) {
			$output = "<tr><td>Gestion des Switches</td>";
                        $output .= "<td>".FS::$iMgr->addCheck("mrule_switches_read",in_array("mrule_switches_read",$activerules),"Lire les données")."</td></tr>
			<tr><td></td><td>".FS::$iMgr->addCheck("mrule_switches_write",in_array("mrule_switches_write",$activerules),"Modifier les données")."</td></tr>";
                        return $output;
                }

                public function canAccessToModule() {
			if(FS::$sessMgr->isConnected() && FS::$sessMgr->hasRight("mrule_switches_read"))
                                return true;
                        return false;
                }
        };
?>
