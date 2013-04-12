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

        class rIPManager extends FSRules {
                function rIPManager() { $this->connectedstate = 1; }

                public function showMgmtInterface($activerules = array()) {
			$output = "<tr><td>Supervision DHCP</td>
					<td>".FS::$iMgr->check("mrule_ipmanager_read",array("check" => in_array("mrule_ipmanager_read",$activerules),"label" => "Lire les données"))."</td></tr>
				<tr><td></td>
					<td>".FS::$iMgr->check("mrule_ipmanager_servermgmt",array("check" => in_array("mrule_ipmanager_servermgmt",$activerules),"label" => "Gérer les serveurs"))."</td></tr>";
                        return $output;
                }

                public function canAccessToModule() {
			if (FS::$sessMgr->isConnected() && FS::$sessMgr->hasRight("mrule_ipmanager_read"))
                                return true;
                        return false;
                }
        };
?>
