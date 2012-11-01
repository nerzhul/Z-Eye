<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	
	class iIcinga extends genModule{
		function iIcinga() { parent::genModule(); $this->loc = new lConnect(); }
		public function Load() {
			$output = $this->showTabPanel();
			return $output;
		}
		
		private function showTabPanel() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid,$this->loc->s("General"),$sh);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid,$this->loc->s("Hosts"),$sh);
				$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid,$this->loc->s("Hostgroups"),$sh);
				$output .= FS::$iMgr->tabPanElmt(4,"index.php?mod=".$this->mid,$this->loc->s("Services"),$sh);
				$output .= FS::$iMgr->tabPanElmt(5,"index.php?mod=".$this->mid,$this->loc->s("Timeperiods"),$sh);
				$output .= FS::$iMgr->tabPanElmt(6,"index.php?mod=".$this->mid,$this->loc->s("Contacts"),$sh);
				$output .= FS::$iMgr->tabPanElmt(7,"index.php?mod=".$this->mid,$this->loc->s("Contactgroups"),$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
				return $output;
			}
			
			if(!$sh) $sh = 1;
			
			switch($sh) {
				case 1: $output .= $this->showGeneralTab(); break;
				case 2: $output .= $this->showHostsTab(); break;
				case 3: $output .= $this->showHostgroupsTab(); break;
				case 4: $output .= $this->showServicesTab(); break;
				case 5: $output .= $this->showTimeperiodsTab(); break;
				case 6: $output .= $this->showContactsTab(); break;
				case 7: $output .= $this->showContactgroupsTab(); break;
			return $output;
		}
		
		private function showGeneralTab() {
			$output = "";
			
			return $output;
		}
		
		private function showHostsTab() {
			$output = "";
			
			return $output;
		}
		
		private function showHostgroupsTab() {
			$output = "";
			
			return $output;
		}
		
		private function showServicesTab() {
			$output = "";
			
			return $output;
		}
		
		private function showTimeperiodsTab() {
			$output = "";
			
			return $output;
		}
		
		private function showContactsTab() {
			$output = "";
			
			return $output;
		}
		
		private function showContactgroupsTab() {
			$output = "";
			
			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: break;
			}
		}
	};
?>
