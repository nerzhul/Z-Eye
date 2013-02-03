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

	class iLogs extends genModule{
		function iLogs() { parent::genModule(); $this->loc = new lLogs(); }
		public function Load() {
			FS::$iMgr->setCurrentModule($this);
			$output = $this->showLogs();
			return $output;
		}

		private function getApplicationLogs() {
			$ufilter = FS::$secMgr->checkAndSecurisePostData("uf");
			$appfilter = FS::$secMgr->checkAndSecurisePostData("af");
			$lfilter = FS::$secMgr->checkAndSecurisePostData("lf");
			
			$filter = "";
			if($ufilter)
				$filter .= "_user = '".$ufilter."'";
			if($appfilter) {
				if(strlen($filter) > 0) $filter .= " AND ";
				$filter .= "module = '".$appfilter."'";
			}
			if($lfilter != NULL) {
				if(strlen($filter) > 0) $filter .= " AND ";
				$filter .= "level = '".$lfilter."'";
			}
			
			$output = "";
			$found = false;
			$query = FS::$dbMgr->Select("z_eye_logs","date,module,level,_user,txt",$filter,"date",1);
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Date")."</th><th>".$this->loc->s("Module")."</th><th>".$this->loc->s("Level")."</th>
						<th>".$this->loc->s("User")."</th><th>".$this->loc->s("Entry")."</th></tr>";
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
			
			if($found) $output .= "</table>";
			else $output .= FS::$iMgr->printError($this->loc->s("err-no-logs"));
			return $output;
		}

		private function showLogs() {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid,$this->loc->s("webapp"),$sh);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid,$this->loc->s("Collector"),$sh);
				//$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid,$this->loc->s("Stats"),$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
		}
			else if(!$sh || $sh == 1) {
				$output = "<script type=\"text/javascript\">function filterAppLogs() {
					$('#logd').fadeOut();
					$.post('index.php?mod=".$this->mid."&act=1', $('#logf').serialize(), function(data) {
						$('#logd').html(data);
						$('#logd').fadeIn();
						});
					}</script>";
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=1",array("id" => "logf"));
				$output .= FS::$iMgr->select("uf","filterAppLogs()");
				$output .= FS::$iMgr->selElmt("--".$this->loc->s("User")."--","",true);
				$query = FS::$dbMgr->Select("z_eye_logs","_user","_user = _user GROUP BY _user","_user",2);
				while($data = FS::$dbMgr->Fetch($query))
					$output .= FS::$iMgr->selElmt($data["_user"],$data["_user"]);

				$output .= "</select>".FS::$iMgr->select("af","filterAppLogs()");
				$output .= FS::$iMgr->selElmt("--".$this->loc->s("Module")."--","",true);
				$query = FS::$dbMgr->Select("z_eye_logs","module","module = module GROUP BY module","module",2);
				while($data = FS::$dbMgr->Fetch($query))
					$output .= FS::$iMgr->selElmt($data["module"],$data["module"]);
				
				$output .= "</select>".FS::$iMgr->select("lf","filterAppLogs()");
				$output .= FS::$iMgr->selElmt("--".$this->loc->s("Level")."--","",true);
				$output .= FS::$iMgr->selElmt("Info",0);
				$output .= FS::$iMgr->selElmt("Warn",1);
				$output .= FS::$iMgr->selElmt("Crit",2);
				
				$output .= "</select>".FS::$iMgr->button("but",$this->loc->s("Filter"),"filterAppLogs()");
				$output .= "</form>";
				$output .= "<div id=\"logd\">".$this->getApplicationLogs()."</div>";
			}
			else if($sh == 2) {
				$output = "<pre>";
				$fp = fopen(dirname(__FILE__)."/../../../datas/logs/z_eye_collector.log","r");
				fseek($fp,-(sizeof('a')), SEEK_END);
				$linecount = 30;
				$fileoutput = "";
				$line = "";
				while(ftell($fp) > 0 && $linecount > 0) {
					$chr = fgetc($fp);
					if($chr == "\n") {
						$fileoutput .= $line."<br />";
						$line = "";
						$linecount--;
					}
					else
						$line = $chr.$line;
					fseek($fp, -(sizeof('a') * 2), SEEK_CUR);
				}
				fclose($fp);
				$output .= preg_replace("#\[Z\-Eye\]#","",$fileoutput);
				$output .= "</pre>";
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
?>
