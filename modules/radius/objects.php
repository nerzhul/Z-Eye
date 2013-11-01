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
	
	final class radiusGroup extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->readRight = "rule-read-datas";
			$this->writeRight = "rule-write-datas";
		}
		
		public function showForm($groupname = "") {
			if (!$this->canWrite()) {
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			}
			
			$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
			$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
			$radport = FS::$secMgr->checkAndSecuriseGetData("p");
			
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
			
			$tempSelect = FS::$iMgr->select("radgrptpl",array("js" => "addTemplAttributes()")).
				FS::$iMgr->selElmt($this->loc->s("None"),0).
				FS::$iMgr->selElmt("VLAN",1)."</select>";
				
			return FS::$iMgr->cbkForm("3")."<table>".
				FS::$iMgr->idxLines(array(
					array("Profilname","groupname",array("type" => "idxedit",
						"length" => "40", "edit" => false)),
					array("Template","",array("type" => "raw", "value" => $tempSelect)),
					array("Attributes","",array("type" => "raw", "value" => "<span id=\"attrgrpn\"></span>"))
				)).
				"<tr><td colspan=\"2\">".
				FS::$iMgr->hidden("r",$raddb).FS::$iMgr->hidden("h",$radhost).FS::$iMgr->hidden("p",$radport).
				FS::$iMgr->button("newattr","Nouvel attribut","addAttrElmt('','','','')").
				FS::$iMgr->submit("",$this->loc->s("Save")).
				"</td></tr></table></form>";
		}
	};	
	
?>
