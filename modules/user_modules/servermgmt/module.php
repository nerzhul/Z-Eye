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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	
	class iServerMgmt extends genModule{
		function iServerMgmt() { parent::genModule(); $this->loc = new lServerMgmt(); }
		public function Load() {
			$output = "<h1>".$this->loc->s("title-analysismgmt")."</h1>";
			$do = FS::$secMgr->checkAndSecuriseGetData("do");
			switch($do) {
				case 1: case 2:
					$output .= $this->CreateOrEditServer($do == 1 ? true : false);
					break;
				case 4: case 5:
					$output .= $this->CreateOrEditRadiusDB($do == 4 ? true : false);
					break;
				case 7: case 8:
					$output .= $this->CreateOrEditDeviceSaveServer($do == 7 ? true : false);
					break;
				default:
					$output .= $this->showServerList();
					break;
			}
			$output .= "</div>";
			return $output;
		}

		private function CreateOrEditDeviceSaveServer($create) {
			$saddr = "";
			$slogin = "";
			$stype = 1;
			$spwd = "";
			$spath = "";
			FS::$iMgr->showReturnMenu(true);
			if($create)
				$output = "<h2>".$this->loc->s("title-add-backup-switch-server")."</h2>";
			else {
				$output = "<h2>".$this->loc->s("title-edit-backup-switch-server")."</h2>";
				$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
				$type = FS::$secMgr->checkAndSecuriseGetData("type");
				if(!$addr || $addr == "" || !$type || !FS::$secMgr->isNumeric($type)) {
					$output .= FS::$iMgr->printError($this->loc->s("err-no-server-get")." !");
					return $output;
				}
				$query = FS::$dbMgr->Select("z_eye_save_device_servers","login,pwd,path","addr = '".$addr."' AND type = '".$type."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$saddr = $addr;
					$slogin = $data["login"];
					$spwd = $data["pwd"];
					$stype = $type;
					$spath = $data["path"];
				}
				else {
					$output .= FS::$iMgr->printError($this->loc->s("err-bad-server")." !");
					return $output;
				}
			}

			$output .= "<a href=\"m-".$this->mid.".html\">".$this->loc->s("Return")."</a><br />";

			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-miss-bad-fields")." !"); break;
				case 3: if($create) $output .= FS::$iMgr->printError($this->loc->s("err-server-exist")." !"); break;
			}

			$output .= "<script type=\"text/javascript\">function arangeform() {";
			$output .= "if(document.getElementsByName('stype')[0].value == 1) {";
			$output .= "$('#tohide1').hide();";
			$output .= "$('#tohide2').hide();";
			$output .= "$('#tohide3').hide();";
			$output .= "} else if(document.getElementsByName('stype')[0].value == 2 || document.getElementsByName('stype')[0].value == 4 || document.getElementsByName('stype')[0].value == 5) {";
			$output .= "$('#tohide1').show();";
			$output .= "$('#tohide2').show();";
			$output .= "$('#tohide3').show();";
			$output .= "}";
			$output .= "};</script>";

			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=".($create ? 7 : 8));

			if($create == false) {
				$output .= FS::$iMgr->hidden("saddr",$saddr);
				$output .= FS::$iMgr->hidden("stype",$stype);
			}

			$output .= "<table class=\"standardTable\">";
			if($create) {
				$output .= FS::$iMgr->idxLine($this->loc->s("ip-addr"),"saddr",$saddr,array("type" => "ip"));
				$output .= "<tr><td>".$this->loc->s("srv-type")."</td><td>";
				$output .= FS::$iMgr->select("stype","arangeform();");
				$output .= FS::$iMgr->selElmt("TFTP",1);
				$output .= FS::$iMgr->selElmt("FTP",2);
				$output .= FS::$iMgr->selElmt("SCP",4);
				$output .= FS::$iMgr->selElmt("SFTP",5);
				$output .= "</select>";
				$output .= "</td></tr>";
			}
			else {
				$output .= "<tr><th>".$this->loc->s("ip-addr")."</th><th>".$saddr."</th></tr>";
				$output .= "<tr><td>".$this->loc->s("srv-type")."</td><td>";
				switch($stype) {
					case 1: $output .= "TFTP"; break;
					case 2: $output .= "FTP"; break;
					case 4: $output .= "SCP"; break;
					case 5: $output .= "SFTP"; break;
				}
			}
			$output .= "<tr id=\"tohide1\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("User")."</td><td>".FS::$iMgr->input("slogin",$slogin)."</td></tr>";
			$output .= "<tr id=\"tohide2\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("Password")."</td><td>".FS::$iMgr->password("spwd","")."</td></tr>";
			$output .= "<tr id=\"tohide3\" ".($stype == 1 ? "style=\"display:none;\"" : "")."><td>".$this->loc->s("Password-repeat")."</td><td>".FS::$iMgr->password("spwd2","")."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("server-path"),"spath",$spath);
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table>";

			return $output;
		}

		private function CreateOrEditRadiusDB($create) {
			$saddr = "";
			$slogin = "";
			$sdbname = "";
			$sport = 3306;
			$spwd = "";
			$salias = "";
			FS::$iMgr->showReturnMenu(true);
			if($create)
				$output = "<h2>".$this->loc->s("title-add-radius")."</h2>";
			else {
				$output = "<h2>".$this->loc->s("title-edit-radius")."</h2>";
				$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
				$port = FS::$secMgr->checkAndSecuriseGetData("pr");
				$dbname = FS::$secMgr->checkAndSecuriseGetData("db");
				if(!$addr || $addr == "" || !$port || !FS::$secMgr->isNumeric($port) || !$dbname || $dbname == "") {
					$output .= FS::$iMgr->printError($this->loc->s("err-no-db")." !");
					return $output;
				}
				$query = FS::$dbMgr->Select("z_eye_radius_db_list","radalias,login,pwd","addr = '".$addr."' AND port = '".$port."' AND dbname = '".$dbname."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$saddr = $addr;
					$slogin = $data["login"];
					$spwd = $data["pwd"];
					$salias = $data["radalias"];
					$sport = $port;
					$sdbname = $dbname;
				}
				else {
					$output .= FS::$iMgr->printError($this->loc->s("err-invalid-db")." !");
					return $output;
				}
			}

			$output .= "<a href=\"m-".$this->mid.".html\">".$this->loc->s("Return")."</a><br />";

			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-miss-bad-fields")." !"); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-bad-server")." !"); break;
				case 3: if($create) $output .= FS::$iMgr->printError($this->loc->s("err-server-exist")." !"); break;
			}

			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=".($create ? 4 : 5),array("id" => "cesrv"));

			if($create == false) {
				$output .= FS::$iMgr->hidden("saddr",$saddr);
				$output .= FS::$iMgr->hidden("sport",$sport);
				$output .= FS::$iMgr->hidden("sdbname",$sdbname);
			}

			$output .= "<table class=\"standardTable\">";
			if($create) {
				$output .= FS::$iMgr->idxLine($this->loc->s("ip-addr-dns"),"saddr",$saddr);
				$output .= FS::$iMgr->idxLine($this->loc->s("Port"),"sport","",array("value" => $sport, "type" => "num"));
				$output .= FS::$iMgr->idxLine($this->loc->s("db-name"),"sdbname",$sdbname);
			}
			else {
				$output .= "<tr><th>".$this->loc->s("ip-addr-dns")."</th><th>".$saddr."</th></tr>";
				$output .= "<tr><td>".$this->loc->s("Port")."</td><td>".$sport."</td></tr>";
				$output .= "<tr><td>".$this->loc->s("db-name")."</td><td>".$sdbname."</td></tr>";
			}
			$output .= FS::$iMgr->idxLine($this->loc->s("User"),"slogin",$slogin);
			$output .= FS::$iMgr->idxLine($this->loc->s("Password"),"spwd","",array("type" => "pwd"));
			$output .= FS::$iMgr->idxLine($this->loc->s("Password-repeat"),"spwd2","",array("type" => "pwd"));
			$output .= FS::$iMgr->idxLine($this->loc->s("Alias"),"salias",$salias);
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table>";

			$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=".($create ? 4 : 5),"cesrv");
			return $output;
		}

		private function CreateOrEditServer($create) {
			$saddr = "";
			$slogin = "";
			$dns = 0;
			$namedpath = "";
			$chrootnamed = "";
			FS::$iMgr->showReturnMenu(true);
			if($create)
				$output = "<h2>".$this->loc->s("add-server")."</h2>";
			else {
				$output = "<h2>".$this->loc->s("edit-server")."</h2>";
				$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
				if(!$addr || $addr == "") {
					$output .= FS::$iMgr->printError($this->loc->s("err-no-server-get")." !");
					return $output;
				}
				$query = FS::$dbMgr->Select("z_eye_server_list","login,dns,chrootnamed,namedpath","addr = '".$addr."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$saddr = $addr;
					$slogin = $data["login"];
					$dns = $data["dns"];
					$namedpath = $data["namedpath"];
					$chrootnamed = $data["chrootnamed"];
				}
				else {
					$output .= FS::$iMgr->printError($this->loc->s("err-bad-server")." !");
					return $output;
				}
			}
			
			$output .= "<a href=\"m-".$this->mid.".html\">".$this->loc->s("Return")."</a><br />";
			
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-miss-bad-fields")." !"); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-unable-conn")." !"); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-bad-login")." !"); break;
				case 4: if($create) $output .= FS::$iMgr->printError($this->loc->s("err-server-exist")." !"); break;
			}
			
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=".($create ? 1 : 2));
			
			if($create == false)
				$output .= FS::$iMgr->hidden("saddr",$saddr);
	
			$output .= "<table class=\"standardTable\">";
			if($create)
				$output .= FS::$iMgr->idxLine($this->loc->s("ip-addr-dns"),"saddr",$saddr);
			else
				$output .= "<tr><td>".$this->loc->s("ip-addr-dns")."</td><td>".$saddr."</td></tr>";		
			$output .= FS::$iMgr->idxLine($this->loc->s("ssh-user"),"slogin",$slogin);
			$output .= FS::$iMgr->idxLine($this->loc->s("Password"),"spwd","",array("type" => "pwd"));
			$output .= FS::$iMgr->idxLine($this->loc->s("Password-repeat"),"spwd2","",array("type" => "pwd"));
			$output .= FS::$iMgr->idxLine("DNS ?","dns",$dns > 0 ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("named-conf-path"),"namedpath",$namedpath,array("tooltip" => $this->loc->s("tooltip-rights")));
			$output .= FS::$iMgr->idxLine($this->loc->s("chroot-path"),"chrootnamed",$chrootnamed,array("tooltip" => $this->loc->s("tooltip-rights")));
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table>";
			
			return $output;
		}
		
		private function showServerList() {
			$output = "<h2>".$this->loc->s("title-server-list")."</h2>";
			$output .= "<a href=\"index.php?mod=".$this->mid."&do=1\">".$this->loc->s("New-server")."</a><br />";
			$tmpoutput = "<table class=\"standardTable\"><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("Login").
				"</th><th>DNS</th><th>".$this->loc->s("Remove")."</th></tr>";
			$found = false;
			$query = FS::$dbMgr->Select("z_eye_server_list","addr,login,dns");
			while($data = FS::$dbMgr->Fetch($query)) {
				if($found == false) $found = true;
				$tmpoutput .= "<tr><td><a href=\"index.php?mod=".$this->mid."&do=2&addr=".$data["addr"]."\">".$data["addr"];
				$tmpoutput .= "</td><td>".$data["login"]."</td><td>";
				$tmpoutput .= "<center>".($data["dns"] > 0 ? "X" : "")."</center></td><td><center>";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&act=3&srv=".$data["addr"]."\">";
				$tmpoutput .= FS::$iMgr->removeIcon();
				$tmpoutput .= "</center></td></tr>";
			}
			if($found)
				$output .= $tmpoutput."</table>";
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-server-found")." !");

			$output .= "<h2>".$this->loc->s("title-radius-db")."</h2>";
			$output .= "<a href=\"index.php?mod=".$this->mid."&do=4\">".$this->loc->s("New-base")."</a><br />";
			$tmpoutput = "<table class=\"standardTable\"><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("Port")."</th><th>"
				.$this->loc->s("Host")."</th><th>".$this->loc->s("Login")."</th><th>".$this->loc->s("Remove")."</th></tr>";
			$found = false;
			$query = FS::$dbMgr->Select("z_eye_radius_db_list","addr,port,dbname,login");
			while($data = FS::$dbMgr->Fetch($query)) {
				if($found == false) $found = true;
				$tmpoutput .= "<tr><td><a href=\"index.php?mod=".$this->mid."&do=5&addr=".$data["addr"]."&pr=".$data["port"]."&db=".$data["dbname"]."\">".$data["addr"];
				$tmpoutput .= "</td><td>".$data["port"]."</td><td>".$data["dbname"]."</td><td>".$data["login"]."</td><td><center>";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&act=6&addr=".$data["addr"]."&pr=".$data["port"]."&db=".$data["dbname"]."\">";
				$tmpoutput .= FS::$iMgr->removeIcon();
				$tmpoutput .= "</center></td></tr>";
			}
			if($found)
				$output .= $tmpoutput."</table>";
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-db-given")." !");
				
			$output .= "<h2>".$this->loc->s("title-backup-switch")."</h2>";
			$output .= "<a href=\"index.php?mod=".$this->mid."&do=7\">".$this->loc->s("New-server")."</a><br />";
			$tmpoutput = "<table class=\"standardTable\"><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("Type")."</th><th>".
				$this->loc->s("server-path")."</th><th>".$this->loc->s("Login")."</th><th>".$this->loc->s("Remove")."</th></tr>";
			$found = false;
			$query = FS::$dbMgr->Select("z_eye_save_device_servers","addr,type,path,login");
			while($data = FS::$dbMgr->Fetch($query)) {
				if($found == false) $found = true;
				$tmpoutput .= "<tr><td><a href=\"index.php?mod=".$this->mid."&do=8&addr=".$data["addr"]."&type=".$data["type"]."\">".$data["addr"];
				$tmpoutput .= "</td><td>";
				switch($data["type"]) {
					case 1: $tmpoutput .= "TFTP"; break;
					case 2: $tmpoutput .= "FTP"; break;
					case 4: $tmpoutput .= "SCP"; break;
					case 5: $tmpoutput .= "SFTP"; break;
				}
				$tmpoutput .= "</td><td>".$data["path"]."</td><td>".$data["login"]."</td><td><center>";
				$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&act=9&addr=".$data["addr"]."&type=".$data["type"]."\">";
				$tmpoutput .= FS::$iMgr->removeIcon();
				$tmpoutput .= "</center></td></tr>";
			}
			if($found)
				$output .= $tmpoutput."</table>";
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-db-given")." !");
			return $output;	
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Adding server
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$dns = FS::$secMgr->checkAndSecurisePostData("dns");
					$namedpath = FS::$secMgr->checkAndSecurisePostData("namedpath");
					$chrootnamed = FS::$secMgr->checkAndSecurisePostData("chrootnamed");
					if($saddr == NULL || $saddr == "" || $slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" ||
						$spwd != $spwd2 ||
						$dns == "on" && ($namedpath == NULL || $namedpath == "" || !FS::$secMgr->isPath($namedpath) ||
							(($chrootnamed == NULL || $chrootnamed == "") && !FS::$secMgr->isPath($chrootnamed)))
						) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"Some datas are invalid or wrong for add server");
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=1");
						return;
					}
					$conn = ssh2_connect($saddr,22);
					if(!$conn) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=2");
						return;
					}
					if(!ssh2_auth_password($conn,$slogin,$spwd)) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=3");
						return;
					}
					if(FS::$dbMgr->GetOneData("z_eye_server_list","login","addr ='".$saddr."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",1,"Unable to add server '".$saddr."': already exists");
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=4");
						return;
					}
					FS::$dbMgr->Insert("z_eye_server_list","addr,login,pwd,dns,namedpath,chrootnamed",
					"'".$saddr."','".$slogin."','".$spwd."','".($dns == "on" ? 1 : 0)."','".$namedpath."','".$chrootnamed."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Added server '".$saddr."' options: ".($dns == "on" ? "dns checking" : ""));
					header("Location: m-".$this->mid.".html");
					break;
				case 2: // server edition
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$dns = FS::$secMgr->checkAndSecurisePostData("dns");
					$namedpath = FS::$secMgr->checkAndSecurisePostData("namedpath");
					$chrootnamed = FS::$secMgr->checkAndSecurisePostData("chrootnamed");
					if($saddr == NULL || $saddr == "" || $slogin == NULL || $slogin == "" || $spwd != $spwd2
                                                || $dns == "on" && ($namedpath == NULL || $namedpath == "" || !FS::$secMgr->isPath($namedpath) ||
							(($chrootnamed == NULL || $chrootnamed == "") && !FS::$secMgr->isPath($chrootnamed)))
						) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"Some fields are missing/wrong for server edition");
						header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&err=1");
						return;
					}
					if($spwd != NULL || $spwd != "") {
						$conn = ssh2_connect($saddr,22);
						if(!$conn) {
							header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&err=2");
							return;
						}
						if(!ssh2_auth_password($conn,$slogin,$spwd)) {
							header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&err=3");
							return;
						}
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Edit server password for server '".$saddr."'");
						if($spwd == $spwd2) FS::$dbMgr->Update("z_eye_server_list","pwd = '".$spwd."'","addr = '".$saddr."'");
					}
					FS::$dbMgr->Update("z_eye_server_list","login = '".$slogin."', dns = '".($dns == "on" ? 1 : 0)."', chrootnamed = '".$chrootnamed."', namedpath='".$namedpath."', addr = '".$saddr."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Edit informations for server '".$server."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 3: { // server removal
					if($srv = FS::$secMgr->checkAndSecuriseGetData("srv")) {
							FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Removing server '".$srv."' from database");
							FS::$dbMgr->Delete("z_eye_server_list","addr = '".$srv."'");
					}
					header('Location: m-'.$this->mid.'.html');
					return;
				}
				case 4: // add radius db
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$sport = FS::$secMgr->checkAndSecurisePostData("sport");
					$sdbname = FS::$secMgr->checkAndSecurisePostData("sdbname");
					$salias = FS::$secMgr->checkAndSecurisePostData("salias");
					if($saddr == NULL || $saddr == "" || $salias == NULL || $salias == "" || $sport == NULL || !FS::$secMgr->isNumeric($sport) || $sdbname == NULL || $sdbname == "" || $slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" ||
						$spwd != $spwd2) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"Some fields are missing or wrong for radius db adding");
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=1");
						return;
					}

					$testDBMgr = new AbstractSQLMgr();
					$testDBMgr->setConfig("my",$sdbname,$sport,$saddr,$slogin,$spwd);

					$conn = $testDBMgr->Connect();
					if($conn != 0) {
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=2");
						return;
					}
					FS::$dbMgr->Connect();
					if(FS::$dbMgr->GetOneData("z_eye_radius_db_list","login","addr ='".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",1,"Radius DB already exists (".$sdbname."@".$saddr.":".$sport.")");
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=3");
						return;
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Added radius DB ".$sdbname."@".$saddr.":".$sport);
					FS::$dbMgr->Insert("z_eye_radius_db_list","addr,port,dbname,login,pwd,radalias","'".$saddr."','".$sport."','".$sdbname."','".$slogin."','".$spwd."','".$salias."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 5: // radius db edition
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$sport = FS::$secMgr->checkAndSecurisePostData("sport");
					$sdbname = FS::$secMgr->checkAndSecurisePostData("sdbname");
					$salias = FS::$secMgr->checkAndSecurisePostData("salias");
					if($saddr == NULL || $saddr == "" || $slogin == NULL || $slogin == "" || $spwd != $spwd2 || $salias == NULL || $salias == "") {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"Some fields are missing or wrong for radius db edit");
						header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&pr=".$sport."&db=".$sdbname."&err=1");
						return;
					}
					if($spwd != NULL || $spwd != "") {
						$testDBMgr = new AbstractSQLMgr();
						$testDBMgr->setConfig("my",$sdbname,$sport,$saddr,$slogin,$spwd);

						$conn = $testDBMgr->Connect();
						if(!$conn) {
							FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"Unable to connect to database ".$sdbname."@".$saddr);
							header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&pr=".$sport."&db=".$sdbname."&err=2");
							return;
						}
						FS::$dbMgr->Connect();
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Edit password for radius db ".$sdbname."@".$saddr.":".$sport);
						if($spwd == $spwd2) FS::$dbMgr->Update("z_eye_radius_db_list","pwd = '".$spwd."'","addr = '".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'");
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Edit radius db ".$sdbname."@".$saddr.":".$sport);
					FS::$dbMgr->Update("z_eye_radius_db_list","login = '".$slogin."', radalias = '".$salias."'","addr = '".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 6: { // removal of radius DB
					$saddr = FS::$secMgr->checkAndSecuriseGetData("addr");
					$sport = FS::$secMgr->checkAndSecuriseGetData("pr");
					$sdbname = FS::$secMgr->checkAndSecuriseGetData("db");
					if($saddr && $sport && $sdbname) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Remove Radius DB ".$sdbname."@".$saddr.":".$sport);
						FS::$dbMgr->Delete("z_eye_radius_db_list","addr = '".$saddr."' AND port = '".$sport."' AND dbname = '".$sdbname."'");
					}
					header('Location: m-'.$this->mid.'.html');
				}
				case 7: // Save server for switch config
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$stype = FS::$secMgr->checkAndSecurisePostData("stype");
					$spath = FS::$secMgr->checkAndSecurisePostData("spath");
					if($saddr == NULL || $saddr == "" || !FS::$secMgr->isIP($saddr) || $spath == NULL || $spath == "" || $stype == NULL || ($stype != 1 && $stype != 2 && $stype != 4 && $stype != 5) || ($stype > 1 && ($slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" || $spwd != $spwd2)) || ($stype == 1 && ($slogin != "" || $spwd != "" || $spwd2 != ""))) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"Some fields are missing/wrong for saving switch config");
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=1");
						return;
					}

					if(FS::$dbMgr->GetOneData("z_eye_save_device_servers","addr","addr ='".$saddr."' AND type = '".$stype."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",1,"Server '".$saddr."' already exists for saving switch config");
						header("Location: index.php?mod=".$this->mid."&do=".$act."&err=3");
						return;
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Added server '".$saddr."' (type ".$stype.") for saving switch config");
					FS::$dbMgr->Insert("z_eye_save_device_servers","addr,type,path,login,pwd","'".$saddr."','".$stype."','".$spath."','".$slogin."','".$spwd."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 8: // Edit save server
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$stype = FS::$secMgr->checkAndSecurisePostData("stype");
					$spath = FS::$secMgr->checkAndSecurisePostData("spath");
					if($saddr == NULL || $saddr == "" || !FS::$secMgr->isIP($saddr) || $spath == NULL || $spath == "" || ($stype > 1 && ($slogin == NULL || $slogin == "" || $spwd != $spwd2))) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"Some fields are missing/wrong for saving switch config");
						header("Location: index.php?mod=".$this->mid."&do=".$act."&addr=".$saddr."&type=".$stype."&err=1");
						return;
					}
					FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Update server '".$saddr."' for saving switch config");
					FS::$dbMgr->Update("z_eye_save_device_servers","path = '".$spath."', pwd = '".$spwd."', login = '".$slogin."'","addr = '".$saddr."' AND type = '".$stype."'");
					header("Location: m-".$this->mid.".html");
					break;
				case 9: {
					$saddr = FS::$secMgr->checkAndSecuriseGetData("addr");
					$stype = FS::$secMgr->checkAndSecuriseGetData("type");
					if($saddr && $stype) {
							FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Delete server '".$saddr."' for saving switch config");
							FS::$dbMgr->Delete("z_eye_save_device_servers","addr = '".$saddr."' AND type = '".$stype."'");
					}	
					header('Location: m-'.$this->mid.'.html');				
				}
				break;
				default: break;
			}
		}
	};
?>
