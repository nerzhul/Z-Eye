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
	
	final class routerObj extends FSMObj {
		function __construct() {
			parent::__construct();
			//$this->sqlTable = PGDbConfig::getDbPrefix()."dns_zones";
			$this->sqlAttrId = "zonename";
			$this->readRight = "mrule_routermgmt_zone_read";
			$this->writeRight = "mrule_routermgmt_zone_write";
			$this->errNotExists = "err-router-not-exists";
			$this->errAlreadyExists = "err-router-already-exists";

			/*$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "dnszone",
				"sqltable" => "dns_zones",
				"sqlattrid" => "zonename",
				"attrlist" => array(array("Zone","zonename",""), array("Zone-type","zonetype","s",
					array(1 => "Classic", 2 => "Slave-only", 3 => "Forward-only")),
					array("Desc","description","")),
				"sorted" => true,
				"odivnb" => 10,
				"odivlink" => "zonename=",
				"rmcol" => true,
				"rmlink" => "zonename",
				"rmactid" => 12,
				"rmconfirm" => "confirm-remove-zone",
			));*/
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(9,$this->loc->s("declare-router"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($rname = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printError("err-no-right");
			}

			if (!$this->Load($rname)) {
				return FS::$iMgr->printError($this->errNotExists);
			}
			return $output;
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
			}
			else {
			}
		}

		protected function Load($name = "") {
			$this->routername = $name;

			if ($this->routername) {
				return false;
			}
			return true;
		}

		protected function removeFromDB($rname) {
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEchoError("err-no-right");
				return;
			} 

			$rname = FS::$secMgr->checkAndSecurisePostData("rname");

			FS::$dbMgr->BeginTr();

			if ($edit) {
				$this->removeFromDB($rname);
			}

			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($rname,$edit);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEchoError("err-no-right");
				return;
			} 

			$rname = FS::$secMgr->checkAndSecuriseGetData("rname");

			if (!$zonename) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!$this->exists($rname)) {
				FS::$iMgr->ajaxEchoError($this->errNotExists);
				return;
			}

			FS::$dbMgr->BeginTr();
			$this->removeFromDB($rname);
			FS::$dbMgr->CommitTr();

			$this->log(0,"Removing router '".$rname."'");

			$js = $this->tMgr->removeLine($rname);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}
		
		private $routername;
	};
?>
