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
	
	require_once(dirname(__FILE__)."/../../lib/FSS/modules/Network.FS.class.php");

	final class iIPManager extends FSModule{
		function __construct($locales) { parent::__construct($locales); }

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-ip-management"));
			$output = $this->showMain();
			return $output;
		}

		private function showMain() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");

			if(!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h1("title-ip-management");
				$tabs = array(
					array(1,"mod=".$this->mid,$this->loc->s("Consult")),
					array(2,"mod=".$this->mid,$this->loc->s("Manage-Subnets")),
					array(4,"mod=".$this->mid,$this->loc->s("Advanced-tools"))
					);
				
				if(FS::$sessMgr->hasRight("mrule_ipmanager_servermgmt"))
					array_push($tabs,array(3,"mod=".$this->mid,$this->loc->s("Manage-Servers")));

				$output .= FS::$iMgr->tabPan($tabs,$sh);
			}	
			else {
				switch($sh) {
					case 1: $output .= $this->showStats(); break;
					case 2: $output .= $this->showSubnetMgmt(); break;
					case 3: $output .= $this->showDHCPMgmt(); break;
					case 4: $output .= $this->showAdvancedTools(); break;
				}
			}
			return $output;
		}

		private function showStats() {
			$output = "";
			$formoutput = "";

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");

			$netfound = false;
			$tmpoutput = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=1");
			$tmpoutput .= FS::$iMgr->select("f");
			$formoutput = "";

			// @TODO: see if a subnet is under another subnet

			// We bufferize all netid because of multiple sources
			$netarray = array();

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid,netmask");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!isset($netarray[$data["netid"]]))
					$netarray[$data["netid"]] = $data["netmask"];
			}

			/*$query = FS::$dbMgr->Select("subnets","net");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!isset($netarray[$data["netid"]]))
					$netarray[$data["netid"]] = "";
			}*/

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!isset($netarray[$data["netid"]]))
					$netarray[$data["netid"]] = $data["netmask"];
			}
			
			ksort($netarray);
			foreach($netarray as $netid => $netmask) {
				$formoutput .= FS::$iMgr->selElmt($netid."/".$netmask,$netid,$filter == $netid);
			}

			$tmpoutput .= $formoutput;
			$tmpoutput .= "</select> ";
			$tmpoutput .= FS::$iMgr->select("view").FS::$iMgr->selElmt($this->loc->s("Stats"),1).FS::$iMgr->selElmt($this->loc->s("History"),2).
				FS::$iMgr->selElmt($this->loc->s("Monitoring"),3)."</select> ";
			
			$tmpoutput .= FS::$iMgr->submit("","Consulter");
			$tmpoutput .= "</form><br />";

			if(count($netarray) == 0)
				return FS::$iMgr->printError($this->loc->s("no-net-found"));

			$output .= $tmpoutput.
				"<div id=\"netHCr\"></div><div id=\"netshowcont\"></div>";
					
			return $output;
		}

		private function showSubnetIPList($filter) {
			$netid = ""; $netmask = "";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid,netmask","netid = '".$filter."'");
			if($data = FS::$dbMgr->Fetch($query)) {
				$netid = $data["netid"]; $netmask = $data["netmask"];
			}
			if(!$netid) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask","netid = '".$filter."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$netid = $data["netid"]; $netmask = $data["netmask"];
				}
			}
			// @TODO: netdisco subnets
			$iparray = array();
			$output = FS::$iMgr->h3("Réseau : ".$netid."/".$netmask,true);
			$output .= "<center><div id=\"".FS::$iMgr->formatHTMLId($netid)."\"></div></center>";

			$netobj = new FSNetwork();
			$netobj->setNetAddr($netid);
			$netobj->setNetMask($netmask);

			$swfound = false;

			// Bufferize switch list
			$switchlist = array();

			$query2 = FS::$dbMgr->Select("device","ip,name");
			while($data2 = FS::$dbMgr->Fetch($query2))
				$switchlist[$data2["ip"]] = $data2["name"];

			// Initiate network IPs
			$lastip = $netobj->getLastUsableIPLong();
			for($i=($netobj->getFirstUsableIPLong());$i<$lastip;$i++) {
				$iparray[$i] = array();
				$iparray[$i]["mac"] = "";
				$iparray[$i]["host"] = "";
				$iparray[$i]["ltime"] = "";
				$iparray[$i]["distrib"] = 0;
				$iparray[$i]["servers"] = array();
				$iparray[$i]["switch"] = "";
				$iparray[$i]["port"] = "";
			}

			// Fetch datas
			$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","ip,macaddr,hostname,leasetime,distributed,server","netid = '".$netid."'");
			while($data2 = FS::$dbMgr->Fetch($query2)) {
				// If it's reserved on a host don't override status
				if($iparray[ip2long($data2["ip"])]["distrib"] != 3) {
					$iparray[ip2long($data2["ip"])]["mac"] = $data2["macaddr"];
					$iparray[ip2long($data2["ip"])]["host"] = $data2["hostname"];
					$iparray[ip2long($data2["ip"])]["ltime"] = $data2["leasetime"];
					$iparray[ip2long($data2["ip"])]["distrib"] = $data2["distributed"];
				}
				// List servers where the data is
				array_push($iparray[ip2long($data2["ip"])]["servers"],$data2["server"]);
				if(strlen($iparray[ip2long($data2["ip"])]["mac"]) > 0 && strlen($iparray[ip2long($data2["ip"])]["switch"]) == 0) {
					$sw = FS::$dbMgr->GetOneData("node","switch","mac = '".$iparray[ip2long($data2["ip"])]["mac"]."'",array("order" => "time_last","ordersens" => 2));
					$port = FS::$dbMgr->GetOneData("node","port","mac = '".$iparray[ip2long($data2["ip"])]["mac"]."'",array("order" => "time_last","ordersens" => 2));
					if($sw && $port) {
						$iparray[ip2long($data2["ip"])]["switch"] = $switchlist[$sw];
						$iparray[ip2long($data2["ip"])]["port"] = $port;
						$swfound = true;
					}
				}

			}

			$used = 0;
			$reserv = 0;
			$free = 0;
			$distrib = 0;
			$fixedip = 0;

			$output .= "<table id=\"tipList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("IP-Addr")."</th><th>".$this->loc->s("Status")."</th>
				<th>".$this->loc->s("MAC-Addr")."</th><th>".$this->loc->s("Hostname")."</th><th>";
			if($swfound)
				$output .= $this->loc->s("Switch")."</th><th>".$this->loc->s("Port")."</th><th>";
			$output .= "Fin du bail</th><th>Serveurs</th></tr></thead>";

			foreach($iparray as $key => $value) {
				$rstate = "";
				$style = "";
				switch($value["distrib"]) {
					case 1:
						$rstate = $this->loc->s("Free");
						$style = "background-color: #BFFFBF;";
						$free++;
						break;
					case 2:
						$rstate = $this->loc->s("Used");
						$style = "background-color: #FF6A6A;";
						$used++;
						break;
					case 3:
						$rstate = $this->loc->s("Reserved");
						$style = "background-color: #FFFF80;";
						$reserv++;
						break;
					case 4:
						$rstate = $this->loc->s("Distributed");
						$style = "background-color: #BFFBFF;";
						$distrib++;
						break;
					default: {
							$rstate = $this->loc->s("Free");
							$style = "background-color: #BFFFBF;";
							$mac = FS::$dbMgr->GetOneData("node_ip","mac","ip = '".long2ip($key)."' AND time_last > (current_timestamp - interval '1 hour') AND active = 't'");
							if($mac) {
								$query3 = FS::$dbMgr->Select("node","switch,port,time_last","mac = '".$mac."' AND active = 't'");
								if($data3 = FS::$dbMgr->Fetch($query3)) {
									$rstate = $this->loc->s("Stuck-IP");
									$style = "background-color: orange;";
									$fixedip++;
								}
								else
									$free++;
							}
							else
								$free++;
						}
						break;
				}
				$output .= "<tr style=\"$style\"><td><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".long2ip($key)."\">";
				$output .= long2ip($key)."</a>";
				$output .= "</td><td>".$rstate."</td><td>";
				$output .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$value["mac"]."\">".$value["mac"]."</a></td><td>";
				$output .= $value["host"]."</td><td>";
				// Show switch column only of a switch is here
				if($swfound) {
					$output .= (strlen($value["switch"]) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$value["switch"]."\">".$value["switch"]."</a>" : "");
					$output .= "</td><td>";
					$output .= (strlen($value["switch"]) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$value["switch"]."&p=".$value["port"]."\">".$value["port"]."</a>" : "");
					$output .= "</td><td>";
				}
				$output .= $value["ltime"]."</td><td>";
				$count = count($value["servers"]);
				for($i=0;$i<$count;$i++) {
					if($i > 0) $output .= "<br />";
					$output .= $value["servers"][$i];
				}
				$output .= "</td></tr>";
			}
			$output .= "</table>";
			FS::$iMgr->jsSortTable("tipList");

			$js = "setTimeout(function() { var chart = new Highcharts.Chart({
				chart: { renderTo: 'netHCr', plotBackgroundColor: null, plotBorderWidth: null, plotShadow: false },
				title: { text: '' },
				tooltip: { formatter: function() { return '<b>'+this.point.name+'</b>: '+this.y+' ('+
							Math.round(this.percentage*100)/100+' %)'; } },
				plotOptions: {
					pie: { allowPointSelect: true, cursor: 'pointer', dataLabels: {
							enabled: true,formatter: function() { return '<b>'+this.point.name+'</b>: '+
							this.y+' ('+Math.round(this.percentage*100)/100+' %)'; }
				}}},
				series: [{ type: 'pie', data: [";
			if($used > 0) $js .= "{ name: '".$this->loc->s("Baux")."', y: ".$used.", color: 'red' },";
			if($reserv > 0) $js .= "{ name: '".$this->loc->s("Reservations")."', y: ".$reserv.", color: 'yellow'},";
			if($fixedip > 0) $js .= "{ name: '".$this->loc->s("Stuck-IP")."', y: ".$fixedip.", color: 'orange'},";
			if($distrib > 0) $js .= "{ name: '".$this->loc->s("Available-s")."', y: ".$distrib.", color: 'cyan'},";
			$js .= "{ name: '".$this->loc->s("Free-s")."', y:".$free.", color: 'green'}]
				}]});},300);";
			FS::$iMgr->js($js);

			return $output;
		}

		private function showSubnetHistory($filter) {
			$output = FS::$iMgr->js("function historyDateChange() {
				$('#hstcontent').hide(\"slow\",function() { $('#hstcontent').html(''); 
				$.post('index.php?mod=".$this->mid."&act=4',$('#hstfrm').serialize(), function(data) {
					$('#hstcontent').show(\"fast\",function() { $('#hstcontent').html(data); });
				}); }); }");

			$output .= "<div id=\"hstcontent\">".$this->showHistory($filter)."</div>";
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=4",array("id" => "hstfrm"));
			$output .= FS::$iMgr->hidden("filter",$filter);
			$date = FS::$dbMgr->GetMin(PGDbConfig::getDbPrefix()."dhcp_subnet_history","collecteddate");
			if(!$date) $date = "now";
			$diff = ceil((strtotime("now")-strtotime($date))/(24*60*60));
			$output .= FS::$iMgr->slider("hstslide","daterange",1,$diff,array("hidden" => "jour(s)","width" => "200px","value" => "1"));
			$output .= FS::$iMgr->button("but",$this->loc->s("change-interval"),"historyDateChange()")."</form>";
			return $output;
		}

		private function showSubnetMonitoring($filter) {
			$output = FS::$iMgr->h4("Monitoring",true);
			$wlimit = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","warnuse","subnet = '".$filter."'");
			$climit = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","crituse","subnet = '".$filter."'");
			$maxage = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","maxage","subnet = '".$filter."'");
			$enmon = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","enmon","subnet = '".$filter."'");
			$eniphistory = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","eniphistory","subnet = '".$filter."'");
			$contact = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_monitoring","contact","subnet = '".$filter."'");

			$output .= "<div id=\"monsubnetres\"></div>".
				FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&f=".$filter."&act=3").
				"<ul class=\"ulform\"><li>".FS::$iMgr->check("eniphistory",array("check" => $eniphistory == 't',"label" => $this->loc->s("En-IP-history")))."</li>
				<li>".FS::$iMgr->check("enmon",array("check" => $enmon == 1,"label" => $this->loc->s("En-monitor")))."</li><li>".
				FS::$iMgr->numInput("wlimit",($wlimit > 0 ? $wlimit : 0),array("size" => 3, "length" => 3, "label" => $this->loc->s("warn-line"), "tooltip" => "tooltip-%use"))."</li><li>".
				FS::$iMgr->numInput("climit",($climit > 0 ? $climit : 0),array("size" => 3, "length" => 3, "label" => $this->loc->s("crit-line"), "tooltip" => "tooltip-%use"))."</li><li>".
				FS::$iMgr->numInput("maxage",($maxage > 0 ? $maxage : 0),array("size" => 7, "length" => 7, "label" => $this->loc->s("max-age"), "tooltip" => "tooltip-max-age"))."</li><li>".
				FS::$iMgr->input("contact",$contact,20,40,$this->loc->s("Contact"),"tooltip-contact")."</li><li>".
				FS::$iMgr->submit("",$this->loc->s("Save"))."</li></ul></form>";
			return $output;
		}

		private function showAdvancedTools() {
			$output = FS::$iMgr->h4("title-search-old");
			$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=2");
			$output .= $this->loc->s("intval-days")." ".FS::$iMgr->numInput("ival")."<br />";
			$output .= FS::$iMgr->submit("",$this->loc->s("Search"));
			$output .= "</form><div id=\"obsres\"></div>";
			return $output;
		}

		private function showSubnetMgmt() {
			$output = FS::$iMgr->h2("title-declared-subnets");
	                $output .= FS::$iMgr->opendiv(1,$this->loc->s("declare-subnet"),array("line" => true));

			$output .= "<div id=\"declsubnets\">";

			$found = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask,vlanid,subnet_desc,subnet_short_name");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = 1;
					$output .= $this->showDeclaredNetTableHead();
				}
				$output .= $this->tableDeclaredNetEntry($data["netid"],$data["netmask"],$data["subnet_desc"],$data["subnet_short_name"],$data["vlanid"]);
			}
			if($found) $output .= "</table>".FS::$iMgr->jsSortTable("declsubnettable");
			$output .= "</div>";
			return $output;
		}

		private function showDeclaredNetTableHead() {
			return "<table id=\"declsubnettable\"><thead id=\"declsubnethead\"><tr><th>".$this->loc->s("netid")."/".$this->loc->s("netmask")."</th><th>".
				$this->loc->s("vlanid")."</th><th>".$this->loc->s("Usable")."</th><th>".$this->loc->s("subnet-shortname")."</th><th>".$this->loc->s("subnet-desc")."</th>
				<th>".$this->loc->s("dhcp-clusters")."</th><th></th></tr></thead>";
		}

		private function tableDeclaredNetEntry($netid,$netmask,$desc,$shortname,$vlanid) {
			$net = new FSNetwork();
			$net->SetNetAddr($netid);
			$net->SetNetMask($netmask);
			$net->CalcCIDR();
			$output = "<tr id=\"ds".FS::$iMgr->formatHTMLId($netid)."tr\"><td>".FS::$iMgr->opendiv(2,$netid,array("lnkadd" => "netid=".$netid)).
				"/".$netmask." (/".$net->getCIDR().")</td><td>".$vlanid."</td><td>".
				($net->getMaxHosts()-2)."</td><td>".$shortname."</td><td>".$desc."</td><td>";
			
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","clustername","subnet = '".$netid."'");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found)
					$found = true;
				else
					$output .= "<br />";
				$output .= $data["clustername"];
			}

			if(!$found) $output .= $this->loc->s("None");

			$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=8&netid=".$netid,array("js" => true, "confirm" =>
				array($this->loc->s("confirm-remove-declared-subnet").$netid."' ?","Confirm","Cancel"))).
				"</td></tr>";
			return $output;
		}

		private function showDHCPSubnetForm($netid = "") {
			$netmask = ""; $vlanid = 0; $shortname = ""; $desc = "";
			if($netid) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netmask,vlanid,subnet_desc,subnet_short_name","netid = '".$netid."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$netmask = $data["netmask"];
					$vlanid = $data["vlanid"];
					$shortname = $data["subnet_short_name"];
					$desc = $data["subnet_desc"];
				}
			}
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=7")."<table>".
				FS::$iMgr->idxLine($this->loc->s("netid"),"netid",$netid,array("type" => "idxipedit", "length" => 16, "edit" => $netid != "")).
				FS::$iMgr->idxLine($this->loc->s("netmask"),"netmask",$netmask,array("length" => 16, "type" => "ip")).
				FS::$iMgr->idxLine($this->loc->s("vlanid"),"vlanid",$vlanid,array("length" => 4, "type" => "num", "value" => $vlanid, "tooltip" => "tooltip-vlanid")).
				FS::$iMgr->idxLine($this->loc->s("subnet-shortname"),"shortname",$shortname,array("length" => 32, "tooltip" => "tooltip-shortname")).
				FS::$iMgr->idxLine($this->loc->s("subnet-desc"),"desc",$desc,array("length" => 128, "tooltip" => "tooltip-desc"));

			// Clusters associated to subnet (if there is clusters)
			$clusters = array();
			if($netid) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","clustername","subnet = '".$netid."'");
				while($data = FS::$dbMgr->Fetch($query)) {
					array_push($clusters,$data["clustername"]);
				}
			}

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","",array("order" => "clustername"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$output .= "<tr ".FS::$iMgr->tooltip("tooltip-dhcp-cluster-distrib")."><td>".$this->loc->s("dhcp-cluster")."</td><td>".FS::$iMgr->select("subnetclusters","",NULL,true);
				}
				$output .= FS::$iMgr->selElmt($data["clustername"],$data["clustername"],in_array($data["clustername"],$clusters));
			}
			if($found) $output .= "</select></td></tr>";

			$output .= FS::$iMgr->tableSubmit("Save");
			return $output;
		}

		private function showDHCPSrvForm($addr = "") {
			$user = ""; $dhcpdpath = ""; $leasepath = ""; $reservconfpath = ""; $subnetconfpath = ""; $alias = ""; $description = "";
			if($addr) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_servers","alias,description,sshuser,dhcpdpath,leasespath,reservconfpath,subnetconfpath","addr = '".$addr."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$alias = $data["alias"];
					$description = $data["description"];
					$user = $data["sshuser"];
					$dhcpdpath = $data["dhcpdpath"];
					$leasepath = $data["leasespath"];
					$reservconfpath = $data["reservconfpath"];
					$subnetconfpath = $data["subnetconfpath"];
				}
			}
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=5").$this->loc->s("note-needed")."<table>".
				FS::$iMgr->idxLine($this->loc->s("server-addr")." (*)","addr",$addr,array("type" => "idxedit", "length" => 128, "edit" => $addr != "")).
				FS::$iMgr->idxLine($this->loc->s("server-alias"),"alias",$alias,array("length" => 64, "tooltip" => "tooltip-dhcp-alias")).
				FS::$iMgr->idxLine($this->loc->s("server-desc"),"description",$description,array("length" => 128, "tooltip" => "tooltip-dhcp-desc")).
				FS::$iMgr->idxLine($this->loc->s("ssh-user")." (*)","sshuser",$user,array("length" => 128)).
				FS::$iMgr->idxLine($this->loc->s("ssh-pwd")." (*)","sshpwd","",array("type" => "pwd")).
				FS::$iMgr->idxLine($this->loc->s("ssh-pwd-repeat")." (*)","sshpwd2","",array("type" => "pwd")).
				FS::$iMgr->idxLine($this->loc->s("dhcpd-path"),"dhcpdpath",$dhcpdpath,array("length" => 980, "size" => 30, "tooltip" => "tooltip-dhcpdpath")).
				FS::$iMgr->idxLine($this->loc->s("lease-path"),"leasepath",$leasepath,array("length" => 980, "size" => 30, "tooltip" => "tooltip-leasepath")).
				FS::$iMgr->idxLine($this->loc->s("reservconf-path"),"reservconfpath",$reservconfpath,array("length" => 980, "size" => 30, "tooltip" => "tooltip-reservconfpath")).
				FS::$iMgr->idxLine($this->loc->s("subnetconf-path"),"subnetconfpath",$subnetconfpath,array("length" => 980, "size" => 30, "tooltip" => "tooltip-subnetconfpath")).
				FS::$iMgr->tableSubmit("Save");
			return $output;
		}

		private function showDHCPSrvList() {
			$output = "<table><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("server-alias")."</th><th>".$this->loc->s("server-desc")."</th><th>".$this->loc->s("ssh-user")."</th>
				<th>".$this->loc->s("member-of")."<th></th></tr>";

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_servers","addr,alias,description,sshuser,dhcpdpath,leasespath,reservconfpath,subnetconfpath");
			while($data = FS::$dbMgr->Fetch($query)) {
				$output .= "<tr><td>".FS::$iMgr->opendiv(3,$data["addr"],array("lnkadd" => "addr=".$data["addr"]))."</td><td>".$data["alias"]."</td><td>".$data["description"]."</td><td>".
					$data["sshuser"]."</td><td>";

				$found = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","dhcpaddr = '".$data["addr"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if(!$found)
						$found = true;
					else
						$output .= "<br />";
					$output .= $data2["clustername"];
				}

				if(!$found) $output .= $this->loc->s("None");
				
				$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=6&addr=".$data["addr"],array("js" => true, "confirm" =>
					array($this->loc->s("confirm-remove-dhcp").$data["addr"]."' ?","Confirm","Cancel"))).
					"</td></tr>";
			}
			$output .= "</table>";
			return $output;
		}

		private function showDHCPMgmt() {
			if(!FS::$sessMgr->hasRight("mrule_ipmanager_servermgmt"))
				return "";

			$output = FS::$iMgr->h2("title-dhcp-cluster-mgmt");

			$dhcpcount = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_servers","addr");
			
			if($dhcpcount > 0) {
				// To add DHCP cluster
				$output .= FS::$iMgr->opendiv(4,$this->loc->s("add-cluster")).
					"<div id=\"clusterdiv\">";

				// To edit/delete clusters
				if(FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_cluster","dhcpaddr") > 0) {
					$output .= $this->showDHCPClusterList();
				}
				$output .= "</div>";
			}
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-need-dhcp-server"));


			$output .= FS::$iMgr->h2("title-dhcp-server-mgmt");
			// To add servers
			$output .= FS::$iMgr->opendiv(5,$this->loc->s("title-add-server"));


			// To edit/delete servers
			if($dhcpcount > 0) {
				$output .= $this->showDHCPSrvList();
			}

			return $output;
		}

		private function showDHCPClusterForm($name = "") {
			$members = array();
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_cluster","dhcpaddr","clustername = '".$name."'");
				while($data = FS::$dbMgr->Fetch($query)) {
					array_push($members,$data["dhcpaddr"]);
				}
			}
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=9")."<table>".
				FS::$iMgr->idxLine($this->loc->s("Cluster-name"),"cname",$name,array("type" => "idxedit", "edit" => $name != "")).
				"<tr><td>".$this->loc->s("Cluster-members")."</td><td>".FS::$iMgr->select("clustermembers","",NULL,true);

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_servers","addr,alias","",array("order" => "addr"));
			while($data = FS::$dbMgr->Fetch($query))
				$output .= FS::$iMgr->selElmt($data["addr"].($data["alias"] ? " (".$data["alias"].")" : ""),$data["addr"],in_array($data["addr"],$members));

			$output .= "</select>".FS::$iMgr->tableSubmit("Save");
			return $output;
		}

		private function showDHCPClusterList() {
			$output = $this->showTableHeadCluster();
			$clusters = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername,dhcpaddr");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!isset($clusters[$data["clustername"]]))
					$clusters[$data["clustername"]] = array();
				array_push($clusters[$data["clustername"]],$data["dhcpaddr"]);
			}

			foreach($clusters as $clustername => $dhcplist) {
				$output .= $this->showDHCPClusterTableEntry($clustername,$dhcplist);
			}

			$output .= "</table>".FS::$iMgr->jsSortTable("clustertable");
			return $output;
		}

		private function showTableHeadCluster() {
			return "<table id=\"clustertable\"><thead id=\"clusterth\"><tr><th>".$this->loc->s("Cluster-name")."</th><th>".$this->loc->s("Cluster-members")."</th><th></th></tr></thead>";
		}

		private function showDHCPClusterTableEntry($clustername,$members) {
			$output = "<tr id=\"cl".FS::$iMgr->formatHTMLId($clustername)."tr\"><td>".FS::$iMgr->opendiv(6,$clustername,array("lnkadd" => "name=".$clustername))."</td><td>";

			$found = false;
			for($i=0;$i<count($members);$i++) {
				if(!$found)
					$found = true;
				else
					$output .= "<br />";
				$output .= $members[$i];
			}

			$output .= "</td><td>".
				FS::$iMgr->removeIcon("mod=".$this->mid."&act=10&cluster=".$clustername,array("js" => true, "confirm" =>
				array($this->loc->s("confirm-remove-cluster").$clustername."' ?","Confirm","Cancel"))).
				"</td></tr>";
			return $output;
		}

		private function showHistory($filter,$interval = 1) {
			$output = FS::$iMgr->h3($this->loc->s("title-history-since")." ".$interval." ".$this->loc->s("days"),true);
			$results = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_history","ipfree,ipactive,ipreserved,ipdistributed,collecteddate",
				"collecteddate > (NOW()- '".$interval." day'::interval) and subnet = '".$filter."'",
				array("order" => "collecteddate","ordersens" => 2));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!isset($results[$data["collecteddate"]])) $results[$data["collecteddate"]] = array();
				$results[$data["collecteddate"]]["baux"] = $data["ipactive"];
				$results[$data["collecteddate"]]["reserv"] = $data["ipreserved"];
				$results[$data["collecteddate"]]["avail"] = $data["ipdistributed"];
			}
			$netobj = new FSNetwork();
                        $netobj->setNetAddr($filter);
                        $netobj->setNetMask(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netmask","netid ='".$filter."'"));

			// JS Table
			$labels = $baux = $reserv = $avail = $free = $total = "";
			// To show or not if no data
			$reservshow = $bauxshow = $availshow = false;
			// Show only modifications
			$lastvalues = array();
			end($results);
			$lastres = key($results);
			$totalvals = 0;
			foreach($results as $date => $values) {
				if($labels == "") {
					// Bufferize vals
                                        $bauxval = (isset($values["baux"]) ? $values["baux"] : 0);
                                        $reservval = (isset($values["reserv"]) ? $values["reserv"] : 0);
                                        $availval = (isset($values["avail"]) ? $values["avail"] : 0);

                                        // Write js table
                                        $labels .= "'".$date."'";
                                        if($bauxval > 0) $bauxshow = true;
                                        $baux .= $bauxval;
                                        if($reservval > 0) $reservshow = true;
                                        $reserv .= $reservval;
                                        if($availval > 0) $availshow = true;
                                        $avail .= $availval;

                                        $totdistrib = ($bauxval+$reservval+$availval);
                                        $total .= $totdistrib;
                                        $free .= ($netobj->getMaxHosts() - $totdistrib);
                                        // Save this occur
                                        $lastvalues = array("baux" => $bauxval, "reserv" => $reservval, "avail" => $availval);
					$totalvals++;
				}
				else {
					// Bufferize vals
					$bauxval = (isset($values["baux"]) ? $values["baux"] : 0);
					$reservval = (isset($values["reserv"]) ? $values["reserv"] : 0);
					$availval = (isset($values["avail"]) ? $values["avail"] : 0);

					if($bauxval != $lastvalues["baux"] || $reservval != $lastvalues["reserv"] ||
						$availval != $lastvalues["avail"] || $date == $lastres) {
						// Write js table
						$labels .= ",'".$date."'";
						if($bauxval > 0) $bauxshow = true;
						$baux .= ",".$bauxval;
						if($reservval > 0) $reservshow = true;
                	                	$reserv .= ",".$reservval;
						if($availval > 0) $availshow = true;
        	                                $avail .= ",".$availval;

						$totdistrib = ($bauxval+$reservval+$availval);
                	                        $total .= ",".$totdistrib;
                        	                $free .= ",".($netobj->getMaxHosts() - $totdistrib);
						$totalvals++;
					}
					// Save this occur
					$lastvalues = array("baux" => $bauxval, "reserv" => $reservval, "avail" => $availval);
				}
			}
                        $js = "setTimeout(function() { var hstgr = new Highcharts.Chart({
                                chart: { renderTo: 'netHCr', type: 'line' },
                                       title: { text: '' },
				tooltip: { crosshairs: true },
                                       xAxis: { categories: [".$labels."], gridLineWidth: 1, tickInterval: ".round($totalvals/10)." },
                                       yAxis: { title: { text: 'Nombre d\'adresses' } },
                                       legend: { layout: 'vertical', align: 'right', verticalAlign: 'top',
                                       	x: -10, y: 100 },
                                       series: [ { name: '".addslashes($this->loc->s("Usable"))."',
					data: [".$total."], color: 'green' },
					{ name: '".addslashes($this->loc->s("not-usable"))."',
                                               data: [".$free."], color: 'black' },";
				if($bauxshow) $js .= "{ name: '".addslashes($this->loc->s("Baux"))."',
					data: [".$baux."], color: 'red' },";
				if($reservshow) $js .= "{ name: '".addslashes($this->loc->s("Reservations"))."',
					data: [".$reserv."], color: 'yellow' },";
				if($availshow) $js .= "{ name: '".addslashes($this->loc->s("Available-s"))."',
					data: [".$avail."], color: 'cyan' }";
			$js .= "]});},300);";
			FS::$iMgr->js($js);
			return $output;
		}

		private function writeConfigToServer($server = NULL) {

			$conns = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."server_list","addr,login,pwd","dhcp = 1");
		        while($data = FS::$dbMgr->Fetch($query)) {
		                $conn = ssh2_connect($data["addr"],22);
                		if(!$conn) {
		                        return $this->loc->s("error-fail-connect-ssh").$data["addr"];
		                }
                		else if(!ssh2_auth_password($conn, $data["login"], $data["pwd"])) {
                			return "Authentication error for server '".$data["addr"]."' with login '".$data["login"];
		                }
			}
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->showDHCPSubnetForm();
				case 2:
					$netid = FS::$secMgr->checkAndSecuriseGetData("netid");
					if(!$netid)
						return $this->loc->s("err-bad-datas");

					return $this->showDHCPSubnetForm($netid);
				case 3: 
					$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
					if(!$addr)
						return $this->loc->s("err-bad-datas");

					return $this->showDHCPSrvForm($addr);
				case 4: return $this->showDHCPClusterForm();
				case 5: return $this->showDHCPSrvForm();
				case 6:
					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if(!$name)
						return $this->loc->s("err-bad-datas");

					return $this->showDHCPClusterForm($name);
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					$view = FS::$secMgr->checkAndSecurisePostData("view");
					if(!$filtr || !$view || !FS::$secMgr->isNumeric($view) || $view < 1 || $view > 3) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are missing when try to filter values");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}
					
					switch($view) {
						case 1: $subout = $this->showSubnetIPList($filtr); break;
						case 2: $subout = $this->showSubnetHistory($filtr); break;
						case 3: $subout = $this->showSubnetMonitoring($filtr); break;
					}
					$js = "$('#netshowcont').html('".addslashes(preg_replace("[\n]","",$subout))."');";
					if($view == 3) 
						$js .= "$('#netHCr').html('');";

					FS::$iMgr->ajaxEcho("Done",$js);
					//FS::$iMgr->redir("mod=".$this->mid."&sh=11&f=".$filtr);
					return;
				case 2:
					$interval = FS::$secMgr->checkAndSecurisePostData("ival");
					if(!$interval || !FS::$secMgr->isNumeric($interval) ||
						$interval < 1) {
						FS::$iMgr->ajaxEcho("err-invalid-req");
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are missing when trying to find obsolete datas");
						return;
					}

					$output = "";
					$obsoletes = array();
					$found = false;
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","ip,macaddr,hostname","distributed = 3");
					while($data = FS::$dbMgr->Fetch($query)) {
						$ltime = FS::$dbMgr->GetOneData("node","time_last","mac = '".$data["macaddr"]."'",array("order" => "time_last","ordersens" => 1,"limit" => 1));
						if($ltime) {
							if(strtotime($ltime) < strtotime("-".$interval." day",strtotime(date("y-m-d H:i:s")))) {
								$obsoletes[$data["ip"]] = $data["ip"]." - <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["macaddr"]."\">".$data["macaddr"]."</a>";
								$obsoletes[$data["ip"]] .= " (".$this->loc->s("last-view")." ".date("d/m/y H:i",strtotime($ltime)).")";
								$obsoletes[$data["ip"]] .= "<br />";
								if(!$found) $found = true;
							}
						}
					}
					if($found) {
						$output = FS::$iMgr->h4("title-old-record");
						$logbuffer = "";
						foreach($obsoletes as $key => $value) {
							$logbuffer .= $value;
							$output .= $value;
						}
						
						FS::$iMgr->ajaxEcho("Done","$('#obsres').html('".addslashes($output)."');");
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"User find obsolete datas :<br />".$logbuffer);
					}
					else
						FS::$iMgr->ajaxEcho("Done","$('#obsres').html('".addslashes(FS::$iMgr->printDebug($this->loc->s("no-old-record")))."');");
					
					return;
				case 3:
					$filtr = FS::$secMgr->checkAndSecuriseGetData("f");
					$warn = FS::$secMgr->checkAndSecurisePostData("wlimit");
					$crit = FS::$secMgr->checkAndSecurisePostData("climit");
					$maxage = FS::$secMgr->checkAndSecurisePostData("maxage");
					$contact = FS::$secMgr->checkAndSecurisePostData("contact");
					$enmon = FS::$secMgr->checkAndSecurisePostData("enmon");
					$eniphistory = FS::$secMgr->checkAndSecurisePostData("eniphistory");
					if(!$filtr || !FS::$secMgr->isIP($filtr) || !$warn || !FS::$secMgr->isNumeric($warn) || $warn < 0 || $warn > 100|| !$crit || !FS::$secMgr->isNumeric($crit) || $crit < 0 || $crit > 100 ||
						!FS::$secMgr->isNumeric($maxage) || $maxage < 0 || !$contact || !FS::$secMgr->isMail($contact)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are missing when try to monitor subnet");
						FS::$iMgr->ajaxEcho("err-miss-data");
						return;
					}
					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid","netid = '".$filtr."'");
					if(!$exist) 
						$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid","netid = '".$filtr."'");

					if(!$exist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"User try to monitor inexistant subnet '".$filtr."'");
						FS::$iMgr->ajaxEcho("err-bad-subnet");
						return;
					}

					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_monitoring","subnet = '".$filtr."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_monitoring","subnet,warnuse,crituse,contact,enmon,maxage,eniphistory","'".$filtr."','".$warn."','".$crit."','".$contact."','".
						($enmon == "on" ? "1" : "0")."','".$maxage."','".($eniphistory == "on" ? "t" : "f")."'");
					FS::$dbMgr->CommitTr();

					FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"User ".($enmon == "on" ? "enable" : "disable")." monitoring for subnet '".$filtr."'");
					FS::$iMgr->ajaxEcho("modif-record");
					return;
				case 4:
					$filter = FS::$secMgr->checkAndSecurisePostData("filter");
					$daterange = FS::$secMgr->checkAndSecurisePostData("daterange");
					if(!$filter || !$daterange || !FS::$secMgr->isNumeric($daterange) || $daterange < 1) {
						echo FS::$iMgr->printError($this->loc->s("bad-datas"));
						return;
					}
					echo $this->showHistory($filter,$daterange);
					return;
				// Add/Edit DHCP server
				case 5:
					if(!FS::$sessMgr->hasRight("mrule_ipmanager_servermgmt")) {
						FS::$iMgr->ajaxEcho("err-no-rights");
						return;
					}

					$saddr = FS::$secMgr->checkAndSecurisePostData("addr");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
                                        $slogin = FS::$secMgr->checkAndSecurisePostData("sshuser");
                                        $spwd = FS::$secMgr->checkAndSecurisePostData("sshpwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("sshpwd2");
                                        $dhcpdpath = FS::$secMgr->checkAndSecurisePostData("dhcpdpath");
                                        $alias = FS::$secMgr->checkAndSecurisePostData("alias");
                                        $desc = FS::$secMgr->checkAndSecurisePostData("description");
                                        $leasepath = FS::$secMgr->checkAndSecurisePostData("leasepath");
					$reservconfpath = FS::$secMgr->checkAndSecurisePostData("reservconfpath");
					$subnetconfpath = FS::$secMgr->checkAndSecurisePostData("subnetconfpath");
                                        if($saddr == NULL || $saddr == "" || $slogin == NULL || $slogin == "" || $spwd == NULL || $spwd == "" || $spwd2 == NULL || $spwd2 == "" ||
                                                $dhcpdpath == NULL || $dhcpdpath == "" || !FS::$secMgr->isPath($dhcpdpath) ||
                                                $leasepath == NULL || $leasepath == "" || !FS::$secMgr->isPath($leasepath) ||
						$reservconfpath && ($reservconfpath == "" || !FS::$secMgr->isPath($reservconfpath)) ||
						$subnetconfpath && ($subnetconfpath == "" || !FS::$secMgr->isPath($subnetconfpath)) ||
						$alias && !FS::$secMgr->isAlphaNumeric($alias) || $desc && !FS::$secMgr->isSentence($desc) ||
						$edit && $edit != 1
                                        ) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Some datas are invalid or wrong for add server");
						FS::$iMgr->ajaxEcho("err-bad-datas");
                                                return;
                                        }
					if($spwd != $spwd2) {
						FS::$iMgr->ajaxEcho("err-pwd-not-match");
                                                return;
                                        }

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_servers","sshuser","addr ='".$saddr."'");
					if($edit) {
						if(!$exist) {
							FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to edit server '".$saddr."': not exists");
							FS::$iMgr->ajaxEcho("err-not-exists");
							return;
						}
					}
					else {
						if($exist) {
							FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to add server '".$saddr."': already exists");
							FS::$iMgr->ajaxEcho("err-already-exists");
							return;
						}

					}
					$conn = ssh2_connect($saddr,22);
                                        if(!$conn) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"SSH Connection failed for '".$saddr."'");
						FS::$iMgr->ajaxEcho("err-ssh-conn-failed");
                                                return;
                                        }
					if(!ssh2_auth_password($conn,$slogin,$spwd)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"SSH Auth failed for '".$slogin."'@'".$saddr."'");
						FS::$iMgr->ajaxEcho("err-ssh-auth-failed");
                                                return;
                                        }
					/*
					* We try to read files
					*/
					// dhcpd.conf
					$stream = ssh2_exec($conn,"if [ -r ".$dhcpdpath." ]; then; echo 0; else; echo 1; fi;");
					$cmdret = "";
		        	        stream_set_blocking($stream, true);
	        	        	while ($buf = fread($stream, 4096))
						$cmdret .= $buf;

					if($cmdret != 0) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to read file '".$dhcpdpath."' on '".$saddr."'");
						FS::$iMgr->ajaxEcho("err-unable-read")." '".$dhcpdpath."'";
                                                return;
					}

					// dhcpd.leases
					$stream = ssh2_exec($conn,"if [ -r ".$leasepath." ]; then; echo 0; else; echo 1; fi;");
                                        $cmdret = "";
                                        stream_set_blocking($stream, true);
                                        while ($buf = fread($stream, 4096))
                                                $cmdret .= $buf;

                                        if($cmdret != 0) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to read file '".$leasepath."' on '".$saddr."'");
						FS::$iMgr->ajaxEcho("err-unable-read")." '".$leasepath."'";
                                                return;
                                        }

					if($reservconfpath && strlen($reservconfpath) > 0) {
						$stream = ssh2_exec($conn,"if [ -r ".$reservconfpath." -a -w ".$reservconfpath." ]; then; echo 0; else; echo 1; fi;");
        	                                $cmdret = "";
                	                        stream_set_blocking($stream, true);
                        	                while ($buf = fread($stream, 4096))
                                	                $cmdret .= $buf;

                                        	if($cmdret != 0) {
                                                	FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to read file '".$reservconfpath."' on '".$saddr."'");
							FS::$iMgr->ajaxEcho("err-unable-read")." '".$reservconfpath."'";
        	                                        return;
                	                        }
					}

					if($subnetconfpath && strlen($subnetconfpath) > 0) {
						$stream = ssh2_exec($conn,"if [ -r ".$subnetconfpath." -a -w ".$subnetconfpath." ]; then; echo 0; else; echo 1; fi;");
        	                                $cmdret = "";
                	                        stream_set_blocking($stream, true);
                        	                while ($buf = fread($stream, 4096))
                                	                $cmdret .= $buf;

                                        	if($cmdret != 0) {
                                                	FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",1,"Unable to read file '".$subnetconfpath."' on '".$saddr."'");
							FS::$iMgr->ajaxEcho("err-unable-read")." '".$subnetconfpath."'";
        	                                        return;
                	                        }
					}

					FS::$dbMgr->BeginTr();
					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_servers","addr = '".$saddr."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_servers","addr,alias,description,sshuser,sshpwd,dhcpdpath,leasespath,reservconfpath,subnetconfpath","'".$saddr."','".$alias."','".$desc.
						"','".$slogin."','".$spwd."','".$dhcpdpath."','".$leasepath."','".$reservconfpath."','".$subnetconfpath."'");
					FS::$dbMgr->CommitTr();

					if($edit)
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"Edited DHCP server '".$saddr."' (login: '".$slogin."')");
					else
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"Added DHCP server '".$saddr."' (login: '".$slogin."')");
					FS::$iMgr->redir("mod=".$this->mid,true);
					return;
				// Delete DHCP Server
				case 6:
					if(!FS::$sessMgr->hasRight("mrule_ipmanager_servermgmt")) {
						FS::$iMgr->ajaxEcho("err-no-rights");
						return;
					}

					$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
					if(!$addr) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"No DHCP server specified to remove");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_servers","sshuser","addr = '".$addr."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Unknown DHCP server specified to remove");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_ip_history","server = '".$addr."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_ip_cache","server = '".$addr."'");
					// Later
					// FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","server = '".$addr."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_servers","addr = '".$addr."'");
					FS::$dbMgr->CommitTr();

					FS::$iMgr->ajaxEcho("Done");
					FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);
                                        return;
				// Add DHCP subnet
				case 7:
					$netid = FS::$secMgr->checkAndSecurisePostData("netid");
					$netmask = FS::$secMgr->checkAndSecurisePostData("netmask");
					$vlanid = FS::$secMgr->checkAndSecurisePostData("vlanid");
					$desc = FS::$secMgr->checkAndSecurisePostData("desc");
					$shortname = FS::$secMgr->checkAndSecurisePostData("shortname");
					$subnetclusters = FS::$secMgr->checkAndSecurisePostData("subnetclusters");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$netid || !FS::$secMgr->isIP($netid) || !$netmask || !FS::$secMgr->isMaskAddr($netmask) || !$vlanid || !FS::$secMgr->isNumeric($vlanid) ||
						$vlanid < 0 || $vlanid > 4096 || !$desc || !$shortname || preg_match("# #",$shortname) || $subnetclusters && !is_array($subnetclusters) 
						|| $edit && $edit != 1) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Bad datas entered when adding Declared subnet");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netmask","netid = '".$netid."'");
					if($edit) {
						if(!$exist) {
							FS::$iMgr->ajaxEcho("err-subnet-not-exists");
							return;
						}
					}
					else {
						if($exist) {
							FS::$iMgr->ajaxEcho("err-subnet-already-exists");
							return;
						}
					}

					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid","netid != '".$netid."' AND vlanid = '".$vlanid."'")) {
						FS::$iMgr->ajaxEcho("err-vlan-already-used");
						return;
					}

					if($subnetclusters) {
						for($i=0;$i<count($subnetclusters);$i++) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","clustername = '".$subnetclusters[$i]."'")) {
								FS::$iMgr->ajaxEcho("err-cluster-not-exists");
								return;
							}
						}
					}
					

					FS::$dbMgr->BeginTr();
					if($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid = '".$netid."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","subnet = '".$netid."'");
					}
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask,vlanid,subnet_short_name,subnet_desc","'".$netid."','".$netmask.
						"','".$vlanid."','".$shortname."','".$desc."'");

					if($subnetclusters) {
						for($i=0;$i<count($subnetclusters);$i++) {
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","clustername,subnet","'".$subnetclusters[$i]."','".$netid."'");
						}
					}

					FS::$dbMgr->CommitTr();

					$js = "";
					$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid");
					// One record we must create table if it's not and edit
					if($count == 1 && !$edit) {
						$jscontent = $this->showDeclaredNetTableHead()."</table>".FS::$iMgr->jsSortTable("declsubnettable");
						$js .= "$('#declsubnets').html('".addslashes($jscontent)."'); $('#declsubnets').show('slow');";
					}

					if($edit) $js = "hideAndRemove('#ds".FS::$iMgr->formatHTMLId($netid)."tr'); setTimeout(function(){";
					$jscontent = $this->tableDeclaredNetEntry($netid,$netmask,$desc,$shortname,$vlanid);
					$js .= "$('".addslashes($jscontent)."').insertAfter('#declsubnethead');";
					if($edit) $js .= "},1200);";

					FS::$iMgr->ajaxEcho("Done",$js);
					return;
				// Remove DHCP subnet
				case 8:
					$netid = FS::$secMgr->checkAndSecuriseGetData("netid");
					if(!$netid || !FS::$secMgr->isIP($netid)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",2,"Bad datas given when deleting Declared subnet");
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}
					
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid = '".$netid."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","subnet = '".$netid."'");
					FS::$dbMgr->CommitTr();

					$js = "hideAndRemove('#ds".FS::$iMgr->formatHTMLId($netid)."tr');";
					
					// Also remove table is no record
					$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid");
					if($count == 0) {
						$js .= "$('#declsubnets').hide('slow',function() { $('#declsubnets').html(''); });";
					}

					FS::$iMgr->ajaxEcho("Done",$js);
					return;
				// Add/Edit cluster
				case 9:
					if(!FS::$sessMgr->hasRight("mrule_ipmanager_servermgmt")) {
						FS::$iMgr->ajaxEcho("err-no-rights");
						return;
					}

					$cname = FS::$secMgr->checkAndSecurisePostData("cname");
					$cmembers = FS::$secMgr->checkAndSecurisePostData("clustermembers");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if(!$cname || !$cmembers || !is_array($cmembers) || $edit && $edit != 1) {
						if(!$cmembers || !is_array($cmembers))
							FS::$iMgr->ajaxEcho("err-cluster-need-members");
						else
							FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","clustername = '".$cname."'");
					if($edit) {
						if(!$exist) {
							FS::$iMgr->ajaxEcho("err-cluster-not-exists");
							return;
						}
					}
					else {
						if($exist) {
							FS::$iMgr->ajaxEcho("err-cluster-already-exists");
							return;
						}
					}

					$count = count($cmembers);
					for($i=0;$i<$count;$i++) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_servers","addr","addr = '".$cmembers[$i]."'")) {
							FS::$iMgr->ajaxEcho("err-dhcpserver-not-exists");
							return;
						}
					}
					
					FS::$dbMgr->BeginTr();
					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername = '".$cname."'");
					for($i=0;$i<$count;$i++)
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername,dhcpaddr","'".$cname."','".$cmembers[$i]."'");
					FS::$dbMgr->CommitTr();

					$js = "";
					$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername");
					if($count == 1 && !$edit) {
						$jscontent = $this->showTableHeadCluster()."</table>".FS::$iMgr->jsSortTable("clustertable");
						$js .= "$('#clusterdiv').html('".addslashes($jscontent)."'); $('#clusterdiv').show('slow');";
					}
					if($edit) $js .= "hideAndRemove('#cl".FS::$iMgr->formatHTMLId($cname)."tr'); setTimeout(function() {";
					$jscontent = $this->showDHCPClusterTableEntry($cname,$cmembers);
					$js .= "$('".addslashes($jscontent)."').insertAfter('#clusterth');";
					if($edit) $js .= "},1200);";

					FS::$iMgr->ajaxEcho("Done",$js);
					return;
				// Remove cluster
				case 10:
					if(!FS::$sessMgr->hasRight("mrule_ipmanager_servermgmt")) {
						FS::$iMgr->ajaxEcho("err-no-rights");
						return;
					}

					$cname = FS::$secMgr->checkAndSecuriseGetData("cluster");
					if(!$cname) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","clustername = '".$cname."'")) {
						FS::$iMgr->ajaxEcho("err-cluster-not-exists");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername = '".$cname."'");

					$js = "";
					$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername");
					if($count == 0)
						$js .= "hideAndRemove('#clustertable');";
					else
						$js .= "hideAndRemove('#cl".FS::$iMgr->formatHTMLId($cname)."tr');";

					FS::$iMgr->ajaxEcho("Done",$js);
					return;
			}
		}
	};
?>
