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

	final class dnsServer extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_servers";
			$this->sqlAttrId = "addr";
			$this->readRight = "mrule_dnsmgmt_read";
			$this->writeRight = "mrule_dnsmgmt_write";
			$this->errNotExists = "err-server-not-exists";
			$this->errAlreadyExists = "err-server-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "dnssrv",
				"sqltable" => "dns_servers",
				"sqlattrid" => "addr",
				"attrlist" => array(array("Addr","addr",""), array("Login","sshuser","")),
				"sorted" => true,
				"odivnb" => 1,
				"odivlink" => "addr=",
				"rmcol" => true,
				"rmlink" => "mod=".$this->mid."&act=4&addr",
				"rmconfirm" => "confirm-remove-tsig",
			));
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(1,$this->loc->s("add-server"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($addr = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			}


			if (!$this->Load($addr)) {
				return FS::$iMgr->printError($this->loc->s($this->errNotExists));
			}

			
			$output = FS::$iMgr->cbkForm("3")."<table>".
				FS::$iMgr->idxLine($this->loc->s("ip-addr-dns"),"saddr",$this->addr,array("type" => "idxedit", "value" => $this->addr,
					"length" => "128", "edit" => $this->addr != "")).
				FS::$iMgr->idxLine($this->loc->s("ssh-user"),"slogin",$this->sshUser).
				FS::$iMgr->idxLine($this->loc->s("Password"),"spwd","",array("type" => "pwd")).
				FS::$iMgr->idxLine($this->loc->s("Password-repeat"),"spwd2","",array("type" => "pwd")).
				FS::$iMgr->idxLine($this->loc->s("named-conf-path"),"namedpath",$this->namedPath,array("tooltip" => "tooltip-rights")).
				FS::$iMgr->idxLine($this->loc->s("chroot-path"),"chrootnamed",$this->chrootPath,array("tooltip" => "tooltip-chroot")).
				FS::$iMgr->aeTableSubmit($addr != "");
			
			return $output;
		}

		protected function Load($addr = "") {
			$this->addr = $addr;
			$this->sshUser = ""; $this->namedPath = ""; $this->chrootPath = "";

			if($this->addr) {
				$query = FS::$dbMgr->Select($this->sqlTable,"sshuser,namedpath,chrootpath,tsig","addr = '".$addr."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$this->sshUser = $data["sshuser"];
					$this->namedPath = $data["namedpath"];
					$this->chrootPath = $data["chrootpath"];
					$this->TSIGKey = $data["tsig"];
					return true;
				}
				else {
					return false;
				}
			}
			return true;
		}

		protected function removeFromDB($name) {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlTable,"addr = '".$name."'");
			FS::$dbMgr->CommitTr();
		}

		public function Modify() {
			if(!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
			$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
			$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
			$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
			$namedpath = FS::$secMgr->checkAndSecurisePostData("namedpath");
			$chrootnamed = FS::$secMgr->checkAndSecurisePostData("chrootnamed");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if(!$saddr || !$slogin || !$spwd || !$spwd2 || $spwd != $spwd2 ||
				!$namedpath || !FS::$secMgr->isPath($namedpath) ||
					(!$chrootnamed && !FS::$secMgr->isPath($chrootnamed))
				) {
				$this->log(2,"Some datas are invalid or wrong for add server");
				FS::$iMgr->ajaxEcho("err-miss-bad-fields");
				return;
			}

			$ssh = new SSH($saddr);
			if(!$ssh->Connect()) {
				FS::$iMgr->ajaxEcho("err-unable-conn");
				return;
			}
			if(!$ssh->Authenticate($slogin,$spwd)) {
				FS::$iMgr->ajaxEcho("err-bad-login");
				return;
			}
		
			$exists = $this->exists($saddr);
			if($edit) {	
				if(!$exists) {
					$this->log(1,"Unable to add server '".$saddr."': already exists");
					FS::$iMgr->ajaxEcho($this->errAlreadyExists);
					return;
				}

			}
			else {
				if($exists) {
					$this->log(1,"Unable to add server '".$saddr."': already exists");
					FS::$iMgr->ajaxEcho($this->errNotExists);
					return;
				}
			}

			FS::$dbMgr->BeginTr();

			if ($edit) {
				FS::$dbMgr->Delete($this->sqlTable,"addr = '".$saddr."'");
			}
			FS::$dbMgr->Insert($this->sqlTable,"addr,sshuser,sshpwd,namedpath,chrootpath",
				"'".$saddr."','".$slogin."','".$spwd."','".$namedpath."','".$chrootnamed."'");

			FS::$dbMgr->CommitTr();

			//$this->log(0,"Added server '".$saddr."'");

			$js = $this->tMgr->addLine($saddr,$edit);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		public function Remove() {
			if(!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
			
			if (!$addr) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}

			if (!$this->exists($addr)) {
				FS::$iMgr->ajaxEcho($this->errNotExists);
				return;
			}
			
			$this->removeFromDB($addr);
			//$this->log(0,"Removing server '".$addr."'");

			$js = $this->tMgr->removeLine($addr);
			FS::$iMgr->ajaxEcho("Done",$js);
		}
		private $addr;
		private $sshUser;
		private $chrootPath;
		private $namedPath;
		private $TSIGKey;
	};

	final class dnsTSIGKey extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_tsig";
			$this->sqlAttrId = "keyalias";
			$this->readRight = "mrule_dnsmgmt_read";
			$this->writeRight = "mrule_dnsmgmt_write";
			$this->errNotExists = "err-tsig-key-not-exists";
			$this->errAlreadyExists = "err-tsig-key-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "tsig",
				"sqltable" => "dns_tsig",
				"sqlattrid" => "keyalias",
				"attrlist" => array(array("key-alias","keyalias",""), array("key-id","keyid",""),
					array("algorithm","keyalgo","sr",array(1 => "HMAC-MD5", 2 => "HMAC-SHA1", 3 => "HMAC-SHA256")),
					array("Value","keyvalue","")),
				"sorted" => true,
				"odivnb" => 4,
				"odivlink" => "keyalias=",
				"rmcol" => true,
				"rmlink" => "mod=".$this->mid."&act=6&keyalias",
				"rmconfirm" => "confirm-remove-tsig",
			));
		}

		protected function Load($name = "") {
			$this->name = $name;
			$this->keyid = ""; $this->keyvalue = ""; $this->keyalgo = "";

			if($this->name) {
				if($data = FS::$dbMgr->GetOneEntry($this->sqlTable,"keyid,keyalgo,keyvalue",
					"keyalias = '".$name."'")) {
					$this->keyid = $data["keyid"];
					$this->keyalgo = $data["keyalgo"];
					$this->keyvalue = $data["keyvalue"];
					return true;
				}
				return false;
			}
			return true;
		}

		protected function removeFromDB($name) {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlTable,"keyalias = '".$name."'");
			FS::$dbMgr->CommitTr();
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(3,$this->loc->s("define-tsig-key"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($name = "") {
			if (!$this->canRead()) {
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			}

			if (!$this->Load($name)) {
				return FS::$iMgr->printError($this->loc->s($this->errNotExists));
			}

			$output = FS::$iMgr->cbkForm("5")."<table>".
				FS::$iMgr->idxLine($this->loc->s("key-alias"),"keyalias",$this->name,array("type" => "idxedit", "length" => 64,
					"edit" => $this->name != "")).
				FS::$iMgr->idxLine($this->loc->s("key-id"),"keyid",$this->keyid,array("length" => 32, "value" => $this->keyid)).
				"<tr><td>".$this->loc->s("algorithm")."</td><td>".FS::$iMgr->select("keyalgo").
					FS::$iMgr->selElmt("HMAC-MD5",1,$this->keyalgo == 1).FS::$iMgr->selElmt("HMAC-SHA1",2,$this->keyalgo == 2).
					FS::$iMgr->selElmt("HMAC-SHA256",3,$this->keyalgo == 3)."</select>".
				FS::$iMgr->idxLine($this->loc->s("Value"),"keyvalue",$this->keyvalue,array("length" => 128, "size" => 30, "value" => $this->keyvalue)).
				FS::$iMgr->aeTableSubmit($this->name == "");

			return $output;
		}

		public function Modify() {
			if(!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$keyalias = FS::$secMgr->checkAndSecurisePostData("keyalias");
			$keyid = FS::$secMgr->checkAndSecurisePostData("keyid");
			$keyalgo = FS::$secMgr->checkAndSecurisePostData("keyalgo");
			$keyvalue = FS::$secMgr->checkAndSecurisePostData("keyvalue");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if(!$keyalias || !$keyid || !$keyalgo || !FS::$secMgr->isNumeric($keyalgo) || !$keyvalue ||
				$edit && $edit != 1) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}

			$exist = $this->exists($keyalias);
			if($edit) {
				if(!$exist) {
					FS::$iMgr->ajaxEcho($this->errNotExists);
					return;
				}
			}
			else {
				if($exist) {
					FS::$iMgr->ajaxEcho($this->errAlreadyExists);
					return;
				}
				$exist = FS::$dbMgr->GetOneEntry($this->sqlTable,"keyalias","keyid = '".$keyid.
					"' AND keyalgo = '".$keyalgo."' AND keyvalue = '".$keyvalue."'");
				if($exist) {
					FS::$iMgr->ajaxEcho("err-tsig-key-exactly-same");
					return;
				}
			}
			
			if(!FS::$secMgr->isHostname($keyid)) {
				FS::$iMgr->ajaxEcho("err-tsig-key-id-invalid");
				return;
			}

			if($keyalgo < 1 || $keyalgo > 3) {
				FS::$iMgr->ajaxecho("err-tsig-key-algo-invalid");
				return;
			}

			FS::$dbMgr->BeginTr();
			if($edit) {
				FS::$dbMgr->Delete($this->sqlTable,"keyalias = '".$keyalias."'");
			}
			FS::$dbMgr->Insert($this->sqlTable,"keyalias,keyid,keyalgo,keyvalue","'".$keyalias."','".
				$keyid."','".$keyalgo."','".$keyvalue."'");
			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($keyalias,$edit);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		public function Remove() {
			if(!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 
			$keyalias = FS::$secMgr->checkAndSecuriseGetData("keyalias");
			if(!$keyalias) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}
			
			if(!$this->exists($keyalias)) {
				FS::$iMgr->ajaxEcho($this->errNotExists);
				return;
			}

			$this->removeFromDB($keyalias);

			$js = $this->tMgr->removeLine($keyalias);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		private $name;
		private $keyid;
		private $keyvalue;
		private $keyalgo;
	};
?>
