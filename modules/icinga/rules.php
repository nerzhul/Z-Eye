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
		function rIcinga() { $this->connectedstate = 1; }

		public function showMgmtInterface($activerules = array()) {
			$output = "<tr><td>Monitoring de services</td>";
                        $output .= "<td>".FS::$iMgr->check("mrule_icinga_read",array("check" => in_array("mrule_icinga_read",$activerules),"label" => "Lire les données"))."</td></tr>";
                        $output .= "<tr><td></td><td>".FS::$iMgr->check("mrule_icinga_write",array("check" => in_array("mrule_icinga_write",$activerules),"label" => "Modifier les données"))."</td></tr>";
                        $output .= "<tr><td></td><td>".FS::$iMgr->check("mrule_icinga_cmd_write",array("check" => in_array("mrule_icinga_cmd_write",$activerules),"label" => "Modifier les commandes"))."</td></tr>";
                        $output .= "<tr><td></td><td>".FS::$iMgr->check("mrule_icinga_ctg_write",array("check" => in_array("mrule_icinga_ctg_write",$activerules),"label" => "Modifier les groupes de contacts"))."</td></tr>";
                        $output .= "<tr><td></td><td>".FS::$iMgr->check("mrule_icinga_ct_write",array("check" => in_array("mrule_icinga_ct_write",$activerules),"label" => "Modifier les contacts"))."</td></tr>";
                        $output .= "<tr><td></td><td>".FS::$iMgr->check("mrule_icinga_tp_write",array("check" => in_array("mrule_icinga_tp_write",$activerules),"label" => "Modifier les périodes temporelles"))."</td></tr>";
                        $output .= "<tr><td></td><td>".FS::$iMgr->check("mrule_icinga_srv_write",array("check" => in_array("mrule_icinga_srv_write",$activerules),"label" => "Modifier les services"))."</td></tr>";
                        $output .= "<tr><td></td><td>".FS::$iMgr->check("mrule_icinga_hg_write",array("check" => in_array("mrule_icinga_hg_write",$activerules),"label" => "Modifier les groupes d'hôtes"))."</td></tr>";
                        $output .= "<tr><td></td><td>".FS::$iMgr->check("mrule_icinga_host_write",array("check" => in_array("mrule_icinga_host_write",$activerules),"label" => "Modifier les hôtes"))."</td></tr>";
                        return $output;
		}

		public function canAccessToModule() {
			if(FS::$sessMgr->isConnected() && FS::$sessMgr->hasRight("mrule_icinga_read"))
				return true;
			return false;
		}
	};
?>
