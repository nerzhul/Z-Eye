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
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->vlanTable,"vlan","CAST(vlan as TEXT) ILIKE '".$search."%'",
					array("order" => "vlan","limit" => "10","group" => "vlan"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["vlan"][] = $data["vlan"];
				}
				
				$query = FS::$dbMgr->Select($this->sqlTable,"name","name ILIKE '".$search."%'",
					array("order" => "name","limit" => "10","group" => "name"));
				while ($data = FS::$dbMgr->Fetch($query)) {
						$autoresults["device"][] = $data["name"];
				}
			}
			else {
				$output = "";
				$resout = "";
				$found = false;
				
				// Device himself
				$query = FS::$dbMgr->Select($this->sqlTable,"mac,ip,description,model","name ILIKE '".$search."'");
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
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"name","name ILIKE '".$search."%'",
					array("order" => "name","limit" => "10","group" => "name"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["portname"][] = $data["name"];
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
					$prise =  FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise","ip = '".$data["ip"]."' AND port = '".$data["port"]."'");
					if (!isset($devportname[$swname]))
						$devportname[$swname] = array();

					$devportname[$swname][$data["port"]] = array($data["name"],$prise);

					//$this->nbresults++;
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
	};
	
	final class netNode extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = "node";
			$this->nbtTable = "node_nbt";
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->nbtTable,"domain","domain ILIKE '%".$search."%'",
					array("order" => "domain","limit" => "10","group" => "domain"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["nbdomain"][] = $data["domain"];
				}

				$query = FS::$dbMgr->Select($this->nbtTable,"nbname","nbname ILIKE '%".$search."%'",
					array("order" => "nbname","limit" => "10","group" => "nbname"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["nbname"][] = $data["nbname"];
				}
			}
			else {
				$output = "";
				$resout = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->nbtTable,"mac,ip,domain,nbname,nbuser,time_first,time_last",
					"domain ILIKE '%".$search."%' OR nbname ILIKE '%".$search."%'");
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
			}
		}
		
		private $nbtTable;
	};
	
	final class netPlug extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."switch_port_prises";
			$this->deviceTable = "device";
			$this->devPortTable = "device_port";
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"prise","prise ILIKE '".$search."%'",array("order" => "prise","limit" => "10","group" => "prise"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["prise"][] = $data["prise"];
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
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"room","room ILIKE '".$search."%'",
					array("order" => "room","limit" => "10","group" => "room"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["room"][] = $data["room"];
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
					$devroom[$swname][$data["port"]]["desc"] = FS::$dbMgr->GetOneData($this->sqlTable,"name",
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
