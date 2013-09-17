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
	
	final class netDevice extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = "device";
			$this->vlanTable = "device_vlan";
			$this->readRight = "mrule_switches_read";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return "";
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->vlanTable,"vlan","CAST(vlan as TEXT) ILIKE '".$search."%'",
					array("order" => "vlan","limit" => "10","group" => "vlan"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("vlan",$data["vlan"]);
				}
				
				$query = FS::$dbMgr->Select($this->sqlTable,"name","name ILIKE '".$search."%'",
					array("order" => "name","limit" => "10","group" => "name"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("device",$data["name"]);
				}
				
				$query = FS::$dbMgr->Select($this->sqlTable,"ip","host(ip) ILIKE '".$search."%'",
					array("order" => "ip","limit" => "10","group" => "ip"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("ip",$data["ip"]);
				}
				
				$query = FS::$dbMgr->Select($this->sqlTable,"mac","text(mac) ILIKE '".$search."%'",
					array("order" => "mac","limit" => "10","group" => "mac"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("mac",$data["mac"]);
				}
			}
			else {
				$output = "";
				$resout = "";
				$found = false;
				
				// Device himself
				$query = FS::$dbMgr->Select($this->sqlTable,"mac,ip,description,model",
					"name ILIKE '".$search."' OR host(ip) = '".$search."' OR CAST(mac as varchar) = '".$search."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$output = "<b>".$this->loc->s("Informations")."<i>: </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
					if (strlen($data["mac"]) > 0) {
						$output .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a> - ";
					}
					$output .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a>)<br />";
						"<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
						"<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br />";
						
					$resout .= $this->searchResDiv($locoutput,"Network-device");
					//$this->nbresults++;
				}
				
				// VLAN on a device
				$output = "";
				
				if (FS::$secMgr->isNumeric($search)) {
					$query = FS::$dbMgr->Select($this->vlanTable,"ip,description","vlan = '".$search."'",array("order" => "ip"));
					while ($data = FS::$dbMgr->Fetch($query)) {
						if ($found = false) {
							$found = true;
						}
						
						if ($dname = FS::$dbMgr->GetOneData($this->sqlTable,"name","ip = '".$data["ip"]."'")) {
							$output .= "<li> <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$dname."&fltr=".$search."\">".$dname."</a> (".$data["description"].")<br />";
						}
					}
					if ($found) {
						$resout .= $this->searchResDiv($output,"title-vlan-device");
					}
				}
				return $resout;
			}
		}
		private $vlanTable;
	};
	
	final class netDevicePort extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = "device_port";
			$this->sqlPlugRoomTable = PGDbConfig::getDbPrefix()."switch_port_prises";
			$this->readRight = "mrule_switches_read";
		}
		
		public function Load($device = "", $port = "") {
			$this->device = $device;
			$this->port = $port;
			$this->plug = "";
			$this->room = "";
			$this->deviceIP = "";
			
			if ($this->device != "" && $this->port != "") {
				$this->deviceIP = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");

				if (!$this->deviceIP) {
					return false;
				}
				$query = FS::$dbMgr->Select($this->sqlPlugRoomTable,"prise,room","ip = '".$this->deviceIP.
					"' AND port = '".$this->port."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					$this->plug = $data["prise"];
					$this->room = $data["room"];
				}
			}
			return true;
		}
		
		public function removeDatas() {
			FS::$dbMgr->Delete($this->sqlPlugRoomTable,"ip = '".$dip."'");
		}
		
		public function SaveRoomAndPlug() {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlPlugRoomTable,"ip = '".$this->deviceIP."' AND port = '".$this->port."'");
			FS::$dbMgr->Insert($this->sqlPlugRoomTable,"ip,port,prise,room","'".$this->deviceIP."','".$this->port."','".$this->plug."','".$this->room."'");
			FS::$dbMgr->CommitTr();
		}
		
		public function injectPlugRoomCSV() {
			$csv = FS::$secMgr->checkAndSecurisePostData("csv");
			$sep = FS::$secMgr->checkAndSecurisePostData("sep");
			$repl = FS::$secMgr->checkAndSecurisePostData("repl");
			
			if (!$csv || !$sep || $sep != "," && $sep != ";") {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}
			
			$csv = preg_replace("#[\r]#","",$csv);
			$lines = preg_split("#[\n]#",$csv);
			if (!$lines) {
				FS::$iMgr->ajaxEcho("err-invalid-csv");
				return;
			}
			
			$plugAndRooms = array();
			
			$count = count($lines);
			for ($i=0;$i<$count;$i++) {
				$entry = preg_split("#[".$sep."]#",$lines[$i]);
				
				// Entry has 4 fields
				if (count($entry) != 4) {
					FS::$iMgr->ajaxEcho($this->loc->s("err-invalid-csv-entry").$entry."'","",true);
					return;
				}
				
				// Create array dimension by device
				if (!isset($plugAndRooms[$entry[0]])) {
					$plugAndRooms[$entry[0]] = array();
				}
				
				// Create array dimension by port
				if (!isset($plugAndRooms[$entry[0]][$entry[1]])) {
					$plugAndRooms[$entry[0]][$entry[1]] = array();
				}
				
				// Store port informations into buffer
				$plugAndRooms[$entry[0]][$entry[1]][0] = $entry[2];
				$plugAndRooms[$entry[0]][$entry[1]][1] = $entry[3];
			}
			
			// Now we test if devices and ports exists
			foreach ($plugAndRooms as $device => $ports) {
				$deviceIP = FS::$dbMgr->GetOneData("device","ip","name = '".$device."'");
				if (!$deviceIP) {
					FS::$iMgr->ajaxEcho($this->loc->s("err-invalid-csv-device").$device,"",true);
					return;
				}
				foreach($ports as $port => $values) {
					if (!FS::$dbMgr->GetOneData($this->sqlTable,"name","ip = '".$deviceIP."' AND port = '".$port."'")) {
						FS::$iMgr->ajaxEcho($this->loc->s("err-invalid-csv-port").$device."/".$port."'","",true);
						return;
					}
					
					if ($repl != "on" && FS::$dbMgr->GetOneData($this->sqlPlugRoomTable,"ip",
						"ip = '".$deviceIP."' AND port = '".$port."' AND (prise != '' OR room != '')")) {
						FS::$iMgr->ajaxEcho($this->loc->s("err-csv-replace-data").$device."/".$port."'","",true);
						return;
					}
					
					// We add device IP there for improve perfs (not the best location)
					$plugAndRooms[$device][$port][2] = $deviceIP;
				}
			}
			
			FS::$dbMgr->BeginTr();
			foreach ($plugAndRooms as $device => $ports) {
				foreach($ports as $port => $values) {
					if ($repl == "on") {
						FS::$dbMgr->Delete($this->sqlPlugRoomTable,"ip = '".$values[2]."' AND port = '".$port."'");
					}
					
					FS::$dbMgr->Insert($this->sqlPlugRoomTable,"ip,port,prise,room","'".$values[2]."','".$port."','".$values[0]."','".$values[1]."'");
				}
			}
			FS::$dbMgr->CommitTr();
			FS::$iMgr->ajaxEcho("Done");
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return "";
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"name","name ILIKE '".$search."%'",
					array("order" => "name","limit" => "10","group" => "name"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("devport",$data["name"]);
				}
			}
			else {
				$output = "";
				$resout = "";
				$found = false;
				
				$devportname = array();
				$query = FS::$dbMgr->Select($this->sqlTable,"ip,port,name","name ILIKE '%".$search."%'",array("order" => "ip,port"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false)
						$found = true;
					$swname = FS::$dbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
					$prise =  FS::$dbMgr->GetOneData($this->sqlPlugRoomTable,"prise","ip = '".$data["ip"]."' AND port = '".$data["port"]."'");
					if (!isset($devportname[$swname]))
						$devportname[$swname] = array();

					$devportname[$swname][$data["port"]] = array($data["prise"],$prise);
				}

				if ($found) {
					$output = "";
					foreach ($devportname as $device => $devport) {
						if ($output != "") {
							$output .= FS::$iMgr->hr();
						}
						
						$output .= $this->loc->s("Device").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
							"&d=".$device."\">".$device."</a><ul>";
						foreach ($devport as $port => $portdata) {
							$convport = preg_replace("#\/#","-",$port);
							$output .= "<li><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."#".$convport."\">".$port."</a> ".
								"<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> ".
								"<br /><b>".$this->loc->s("Description").":</b> ".$portdata[0];
								
							if ($portdata[1]) {
								$output .= "<br /><b>".$this->loc->s("Plug").":</b> ".$portdata[1];
							}
							$output .= "</li>";
						}
						$output .= "</ul>";
					}
					$resout .= $this->searchResDiv($output,"Ref-desc");
				}
				return $resout;
			}
		}
		
		public function setPlug($plug) {
			$this->plug = $plug;
		}
		
		public function getPlug() {
			return $this->plug;
		}
		
		public function setRoom($room) {
			$this->room = $room;
		}
		
		public function getRoom() {
			return $this->room;
		}
		
		private $device;
		private $deviceIP;
		private $port;
		private $plug;
		private $room;
		
		private $sqlPlugRoomTable;
	};
	
	final class netNode extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = "node";
			$this->nbtTable = "node_nbt";
			$this->nipTable = "node_ip";
			$this->readRight = "mrule_switches_read";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return "";
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->nbtTable,"domain","domain ILIKE '%".$search."%'",
					array("order" => "domain","limit" => "10","group" => "domain"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("nbdomain",$data["domain"]);
				}

				$query = FS::$dbMgr->Select($this->nbtTable,"nbname","nbname ILIKE '%".$search."%'",
					array("order" => "nbname","limit" => "10","group" => "nbname"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("nbname",$data["nbname"]);
				}
				
				$query = FS::$dbMgr->Select($this->nipTable,"ip","host(ip) ILIKE '".$search."%'",
					array("order" => "ip","limit" => "10","group" => "ip"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("ip",$data["ip"]);
				}
				
				$query = FS::$dbMgr->Select($this->nbtTable,"ip","host(ip) ILIKE '".$search."%'",
					array("order" => "ip","limit" => "10","group" => "ip"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("ip",$data["ip"]);
				}
				
				$query = FS::$dbMgr->Select($this->nipTable,"mac","text(mac) ILIKE '".$search."%'",
					array("order" => "mac","limit" => "10","group" => "mac"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("mac",$data["mac"]);
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"mac","text(mac) ILIKE '".$search."%'",
					array("order" => "mac","limit" => "10","group" => "mac"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("mac",$data["mac"]);
				}

				$query = FS::$dbMgr->Select($this->nbtTable,"mac","text(mac) ILIKE '".$search."%'",
					array("order" => "mac","limit" => "10","group" => "mac"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("mac",$data["mac"]);
				}
			}
			else {
				$output = "";
				$resout = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->nbtTable,"mac,ip,domain,nbname,nbuser,time_first,time_last",
					"domain ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
						$output = "<table class=\"standardTable\"><tr><th>".$this->loc->s("Node")."</th><th>".
							$this->loc->s("Name")."</th><th>".$this->loc->s("User")."</th><th>".$this->loc->s("First-view")."</th><th>".$this->loc->s("Last-view")."</th></tr>";
					}
					$fst = preg_split("#\.#",$data["time_first"]);
					$lst = preg_split("#\.#",$data["time_last"]);
					$output .= "<tr><td><a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a></td><td>".
						"\\\\<a href=\"index.php?mod=".$this->mid."&s=".$data["domain"]."\">".$data["domain"]."</a>\\<a href=\"index.php?mod=".
						$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a></td><td>".
						($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")." @ <a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".
						$data["ip"]."</a></td><td>".$fst[0]."</td><td>".$lst[0]."</td></tr>";
					//$this->nbresults++;
				}

				if ($found) {
					$resout .= $this->searchResDiv($output."</table>","title-netbios");
				}
				
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->nbtTable,"mac,ip,domain,nbname,nbuser,time_first,time_last",
					"host(ip) = '".$search."' OR nbname ILIKE '%".$search."%' OR CAST(mac AS varchar) = '".$search."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}

					$fst = preg_split("#\.#",$data["time_first"]);
					$lst = preg_split("#\.#",$data["time_last"]);

					$output .= $this->loc->s("netbios-machine").": \\\\<a href=\"index.php?mod=".$this->mid.
						"&s=".$data["domain"]."\">".$data["domain"]."</a>";
						"\\<a href=\"index.php?mod=".$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a><br />";
						$this->loc->s("netbios-user").": ".($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")."@".$search."<br />";
						"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")";
					//$this->nbresults++;
				}
				
				if ($found) {
					$resout .= $this->searchResDiv($output,"title-netbios");
				}
				
				$output = "";
				$found = false;
				$lastmac = "";
				
				$query = FS::$dbMgr->Select($this->nipTable,"mac,time_first,time_last","host(ip) = '".$search."' OR CAST(mac AS varchar) = '".$search."'",
					array("order" => "time_last","ordersens" => 1));
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
						$lastmac = $data["mac"];
					}
					else {
						$output .= FS::$iMgr->hr();
					}
					
					$fst = preg_split("#\.#",$data["time_first"]);
					$lst = preg_split("#\.#",$data["time_last"]);
					$output .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"].
						"</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0].
						" ".$this->loc->s("and-the")." ".$lst[0].")";
					//$this->nbresults++;
				}
				
				if ($found) {
					$resout .= $this->searchResDiv($output,"title-mac-addr");
				}
				
				if ($lastmac) {
					$output = "";
					$query = FS::$dbMgr->Select("node","switch,port,time_first,time_last","CAST(mac as varchar) ILIKE '".$lastmac."' AND active = 't'",array("order" => "time_last","ordersens" => 1,"limit" => 1));
					if ($data = FS::$dbMgr->Fetch($query)) {
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$switch = FS::$dbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
						$piece = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise",
							"ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
						$convport = preg_replace("#\/#","-",$data["port"]);
						
						$output .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ".
							"[<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".
							$convport."\">".$data["port"]."</a>] ".
							"<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".
							$data["port"]."\">".FS::$iMgr->img("styles/images/pencil.gif",10,10)."</a>";
							
						if ($piece) {
							$output .= "/ ".$this->loc->s("Plug")." ".$piece;
						}
						
						$output .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
						$resout .= $this->searchResDiv($output,"title-last-device");
					}
				}
			
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,"switch,port,time_first,time_last","CAST (mac AS varchar) = '".$search."'",
					array("order" => "time_last","ordersens" => 2));
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					
					$switch = FS::$dbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
					$piece = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
					$convport = preg_replace("#\/#","-",$data["port"]);
					$output .=  "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ".
						"[<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
						"&d=".$switch."#".$convport."\">".$data["port"]."</a>] ".
						"<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
						"&d=".$switch."&p=".$data["port"]."\">".FS::$iMgr->img("styles/images/pencil.gif",10,10)."</a>".
						($piece == NULL ? "" : " / Prise ".$piece);
					$fst = preg_split("#\.#",$data["time_first"]);
					$lst = preg_split("#\.#",$data["time_last"]);
					$output .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
				}

				if ($found) {
					$resout .= $this->searchResDiv($output,"title-network-places");
				}
				
				return $resout;
			}
		}
		
		private $nbtTable;
		private $nipTable;
	};
	
	final class netPlug extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."switch_port_prises";
			$this->deviceTable = "device";
			$this->devPortTable = "device_port";
			$this->readRight = "mrule_switches_read";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return "";
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"prise","prise ILIKE '".$search."%'",array("order" => "prise","limit" => "10","group" => "prise"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("plug",$data["prise"]);
				}
			}
			else {
				$found = false;
				$output = "";
				$resout = "";

				$query = FS::$dbMgr->Select($this->sqlTable,"ip,port,prise","prise ILIKE '".$search."%'",array("order" => "port"));
				$devprise = array();
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					
					$swname = FS::$dbMgr->GetOneData($this->deviceTable,"name","ip = '".$data["ip"]."'");
					if (!isset($devprise[$swname])) {
						$devprise[$swname] = array();
					}

					$devprise[$swname][$data["port"]]["plug"] = $data["prise"];
					$devprise[$swname][$data["port"]]["desc"] = FS::$dbMgr->GetOneData($this->devPortTable,"name","ip = '".$data["ip"]."' AND port = '".$data["port"]."'");
					//$this->nbresults++;
				}
				if ($found) {
					foreach ($devprise as $device => $devport) {
						$output .= $this->loc->s("Device").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
							"&d=".$device."\">".$device."</a><ul>";
						foreach ($devport as $port => $values) {
							$convport = preg_replace("#\/#","-",$port);
							$output .= "<li><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."#".$convport."\">".$port."</a> ".
								"<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> ".
								"<br /><b>".$this->loc->s("Plug").":</b> ".$values["plug"];
							if ($values["desc"]) {
								$output .= "<br /><b>".$this->loc->s("Description").":</b> ".$values["desc"];
							}
							$output .= "</li>";
						}
						$output .= "</ul><br />";
					}
					$resout .= $this->searchResDiv($output,"Ref-plug");
				}
				return $resout;
			}
		}
		
		private $deviceTable;
		private $devPortTable;
	};
	
	final class netRoom extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."switch_port_prises";
			$this->devPortTable = "device_port";
			$this->deviceTable = "device";
			$this->readRight = "mrule_switches_read";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return "";
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"room","room ILIKE '".$search."%'",
					array("order" => "room","limit" => "10","group" => "room"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("room",$data["room"]);
				}
			}
			else {
				$found = false;
				$output = "";
				$resout = "";

				$query = FS::$dbMgr->Select($this->sqlTable,"ip,port,room","room ILIKE '".$search."%'",array("order" => "port"));
				$devroom = array();
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					
					$swname = FS::$dbMgr->GetOneData($this->deviceTable,"name","ip = '".$data["ip"]."'");
					if (!isset($devroom[$swname])) {
						$devroom[$swname] = array();
					}

					$devroom[$swname][$data["port"]]["room"] = $data["room"];
					$devroom[$swname][$data["port"]]["desc"] = FS::$dbMgr->GetOneData($this->devPortTable,"name",
						"ip = '".$data["ip"]."' AND port = '".$data["port"]."'");
					//$this->nbresults++;
				}
				if ($found) {
					foreach ($devroom as $device => $devport) {
						$output .= $this->loc->s("Device").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
							"&d=".$device."\">".$device."</a><ul>";
						foreach ($devport as $port => $values) {
							$convport = preg_replace("#\/#","-",$port);
							$output .= "<li><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."#".$convport."\">".$port."</a> ".
								"<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> ".
								"<br /><b>".$this->loc->s("Room").":</b> ".$values["room"];
							if ($values["desc"]) {
								$output .= "<br /><b>".$this->loc->s("Description").":</b> ".$values["desc"];
							}
							$output .= "</li>";
						}
						$output .= "</ul><br />";
					}
					$resout .= $this->searchResDiv($output,"Ref-room");
				}
				return $resout;
			}
		}
		
		private $deviceTable;
		private $devPortTable;
	};
?>
