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
	
	final class radiusUser extends radiusObject {
		function __construct() {
			parent::__construct();
			$this->readRight = "rule-read-datas";
			$this->writeRight = "rule-write-datas";
		}
		
		public function showForm($username = "") {
			if (!$this->canWrite()) {
				return FS::$iMgr->printNoRight("show user form");
			}
			
			$radalias = FS::$secMgr->checkAndSecuriseGetData("ra");
			
			$connOK = $this->connectToRaddb($radalias);
			if (!$connOK) {
				return FS::$iMgr->printError("err-db-conn-fail");
			}
			
			$pwdtype = "";
			$utype = 1;
			
			if ($username) {
				$userexist = $this->radSQLMgr->GetOneData($this->raddbinfos["tradcheck"],"username","username = '".$username."'");
				if (!$userexist) {
					return FS::$iMgr->printError("err-no-user");
				}
				$userpwd = $this->radSQLMgr->GetOneData($this->raddbinfos["tradcheck"],"value",
					"username = '".$username."' AND op = ':=' AND attribute IN('Cleartext-Password','User-Password','Crypt-Password','MD5-Password','SHA1-Password','CHAP-Password')");
				if ($userpwd) {
					$pwdtype = $this->radSQLMgr->GetOneData($this->raddbinfos["tradcheck"],"attribute","username = '".$username."' AND op = ':=' AND value = '".$userpwd."'");
				}
				$grpcount = $this->radSQLMgr->Count($this->raddbinfos["tradusrgrp"],"groupname","username = '".$username."'");
				$attrcount = $this->radSQLMgr->Count($this->raddbinfos["tradcheck"],"username","username = '".$username."'");
				$attrcount += $this->radSQLMgr->Count($this->raddbinfos["tradreply"],"username","username = '".$username."'");
			}
			
			FS::$iMgr->js("function changeUForm() {
				if (document.getElementsByName('utype')[0].value == 1) {
					$('#userdf').show();
				}
				else if (document.getElementsByName('utype')[0].value == 2) {
					$('#userdf').hide();
				}
				else if (document.getElementsByName('utype')[0].value == 3) {
					$('#userdf').hide();
				}
			}; var grpidx = 0; function addGrpForm(grpval='') {
				$('<span class=\"ugroupli'+grpidx+'\">".FS::$iMgr->select("ugroup'+grpidx+'").
					FS::$iMgr->selElmt("","none").$this->addGroupList()."</select>
				<a onclick=\"javascript:delGrpElmt('+grpidx+');\">".
				FS::$iMgr->img("styles/images/cross.png",15,15).
				"</a><br /></span>').insertBefore('#radusrgrplist');
				if(grpval != '') $('#ugroup'+grpidx).val(grpval);
				grpidx++;
			}
			function delGrpElmt(grpidx) {
				$('.ugroupli'+grpidx).remove();
			}
			var attridx = 0; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<span class=\"attrli'+attridx+'\">".
			FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40)." Op ".FS::$iMgr->select("attrop'+attridx+'").
			FS::$iMgr->raddbCondSelectElmts().
			"</select> Valeur".FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40,"")." Cible ".FS::$iMgr->select("attrtarget'+attridx+'").
			FS::$iMgr->selElmt("check",1).
			FS::$iMgr->selElmt("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">".
			FS::$iMgr->img("styles/images/cross.png",15,15).
			"</a><br /></span>').insertBefore('#frmattrid');
			$('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(''+attrop);
			$('#attrtarget'+attridx).val(attrtarget); attridx++;};
			function delAttrElmt(attridx) {
				$('.attrli'+attridx).remove();
			}");
			
			if ($username && (FS::$secMgr->isMacAddr($username) || preg_match('#^[0-9A-F]{12}$#i', $username))) {
				$utype = 2;
			}
			
			// Load attributes and groups
			if ($username) {
				$query = $this->radSQLMgr->Select($this->raddbinfos["tradusrgrp"],"groupname","username = '".$username."'");
				while ($data = $this->radSQLMgr->Fetch($query)) {
					FS::$iMgr->js(sprintf("addGrpForm('%s');",$data["groupname"]));
				}
				$query = $this->radSQLMgr->Select($this->raddbinfos["tradcheck"],"attribute,op,value","username = '".$username."'");
				while ($data = $this->radSQLMgr->Fetch($query)) {
					// If mac addr, don't show the attribute for MAB
					if ($utype == 2 && $data["attribute"] == "Auth-Type" && $data["op"] == ":=" && $data["value"] == "Accept") {
						continue;
					}
					FS::$iMgr->js("addAttrElmt('".$data["attribute"]."','".$data["value"]."','".$data["op"]."','1');");
				}

				$query = $this->radSQLMgr->Select($this->raddbinfos["tradreply"],"attribute,op,value","username = '".$username."'");
				while ($data = $this->radSQLMgr->Fetch($query)) {
					FS::$iMgr->js("addAttrElmt('".$data["attribute"]."','".$data["value"]."','".$data["op"]."','2');");
				}
			}

			if ($username) {
				if ($utype == 1) {
					$tempSelect = $this->loc->s("User").FS::$iMgr->hidden("utype",$utype);
				}
				else if ($utype == 2) {
					$tempSelect = $this->loc->s("Mac-addr").FS::$iMgr->hidden("utype",$utype);
				}
			}
			else {
				$tempSelect = FS::$iMgr->select("utype",array("js" => "changeUForm()",)).
					FS::$iMgr->selElmt($this->loc->s("User"),1,$utype == 1).
					FS::$iMgr->selElmt($this->loc->s("Mac-addr"),2,$utype == 2).
					"</select>";
			}
				
			if ($utype == 1) {
				$pwdSelect = FS::$iMgr->select("upwdtype").
					FS::$iMgr->selElmt("Cleartext-Password",1,$pwdtype == 1).
					FS::$iMgr->selElmt("User-Password",2,$pwdtype == 2).
					FS::$iMgr->selElmt("Crypt-Password",3,$pwdtype == 3).
					FS::$iMgr->selElmt("MD5-Password",4,$pwdtype == 4).
					FS::$iMgr->selElmt("SHA1-Password",5,$pwdtype == 5).
					FS::$iMgr->selElmt("CHAP-Password",6,$pwdtype == 6).
					"</select>";
			}

			$output = FS::$iMgr->cbkForm("2").
				"<table>".FS::$iMgr->idxLines(array(
				array("User","username",array("type" => "idxedit", "value" => $username,
						"length" => "40", "edit" => $username != "")),
				array("Auth-Type","",array("type" => "raw", "value" => $tempSelect))
			));
			
			// Show this field only if not a Mac Addr
			if ($utype == 1) {
				$output .= FS::$iMgr->idxLines(array(
					array("Password","pwd",array("type" => "pwd")),
					array("Pwd-Type","",array("type" => "raw", "value" => $pwdSelect)),
				));
			}
			
			$output .= FS::$iMgr->idxLines(array(
				array("Groups","",array("type" => "raw", "value" => "<span id=\"radusrgrplist\"></span>")),
				array("Attributes","",array("type" => "raw", "value" => "<span id=\"frmattrid\"></span>")),
			)).
				"<tr><td colspan=\"2\">".
				FS::$iMgr->hidden("ra",$radalias).
				FS::$iMgr->button("newgrp",$this->loc->s("New-Group"),"addGrpForm()").
				FS::$iMgr->button("newattr",$this->loc->s("New-Attribute"),"addAttrElmt('','','','');").
				FS::$iMgr->submit("",$this->loc->s("Save"))."</td></tr></table></form>";
			
			return $output;
		}
		
		public function Modify() {
			$radalias = FS::$secMgr->checkAndSecurisePostData("ra");
			$utype = FS::$secMgr->checkAndSecurisePostData("utype");
			$username = FS::$secMgr->checkAndSecurisePostData("username");
			$upwd = FS::$secMgr->checkAndSecurisePostData("pwd");
			$upwdtype = FS::$secMgr->checkAndSecurisePostData("upwdtype");

			// Check all fields
			if (!$username || $username == "" || !$utype || !FS::$secMgr->isNumeric($utype) ||
				$utype < 1 || $utype > 3 || 
				($utype == 1 && (!$upwd || $upwd == "" || !$upwdtype || $upwdtype < 1 || $upwdtype > 6))) {
				$this->log(2,"Some fields are missing for user edition");
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			// if type 2: must be a mac addr
			if ($utype == 2 && (!FS::$secMgr->isMacAddr($username) && !preg_match('#^[0-9A-F]{12}$#i', $username))) {
				$this->log(2,"Wrong datas for user edition");
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}
			
			$connOK = $this->connectToRaddb($radalias);
			if (!$connOK) {
				$this->log(2,"Unable to connect to radius database '".$radalias."'");
				FS::$iMgr->ajaxEchoError("err-db-conn-fail");
				return;
			}

			$this->radSQLMgr->BeginTr();
			
			// For Edition Only, don't delete acct records
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			if ($edit == 1) {
				$this->radSQLMgr->Delete($this->raddbinfos["tradcheck"],"username = '".$username."'");
				$this->radSQLMgr->Delete($this->raddbinfos["tradreply"],"username = '".$username."'");
				$this->radSQLMgr->Delete($this->raddbinfos["tradusrgrp"],"username = '".$username."'");
				if ($this->hasExpirationEnabled()) {
					$this->radSQLMgr->Delete(PGDbConfig::getDbPrefix()."radusers","username = '".$username."'");
				}
			}
			
			$userexist = $this->radSQLMgr->GetOneData($this->raddbinfos["tradcheck"],"username","username = '".$username."'");
			if (!$userexist || $edit == 1) {
				if ($utype == 1) {
					switch($upwdtype) {
						case 1: $attr = "Cleartext-Password"; $value = $upwd; break;
						case 2: $attr = "User-Password"; $value = $upwd; break;
						case 3: $attr = "Crypt-Password"; $value = crypt($upwd); break;
						case 4: $attr = "MD5-Password"; $value = md5($upwd); break;
						case 5: $attr = "SHA1-Password"; $value = sha1($upwd); break;
						case 6: $attr = "CHAP-Password"; $value = $upwd; break;
					}
				}
				else {
					$attr = "Auth-Type";
					$value = "Accept";
				}
				
				// For pgsql compat
				$maxIdChk = $this->radSQLMgr->GetMax($this->raddbinfos["tradcheck"],"id");
				$maxIdChk++;
				$maxIdRep = $this->radSQLMgr->GetMax($this->raddbinfos["tradreply"],"id");
				$maxIdRep++;
				
				$this->radSQLMgr->Insert($this->raddbinfos["tradcheck"],"id,username,attribute,op,value","'".$maxIdChk."','".$username."','".$attr."',':=','".$value."'");
				foreach ($_POST as $key => $value) {
				if (preg_match("#^ugroup#",$key)) {
						$groupfound = $this->radSQLMgr->GetOneData($this->raddbinfos["tradgrprep"],"groupname","groupname = '".$value."'");
						
						if (!$groupfound) {
							$groupfound = $this->radSQLMgr->GetOneData($this->raddbinfos["tradgrpchk"],"groupname","groupname = '".$value."'");
						}
						
						if ($groupfound) {
							$usergroup = $this->radSQLMgr->GetOneData($this->raddbinfos["tradusrgrp"],"groupname","username = '".$username."' AND groupname = '".$value."'");
							if (!$usergroup) {
								$this->radSQLMgr->Insert($this->raddbinfos["tradusrgrp"],"username,groupname,priority","'".$username."','".$value."','1'");
							}
						}
					}
				}

				$attrTab = array();
				foreach ($_POST as $key => $value) {
					if (preg_match("#attrval#",$key)) {
						$key = preg_replace("#attrval#","",$key);
						if (!isset($attrTab[$key])) $attrTab[$key] = array();
						$attrTab[$key]["val"] = $value;
					}
					else if (preg_match("#attrkey#",$key)) {
						$key = preg_replace("#attrkey#","",$key);
						if (!isset($attrTab[$key])) $attrTab[$key] = array();
						$attrTab[$key]["key"] = $value;
					}
					else if (preg_match("#attrop#",$key)) {
						$key = preg_replace("#attrop#","",$key);
						if (!isset($attrTab[$key])) $attrTab[$key] = array();
						$attrTab[$key]["op"] = $value;
					}
					else if (preg_match("#attrtarget#",$key)) {
						$key = preg_replace("#attrtarget#","",$key);
						if (!isset($attrTab[$key])) $attrTab[$key] = array();
						$attrTab[$key]["target"] = $value;
					}
				}
				foreach ($attrTab as $attrKey => $attrEntry) {
					if (!isset($attrEntry["op"])) {
						FS::$iMgr->ajaxEchoOK("err-bad-datas");
						return;
					}

					if ($attrEntry["target"] == "2") {
						$maxIdRep++;
						$this->radSQLMgr->Insert($this->raddbinfos["tradreply"],"id,username,attribute,op,value","'".$maxIdRep."','".$username.
							"','".$attrEntry["key"]."','".$attrEntry["op"]."','".$attrEntry["val"]."'");
					}
					else if ($attrEntry["target"] == "1") {
						$maxIdChk++;
						$this->radSQLMgr->Insert($this->raddbinfos["tradcheck"],"id,username,attribute,op,value","'".$maxIdChk."','".$username.
							"','".$attrEntry["key"]."','".$attrEntry["op"]."','".$attrEntry["val"]."'");
					}
				}

				if ($this->hasExpirationEnabled($this->raddbinfos["addr"],$this->raddbinfos["port"],
					$this->raddbinfos["dbname"])) {
					$this->radSQLMgr->Delete(PGDbConfig::getDbPrefix()."radusers","username = '".$username."'");
					$expiretime = FS::$secMgr->checkAndSecurisePostData("expiretime");
					if ($expiretime) {
						$this->radSQLMgr->Insert(PGDbConfig::getDbPrefix()."radusers","username,expiration","'".$username."','".date("y-m-d",strtotime($expiretime))."'");
					}
				}
				
			}
			else {
				$this->log(1,"Try to add user ".$username." but user already exists");
				FS::$iMgr->ajaxEchoErrorNC("err-exist");
				return;
			}
			$this->radSQLMgr->CommitTr();
			
			$this->log(0,"User '".$username."' edited/created");
			FS::$iMgr->redir("mod=".$this->mid."&ra=".$radalias,true);
		}
	};
	
	final class radiusGroup extends radiusObject {
		function __construct() {
			parent::__construct();
			$this->readRight = "rule-read-datas";
			$this->writeRight = "rule-write-datas";
		}
		
		public function showForm($groupname = "") {
			if (!$this->canWrite()) {
				return FS::$iMgr->printNoRight("show group form");
			}
			
			$radalias = FS::$secMgr->checkAndSecuriseGetData("ra");
			
			$connOK = $this->connectToRaddb($radalias);
			if (!$connOK) {
				return FS::$iMgr->printError("err-db-conn-fail");
			}
			
			if ($groupname) {
				$groupexist = $this->radSQLMgr->GetOneData($this->raddbinfos["tradgrpchk"],"groupname","groupname = '".$groupname."'");
				// If group is not in check attributes, look into reply attributes
				if (!$groupexist) {
					$groupexist = $this->radSQLMgr->GetOneData($this->raddbinfos["tradgrprep"],"groupname","groupname = '".$groupname."'");
				}
				if (!$groupexist) {
					return FS::$iMgr->printError(sprintf($this->loc->s("err-group-not-exists"),$groupname),true);
				}
				
				$attrcount = $this->radSQLMgr->Count($this->raddbinfos["tradgrpchk"],"groupname","groupname = '".$groupname."'");
				$attrcount += $this->radSQLMgr->Count($this->raddbinfos["tradgrprep"],"groupname","groupname = '".$groupname."'");
			}
			
			FS::$iMgr->js("var attridx = 0; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<span class=\"attrli'+attridx+'\">".
				FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40,"Name")." Op ".FS::$iMgr->select("attrop'+attridx+'").
				FS::$iMgr->raddbCondSelectElmts().
				"</select> Valeur".FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40)." ".$this->loc->s("Target")." ".FS::$iMgr->select("attrtarget'+attridx+'").
				FS::$iMgr->selElmt("check",1).
				FS::$iMgr->selElmt("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">".
				FS::$iMgr->img("styles/images/cross.png",15,15)."</a></span><br />').insertBefore('#attrgrpn');
				$('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(attrop);
				$('#attrtarget'+attridx).val(attrtarget); attridx++;};
				function delAttrElmt(attridx) {
					$('.attrli'+attridx).remove();
				}
				function addTemplAttributes() {
					switch($('#radgrptpl').val()) {
						case '1':
							addAttrElmt('Tunnel-Private-Group-Id','','=','2');
							addAttrElmt('Tunnel-Type','13','=','2');
							addAttrElmt('Tunnel-Medium-Type','6','=','2');
							break;
					}
			};");
			
			if ($groupname) {
				$attridx = 0;
				$query = $this->radSQLMgr->Select($this->raddbinfos["tradgrpchk"],"attribute,op,value","groupname = '".$groupname."'");
				while ($data = $this->radSQLMgr->Fetch($query)) {
					FS::$iMgr->js("addAttrElmt('".$data["attribute"]."','".$data["value"]."','".$data["op"]."','1');");
				}

				$query = $this->radSQLMgr->Select($this->raddbinfos["tradgrprep"],"attribute,op,value","groupname = '".$groupname."'");
				while ($data = $this->radSQLMgr->Fetch($query)) {
					FS::$iMgr->js("addAttrElmt('".$data["attribute"]."','".$data["value"]."','".$data["op"]."','2');");
				}
			}
			
			$tempSelect = FS::$iMgr->select("radgrptpl",array("js" => "addTemplAttributes()")).
				FS::$iMgr->selElmt($this->loc->s("None"),0).
				FS::$iMgr->selElmt("VLAN",1)."</select>";
				
			return FS::$iMgr->cbkForm("3")."<table>".
				FS::$iMgr->idxLines(array(
					array("Profilname","groupname",array("type" => "idxedit", "value" => $groupname,
						"length" => "40", "edit" => $groupname != "")),
					array("Template","",array("type" => "raw", "value" => $tempSelect)),
					array("Attributes","",array("type" => "raw", "value" => "<span id=\"attrgrpn\"></span>"))
				)).
				"<tr><td colspan=\"2\">".
				FS::$iMgr->hidden("ra",$radalias).
				FS::$iMgr->button("newattr",$this->loc->s("New-Attribute"),"addAttrElmt('','','','')").
				FS::$iMgr->submit("",$this->loc->s("Save")).
				"</td></tr></table></form>";
		}
	};

	class radiusObject extends FSMObj {
		function __construct() {
			parent::__construct();
		}
		
		protected function connectToRaddb($radalias) {
			// Load some other useful datas from DB
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."radius_db_list",
				"login,pwd,dbtype,tradcheck,tradreply,tradgrpchk,tradgrprep,tradusrgrp,tradacct,radalias,addr,port,dbname",
				"radalias='".$radalias."'");
			if ($data = FS::$dbMgr->Fetch($query)) {
				$this->raddbinfos = $data;
			}

			if ($this->raddbinfos["dbtype"] != "my" && $this->raddbinfos["dbtype"] != "pg")
				return NULL;

			$this->radSQLMgr = new AbstractSQLMgr();
			if ($this->radSQLMgr->setConfig($this->raddbinfos["dbtype"],$this->raddbinfos["dbname"],
				$this->raddbinfos["port"],$this->raddbinfos["addr"],$this->raddbinfos["login"],
				$this->raddbinfos["pwd"]) == 0) {
				if ($this->radSQLMgr->Connect() == NULL) {
					return NULL; 
				}
			}
			return true;
		}
		
		protected function hasExpirationEnabled() {
			if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."radius_options",
				"optval","optkey = 'rad_expiration_enable' AND addr = '".$this->raddbinfos["addr"].
					"' AND port = '".$this->raddbinfos["port"].
					"' AND dbname = '".$this->raddbinfos["dbname"]."'") == 1) {
				return true;
			}
			return false;
		}
		
		protected function addGroupList($selectEntry="") {
			$output = "";
			$groups=array();
			$query = $this->radSQLMgr->Select($this->raddbinfos["tradgrpchk"],"distinct groupname");
			while ($data = $this->radSQLMgr->Fetch($query)) {
				if (!in_array($data["groupname"],$groups)) {
					$groups[] = $data["groupname"];
				}
			}
			$query = $this->radSQLMgr->Select($this->raddbinfos["tradgrprep"],"distinct groupname");
			while ($data = $this->radSQLMgr->Fetch($query)) {
				if (!in_array($data["groupname"],$groups)) {
					$groups[] = $data["groupname"];
				}
			}
			$count = count($groups);
			for ($i=0;$i<$count;$i++) {
				$output .= FS::$iMgr->selElmt($groups[$i],$groups[$i],($groups[$i] == $selectEntry));
			}
			return $output;
		}
		
		protected $radSQLMgr;
		protected $raddbinfos;
	}
?>

