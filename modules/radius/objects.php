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
	
	final class radiusGroup extends radiusObject {
		function __construct() {
			parent::__construct();
			$this->readRight = "rule-read-datas";
			$this->writeRight = "rule-write-datas";
		}
		
		public function showForm($groupname = "") {
			if (!$this->canWrite()) {
				return FS::$iMgr->printError("err-no-right");
			}
			
			$radalias = FS::$secMgr->checkAndSecuriseGetData("ra");
			
			$radSQLMgr = $this->connectToRaddb($radalias);
			if (!$radSQLMgr) {
				return FS::$iMgr->printError("err-db-conn-fail");
			}
			
			if ($groupname) {
				$groupexist = $radSQLMgr->GetOneData($this->raddbinfos["tradgrpchk"],"groupname","groupname = '".$groupname."'");
				// If group is not in check attributes, look into reply attributes
				if (!$groupexist) {
					$groupexist = $radSQLMgr->GetOneData($this->raddbinfos["tradgrprep"],"groupname","groupname = '".$groupname."'");
				}
				if (!$groupexist) {
					return FS::$iMgr->printError(sprintf($this->loc->s("err-group-not-exists"),$groupname),true);
				}
				
				$attrcount = $radSQLMgr->Count($this->raddbinfos["tradgrpchk"],"groupname","groupname = '".$groupname."'");
				$attrcount += $radSQLMgr->Count($this->raddbinfos["tradgrprep"],"groupname","groupname = '".$groupname."'");
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
				$query = $radSQLMgr->Select($this->raddbinfos["tradgrpchk"],"attribute,op,value","groupname = '".$groupname."'");
				while ($data = $radSQLMgr->Fetch($query)) {
					FS::$iMgr->js("addAttrElmt('".$data["attribute"]."','".$data["value"]."','".$data["op"]."','1');");
				}

				$query = $radSQLMgr->Select($this->raddbinfos["tradgrprep"],"attribute,op,value","groupname = '".$groupname."'");
				while ($data = $radSQLMgr->Fetch($query)) {
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
				FS::$iMgr->hidden("r",$raddb).FS::$iMgr->hidden("h",$radhost).FS::$iMgr->hidden("p",$radport).
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

			$radSQLMgr = new AbstractSQLMgr();
			if ($radSQLMgr->setConfig($this->raddbinfos["dbtype"],$this->raddbinfos["dbname"],
				$this->raddbinfos["port"],$this->raddbinfos["addr"],$this->raddbinfos["login"],
				$this->raddbinfos["pwd"]) == 0) {
				if ($radSQLMgr->Connect() == NULL) {
					return NULL; 
				}
			}
			return $radSQLMgr;
		}
		
		protected $raddbinfos;
	}
?>

