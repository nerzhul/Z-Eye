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
		function iIcinga() { parent::genModule(); $this->loc = new lIcinga(); }
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
				$output .= FS::$iMgr->tabPanElmt(8,"index.php?mod=".$this->mid,$this->loc->s("Commands"),$sh);
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
				case 8: $output .= $this->showCommandTab(); break;
			}
			return $output;
		}
		
		private function showGeneralTab() {
			$output = "";
			
			return $output;
		}
		
		private function showHostsTab() {
			$output = "";
			
			$formoutput = "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("is-template"),"istemplate",true);
			//$formoutput .= template list
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","");
			
			// Checks
			$formoutput .= $this->genCommandList("checkcommand");
			//$formoutput .= checkperiod
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("check-interval"),"checkintval",3);
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("retry-check-interval"),"retcheckintval",1);
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("max-check"),"maxcheck",10);
			
			// Global
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("eventhdl-en"),"eventhdlen",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("flap-en"),"flapen",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("failpredict-en"),"failpreden",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("perfdata"),"perfdata",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("retainstatus"),"retstatus",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("retainnonstatus"),"retnonstatus",true);
			
			// Notifications
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("notif-en"),"notifen",true);
			// $formoutput .= notifperiod
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("notif-interval"),"notifintval",0);
			// $formoutput .= notifoptions
			// $formoutput .= contactgroups
			$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
			$formoutput .= "</table>";
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-host"));
			
			return $output;
		}
		
		private function showHostgroupsTab() {
			$output = "";
			
			return $output;
		}
		
		private function showServicesTab() {
			$output = "";
			
			$formoutput = "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("is-template"),"istemplate",true);
			//$formoutput .= template list
			
			// Global
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","");
			
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("active-check-en"),"actcheck",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("passive-check-en"),"pascheck",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("parallel-check"),"parcheck",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("obs-over-srv"),"obsess",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("check-freshness"),"freshness",false);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("notif-en"),"notifen",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("eventhdl-en"),"eventhdlen",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("flap-en"),"flapen",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("failpredict-en"),"failpreden",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("perfdata"),"perfdata",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("retainstatus"),"retstatus",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("retainnonstatus"),"retnonstatus",true);
			
			// Checks
			//$formoutput .= checkperiod
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("check-interval"),"checkintval",3);
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("retry-check-interval"),"retcheckintval",1);
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("max-check"),"maxcheck",10);
			
			// Notifications
			// $formoutput .= notifperiod
			// $formoutput .= notifoptions
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("notif-interval"),"notifintval",0);
			// $formoutput .= contactgroups
			$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
			$formoutput .= "</table>";
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-service"));
			
			return $output;
		}
		
		private function showTimeperiodsTab() {
			$output = "";
			
			$formoutput = "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("is-template"),"istemplate",true);
			//$formoutput .= template list
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","");
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Alias"),"alias","");
			$formoutput .= "<tr><td>".$this->loc->s("Monday")."</td><td>".FS::$iMgr->hourlist("mh","mm")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Tuesday")."</td><td>".FS::$iMgr->hourlist("tuh","tum")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Wednesday")."</td><td>".FS::$iMgr->hourlist("wh","wm")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Thursday")."</td><td>".FS::$iMgr->hourlist("thh","thm")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Friday")."</td><td>".FS::$iMgr->hourlist("fh","fm")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Saturday")."</td><td>".FS::$iMgr->hourlist("sah","sam")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Sunday")."</td><td>".FS::$iMgr->hourlist("suh","sum")."</td></tr>";
			$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
			$formoutput .= "</table>";
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-timeperiod"));
			return $output;
		}
		
		private function showContactsTab() {
			$output = "";
			
			$formoutput = "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("is-template"),"istemplate",true);
			//$formoutput .= template list
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","");
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Email"),"mail","");
			//$formoutput .= srvnotifperiod
			//$formoutput .= srvnotifoptions
			$formoutput .= $this->genCommandList("srvnotifcmd");
			//$formoutput .= hostnotifperiod
			//$formoutput .= hostnotifoptions
			$formoutput .= $this->genCommandList("hostnotifcmd");
			$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
			$formoutput .= "</table>";
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-contact"));
			
			return $output;
		}
		
		private function showContactgroupsTab() {
			$output = "";
			
			return $output;
		}
		
		private function showCommandTab() {
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-bad-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-data-not-exist")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-data-exist")); break;
			}
			
			/*
			 * Ajax new command
			 */
			$formoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","",false,array("length" => 60, "size" => 30));
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Command"),"cmd","",false,array("length" => 1024, "size" => 30));
			$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
			$formoutput .= "</table></form>";
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-cmd"));
			
			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_commands","name,cmd","","name");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Command")."</th><th></th></tr>";
				}
				$output .= "<tr><td>".$data["name"]."</td><td>".substr($data["cmd"],0,100).(strlen($data["cmd"]) > 100 ? "..." : "")."</td><td>
						<a href=\"index.php?mod=".$this->mid."&act=2&cmd=".$data["name"]."\">".FS::$iMgr->img("styles/images/cross.png",15,15)."
						</a></td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}
		
		private function genCommandList($name) {
			$output = FS::$iMgr->addList($name);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_commands","name","","name");
			while($data = pg_fetch_array($query)) {
				$output .= FS::$iMgr->addElementToList($data["name"],$data["name"]);
			}
			$output .= "</select>";
			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				// Add command
				case 1:
					$cmdname = FS::$secMgr->checkAndSecurisePostData("name");
					$cmd = FS::$secMgr->checkAndSecurisePostData("cmd");
					
					if(!$cmdname || !$cmd || preg_match("#[ ]#",$cmdname)) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=1");
						return;
					}
					
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_commands","cmd","name = '".$cmdname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=3");
						return;
					}
					
					FS::$pgdbMgr->Insert("z_eye_icinga_commands","name,cmd","'".$cmdname."','".$cmd."'");
					header("Location: index.php?mod=".$this->mid."&sh=8");
					return;
				// Remove command
				case 2:
					$cmdname = FS::$secMgr->checkAndSecuriseGetData("cmd");
					if(!$cmdname) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=1");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_commands","cmd","name = '".$cmdname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=2");
						return;
					}
					
					FS::$pgdbMgr->Delete("z_eye_icinga_commands","name = '".$cmdname."'");
					header("Location: index.php?mod=".$this->mid."&sh=8");
					return;
				// Edit command
				case 3:
					// @TODO
					header("Location: index.php?mod=".$this->mid."&sh=8");
					return;
			}
		}
	};
?>
