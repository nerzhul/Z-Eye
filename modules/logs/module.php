<?php
	/*
	* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

	require_once(dirname(__FILE__)."/rules.php");
	require_once(dirname(__FILE__)."/../../lib/FSS/LDAP.FS.class.php");

	if(!class_exists("iLogs")) {
		
	final class iLogs extends FSModule {
		function __construct() {
			parent::__construct();
			$this->rulesclass = new rLogs();
			
			$this->menu = _("Z-Eye Engine");
			$this->menutitle = _("Z-Eye logs");
		}

		public function Load() {
			FS::$iMgr->setTitle(_("menu-title"));
			$output = $this->showLogs();
			return $output;
		}

		private function getApplicationLogs() {
			$ufilter = FS::$secMgr->checkAndSecurisePostData("uf");
			$appfilter = FS::$secMgr->checkAndSecurisePostData("af");
			$lfilter = FS::$secMgr->checkAndSecurisePostData("lf");
			
			$filter = "";
			if ($ufilter)
				$filter .= "_user = '".$ufilter."'";
			if ($appfilter) {
				if (strlen($filter) > 0) $filter .= " AND ";
				$filter .= "module = '".$appfilter."'";
			}
			if ($lfilter != NULL) {
				if (strlen($filter) > 0) $filter .= " AND ";
				$filter .= "level = '".$lfilter."'";
			}
			
			$output = "";
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."logs","date,module,level,_user,txt",$filter,array("order" => "date","ordersens" => 1));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
					$output .= "<table id=\"tlogList\"><thead><tr><th class=\"headerSortDown\">"._("Date")."</th><th>"._("Module")."</th><th>"._("Level")."</th>
						<th>"._("User")."</th><th>"._("Entry")."</th></tr></thead>";
				}
				$date = preg_split("#[.]#",$data["date"]);
				$lineoutput = "<td>".$date[0]."</td><td>".$data["module"]."</td><td>";
				switch($data["level"]) {
					case 0: $lineoutput .= "Info"; $lineoutput = "<tr>".$lineoutput; break;
					case 1: $lineoutput .= "Warn"; $lineoutput = "<tr style=\"background-color: #FFDD00;\">".$lineoutput; break;
					case 2: $lineoutput .= "Crit"; $lineoutput = "<tr style=\"background-color: #EE0000;\">".$lineoutput; break;
					default: $lineoutput .= "Unk"; $lineoutput = "<tr>".$lineoutput; break;
				}
				$output .= $lineoutput."</td><td>".$data["_user"]."</td><td>".preg_replace("#[\n]#","<br />",$data["txt"])."</td></tr>";
			}
			
			if ($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tlogList");
			}
			else $output .= FS::$iMgr->printError("err-no-logs");
			return $output;
		}

		private function showLogs() {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			if (!FS::isAjaxCall()) {
				$panElmts = array(array(1,"mod=".$this->mid,_("webapp")),
					array(2,"mod=".$this->mid,_("Service")));
				$output .= FS::$iMgr->tabPan($panElmts,$sh);
			}
			else if (!$sh || $sh == 1) {
				FS::$iMgr->setURL("sh=1");
				FS::$iMgr->js("function filterAppLogs() {
					$('#logd').fadeOut();
					$.post('?mod=".$this->mid."&act=1',
						$('#logf').serialize(), function(data) {
						setJSONContent('#logd',data);
						$('#logd').fadeIn();
						});
					}");
				
				$output = FS::$iMgr->form("?mod=".$this->mid."&act=1",array("id" => "logf")).
					FS::$iMgr->select("uf",array("js" => "filterAppLogs()")).
					FS::$iMgr->selElmt("--"._("User")."--","",true);
					
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."logs",
					"_user","",array("order" => "_user","ordersens" => 2,"group" => "_user"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$output .= FS::$iMgr->selElmt($data["_user"],$data["_user"]);
				}

				$output .= "</select>".FS::$iMgr->select("af",array("js" => "filterAppLogs()")).
					FS::$iMgr->selElmt("--"._("Module")."--","",true);
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."logs",
					"module","",array("order" => "module","ordersens" => 2,"group" => "module"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$output .= FS::$iMgr->selElmt($data["module"],$data["module"]);
				}
				
				$output .= "</select>".FS::$iMgr->select("lf",array("js" => "filterAppLogs()")).
					FS::$iMgr->selElmt("--"._("Level")."--","",true).
					FS::$iMgr->selElmt("Info",0).
					FS::$iMgr->selElmt("Warn",1).
					FS::$iMgr->selElmt("Crit",2).
					"</select>".FS::$iMgr->button("but",_("Filter"),"filterAppLogs()").
					"</form>".
					"<div id=\"logd\">".$this->getApplicationLogs()."</div>";
			}
			else if ($sh == 2) {
				FS::$iMgr->setURL("sh=2");
				return file_get_contents("http://localhost:8080/logs/service_logs");
			}
			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Ajax filtering for app log
				echo $this->getApplicationLogs();
				return;
			}
		}
	};
	
	}
	
	$module = new iLogs();
?>
