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
			$this->infoTable = PGDbConfig::getDbPrefix()."switch_infos";
			$this->vlanTable = "device_vlan";
			$this->readRight = "mrule_switches_read";
		}

		public function Load($device = "") {
			$this->device = $device;
			$this->ip = "";
			$this->dnsName = "";
			$this->building = "";
			$this->snmpLocation = "";
			$this->serial = "";
			$this->model = "";
			$this->os = "";
			$this->osVer = "";
			$this->snmpro = "";
			$this->snmprw = "";

			if ($this->device != "") {
				$query = FS::$dbMgr->Select($this->sqlTable,"ip,serial,model,os,os_ver,dns,location",
					"name = '".$device."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->ip = $data["ip"];
					$this->dnsName = $data["dns"];
					$this->snmpLocation = $data["location"];
					$this->serial = $data["serial"];
					$this->model = $data["model"];
					$this->os = $data["os"];
					$this->osVer = $data["os_ver"];

					$this->snmpro = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache",
						"snmpro","device = '".$this->device."'");
					$this->snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache",
						"snmprw","device = '".$this->device."'");
					$query2 = FS::$dbMgr->Select($this->infoTable,"building",
						"device = '".$this->device."'");
					if ($data2 = FS::$dbMgr->Fetch($query2)) {
						$this->building = $data2["building"];
					}
				}
				else {
					return false;
				}
			}
			return true;
		}

		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}

			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->vlanTable,"vlan","CAST(vlan as TEXT) ILIKE '".$search."%'",
					array("order" => "vlan","limit" => "10","group" => "vlan"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("vlan",$data["vlan"]);
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"name","name ILIKE '%".$search."%'",
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
				$found = false;
				// Device himself
				$query = FS::$dbMgr->Select($this->sqlTable,"name,mac,ip,description,model",
					"name ILIKE '%".$search."%' OR host(ip) = '".$search."' OR CAST(mac as varchar) ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}

					$output .= FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches")."&d=".$data["name"], $data["name"])." (";

					if (strlen($data["mac"]) > 0) {
						$output .= FS::$iMgr->aLink($this->mid."&s=".$data["mac"], $data["mac"])." - ";
					}

					$output .= FS::$iMgr->aLink($this->mid."&s=".$data["ip"], $data["ip"]).")<br />".
						"<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />".
						"<b><i>".$this->loc->s("Description").": </i></b>".
						preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"]).
						FS::$iMgr->hr();

					FS::$searchMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output,"Network-device");
				}

				// VLAN on a device
				if (FS::$secMgr->isNumeric($search)) {
					$output = "";
					$found = false;
					$query = FS::$dbMgr->Select($this->vlanTable,"ip,description","vlan = '".$search."'",array("order" => "ip"));
					while ($data = FS::$dbMgr->Fetch($query)) {
						if ($found == false) {
							$found = true;
						}

						if ($dname = FS::$dbMgr->GetOneData($this->sqlTable,"name","ip = '".$data["ip"]."'")) {
							$output .= "<li> ".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$dname."&fltr=".$search, $dname)." (".$data["description"].")<br />";
							FS::$searchMgr->incResultCount();
						}
					}
					if ($found) {
						$this->storeSearchResult($output,"title-vlan-device");
					}
				}
			}
		}

		public function showList() {
			FS::$iMgr->setURL("");

			$output = "";
			$foundsw = false;
			$foundwif = false;
			$showtitle = true;

			if (FS::$sessMgr->hasRight("discover")) {
				$showtitle = false;
				$output .= FS::$iMgr->h2("title-global-fct").
					FS::$iMgr->opendiv(1,$this->loc->s("Discover-device"),array("line" => true));
			}

			$outputswitch = "<table id=\"dev\"><thead><tr><th class=\"headerSortDown\">".
				$this->loc->s("Name")."</th><th>".$this->loc->s("IP-addr").
				"</th><th>".$this->loc->s("MAC-addr")."</th><th>".
				$this->loc->s("Model")."</th><th>".$this->loc->s("OS")."</th><th>".
				$this->loc->s("Building")."</th><th>".$this->loc->s("Room")."</th><th>".
				$this->loc->s("Place")." (SNMP)</th><th>".$this->loc->s("Serialnb").
				"</th><th>".$this->loc->s("State")."</th>";

			if (FS::$sessMgr->hasRight("rmswitch")) {
				$outputswitch .= "<th></th>";
			}

			$outputswitch .= "</tr></thead>";

			$outputwifi = FS::$iMgr->h2("title-WiFi-AP").
				"<table id=\"dev2\"><thead><tr><th class=\"headerSortDown\">".
				$this->loc->s("Name")."</th><th>".$this->loc->s("IP-addr").
				"</th><th>".$this->loc->s("Model")."</th><th>".
				$this->loc->s("OS")."</th><th>".$this->loc->s("Building")."</th><th>".
				$this->loc->s("Room")."</th><th>".
				$this->loc->s("Place")." (SNMP)</th><th>".$this->loc->s("Serialnb")."</th>";

			if (FS::$sessMgr->hasRight("rmswitch")) {
				$outputwifi .= "<th></th>";
			}
			$outputwifi .= "</tr></thead>";

			$query = FS::$dbMgr->Select("device","name,ip,mac,os,model,os_ver,location,serial","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				// Rights: show only reading/writing switches
				$snmpro = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmpro","device = '".$data["name"]."'");
				$snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$data["name"]."'");
				$building = FS::$dbMgr->GetOneData($this->infoTable,"building","device = '".$data["name"]."'");
				$room = FS::$dbMgr->GetOneData($this->infoTable,"room","device = '".$data["name"]."'");

				if (!$this->canReadOrWriteRight($snmpro,$snmprw,$data["ip"])) {
					continue;
				}

				// Split WiFi and Switches
				if (preg_match("#AIR#",$data["model"])) {
					if ($foundwif == false) {
						$foundwif = true;
					}
					$outputwifi .= "<tr><td id=\"draga\" draggable=\"true\">".FS::$iMgr->aLink($this->mid."&d=".$data["name"], $data["name"]).
						"</td><td>".$data["ip"]."</td><td>".
						$data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>";

					if (FS::$sessMgr->hasRight("write")) {
						$convname = preg_replace("#\/#","-",$data["name"]);
						$convname = preg_replace("#\.#","-",$convname);
						$outputwifi .= "<div id=\"devbding_".$convname."\">".
							"<a onclick=\"javascript:modifyBuilding('#devbding_".$convname." a',false);\">".
							"<div id=\"devbding_".$convname."l\" class=\"modbuilding\">".
							($building == "" ? $this->loc->s("Modify") : $building).
							"</div></a><a style=\"display: none;\">".
							FS::$iMgr->input("devbding-".$convname,$building,10,10).
							FS::$iMgr->button("Save","OK","javascript:modifyBuilding('#devbding_".$convname.
								"',true,'".$data["name"]."','devbding-".$convname."');").
							"</a></div>";
					}
					else {
						$outputwifi .= $building;
					}

					$outputwifi .= "</td><td>";

					if (FS::$sessMgr->hasRight("write")) {
						$convname = preg_replace("#\/#","-",$data["name"]);
						$convname = preg_replace("#\.#","-",$convname);
						$outputwifi .= "<div id=\"devroom_".$convname."\">".
							"<a onclick=\"javascript:modifyRoom('#devroom_".$convname." a',false);\">".
							"<div id=\"devroom_".$convname."l\" class=\"modroom\">".
							($room == "" ? $this->loc->s("Modify") : $room).
							"</div></a><a style=\"display: none;\">".
							FS::$iMgr->input("devroom-".$convname,$room,10,10).
							FS::$iMgr->button("Save","OK","javascript:modifyRoom('#devroom_".$convname.
								"',true,'".$data["name"]."','devroom-".$convname."');").
							"</a></div>";
					}
					else {
						$outputwifi .= $room;
					}

					$outputwifi .= "</td><td>".$data["location"]."</td><td>".
						$data["serial"]."</td>";
					if (FS::$sessMgr->hasRight("rmswitch")) {
						$outputwifi .= "<td>".FS::$iMgr->removeIcon(17,"device=".$data["name"],array("js" => true,
							"confirmtext" => "confirm-remove-device",
							"confirmval" => $data["name"]
						))."</td>";
					}
					$outputwifi .= "</tr>";
				}
				else {
					if ($foundsw == false) {
						$foundsw = true;
					}
					$outputswitch .= "<tr><td>".FS::$iMgr->aLink($this->mid."&d=".$data["name"], $data["name"]).
						"</td><td>".$data["ip"]."</td><td>".$data["mac"]."</td><td>".
						$data["model"]."</td><td>".$data["os"]." ".$data["os_ver"]."</td><td>";

					if (FS::$sessMgr->hasRight("write")) {
						$convname = preg_replace("#\/#","-",$data["name"]);
						$convname = preg_replace("#\.#","-",$convname);
						$outputswitch .= "<div id=\"devbding_".$convname."\">".
							"<a onclick=\"javascript:modifyBuilding('#devbding_".$convname." a',false);\">".
							"<div id=\"devbding_".$convname."l\" class=\"modbuilding\">".
							($building == "" ? $this->loc->s("Modify") : $building).
							"</div></a><a style=\"display: none;\">".
							FS::$iMgr->input("devbding-".$convname,$building,10,10).
							FS::$iMgr->button("Save","OK","javascript:modifyBuilding('#devbding_".$convname.
								"',true,'".$data["name"]."','devbding-".$convname."');").
							"</a></div>";
					}
					else {
						$outputswitch .= $building;
					}

					$outputswitch .= "</td><td>";

					if (FS::$sessMgr->hasRight("write")) {
						$convname = preg_replace("#\/#","-",$data["name"]);
						$convname = preg_replace("#\.#","-",$convname);
						$outputswitch .= "<div id=\"devroom_".$convname."\">".
							"<a onclick=\"javascript:modifyRoom('#devroom_".$convname." a',false);\">".
							"<div id=\"devroom_".$convname."l\" class=\"modroom\">".
							($room == "" ? $this->loc->s("Modify") : $room).
							"</div></a><a style=\"display: none;\">".
							FS::$iMgr->input("devroom-".$convname,$room,10,10).
							FS::$iMgr->button("Save","OK","javascript:modifyRoom('#devroom_".$convname.
								"',true,'".$data["name"]."','devroom-".$convname."');").
							"</a></div>";
					}
					else {
						$outputswitch .= $room;
					}

					$outputswitch .= "</td><td>".$data["location"]."</td><td>".$data["serial"]."</td><td>
						<div id=\"st".preg_replace("#[.]#","-",$data["ip"])."\">".FS::$iMgr->img("styles/images/loader.gif",24,24)."</div>".
						FS::$iMgr->js("$.post('?mod=".$this->mid."&act=19', { dip: '".$data["ip"]."' }, function(data) {
						$('#st".preg_replace("#[.]#","-",$data["ip"])."').html(data); });")."</td>";

					if (FS::$sessMgr->hasRight("rmswitch")) {
						$outputswitch .= "<td>".FS::$iMgr->removeIcon(17,"device=".$data["name"],array("js" => true,
							"confirmtext" => "confirm-remove-device",
							"confirmval" => $data["name"]
						))."</td>";
					}
					$outputswitch .= "</tr>";
				}
			}
			if ($foundsw || $foundwif) {
				if (FS::$sessMgr->hasRight("globalsave") || FS::$sessMgr->hasRight("globalbackup")) {
					if ($showtitle) $output .= FS::$iMgr->h2("title-global-fct");
					// Openable divs
					$output .= FS::$iMgr->opendiv(2,$this->loc->s("Advanced-Functions"));
				}
				$output .= FS::$iMgr->h2("title-router-switch");

				FS::$iMgr->js("function modifyBuilding(src,sbmit,device_,building_) {
					if (sbmit == true) {
					$.post('?at=3&mod=".$this->mid."&act=27', { device: device_, building: document.getElementsByName(building_)[0].value }, function(data) {
					$(src+'l').html(data); $(src+' a').toggle();
					}); }
					else $(src).toggle(); }
					function modifyRoom(src,sbmit,device_,room_) {
					if (sbmit == true) {
					$.post('?at=3&mod=".$this->mid."&act=28', { device: device_, room: document.getElementsByName(room_)[0].value }, function(data) {
					$(src+'l').html(data); $(src+' a').toggle();
					}); }
					else $(src).toggle(); }");
			}

			if ($foundsw) {
				$output .= $outputswitch.
					"</table>";
				FS::$iMgr->jsSortTable("dev");
			}
			if ($foundwif) {
				$output .= $outputwifi.
					"</table>";
				FS::$iMgr->jsSortTable("dev2");
			}

			if (!$foundsw && !$foundwif) {
				$output .= FS::$iMgr->printError("err-no-device2");
			}

			return $output;
		}

		public function modifyBuilding() {
			$device = FS::$secMgr->checkAndSecurisePostData("device");
			$building = FS::$secMgr->checkAndSecurisePostData("building");
			if (!$device || $building === NULL) {
				$this->log(2,"Some fields are missing (building fast edit)");
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!$this->Load($device)) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights();
				return;
			}

			if (FS::$dbMgr->GetOneData($this->infoTable,"device","device = '".$device."'")) {
				FS::$dbMgr->Update($this->infoTable,"building = '".$building."'","device = '".$device."'");
			}
			else {
				FS::$dbMgr->Insert($this->infoTable,"device,building","'".$device."','".$building."'");
			}

			if ($building) {
				echo $building;
			}
			else {
				FS::$iMgr->ajaxEchoOK("Modify");
			}
			$this->log(0,"Set building for '".$device."' to '".$building."'");
			return;
		}

		public function modifyRoom() {
			$device = FS::$secMgr->checkAndSecurisePostData("device");
			$room = FS::$secMgr->checkAndSecurisePostData("room");
			if (!$device || $room === NULL) {
				$this->log(2,"Some fields are missing (room fast edit)");
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!$this->Load($device)) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights();
				return;
			}

			FS::$dbMgr->BeginTr();

			if (FS::$dbMgr->GetOneData($this->infoTable,"device","device = '".$device."'")) {
				FS::$dbMgr->Update($this->infoTable,"room = '".$room."'","device = '".$device."'");
			}
			else {
				FS::$dbMgr->Insert($this->infoTable,"device,room","'".$device."','".$room."'");
			}

			FS::$dbMgr->CommitTr();

			if ($room) {
				echo $room;
			}
			else {
				FS::$iMgr->ajaxEchoOK("Modify");
			}
			$this->log(0,"Set room for '".$device."' to '".$room."'");
			return;
		}

		protected function canWrite() {
			if (!FS::$sessMgr->hasRight("snmp_".$this->snmprw."_write") &&
				!FS::$sessMgr->hasRight("ip_".$this->ip."_write")) {
				return false;
			}
			return true;
		}

		private function canReadOrWriteRight($snmpro, $snmprw, $dip) {
			if (!FS::$sessMgr->hasRight("snmp_".$snmpro."_read") &&
				!FS::$sessMgr->hasRight("snmp_".$snmprw."_write") &&
				!FS::$sessMgr->hasRight("ip_".$dip."_read") &&
				!FS::$sessMgr->hasRight("ip_".$dip."_write")) {
				return false;
			}
			return true;
		}

		private $ip;
		private $dnsName;
		private $building;
		private $snmpLocation;
		private $serial;
		private $model;
		private $os;
		private $osVer;
		private $snmpro;
		private $snmprw;

		private $infoTable;
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
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			$csv = preg_replace("#[\r]#","",$csv);
			$lines = preg_split("#[\n]#",$csv);
			if (!$lines) {
				FS::$iMgr->ajaxEchoError("err-invalid-csv");
				return;
			}

			$plugAndRooms = array();

			$count = count($lines);
			for ($i=0;$i<$count;$i++) {
				$entry = preg_split("#[".$sep."]#",$lines[$i]);

				// Entry has 4 fields
				if (count($entry) != 4) {
					FS::$iMgr->ajaxEchoError(sprintf($this->loc->s("err-invalid-csv-entry"),$entry),"",true);
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
					FS::$iMgr->ajaxEchoError($this->loc->s("err-invalid-csv-device").$device,"",true);
					return;
				}
				foreach($ports as $port => $values) {
					if (!FS::$dbMgr->GetOneData($this->sqlTable,"name","ip = '".$deviceIP."' AND port = '".$port."'")) {
						FS::$iMgr->ajaxEchoError($this->loc->s("err-invalid-csv-port").$device."/".$port."'","",true);
						return;
					}

					if ($repl != "on" && FS::$dbMgr->GetOneData($this->sqlPlugRoomTable,"ip",
						"ip = '".$deviceIP."' AND port = '".$port."' AND (prise != '' OR room != '')")) {
						FS::$iMgr->ajaxEchoError($this->loc->s("err-csv-replace-data").$device."/".$port."'","",true);
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
			FS::$iMgr->ajaxEchoOK("Done");
		}

		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
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

					$devportname[$swname][$data["port"]] = array($data["name"],$prise);
				}

				if ($found) {
					$output = "";

					foreach ($devportname as $device => $devport) {
						if ($output != "") {
							$output .= FS::$iMgr->hr();
						}

						$output .= $this->loc->s("Device").": ".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
							"&d=".$device, $device)."<ul>";
						foreach ($devport as $port => $portdata) {
							$convport = preg_replace("#\/#","-",$port);
							$output .= "<li>".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."#".$convport, $port)." ".
								FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."&p=".$port, FS::$iMgr->img("styles/images/pencil.gif",12,12))." ".
								"<br /><b>".$this->loc->s("Description").":</b> ".$portdata[0];

							if ($portdata[1]) {
								$output .= "<br /><b>".$this->loc->s("Plug").":</b> ".$portdata[1];
							}
							$output .= "</li>";

							FS::$searchMgr->incResultCount();
						}
						$output .= "</ul>";
					}
					$this->storeSearchResult($output,"Ref-desc");
				}
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
				return;
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
				$found = false;

				$query = FS::$dbMgr->Select($this->nbtTable,"mac,ip,domain,nbname,nbuser,time_first,time_last,active",
					"domain ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
						$output = "<table class=\"standardTable\"><tr><th>".$this->loc->s("Node")."</th><th>".
							$this->loc->s("Name")."</th><th>".$this->loc->s("User")."</th><th>".$this->loc->s("First-view")."</th><th>".
							$this->loc->s("Last-view")."</th><th>".$this->loc->s("Active?")."</th></tr>";
					}
					$fst = preg_split("#\.#",$data["time_first"]);
					$lst = preg_split("#\.#",$data["time_last"]);
					$output .= "<tr><td>".FS::$iMgr->aLink($this->mid."&s=".$data["mac"], $data["mac"])."</td><td>".
						"\\\\".FS::$iMgr->aLink($this->mid."&s=".$data["domain"], $data["domain"])."\\".
						FS::$iMgr->aLink($this->mid."&s=".$data["nbname"], $data["nbname"])."</td><td>".
						($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")." @ ".
						FS::$iMgr->aLink($this->mid."&s=".$data["ip"], $data["ip"]).
						"</td><td>".$fst[0]."</td><td>".$lst[0]."</td><td>";

					$output = sprintf("%s%s</td></tr>",$output,($data["active"] == 't' ? $this->loc->s("Yes") : $this->loc->s("No")));
					FS::$searchMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output."</table>","title-netbios");
				}

				$output = "";
				$found = false;

				$query = FS::$dbMgr->Select($this->nbtTable,"mac,ip,domain,nbname,nbuser,time_first,time_last,active",
					"host(ip) = '".$search."' OR nbname ILIKE '%".$search."%' OR CAST(mac AS varchar) ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}

					$fst = preg_split("#\.#",$data["time_first"]);
					$lst = preg_split("#\.#",$data["time_last"]);

					$output .= $this->loc->s("netbios-machine").": \\\\".FS::$iMgr->aLink($this->mid.
						"&s=".$data["domain"], $data["domain"]).
						"\\".FS::$iMgr->aLink($this->mid."&s=".$data["nbname"], $data["nbname"])."<br />".
						$this->loc->s("netbios-user").": ".($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")."@".$search."<br />";

					$output = sprintf("%s&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(%s %s %s %s %s)",
						$output, $this->loc->s("Between"),$fst[0],$this->loc->s("and-the"),$lst[0],
						$data["active"] == 't' ? "<b>".$this->loc->s("Active")."</b>" : ""
					);

					FS::$searchMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output,"title-netbios");
				}

				$output = "";
				$found = false;
				$lastmac = "";

				$query = FS::$dbMgr->Select($this->nipTable,"mac,time_first,time_last",
					"host(ip) = '".$search."' OR CAST(mac AS varchar) ILIKE '%".$search."%'",
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
					$output .= FS::$iMgr->aLink($this->mid."&s=".$data["mac"], $data["mac"]).
						"<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0].
						" ".$this->loc->s("and-the")." ".$lst[0].")";
					FS::$searchMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output,"title-mac-addr");
				}

				if ($lastmac) {
					$output = "";
					$query = FS::$dbMgr->Select($this->sqlTable,"switch,port,time_first,time_last,active",
						"CAST(mac as varchar) ILIKE '".$lastmac."' AND active = 't'",
						array("order" => "time_last","ordersens" => 1,"limit" => 1));
					if ($data = FS::$dbMgr->Fetch($query)) {
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$switch = FS::$dbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
						$piece = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise",
							"ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
						$convport = preg_replace("#\/#","-",$data["port"]);

						$output .= FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch, $switch)." ".
							"[".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".
							$convport, $data["port"])."] ".
							FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".
							$data["port"], FS::$iMgr->img("styles/images/pencil.gif",10,10));

						if ($piece) {
							$output .= "/ ".$this->loc->s("Plug")." ".$piece;
						}

						$output = sprintf("%s<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(%s %s %s %s%s)<br />",
							$output, $this->loc->s("Between"),$fst[0],$this->loc->s("and-the"),$lst[0],
							$data["active"] == 't' ? " <b>".$this->loc->s("Active")."</b>" : ""
						);

						FS::$searchMgr->incResultCount();
						$this->storeSearchResult($output,"title-last-device");
					}
				}

				$output = "";
				$found = false;

				$query = FS::$dbMgr->Select($this->sqlTable,"switch,port,time_first,time_last,active",
					"CAST (mac AS varchar) ILIKE '%".$search."%'",
					array("order" => "time_last","ordersens" => 2));
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}

					$switch = FS::$dbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
					$piece = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises",
						"prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
					$convport = preg_replace("#\/#","-",$data["port"]);
					$output .=  FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch, $switch)." ".
						"[".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
						"&d=".$switch."#".$convport, $data["port"])."] ".
						FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
						"&d=".$switch."&p=".$data["port"], FS::$iMgr->img("styles/images/pencil.gif",10,10)).
						($piece == NULL ? "" : " / Prise ".$piece);
					$fst = preg_split("#\.#",$data["time_first"]);
					$lst = preg_split("#\.#",$data["time_last"]);

					$output = sprintf("%s<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(%s %s %s %s%s)<br />",
						$output, $this->loc->s("Between"),$fst[0],$this->loc->s("and-the"),$lst[0],
						$data["active"] == 't' ? " <b>".$this->loc->s("Active")."</b>" : ""
					);

					FS::$searchMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output,"title-network-places");
				}
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
				return;
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
					FS::$searchMgr->incResultCount();
				}
				if ($found) {
					foreach ($devprise as $device => $devport) {
						$output .= $this->loc->s("Device").": ".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
							"&d=".$device, $device)."<ul>";
						foreach ($devport as $port => $values) {
							$convport = preg_replace("#\/#","-",$port);
							$output .= "<li>".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."#".$convport, $port)." ".
								FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."&p=".$port, FS::$iMgr->img("styles/images/pencil.gif",12,12))." ".
								"<br /><b>".$this->loc->s("Plug").":</b> ".$values["plug"];
							if ($values["desc"]) {
								$output .= "<br /><b>".$this->loc->s("Description").":</b> ".$values["desc"];
							}
							$output .= "</li>";
						}
						$output .= "</ul><br />";
					}
					$this->storeSearchResult($output,"Ref-plug");
				}
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
			$this->writeRight = "mrule_switches_write";
		}

		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
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
					FS::$searchMgr->incResultCount();
				}
				if ($found) {
					foreach ($devroom as $device => $devport) {
						$output .= $this->loc->s("Device").": ".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
							"&d=".$device, $device)."<ul>";
						foreach ($devport as $port => $values) {
							$convport = preg_replace("#\/#","-",$port);
							$output .= "<li>".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."#".$convport, $port)." ".
								FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("switches").
								"&d=".$device."&p=".$port, FS::$iMgr->img("styles/images/pencil.gif",12,12))." ".
								"<br /><b>".$this->loc->s("Room").":</b> ".$values["room"];
							if ($values["desc"]) {
								$output .= "<br /><b>".$this->loc->s("Description").":</b> ".$values["desc"];
							}
							$output .= "</li>";
						}
						$output .= "</ul><br />";
					}
					$this->storeSearchResult($output,"Ref-room");
				}
			}
		}

		protected function canWrite() {
			if (!FS::$sessMgr->hasRight("snmp_".$this->snmprw."_write") &&
				!FS::$sessMgr->hasRight("ip_".$this->deviceIP."_write")) {
				return false;
			}
			return true;
		}
		public function FastModify() {
			$this->devicePort = FS::$secMgr->checkAndSecurisePostData("swport");
			$this->deviceIP = FS::$secMgr->checkAndSecurisePostData("sw");
			$room = FS::$secMgr->checkAndSecurisePostData("room");
			if ($this->devicePort == NULL || $this->deviceIP == NULL) {
				$this->log(2,"Some fields are missing (plug fast edit)");
				echo "ERROR";
				return;
			}

			$this->device = FS::$dbMgr->GetOneData("device","name","ip = '".$this->deviceIP."'");
			$this->snmprw = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_cache","snmprw","device = '".$device."'");
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights();
				return;
			}

			if ($room == NULL) {
				$room = "";
			}

			$portObj = new netDevicePort();
			if (!$portObj->Load($this->device,$this->devicePort)) {
				echo "ERROR";
				return;
			}

			$portObj->setRoom($room);
			$portObj->SaveRoomAndPlug();

			// Return text for AJAX call
			$this->log(0,"Set room for device '".$this->deviceIP."' to '".$room."' on port '".$this->devicePort."'");
			if ($room == "") {
				$room = $this->loc->s("Modify");
			}
			echo $room;
		}

		private $deviceTable;
		private $devPortTable;
		private $device;
		private $deviceIP;
		private $devicePort;
		private $snmprw;
	};
?>
