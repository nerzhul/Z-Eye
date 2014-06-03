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

	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/rules.php");
	require_once(dirname(__FILE__)."/../../lib/FSS/modules/Network.FS.class.php");
	require_once(dirname(__FILE__)."/objects.php");

	if(!class_exists("iIPManager")) {

	final class iIPManager extends FSModule {
		function __construct() {
			parent::__construct();
			$this->loc = new lIPManager();
			$this->modulename = "ipmmgmt";
			$this->rulesclass = new rIPManager($this->loc);
			$this->menu = $this->loc->s("menu-name");
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-ip-management"));
			$output = $this->showMain();
			return $output;
		}

		private function showMain() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");

			if (!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h1("title-ip-management");
				$tabs = array();
				if (FS::$sessMgr->hasRight("read"))
					$tabs[] = array(1,"mod=".$this->mid,$this->loc->s("Consult"));
				if (FS::$sessMgr->hasRight("subnetmgmt"))
					$tabs[] = array(2,"mod=".$this->mid,$this->loc->s("Manage-Subnets"));
				if (FS::$sessMgr->hasRight("optionsmgmt") ||
					FS::$sessMgr->hasRight("optionsgrpmgmt"))
					$tabs[] = array(5,"mod=".$this->mid,$this->loc->s("Manage-DHCP-Opts"));
				if (FS::$sessMgr->hasRight("advancedtools"))
					$tabs[] = array(4,"mod=".$this->mid,$this->loc->s("Advanced-tools"));
				if (FS::$sessMgr->hasRight("servermgmt"))
					$tabs[] = array(3,"mod=".$this->mid,$this->loc->s("Manage-Servers"));

				$output .= FS::$iMgr->tabPan($tabs,$sh);
			}
			else {
				switch($sh) {
					case 1: $output .= $this->showStats(); break;
					case 2: $output .= $this->showSubnetMgmt(); break;
					case 3: $output .= $this->showDHCPMgmt(); break;
					case 4: $output .= $this->showAdvancedTools(); break;
					case 5: $output .= $this->showDHCPOptsMgmt(); break;
				}
			}
			return $output;
		}

		private function showStats() {
			FS::$iMgr->setURL("sh=1");
			if (!FS::$sessMgr->hasRight("read")) {
				return FS::$iMgr->printNoRight("show IP stats");
			}
			$output = "";
			$formoutput = "";

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");

			$netfound = false;
			$tmpoutput = FS::$iMgr->cbkForm("1");

			// We bufferize all netid because of multiple sources
			$subnetObj = new dhcpSubnet();
			$formoutput = $subnetObj->getSelect(array("name" => "f", "selected" => $filter,
				"withcache" => true));

			$tmpoutput .= $formoutput.
				FS::$iMgr->select("view").FS::$iMgr->selElmt($this->loc->s("Stats"),1).
				FS::$iMgr->selElmt($this->loc->s("History"),2).
				FS::$iMgr->selElmt($this->loc->s("Monitoring"),3)."</select> ".
				FS::$iMgr->submit("","Consulter")."</form><br />";

			if ($formoutput == NULL) {
				return FS::$iMgr->printError("no-net-found");
			}

			$output .= $tmpoutput.
				"<div id=\"netHCr\"></div><div id=\"netshowcont\"></div>";

			return $output;
		}

		private function showSubnetIPList($filter) {
			if (!FS::$sessMgr->hasRight("read")) {
				return FS::$iMgr->printNoRight("show IP list");
			}
			$netid = ""; $netmask = "";
			$netdeclared = false;

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask","netid = '".$filter."'");
			if ($data = FS::$dbMgr->Fetch($query)) {
				$netid = $data["netid"]; $netmask = $data["netmask"];
				$netdeclared = true;
			}

			// Check if subnet is distributed to cluster
			$subnetLinkedToCluster = (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","subnet","subnet = '".$filter."'") != NULL);

			if (!$netid) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid,netmask","netid = '".$filter."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$netid = $data["netid"]; $netmask = $data["netmask"];
				}
			}

			$iparray = array();
			$output = FS::$iMgr->h3("Réseau : ".$netid."/".$netmask,true);

			// range management
			$subnetu = preg_replace("#[.]#","_",$filter);
			if ((FS::$sessMgr->hasRight("rangemgmt") ||
				FS::$sessMgr->hasRight($subnetu."_rangemgmt")) &&
				$netdeclared) {
				$output .= FS::$iMgr->opendiv(14,$this->loc->s("configure-ip-range"),array("line" => true,"lnkadd" => "subnet=".$netid));
				$output .= FS::$iMgr->opendiv(15,$this->loc->s("import-dhcp-reserv"),array("line" => true,"lnkadd" => "subnet=".$netid));
			}

			$output .= "<div id=\"".FS::$iMgr->formatHTMLId($netid)."\"></div>";

			$netobj = new FSNetwork();
			$netobj->setNetAddr($netid);
			$netobj->setNetMask($netmask);

			$this->loadSwitchList();

			// for Z-Eye ipmanager request. Not the better idea, i think
			$iplist = "";
			// Initiate network IPs
			$lastip = $netobj->getLastUsableIPLong();
			for ($i=($netobj->getFirstUsableIPLong());$i<=$lastip;$i++) {
				$iparray[$i] = array();
				$iparray[$i]["mac"] = "";
				$iparray[$i]["host"] = "";
				$iparray[$i]["ltime"] = "";
				/*
				* 0: unk/free, 1: free, 2: used, 3: reserved (cache),
				* 4: distributed, 5: reserved (Z-Eye), 6: distributed (Z-Eye)
				*/
				$iparray[$i]["distrib"] = 0;
				$iparray[$i]["servers"] = array();
				$iparray[$i]["comment"] = "";

				if ($iplist != "") $iplist .= ",";
				$iplist .= "'".long2ip($i)."'";
			}

			$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_range","rangestart,rangestop","subnet = '".$netid."'");
			while ($data2 = FS::$dbMgr->Fetch($query2)) {
				$start = ip2long($data2["rangestart"]);
				$end = ip2long($data2["rangestop"]);
				for ($i=$start;$i<=$end;$i++) {
					$iparray[$i]["distrib"] = 6;
				}
			}
			// Fetch datas
			$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","ip,macaddr,hostname,leasetime,distributed,server","netid = '".$netid."'");
			while ($data2 = FS::$dbMgr->Fetch($query2)) {
				// If it's reserved on a host don't override status
				if ($iparray[ip2long($data2["ip"])]["distrib"] != 3) {
					$iparray[ip2long($data2["ip"])]["mac"] = $data2["macaddr"];
					$iparray[ip2long($data2["ip"])]["host"] = $data2["hostname"];
					$iparray[ip2long($data2["ip"])]["ltime"] = $data2["leasetime"];
					// Only overwrite if it's not dynamicly distributed by Z-Eye and force it if it's a lease
					if ($iparray[ip2long($data2["ip"])]["distrib"] != 6 ||
						$data2["distributed"] == 2) {
						$iparray[ip2long($data2["ip"])]["distrib"] = $data2["distributed"];
					}
				}
				// List servers where the data is
				$iparray[ip2long($data2["ip"])]["servers"][] = $data2["server"];
			}

			$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip","ip,macaddr,hostname,comment,reserv","ip IN (".$iplist.")");
			while ($data2 = FS::$dbMgr->Fetch($query2)) {
				$iparray[ip2long($data2["ip"])]["mac"] = $data2["macaddr"];
				$iparray[ip2long($data2["ip"])]["host"] = $data2["hostname"];
				$iparray[ip2long($data2["ip"])]["comment"] = preg_replace("#[\r\n]#","<br />",$data2["comment"]);
				$iparray[ip2long($data2["ip"])]["distrib"] = ($data2["reserv"] == 't' ? 5 : $iparray[ip2long($data2["ip"])]["distrib"]);
			}


			$used = 0;
			$reserv = 0;
			$free = 0;
			$distrib = 0;
			$fixedip = 0;

			$output .= "<table id=\"tipList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("IP-Addr")."</th><th></th><th>".$this->loc->s("Status")."</th>
				<th>".$this->loc->s("MAC-Addr")."</th><th>".$this->loc->s("Hostname")."</th><th>".$this->loc->s("Comment")."</th><th>".
				$this->loc->s("Switch")."</th><th>".$this->loc->s("Port")."</th><th>".
				$this->loc->s("Lease-end")."</th><th>".$this->loc->s("Servers")."</th><th></th></tr></thead>";

			foreach ($iparray as $key => $value) {
				$style = "";
				switch($value["distrib"]) {
					case 1: $free++; break;
					case 2: $used++; break;
					case 3: case 5: $reserv++; break;
					case 4: case 6: $distrib++; break;
					default: {
							$mac = FS::$dbMgr->GetOneData("node_ip","mac","ip = '".long2ip($key)."' AND time_last > (current_timestamp - interval '1 hour') AND active = 't'");
							if ($mac) {
								$query3 = FS::$dbMgr->Select("node","switch,port,time_last","mac = '".$mac."' AND active = 't'");
								if ($data3 = FS::$dbMgr->Fetch($query3)) {
									$fixedip++;
								}
								else {
									$free++;
								}
							}
							else {
								$free++;
							}
						}
						break;
				}

				$output .= "<tr id=\"sb".FS::$iMgr->formatHTMLId(long2ip($key))."tr\">".
					(new dhcpIP())->genIPLineFull(long2ip($key),
						$value["mac"],$value["host"],$value["comment"],
						$netid,$value["ltime"],$value["servers"],$value["distrib"],
						$subnetLinkedToCluster).
					"</tr>";
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
			if ($used > 0) {
				$js .= "{ name: '".$this->loc->s("Baux")."', y: ".$used.", color: 'red' },";
			}

			if ($reserv > 0) {
				$js .= "{ name: '".$this->loc->s("Reservations")."', y: ".$reserv.", color: 'yellow'},";
			}

			if ($fixedip > 0) {
				$js .= "{ name: '".$this->loc->s("Stuck-IP")."', y: ".$fixedip.", color: 'orange'},";
			}

			if ($distrib > 0) {
				$js .= "{ name: '".$this->loc->s("Available-s")."', y: ".$distrib.", color: 'cyan'},";
			}

			$js .= "{ name: '".$this->loc->s("Free-s")."', y:".$free.", color: 'green'}]
				}]});},300);";
			FS::$iMgr->js($js);

			return $output;
		}

		private function showDHCPImportReservForm($subnet) {
			$subnetu = preg_replace("#[.]#","_",$subnet);
			if (!FS::$sessMgr->hasRight("rangemgmt") &&
				!FS::$sessMgr->hasRight($subnetu."_rangemgmt")) {
				return FS::$iMgr->printNoRight("show reserv importation form");
			}

			$netmask = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netmask","netid = '".$subnet."'");
			if (!$netmask) {
				return FS::$iMgr->printError("err-subnet-not-exists");
			}


			$selOutput = FS::$iMgr->select("sep").FS::$iMgr->selElmt($this->loc->s("comma"),",").
					FS::$iMgr->selElmt($this->loc->s("semi-colon"),";")."</select>";

			return FS::$iMgr->tip("tip-import-reserv").
				FS::$iMgr->cbkForm("19")."<table>".
				FS::$iMgr->hidden("subnet",$subnet).
				FS::$iMgr->idxLines(array(
					array("CSV-content","csv",array("type" => "area", "width" => 600)),
					array("separator","sep",array("type" => "raw", "value" => $selOutput)),
					array("Replace-?","repl",array("type" => "chk"))
				)).
				FS::$iMgr->tableSubmit("Import");
		}

		private function showDHCPRangeForm($subnet) {
			$subnetu = preg_replace("#[.]#","_",$subnet);
			if (!FS::$sessMgr->hasRight("rangemgmt") &&
				!FS::$sessMgr->hasRight($subnetu."_rangemgmt")) {
				return FS::$iMgr->printNoRight("show range form");
			}

			$netmask = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netmask","netid = '".$subnet."'");
			if (!$netmask) {
				return FS::$iMgr->printError("err-subnet-not-exists");
			}

			$output = FS::$iMgr->tip("tip-range").FS::$iMgr->cbkForm("18")."<table>".
				FS::$iMgr->idxLine("start-ip","startip",array("type" => "ip", "length" => 16)).
				FS::$iMgr->idxLine("end-ip","endip",array("type" => "ip", "length" => 16)).
				"<tr><td>".$this->loc->s("Action")."</td><td>".FS::$iMgr->select("rangeact").
				FS::$iMgr->selElmt($this->loc->s("add-to-dynamic-distrib"),1).
				FS::$iMgr->selElmt($this->loc->s("remove-from-dynamic-distrib"),2).
				"</select>".FS::$iMgr->hidden("subnet",$subnet)."</td></tr>".
				FS::$iMgr->aeTableSubmit();

			return $output;
		}

		private function showSubnetHistory($filter) {
			$subnetu = preg_replace("#[.]#","_",$filter);
			if (!FS::$sessMgr->hasRight("history") &&
				!FS::$sessMgr->hasRight($subnetu."_history")) {
				return FS::$iMgr->printNoRight("show subnet history");
			}

			$output = FS::$iMgr->js("function historyDateChange() {
				hideAndEmpty('#hstcontent');
				$.post('?mod=".$this->mid."&act=4',$('#hstfrm').serialize(), function(data) {
					$('#hstcontent').show(\"fast\",function() { $('#hstcontent').html(data); });
				}); };");

			$output .= "<div id=\"hstcontent\">".$this->showHistory($filter)."</div>".
				FS::$iMgr->form("?mod=".$this->mid."&act=4",array("id" => "hstfrm")).
				FS::$iMgr->hidden("filter",$filter);

			$date = FS::$dbMgr->GetMin(PGDbConfig::getDbPrefix()."dhcp_subnet_history","collecteddate");
			if (!$date) {
				$date = "now";
			}
			$diff = ceil((strtotime("now")-strtotime($date))/(24*60*60));

			$output .= FS::$iMgr->slider("hstslide","daterange",1,$diff,array("hidden" => "jour(s)","width" => "200px","value" => "1")).
				FS::$iMgr->button("but",$this->loc->s("change-interval"),"historyDateChange()")."</form>";
			return $output;
		}

		private function showSubnetMonitoring($filter) {
			$subnetu = preg_replace("#[.]#","_",$filter);
			if (!FS::$sessMgr->hasRight("subnetmon") &&
				!FS::$sessMgr->hasRight($subnetu."_subnetmon")) {
				return FS::$iMgr->printNoRight("show subnet monitoring");
			}

			$wlimit = 0; $climit = 0;
			$maxage = 0; $enmon = false; $contact = ""; $eniphistory = false;

			if ($data = FS::$dbMgr->GetOneEntry(PGDbConfig::getDbPrefix()."dhcp_monitoring",
				"warnuse,crituse,maxage,enmon,eniphistory,contact","subnet = '".$filter."'")) {
				$wlimit = $data["warnuse"];
				$climit = $data["crituse"];
				$maxage = $data["maxage"];
				$enmon = $data["enmon"];
				$eniphistory = $data["eniphistory"];
				$contact = $data["contact"];
			}

			$output = "<div id=\"monsubnetres\"></div>".
				FS::$iMgr->cbkForm("3&f=".$filter).
				"<ul class=\"ulform\"><li>".
				FS::$iMgr->check("eniphistory",array("check" => $eniphistory == 't',
					"label" => $this->loc->s("En-IP-history"))).
				"</li><li>".FS::$iMgr->check("enmon",array("check" => $enmon == 1,"label" => $this->loc->s("En-monitor")))."</li><li>".
				FS::$iMgr->numInput("wlimit",($wlimit > 0 ? $wlimit : 0),array("size" => 3, "length" => 3, "label" => $this->loc->s("warn-line"), "tooltip" => "tooltip-%use"))."</li><li>".
				FS::$iMgr->numInput("climit",($climit > 0 ? $climit : 0),array("size" => 3, "length" => 3, "label" => $this->loc->s("crit-line"), "tooltip" => "tooltip-%use"))."</li><li>".
				FS::$iMgr->numInput("maxage",($maxage > 0 ? $maxage : 0),array("size" => 7, "length" => 7, "label" => $this->loc->s("max-age"), "tooltip" => "tooltip-max-age"))."</li><li>".
				FS::$iMgr->input("contact",$contact,20,40,$this->loc->s("Contact"),"tooltip-contact")."</li><li>".
				FS::$iMgr->submit("",$this->loc->s("Save"))."</li></ul></form>";
			return $output;
		}

		private function showAdvancedTools() {
			FS::$iMgr->setURL("sh=4");

			if (!FS::$sessMgr->hasRight("advancedtools")) {
				return FS::$iMgr->printNoRight("show advanced tools");
			}

			return FS::$iMgr->h4("title-search-old").
				FS::$iMgr->cbkForm("2").
				$this->loc->s("intval-days")." ".
				FS::$iMgr->numInput("ival")."<br />".
				FS::$iMgr->submit("",$this->loc->s("Search")).
				"</form><div id=\"obsres\"></div>";
		}

		private function showDHCPOptsMgmt() {
			FS::$iMgr->setURL("sh=5");

			if (!FS::$sessMgr->hasRight("optionsmgmt") &&
				!FS::$sessMgr->hasRight("optionsgrpmgmt")) {
				return FS::$iMgr->printNoRight("show DHCP options management tab");
			}
			$output = FS::$iMgr->h2("title-dhcp-opts-group").FS::$iMgr->tip("tip-dhcp-opt-group")."<br />".
				FS::$iMgr->opendiv(12,$this->loc->s("create-option-group"),array("line" => true));

			$tMgr = new HTMLTableMgr(array(
				"tabledivid" => "dgoptslist",
				"tableid" => "dgopttable",
				"firstlineid" => "dgoptftr",
				"sqltable" => "dhcp_option_group",
				"sqlattrid" => "optgroup",
				"attrlist" => array(array("Groupname","optgroup",""), array("options","optalias","")),
				"sorted" => true,
				"odivnb" => 13,
				"odivlink" => "optgroup=",
				"rmcol" => true,
				"rmlink" => "optgroup",
				"rmactid" => 17,
				"rmconfirm" => "confirm-remove-option",
				"trpfx" => "do",
				"multiid" => true,
				));
			$output .= $tMgr->render();

			$output .= FS::$iMgr->h2("title-dhcp-opts").FS::$iMgr->tip("tip-dhcp-opts")."<br />".
				FS::$iMgr->opendiv(10,$this->loc->s("create-option"),array("line" => true));

			$tMgr = new HTMLTableMgr(array(
				"tabledivid" => "doptslist",
				"tableid" => "dopttable",
				"firstlineid" => "doptftr",
				"sqltable" => "dhcp_option",
				"sqlattrid" => "optalias",
				"attrlist" => array(array("option-alias","optalias",""), array("option-name","optname",""),
					array("option-value","optval","")),
				"sorted" => true,
				"odivnb" => 11,
				"odivlink" => "optalias=",
				"rmcol" => true,
				"rmlink" => "optalias",
				"rmactid" => 15,
				"rmconfirm" => "confirm-remove-option",
				"trpfx" => "do",
				));
			$output .= $tMgr->render();

			$output .= FS::$iMgr->h2("title-custom-dhcp-opts").FS::$iMgr->tip("tip-custom-dhcp-opts")."<br />".
				FS::$iMgr->opendiv(8,$this->loc->s("create-custom-option"),array("line" => true));

			$tMgr = new HTMLTableMgr(array(
				"tabledivid" => "customoptslist",
				"tableid" => "dhcpopttable",
				"firstlineid" => "dhcpoptftr",
				"sqltable" => "dhcp_custom_option",
				"sqlattrid" => "optname",
				"sqlcond" => "protectrm = 'f'",
				"attrlist" => array(array("option-name","optname",""), array("option-code","optcode",""),
					array("option-type","opttype","s",array("boolean" => "boolean",
						"uint8" => "uinteger-8", "uint16" => "uinteger-16", "uint32" => "uinteger-32",
						"int8" => "integer-8", "int16" => "integer-16", "int32" => "integer-32",
						"ip" => "IP-Addr", "text" => "text"))),
				"sorted" => true,
				"odivnb" => 9,
				"odivlink" => "optname=",
				"rmcol" => true,
				"rmlink" => "optname",
				"rmactid" => 13,
				"rmconfirm" => "confirm-remove-custom-option",
				"trpfx" => "dco",
				));
			$output .= $tMgr->render();
			return $output;
		}

		private function showDHCPOptsGroupForm($optgroup="") {
			$options = array();
			$optexist = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_option","optname");
			if (!$optexist) {
				return FS::$iMgr->printError("err-no-dhcp-option");
			}

			if ($optgroup) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_option_group","optalias","optgroup = '".$optgroup."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					$options[] = $data["optalias"];
				}
			}

			$output = FS::$iMgr->cbkForm("16")."<table>".
				FS::$iMgr->idxLine("Groupname","optgroup",array("value" => $optgroup,"type" => "idxedit", "length" => 64,
					"edit" => $optgroup != "")).
				"<tr><td>".$this->loc->s("Group-DHCP-opts")."</td><td>".FS::$iMgr->select("groupoptions",array("multi" => true));

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_option","optalias,optname");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["optalias"]." (".$data["optname"].")",$data["optalias"],in_array($data["optalias"],$options));
			}
			$output .= "</td></tr>".
				FS::$iMgr->aeTableSubmit($optgroup == "");
			return $output;
		}

		private function showDHCPOptsForm($optalias="") {
			$optname = ""; $optvalue = "";
			$customoptexist = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_custom_option","optname");
			if (!$customoptexist) {
				return FS::$iMgr->printError("err-no-dhcp-custom-option");
			}

			if ($optalias) {
				if ($data = FS::$dbMgr->GetOneEntry(PGDbConfig::getDbPrefix()."dhcp_option","optname,optval",
					"optalias = '".$optalias."'")) {
					$optname = $data["optname"];
					$optvalue = $data["optval"];
				}
				else {
					return FS::$iMgr->printError("err-dhcp-option-not-exists");
				}
			}

			$output = FS::$iMgr->cbkForm("14")."<table>".
				FS::$iMgr->idxLine("option-alias","optalias",array("value" => $optalias,"type" => "idxedit", "length" => 64,
					"edit" => $optalias != "")).
				"<tr ".FS::$iMgr->tooltip("tooltip-dhcp-option-value")."><td>".$this->loc->s("option-name")."</td><td>".FS::$iMgr->select("optname");

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_custom_option","optcode,optname,opttype");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["optcode"].": ".$data["optname"]." (".$data["opttype"].")",$data["optname"],$optname == $data["optname"]);
			}

			$output .= "</select></td></tr>".
				FS::$iMgr->idxLine("option-value","optval",array("length" => 500, "size" => 40, "value" => $optvalue)).
				FS::$iMgr->aeTableSubmit($optalias == "");

			return $output;
		}

		private function showDHCPCustomOptsForm($optname="") {
			$opttype = 0; $optcode = 0; $protect = 'f';
			if ($optname) {
				$opttype = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_custom_option","opttype",
					"optname = '".$optname."'");
				$optcode = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_custom_option","optcode",
					"optname = '".$optname."'");
				$protect = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_custom_option","protectrm",
					"optname = '".$optname."'");
			}

			if ($protect == 't') {
				return FS::$iMgr->printError("err-option-code-protected");
			}

			$output = FS::$iMgr->cbkForm("12")."<table>".
				FS::$iMgr->idxLine("option-name","optname",array("value" => $optname,"type" => "idxedit", "length" => 32,
					"edit" => $optname != "")).
				FS::$iMgr->idxLine("option-code","optcode",array("type" => "num", "length" => 3, "size" => 3,
					"edit" => $optcode != "", "value" => $optcode, "tooltip" => "tooltip-dhcp-option-code")).
				"<tr><td>".$this->loc->s("option-type")."</td><td>".FS::$iMgr->select("opttype").
				FS::$iMgr->selElmt($this->loc->s("boolean"),		"boolean",	$opttype == "boolean").
				FS::$iMgr->selElmt($this->loc->s("uinteger-8"),		"uint8",	$opttype == "uint8").
				FS::$iMgr->selElmt($this->loc->s("uinteger-16"),	"uint16",	$opttype == "uint16").
				FS::$iMgr->selElmt($this->loc->s("uinteger-32"),	"uint32",	$opttype == "uint32").
				FS::$iMgr->selElmt($this->loc->s("integer-8"),		"int8",		$opttype == "int8").
				FS::$iMgr->selElmt($this->loc->s("integer-16"),		"int16",	$opttype == "int16").
				FS::$iMgr->selElmt($this->loc->s("integer-32"),		"int32",	$opttype == "int32").
				FS::$iMgr->selElmt($this->loc->s("IP-Addr"),		"ip",		$opttype == "ip").
				FS::$iMgr->selElmt($this->loc->s("text"),		"text",		$opttype == "text").
				"</td></tr></select>".
				FS::$iMgr->aeTableSubmit($optname == "");

			return $output;
		}

		private function showSubnetMgmt() {
			FS::$iMgr->setURL("sh=2");

			if (!FS::$sessMgr->hasRight("subnetmgmt")) {
				return FS::$iMgr->printNoRight("show subnet management");
			}
	                $output = FS::$iMgr->opendiv(1,$this->loc->s("declare-subnet"),array("line" => true));

			$output .= "<div id=\"declsubnets\">";

			$found = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask,vlanid,subnet_desc,subnet_short_name");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = 1;
					$output .= $this->showDeclaredNetTableHead();
				}
				$netv6 = "";
				$prefixlen = 0;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v6_declared",
					"netid,prefixlen","netidv4 = '".$data["netid"]."'");
				if ($data2 = FS::$dbMgr->Fetch($query2)) {
					$netv6 = $data2["netid"];
					$prefixlen = $data2["prefixlen"];
				}
				$output .= $this->tableDeclaredNetEntry($data["netid"],$data["netmask"],$data["subnet_desc"],
					$data["subnet_short_name"],$data["vlanid"],$netv6,$prefixlen);
			}
			if ($found) {
				$output .= "</table>".FS::$iMgr->jsSortTable("declsubnettable");
			}
			$output .= "</div>";
			return $output;
		}

		private function showDeclaredNetTableHead() {
			return "<table id=\"declsubnettable\"><thead id=\"declsubnethead\"><tr><th>".
				$this->loc->s("netid")."/".$this->loc->s("netmask")."</th><th>".
				$this->loc->s("netidv6")."</th><th>".
				$this->loc->s("vlanid")."</th><th>".$this->loc->s("Usable")."</th><th>".
				$this->loc->s("subnet-shortname")."</th><th>".$this->loc->s("subnet-desc")."</th>
				<th>".$this->loc->s("dhcp-clusters")."</th><th></th></tr></thead>";
		}

		private function tableDeclaredNetEntry($netid,$netmask,$desc,$shortname,$vlanid,$netidv6,$prefixlenv6) {
			$net = new FSNetwork();
			$net->SetNetAddr($netid);
			$net->SetNetMask($netmask);
			$net->CalcCIDR();
			$output = "<tr id=\"ds".FS::$iMgr->formatHTMLId($netid)."tr\"><td>".FS::$iMgr->opendiv(2,$netid,array("lnkadd" => "netid=".$netid)).
				"/".$netmask." (/".$net->getCIDR().")</td><td>";

			if ($netidv6 != "" && $prefixlenv6 != 0) {
				$output .= $netidv6."/".$prefixlenv6;
			}

			$output .= "</td><td>".$vlanid."</td><td>".
				($net->getMaxHosts()-1)."</td><td>".$shortname."</td><td>".$desc."</td><td>";

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","clustername","subnet = '".$netid."'");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found)
					$found = true;
				else
					$output .= "<br />";
				$output .= $data["clustername"];
			}

			if (!$found) {
				$output .= $this->loc->s("None");
			}

			$output .= "</td><td>".FS::$iMgr->removeIcon(8,"netid=".$netid,array("js" => true,
				"confirmtext" => "confirm-remove-declared-subnet",
				"confirmval" => $netid
			)).
			"</td></tr>";
			return $output;
		}

		private function showDHCPSubnetForm($netid = "") {
			if (!FS::$sessMgr->hasRight("subnetmgmt")) {
				return FS::$iMgr->printNoRight("show subnet form");
			}
			$netmask = ""; $vlanid = 0; $shortname = ""; $desc = "";
			$router = ""; $domainname = ""; $dns1 = ""; $dns2 = "";
			$mleasetime = 0; $dleasetime = 0;
			$netidv6 = ""; $prefixlenv6 = 0;
			if ($netid) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netmask,vlanid,subnet_desc,subnet_short_name,router,dns1,dns2,domainname,
					mleasetime,dleasetime","netid = '".$netid."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$netmask = $data["netmask"];
					$vlanid = $data["vlanid"];
					$shortname = $data["subnet_short_name"];
					$desc = $data["subnet_desc"];
					$router = $data["router"];
					$dns1 = $data["dns1"];
					$dns2 = $data["dns2"];
					$domainname = $data["domainname"];
					$mleasetime = $data["mleasetime"];
					$dleasetime = $data["dleasetime"];

					$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v6_declared","netid,prefixlen","netidv4 = '".$netid."'");
					if ($data2 = FS::$dbMgr->Fetch($query2)) {
						$netidv6 = $data2["netid"];
						$prefixlenv6 = $data2["prefixlen"];
					}
				}
			}
			$output = FS::$iMgr->cbkForm("7")."<table>".
				FS::$iMgr->idxLine("netid","netid",array("value" => $netid,"type" => "idxipedit", "length" => 16, "edit" => $netid != "")).
				FS::$iMgr->idxLine("netmask","netmask",array("value" => $netmask,"length" => 16, "type" => "ip")).
				FS::$iMgr->idxLine("netidv6","netidv6",array("value" => $netidv6,"length" => 40, "tooltip" => "tooltip-ipv6")).
				FS::$iMgr->idxLine("prefixlen","prefixlen",array("value" => $prefixlenv6,"length" => 3, "type" => "num", "tooltip" => "tooltip-prefixlen")).
				FS::$iMgr->idxLine("vlanid","vlanid",array("value" => $vlanid,"length" => 4, "type" => "num", "tooltip" => "tooltip-vlanid")).
				FS::$iMgr->idxLine("subnet-shortname","shortname",array("value" => $shortname,"length" => 32, "tooltip" => "tooltip-shortname")).
				FS::$iMgr->idxLine("subnet-desc","desc",array("value" => $desc,"length" => 128, "tooltip" => "tooltip-desc"));

			/*
			* Clusters associated to subnet (if there is clusters)
			*/
			$clusters = array();
			if ($netid) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","clustername","subnet = '".$netid."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!in_array($data["clustername"],$clusters))
						$clusters[] = $data["clustername"];
				}
			}

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","",array("order" => "clustername", "group" => "clustername"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$output .= "<tr ".FS::$iMgr->tooltip("tooltip-dhcp-cluster-distrib")."><td>".$this->loc->s("dhcp-cluster")."</td><td>".
						FS::$iMgr->select("subnetclusters",array("multi" => true));
					$found = true;
				}
				$output .= FS::$iMgr->selElmt($data["clustername"],$data["clustername"],in_array($data["clustername"],$clusters));
			}
			if ($found) $output .= "</select></td></tr>";

			/*
			* Misc options, related to classic DHCP subnet mgmt
			*/
			$output .= FS::$iMgr->idxLine($this->loc->s("router")." (*)","router",
				array("value" => $router,"length" => 16, "type" => "ip", "tooltip" => "tooltip-router", "rawlabel" => true)).
				FS::$iMgr->idxLine($this->loc->s("domain-name")." (*)","domainname",
					array("value" => $domainname,"length" => 120, "tooltip" => "tooltip-domainname", "rawlabel" => true)).
				FS::$iMgr->idxLine($this->loc->s("DNS")." 1 (*)","dns1",
					array("value" => $dns1,"length" => 16, "type" => "ip", "rawlabel" => true)).
				FS::$iMgr->idxLine($this->loc->s("DNS")." 2 (*)","dns2",
					array("value" => $dns2,"length" => 16, "type" => "ip", "rawlabel" => true)).
				FS::$iMgr->idxLine($this->loc->s("max-lease-time")." (**)","mleasetime",
					array("value" => $mleasetime,"length" => 7, "type" => "num","tooltip" => "tooltip-max-lease-time", "rawlabel" => true)).
				FS::$iMgr->idxLine($this->loc->s("default-lease-time")." (**)","dleasetime",
					array("length" => 7, "type" => "num", "value" => $dleasetime, "rawlabel" => true,
					"tooltip" => "tooltip-default-lease-time"));

			/*
			* Option groups associated to subnet (if there is option groups)
			*/
			$optgroups = array();
			if ($netid) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_optgroups","optgroup","netid = '".$netid."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					$optgroups[] = $data["optgroup"];
				}
			}

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_option_group","optgroup","",array("order" => "optgroup", "group" => "optgroup"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$output .= "<tr ".FS::$iMgr->tooltip("tooltip-dhcp-option-group")."><td>".$this->loc->s("option-group")."</td><td>".FS::$iMgr->select("dopts",array("multi" => true));
					$found = true;
				}
				$output .= FS::$iMgr->selElmt($data["optgroup"],$data["optgroup"],in_array($data["optgroup"],$optgroups));
			}
			if ($found) $output .= "</select></td></tr>";

			$output .= "<tr><td colspan=\"2\">".FS::$iMgr->tip("(*) ".$this->loc->s("required-if-cluster")."<br />".
				"(**) ".$this->loc->s("tip-inherit-if-null"),true)."</td></tr>".
				FS::$iMgr->aeTableSubmit($netid == "");
			return $output;
		}

		private function showDHCPSrvForm($addr = "") {
			if (!FS::$sessMgr->hasRight("servermgmt")) {
				return FS::$iMgr->printNoRight("show server form");
			}

			$user = "";
			$dhcpdpath = "";
			$leasepath = "";
			$reservconfpath = "";
			$subnetconfpath = "";
			$alias = "";
			$description = "";
			$clusteraddr = "";
			$dhcptype = 0;

			if ($addr) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_servers",
					"alias,description,sshuser,dhcpdpath,leasespath,reservconfpath,subnetconfpath,dhcptype,clusteraddr",
					"addr = '".$addr."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$alias = $data["alias"];
					$description = $data["description"];
					$user = $data["sshuser"];
					$dhcpdpath = $data["dhcpdpath"];
					$leasepath = $data["leasespath"];
					$reservconfpath = $data["reservconfpath"];
					$subnetconfpath = $data["subnetconfpath"];
					$clusteraddr = $data["clusteraddr"];
					$dhcptype = $data["dhcptype"];
				}
			}

			$selForm = sprintf("%s%s</select>",
				FS::$iMgr->select("dhcptype"),
				FS::$iMgr->selElmt("ISC-DHCP",1,$dhcptype == 1)
			);

			$output = FS::$iMgr->cbkForm("5").$this->loc->s("note-needed")."<table>".
				FS::$iMgr->idxLines(array(
					array($this->loc->s("server-addr")." (*)","addr",
						array("value" => $addr,"type" => "idxedit",
							"length" => 128, "edit" => $addr != "",
							"rawlabel" => true, "tooltip" => "tooltip-dhcp-server-ip"
					)),
					array("server-alias","alias",array("value" => $alias,
						"length" => 64, "tooltip" => "tooltip-dhcp-alias")),
					array("server-desc","description",
						array("value" => $description, "length" => 128,
							"tooltip" => "tooltip-dhcp-desc")),
					array($this->loc->s("ssh-user")." (*)","sshuser",
						array("value" => $user,"length" => 128,"rawlabel" => true)),
					array($this->loc->s("ssh-pwd")." (*)","sshpwd",
						array("type" => "pwd","rawlabel" => true)),
					array($this->loc->s("ssh-pwd-repeat")." (*)",
						"sshpwd2", array("type" => "pwd","rawlabel" => true)),
					array("clustering-ip","dhcpdclusterip", array(
						"type" => "ip", "value" => $clusteraddr,
						"tooltip" => "tooltip-clustering-ip")),
					array($this->loc->s("dhcp-type")." (*)","", array(
						"type" => "raw", "rawlabel" => true,
						"value" => $selForm)),
					array("dhcpd-path","dhcpdpath",
						array("value" => $dhcpdpath,"length" => 980,
							"size" => 30, "tooltip" => "tooltip-dhcpdpath")),
					array("lease-path","leasepath",
						array("value" => $leasepath,"length" => 980,
							"size" => 30, "tooltip" => "tooltip-leasepath")),
					array("reservconf-path","reservconfpath",
						array("value" => $reservconfpath,"length" => 980,
						"size" => 30, "tooltip" => "tooltip-reservconfpath")),
					array("subnetconf-path","subnetconfpath",
						array("value" => $subnetconfpath,"length" => 980,
							"size" => 30, "tooltip" => "tooltip-subnetconfpath"))
				)).
				FS::$iMgr->aeTableSubmit($addr == "");
			return $output;
		}

		private function showDHCPSrvList() {
			if (!FS::$sessMgr->hasRight("servermgmt")) {
				return FS::$iMgr->printNoRight("show server list");
			}
			$output = "<table><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("server-alias")."</th><th>".$this->loc->s("server-desc")."</th><th>".$this->loc->s("os-name").
				"</th><th>".$this->loc->s("dhcp-type")."</th><th>".$this->loc->s("ssh-user")."</th><th>".$this->loc->s("member-of")."<th></th></tr>";

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_servers","addr,alias,description,sshuser,dhcpdpath,leasespath,reservconfpath,subnetconfpath,osname,dhcptype");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$output .= "<tr><td>".FS::$iMgr->opendiv(3,$data["addr"],array("lnkadd" => "addr=".$data["addr"]))."</td><td>".$data["alias"]."</td><td>".$data["description"]."</td><td>".
					$data["osname"]."</td><td>";

				switch($data["dhcptype"]) {
					case 1:	$output .= "ISC-DHCP"; break;
					default: $output .= $this->loc->s("unknown"); break;
				}
				$output .= "</td><td>".$data["sshuser"]."</td><td>";

				$found = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","dhcpaddr = '".$data["addr"]."'");
				while ($data2 = FS::$dbMgr->Fetch($query2)) {
					if (!$found)
						$found = true;
					else
						$output .= "<br />";
					$output .= $data2["clustername"];
				}

				if (!$found) $output .= $this->loc->s("None");

				$output .= "</td><td>".FS::$iMgr->removeIcon(6,"addr=".$data["addr"],array("js" => true,
					"confirmtext" => "confirm-remove-dhcp",
					"confirmval" => $data["addr"]
				)).
				"</td></tr>";
			}
			$output .= "</table>";
			return $output;
		}

		private function showDHCPMgmt() {
			FS::$iMgr->setURL("sh=3");

			if (!FS::$sessMgr->hasRight("servermgmt")) {
				return FS::$iMgr->printNoRight("show DHCP management");
			}

			$output = FS::$iMgr->h2("title-dhcp-cluster-mgmt");

			$dhcpcount = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_servers","addr");

			if ($dhcpcount > 0) {
				// To add DHCP cluster
				$output .= FS::$iMgr->opendiv(4,$this->loc->s("add-cluster")).
					"<div id=\"clusterdiv\">";

				// To edit/delete clusters
				if (FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_cluster","dhcpaddr") > 0) {
					$output .= $this->showDHCPClusterList();
				}
				$output .= "</div>";
			}
			else
				$output .= FS::$iMgr->printError("err-need-dhcp-server");


			$output .= FS::$iMgr->h2("title-dhcp-server-mgmt");
			// To add servers
			$output .= FS::$iMgr->opendiv(5,$this->loc->s("title-add-server"));


			// To edit/delete servers
			if ($dhcpcount > 0) {
				$output .= $this->showDHCPSrvList();
			}

			return $output;
		}

		private function showDHCPClusterForm($name = "") {
			if (!FS::$sessMgr->hasRight("servermgmt")) {
				return FS::$iMgr->printNoRight("show cluster form");
			}
			$members = array();
			$clustermode = 0;
			$clustermaster = "";
			if ($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_cluster","dhcpaddr","clustername = '".$name."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					$members[] = $data["dhcpaddr"];
				}
				$clustermode = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_cluster_options","clustermode","clustername = '".$name."'");
				$clustermaster = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_cluster_options","master","clustername = '".$name."'");
			}
			$output = FS::$iMgr->cbkForm("9")."<table>".
				FS::$iMgr->idxLine("Cluster-name","cname",array("value" => $name,"type" => "idxedit", "edit" => $name != "")).
				"<tr><td>".$this->loc->s("Cluster-members")."</td><td>".FS::$iMgr->select("clustermembers",array("multi" => true));

			$outputlist2 = "";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_servers","addr,alias","",array("order" => "addr"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["addr"].($data["alias"] ? " (".$data["alias"].")" : ""),$data["addr"],in_array($data["addr"],$members));
				$outputlist2 .= FS::$iMgr->selElmt($data["addr"].($data["alias"] ? " (".$data["alias"].")" : ""),$data["addr"],$clustermaster == $data["addr"]);
			}

			$output .= "</select></td></tr>".
				"<tr ".FS::$iMgr->tooltip("tooltip-clustermode")."><td>".$this->loc->s("Cluster-mode")."</td><td>".FS::$iMgr->select("clustermode").
				FS::$iMgr->selElmt($this->loc->s("None"),		0,	$clustermode == "0").
				FS::$iMgr->selElmt($this->loc->s("Failover"),		1,	$clustermode == "1").
				FS::$iMgr->selElmt($this->loc->s("Loadbalancing"),	2,	$clustermode == "2")."</select></td></tr>".
				"<tr ".FS::$iMgr->tooltip("tooltip-clustermaster")."><td>".$this->loc->s("Cluster-master")."</td><td>".FS::$iMgr->select("clustermaster").
				FS::$iMgr->selElmt($this->loc->s("None"),"none").$outputlist2."</select></td></tr>".
				FS::$iMgr->aeTableSubmit($name == "");
			return $output;
		}

		private function showDHCPClusterList() {
			if (!FS::$sessMgr->hasRight("servermgmt")) {
				return FS::$iMgr->printNoRight("show cluster list");
			}
			$output = $this->showTableHeadCluster();
			$clusters = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername,dhcpaddr");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!isset($clusters[$data["clustername"]]))
					$clusters[$data["clustername"]] = array();

				$clusters[$data["clustername"]][] = $data["dhcpaddr"];
			}

			foreach ($clusters as $clustername => $dhcplist) {
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
			for ($i=0;$i<count($members);$i++) {
				if (!$found) {
					$found = true;
				}
				else {
					$output .= "<br />";
				}
				$output .= $members[$i];
			}

			$output .= "</td><td>".
				FS::$iMgr->removeIcon(10,"cluster=".$clustername,array("js" => true,
					"confirmtext" => "confirm-remove-cluster",
					"confirmval" => $clustername
				)).
				"</td></tr>";
			return $output;
		}

		private function showIPForm($ip = "") {
			if (!FS::$sessMgr->hasRight("ipmgmt")) {
				return FS::$iMgr->printNoRight("show IP form");
			}
			$mac = "";
			$hostname = "";
			$comment = "";
			$reserv = false;
			$expiration = "";

			if ($ip) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip","macaddr,hostname,comment,reserv,expiration_date","ip = '".$ip."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$mac = $data["macaddr"];
					$hostname = $data["hostname"];
					$comment = $data["comment"];
					$reserv = $data["reserv"] == 't';
					$expiration = $data["expiration_date"];
					if ($expiration) {
						$expirationtmp = preg_split("#[-]#",$expiration);
						$expiration = sprintf("%s-%s-%s",$expirationtmp[2],$expirationtmp[1],$expirationtmp[0]);
					}
				}
			}
			$output = FS::$iMgr->tip("tip-reserv").FS::$iMgr->cbkForm("11")."<table>".
				FS::$iMgr->idxLines(array(
					array("IP-Addr","ip",array("value" => $ip,"type" => "idxedit", "edit" => $ip != "")),
					array("Reserv","reserv",array("value" => $reserv,"type" => "chk", "tooltip" => "tooltip-ip-reserv")),
					array("MAC-Addr","mac",array("value" => $mac)),
					array("Hostname","hostname",array("value" => $hostname, "tooltip" => "tooltip-ip-hostname")),
					array("Comment","comment",array("type" => "area", "length" => 500, "height" => "140", "value" => $comment, "tooltip" => "tooltip-ip-comment")),
					array("Expiration","expiration",array("type" => "calendar", "value" => $expiration, "tooltip" => "tooltip-ip-expiration"))
				));
			/*
			* Option groups associated to IP(if there is option groups)
			*/
			$optgroups = array();
			if ($ip) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ipv4_optgroups","optgroup","ipaddr = '".$ip."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					$optgroups[] = $data["optgroup"];
				}
			}

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_option_group","optgroup","",array("order" => "optgroup", "group" => "optgroup"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$output .= "<tr ".FS::$iMgr->tooltip("tooltip-dhcp-option-group")."><td>".$this->loc->s("option-group")."</td><td>".
						FS::$iMgr->select("ipopts",array("multi" => true));
					$found = true;
				}
				$output .= FS::$iMgr->selElmt($data["optgroup"],$data["optgroup"],in_array($data["optgroup"],$optgroups));
			}
			if ($found) $output .= "</select></td></tr>";


			$output .= FS::$iMgr->aeTableSubmit($ip == "");

			return $output;
		}

		private function showHistory($filter,$interval = 1) {
			$subnetu = preg_replace("#[.]#","_",$filter);
			if (!FS::$sessMgr->hasRight("history") &&
				!FS::$sessMgr->hasRight($subnetu."_history")) {
				return FS::$iMgr->printNoRight("show IP history");
			}
			$output = FS::$iMgr->h3($this->loc->s("title-history-since")." ".$interval." ".$this->loc->s("days"),true);
			$results = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_history","ipfree,ipactive,ipreserved,ipdistributed,collecteddate",
				"collecteddate > (NOW()- '".$interval." day'::interval) and subnet = '".$filter."'",
				array("order" => "collecteddate","ordersens" => 2));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!isset($results[$data["collecteddate"]])) $results[$data["collecteddate"]] = array();
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
			foreach ($results as $date => $values) {
				if ($labels == "") {
					// Bufferize vals
					$bauxval = (isset($values["baux"]) ? $values["baux"] : 0);
					$reservval = (isset($values["reserv"]) ? $values["reserv"] : 0);
					$availval = (isset($values["avail"]) ? $values["avail"] : 0);

					// Write js table
					$labels .= "'".$date."'";
					if ($bauxval > 0) $bauxshow = true;
					$baux .= $bauxval;
					if ($reservval > 0) $reservshow = true;
					$reserv .= $reservval;
					if ($availval > 0) $availshow = true;
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

					if ($bauxval != $lastvalues["baux"] || $reservval != $lastvalues["reserv"] ||
						$availval != $lastvalues["avail"] || $date == $lastres) {
						// Write js table
						$labels .= ",'".$date."'";
						if ($bauxval > 0) $bauxshow = true;
						$baux .= ",".$bauxval;
						if ($reservval > 0) $reservshow = true;
                	                	$reserv .= ",".$reservval;
						if ($availval > 0) $availshow = true;
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
                                       series: [ { name: '".FS::$secMgr->cleanForJS($this->loc->s("Usable"))."',
					data: [".$total."], color: 'green' },
					{ name: '".FS::$secMgr->cleanForJS($this->loc->s("not-usable"))."',
                                               data: [".$free."], color: 'black' },";
				if ($bauxshow) $js .= "{ name: '".FS::$secMgr->cleanForJS($this->loc->s("Baux"))."',
					data: [".$baux."], color: 'red' },";
				if ($reservshow) $js .= "{ name: '".FS::$secMgr->cleanForJS($this->loc->s("Reservations"))."',
					data: [".$reserv."], color: 'yellow' },";
				if ($availshow) $js .= "{ name: '".FS::$secMgr->cleanForJS($this->loc->s("Available-s"))."',
					data: [".$avail."], color: 'cyan' }";
			$js .= "]});},300);";
			FS::$iMgr->js($js);
			return $output;
		}

		private function calculateRanges($subnet,$netobj,$action=0,$startip=0,$endip=0) {
			$ipToDynDistribute = array();
			// We load all ranges and make an IP table
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_range","rangestart,rangestop","subnet = '".$subnet."'");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$start = ip2long($data["rangestart"]);
				$stop = ip2long($data["rangestop"]);
				for ($i=$start;$i<=$stop;$i++) {
					$ipToDynDistribute[$i] = 1;
				}
			}

			// If Action 1: Now we insert our new range
			if ($action == 1) {
				for ($i=ip2long($startip);$i<=ip2long($endip);$i++) {
					$ipToDynDistribute[$i] = 1;
				}
			}
			// If Action 2: we clean dynamic IP from ranges
			else if ($action == 2) {
				for ($i=ip2long($startip);$i<=ip2long($endip);$i++) {
					$ipToDynDistribute[$i] = 0;
				}
			}

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip","ip","reserv = 't' AND inet(ip) > inet('".$netobj->getFirstUsableIP()."')
				AND inet(ip) < inet('".$netobj->getLastUsableIP()."')");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$ipToDynDistribute[ip2long($data["ip"])] = 2;
			}

			$rangeList = array();
			$tmpstart = 0;
			$tmpend = 0;

			ksort($ipToDynDistribute);

			foreach ($ipToDynDistribute as $ip => $type) {
				if ($type == 1) {
					// if no start, we init it
					if ($tmpstart == 0) {
						$tmpstart = $ip;
					}
					// If no end we init it
					if ($tmpend == 0) {
						$tmpend = $ip;
					}
					// If $i is close to $tmpend, we increase range size
					if ($ip == $tmpend+1) {
						$tmpend = $ip;
					}
					// If $i isn't close to tmpend, we store the range and create new range
					else if ($ip > $tmpend+1) {
						$rangeList[] = array($tmpstart,$tmpend);
						$tmpstart = $ip;
						$tmpend = $ip;
					}
				}
				// If it's not a dynamic distributed IP we reset the buffers
				else {
					// If we have a dynamic range, then we store it
					if ($tmpstart != 0 && $tmpend != 0) {
						$rangeList[] = array($tmpstart,$tmpend);
					}
					$tmpstart = 0;
					$tmpend = 0;
				}
			}
			// If buffers are not empty, there is a last range
			if ($tmpstart != 0 && $tmpend != 0) {
				$rangeList[] = array($tmpstart,$tmpend);
			}

			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_range","subnet = '".$subnet."'");

			$count = count($rangeList);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_subnet_range","subnet,rangestart,rangestop","'".
					$subnet."','".long2ip($rangeList[$i][0])."','".long2ip($rangeList[$i][1])."'");
			}

			FS::$dbMgr->CommitTr();
		}

		private function loadSwitchList() {
			// Bufferize switch list
			$this->switchList = array();

			$query2 = FS::$dbMgr->Select("device","ip,name");
			while ($data2 = FS::$dbMgr->Fetch($query2)) {
				$this->switchList[$data2["ip"]] = $data2["name"];
			}
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->showDHCPSubnetForm();
				case 2:
					$netid = FS::$secMgr->checkAndSecuriseGetData("netid");
					if (!$netid) {
						return $this->loc->s("err-bad-datas");
					}

					return $this->showDHCPSubnetForm($netid);
				case 3:
					$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
					if (!$addr) {
						return $this->loc->s("err-bad-datas");
					}

					return $this->showDHCPSrvForm($addr);
				case 4: return $this->showDHCPClusterForm();
				case 5: return $this->showDHCPSrvForm();
				case 6:
					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}

					return $this->showDHCPClusterForm($name);
				case 7:
					$ip = FS::$secMgr->checkAndSecuriseGetData("ip");
					if (!$ip || !FS::$secMgr->isIP($ip)) {
						return $this->loc->s("err-bad-datas");
					}

					return $this->showIPForm($ip);
				case 8: return $this->showDHCPCustomOptsForm();
				case 9:
					$optname = FS::$secMgr->checkAndSecuriseGetData("optname");
					if (!$optname) {
						return $this->loc->s("err-bad-datas");
					}
					return $this->showDHCPCustomOptsForm($optname);
				case 10: return $this->showDHCPOptsForm();
				case 11:
					$optalias = FS::$secMgr->checkAndSecuriseGetData("optalias");
					if (!$optalias) {
						return $this->loc->s("err-bad-datas");
					}
					return $this->showDHCPOptsForm($optalias);
				case 12: return $this->showDHCPOptsGroupForm();
				case 13:
					$optgroup = FS::$secMgr->checkAndSecuriseGetData("optgroup");
					if (!$optgroup) {
						return $this->loc->s("err-bad-datas");
					}
					return $this->showDHCPOptsGroupForm($optgroup);
				case 14:
					$subnet = FS::$secMgr->checkAndSecuriseGetData("subnet");
					if (!$subnet) {
						return $this->loc->s("err-bad-datas");
					}
					return $this->showDHCPRangeForm($subnet);
				case 15:
					$subnet = FS::$secMgr->checkAndSecuriseGetData("subnet");
					if (!$subnet) {
						return $this->loc->s("err-bad-datas");
					}
					return $this->showDHCPImportReservForm($subnet);
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					$view = FS::$secMgr->checkAndSecurisePostData("view");
					if (!$filtr || !$view || !FS::$secMgr->isNumeric($view) || $view < 1 || $view > 3) {
						$this->log(2,"Some datas are missing when try to filter values");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					switch($view) {
						case 1: $subout = $this->showSubnetIPList($filtr); break;
						case 2: $subout = $this->showSubnetHistory($filtr); break;
						case 3: $subout = $this->showSubnetMonitoring($filtr); break;
					}
					$js = "$('#netshowcont').html('".FS::$secMgr->cleanForJS(preg_replace("[\n]","",$subout))."');";
					if ($view == 3) {
						$js .= "$('#netHCr').html('');";
					}
					
					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				case 2:
					$interval = FS::$secMgr->checkAndSecurisePostData("ival");
					if (!$interval || !FS::$secMgr->isNumeric($interval) ||
						$interval < 1) {
						FS::$iMgr->ajaxEchoErrorNC("err-invalid-req");
						$this->log(2,"Some datas are missing when trying to find obsolete datas");
						return;
					}

					$output = "";
					$obsoletes = array();
					$found = false;
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","ip,macaddr,hostname","distributed = 3");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$ltime = FS::$dbMgr->GetOneData("node","time_last","mac = '".$data["macaddr"]."'",array("order" => "time_last","ordersens" => 1,"limit" => 1));
						if ($ltime) {
							if (strtotime($ltime) < strtotime("-".$interval." day",strtotime(date("y-m-d H:i:s")))) {
								$obsoletes[$data["ip"]] = $data["ip"]." - ".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("search")."&s=".$data["macaddr"], $data["macaddr"]);
								$obsoletes[$data["ip"]] .= " (".$this->loc->s("last-view")." ".date("d/m/y H:i",strtotime($ltime)).")";
								$obsoletes[$data["ip"]] .= "<br />";
								if (!$found) $found = true;
							}
						}
					}
					if ($found) {
						$output = FS::$iMgr->h4("title-old-record");
						$logbuffer = "";
						foreach ($obsoletes as $key => $value) {
							$logbuffer .= $value;
							$output .= $value;
						}

						FS::$iMgr->ajaxEchoOK("Done","$('#obsres').html('".FS::$secMgr->cleanForJS($output)."');");
						$this->log(0,"User find obsolete datas");
					}
					else {
						FS::$iMgr->ajaxEchoOK("Done","$('#obsres').html('".FS::$secMgr->cleanForJS(FS::$iMgr->printDebug($this->loc->s("no-old-record")))."');");
					}

					return;
				// Monitor DHCP subnet
				case 3:
					if (!FS::$sessMgr->hasRight("subnetmon")) {
						FS::$iMgr->echoNoRights("monitor a subnet");
						return;
					}
					$filtr = FS::$secMgr->checkAndSecuriseGetData("f");
					$warn = FS::$secMgr->checkAndSecurisePostData("wlimit");
					$crit = FS::$secMgr->checkAndSecurisePostData("climit");
					$maxage = FS::$secMgr->checkAndSecurisePostData("maxage");
					$contact = FS::$secMgr->checkAndSecurisePostData("contact");
					$enmon = FS::$secMgr->checkAndSecurisePostData("enmon");
					$eniphistory = FS::$secMgr->checkAndSecurisePostData("eniphistory");
					if (!$filtr || !FS::$secMgr->isIP($filtr) || !$warn || !FS::$secMgr->isNumeric($warn) ||
						$warn < 0 || $warn > 100|| !$crit || !FS::$secMgr->isNumeric($crit) || $crit < 0 || $crit > 100 ||
						!FS::$secMgr->isNumeric($maxage) || $maxage < 0 || !$contact || !FS::$secMgr->isMail($contact)) {
						$this->log(2,"Some datas are missing when try to monitor subnet");
						FS::$iMgr->ajaxEchoError("err-miss-data");
						return;
					}

					// Also here because we have subnet
					$subnetu = preg_replace("#[.]#","_",$filtr);
					if (!FS::$sessMgr->hasRight($subnetu."_subnetmon")) {
						FS::$iMgr->echoNoRights("monitor a subnet");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid","netid = '".$filtr."'");
					if (!$exist) {
						$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid","netid = '".$filtr."'");
					}

					if (!$exist) {
						$this->log(2,"User try to monitor inexistant subnet '".$filtr."'");
						FS::$iMgr->ajaxEchoError("err-bad-subnet");
						return;
					}

					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_monitoring","subnet = '".$filtr."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_monitoring","subnet,warnuse,crituse,contact,enmon,maxage,eniphistory","'".$filtr."','".$warn."','".$crit."','".$contact."','".
						($enmon == "on" ? "1" : "0")."','".$maxage."','".($eniphistory == "on" ? "t" : "f")."'");
					FS::$dbMgr->CommitTr();

					$this->log(0,"User ".($enmon == "on" ? "enable" : "disable")." monitoring for subnet '".$filtr."'");
					FS::$iMgr->ajaxEchoOK("modif-record");
					return;
				case 4:
					$filter = FS::$secMgr->checkAndSecurisePostData("filter");
					$daterange = FS::$secMgr->checkAndSecurisePostData("daterange");
					if (!$filter || !$daterange || !FS::$secMgr->isNumeric($daterange) || $daterange < 1) {
						echo FS::$iMgr->printError("bad-datas");
						return;
					}
					echo $this->showHistory($filter,$daterange);
					return;
				// Add/Edit DHCP server
				case 5:
					if (!FS::$sessMgr->hasRight("servermgmt")) {
						FS::$iMgr->echoNoRights("modify server");
						return;
					}

					$saddr = FS::$secMgr->checkAndSecurisePostData("addr");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					$slogin = FS::$secMgr->checkAndSecurisePostData("sshuser");
					$spwd = FS::$secMgr->checkAndSecurisePostData("sshpwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("sshpwd2");
					$clusteraddr = FS::$secMgr->checkAndSecurisePostData("dhcpdclusterip");
					$dhcpdpath = FS::$secMgr->checkAndSecurisePostData("dhcpdpath");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$dhcptype = FS::$secMgr->checkAndSecurisePostData("dhcptype");
					$desc = FS::$secMgr->checkAndSecurisePostData("description");
					$leasepath = FS::$secMgr->checkAndSecurisePostData("leasepath");
					$reservconfpath = FS::$secMgr->checkAndSecurisePostData("reservconfpath");
					$subnetconfpath = FS::$secMgr->checkAndSecurisePostData("subnetconfpath");

					if (!$saddr || !$slogin || !$spwd || !$spwd2 ||
						!$dhcpdpath || !$leasepath || !$reservconfpath || !$subnetconfpath ||
						!$alias || $desc && !FS::$secMgr->isSentence($desc) ||
						!$dhcptype || !FS::$secMgr->isNumeric($dhcptype) ||
						$edit && $edit != 1
					) {
						$this->log(2,"Some datas are invalid or wrong for add server");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					if ($clusteraddr && !FS::$secMgr->isIP($clusteraddr)) {
						FS::$iMgr->ajaxEchoErrorNC("err-dhcpserver-invalid-clusteraddr");
						$this->log(1,"Add/edit DHCP server: invalid cluster address");
						return;
					}

					if (!FS::$secMgr->isAlphaNumeric($alias)) {
						FS::$iMgr->ajaxEchoErrorNC("err-dhcpserver-invalid-alias");
						$this->log(1,"Add/edit DHCP server: invalid alias");
						return;
					}

					if (!FS::$secMgr->isPath($dhcpdpath)) {
						FS::$iMgr->ajaxEchoErrorNC("err-dhcpserver-dhcpdpath");
						$this->log(1,"Add/edit DHCP server: invalid dhcpd path");
						return;
					}

					if (!FS::$secMgr->isPath($leasepath)) {
						FS::$iMgr->ajaxEchoErrorNC("err-dhcpserver-leasepath");
						$this->log(1,"Add/edit DHCP server: invalid lease path");
						return;
					}

					if (!FS::$secMgr->isPath($reservconfpath)) {
						FS::$iMgr->ajaxEchoErrorNC("err-dhcpserver-reservconf");
						$this->log(1,"Add/edit DHCP server: invalid reservconf path");
						return;
					}

					if (!FS::$secMgr->isPath($subnetconfpath)) {
						FS::$iMgr->ajaxEchoErrorNC("err-dhcpserver-subnetconf");
						$this->log(1,"Add/edit DHCP server: invalid subnetconf path");
						return;
					}

					if ($spwd != $spwd2) {
						FS::$iMgr->ajaxEchoErrorNC("err-pwd-not-match");
						$this->log(1,"Add/edit DHCP server: passwords don't match");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_servers","sshuser","addr ='".$saddr."'");
					if ($edit) {
						if (!$exist) {
							FS::$iMgr->ajaxEchoError("err-not-exists");
							$this->log(2,"Add/edit DHCP server: server '".$saddr."' doesn't exist");
							return;
						}
					}
					else {
						if ($exist) {
							$this->log(1,"Add/edit DHCP server: server '".$saddr."' already exists");
							FS::$iMgr->ajaxEchoErrorNC("err-already-exists");
							return;
						}

					}
					$ssh = new SSH($saddr,22);

                    if (!$ssh->Connect()) {
						$this->log(1,"Add/edit DHCP server: ssh connection failed to '".$saddr."'");
						FS::$iMgr->ajaxEchoErrorNC("err-ssh-conn-failed");
                        return;
                    }

					if (!$ssh->Authenticate($slogin,$spwd)) {
						$this->log(1,"Add/edit DHCP server: ssh connection failed for '".$slogin."'@'".$saddr."'");
						FS::$iMgr->ajaxEchoErrorNC("err-ssh-auth-failed");
                        return;
                    }

					/*
					* We try to read files
					*/
					if ($ssh->execCmd("if [ -r ".$dhcpdpath." ]; then; echo 0; else; echo 1; fi;") != 0) {
						$this->log(1,"Add/edit DHCP server: Unable to read file '".$dhcpdpath."' on '".$saddr."'");
						FS::$iMgr->ajaxEchoErrorNC("err-unable-read")." '".$dhcpdpath."'";
                        return;
					}

					// dhcpd.leases
                    if ($ssh->execCmd("if [ -r ".$leasepath." ]; then; echo 0; else; echo 1; fi;") != 0) {
                        $this->logt(1,"Add/edit DHCP server: Unable to read file '".$leasepath."' on '".$saddr."'");
						FS::$iMgr->ajaxEchoErrorNC("err-unable-read")." '".$leasepath."'";
						return;
                    }

					if ($reservconfpath && strlen($reservconfpath) > 0) {
						if ($ssh->execCmd("if [ -r ".$reservconfpath." -a -w ".$reservconfpath." ]; then; echo 0; else; echo 1; fi;")!= 0) {
							$this->log(1,"Add/edit DHCP server: Unable to read file '".$reservconfpath."' on '".$saddr."'");
							FS::$iMgr->ajaxEchoErrorNC("err-unable-read")." '".$reservconfpath."'";
							return;
                	     }
					}

					if ($subnetconfpath && strlen($subnetconfpath) > 0) {
						if ($ssh->execCmd("if [ -r ".$subnetconfpath." -a -w ".$subnetconfpath." ]; then; echo 0; else; echo 1; fi;") != 0) {
							$this->log(1,"Add/edit DHCP server: Unable to read file '".$subnetconfpath."' on '".$saddr."'");
							FS::$iMgr->ajaxEchoErrorNC("err-unable-read")." '".$subnetconfpath."'";
							return;
                	    }
					}

					$osname = preg_replace("#[\n\r]#","",$ssh->execCmd("uname -srm"));

					FS::$dbMgr->BeginTr();
					if ($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_servers",
							"addr = '".$saddr."'");
					}

					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_servers",
						"addr,alias,description,sshuser,sshpwd,dhcpdpath,leasespath,reservconfpath,subnetconfpath,dhcptype,osname,clusteraddr",
						"'".$saddr."','".$alias."','".$desc.
						"','".$slogin."','".$spwd."','".$dhcpdpath."','".
						$leasepath."','".$reservconfpath."','".
						$subnetconfpath."','".$dhcptype."','".
						$osname."','".$clusteraddr."'");
					FS::$dbMgr->CommitTr();

					$this->log(0,"Add/edit DHCP server: ".
						($edit ? "Edited" : "Added")." DHCP server '".
						$saddr."' (login: '".$slogin."')");

					FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);
					return;
				// Delete DHCP Server
				case 6:
					if (!FS::$sessMgr->hasRight("servermgmt")) {
						FS::$iMgr->echoNoRights("remove DHCP server");
						return;
					}

					$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
					if (!$addr) {
						$this->log(2,"Delete DHCP server: No DHCP server specified");
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}

					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_servers","sshuser","addr = '".$addr."'")) {
						$this->log(2,"Delete DHCP server: Unknown DHCP server specified");
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}

					if ($clustername = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_cluster_options","clustername","master = '".$addr."'")) {
						$this->log(1,"Delete DHCP server: DHCP server is master of cluster '".$clustername."', cannot remove it");
						FS::$iMgr->ajaxEchoError($this->loc->s("err-remove-dhcpserver-master").$clustername.
							$this->loc->s("err-remove-dhcpserver-master2"),"",true);
						return;
					}

					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_ip_history","server = '".$addr."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_ip_cache","server = '".$addr."'");
					// Later
					//FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","server = '".$addr."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_cluster","dhcpaddr = '".$addr."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_servers","addr = '".$addr."'");
					FS::$dbMgr->CommitTr();

					FS::$iMgr->ajaxEchoOK("Done");
					FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);
					$this->log(0,"Delete DHCP server: server '".$addr."' removed");
                                        return;
				// Add DHCP subnet
				case 7:
					if (!FS::$sessMgr->hasRight("subnetmgmt")) {
						FS::$iMgr->echoNoRights("modify subnet");
						return;
					}

					$netid = FS::$secMgr->checkAndSecurisePostData("netid");
					$netmask = FS::$secMgr->checkAndSecurisePostData("netmask");
					$netidv6 = FS::$secMgr->checkAndSecurisePostData("netidv6");
					$prefixlen = FS::$secMgr->checkAndSecurisePostData("prefixlen");
					$vlanid = FS::$secMgr->checkAndSecurisePostData("vlanid");
					$desc = FS::$secMgr->checkAndSecurisePostData("desc");
					$shortname = FS::$secMgr->checkAndSecurisePostData("shortname");
					$subnetclusters = FS::$secMgr->checkAndSecurisePostData("subnetclusters");
					$router = FS::$secMgr->checkAndSecurisePostData("router");
					$dns1 = FS::$secMgr->checkAndSecurisePostData("dns1");
					$dns2 = FS::$secMgr->checkAndSecurisePostData("dns2");
					$domainname = FS::$secMgr->checkAndSecurisePostData("domainname");
					$mleasetime = FS::$secMgr->checkAndSecurisePostData("mleasetime");
					$dleasetime = FS::$secMgr->checkAndSecurisePostData("dleasetime");
					$dhcpopts = FS::$secMgr->checkAndSecurisePostData("dopts");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if (!$netid || !FS::$secMgr->isIP($netid) || !$netmask || !FS::$secMgr->isMaskAddr($netmask) || $vlanid === NULL || !FS::$secMgr->isNumeric($vlanid) ||
						$vlanid < 0 || $vlanid > 4096 || !$desc || !$shortname || preg_match("# #",$shortname) || $subnetclusters && !is_array($subnetclusters) ||
						$router && !FS::$secMgr->isIP($router) || $dns1 && !FS::$secMgr->isIP($dns1) || $dns2 && (!$dns1 || !FS::$secMgr->isIP($dns2)) ||
						$domainname && !FS::$secMgr->isDNSName($domainname) || $mleasetime && (!FS::$secMgr->isNumeric($mleasetime) || $mleasetime < 60) ||
						$dleasetime && (!FS::$secMgr->isNumeric($dleasetime) || $dleasetime < 30) ||
						$edit && $edit != 1) {
						$this->log(2,"Add/Edit subnet: Bad datas");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					if (!$dleasetime) {
						$dleasetime = 0;
					}

					if (!$mleasetime) {
						$mleasetime = 0;
					}

					// @TODO check if IPv6 network is used elsewhere
					if ($prefixlen && (!$netidv6 ||	!FS::$secMgr->isNumeric($prefixlen)) ||
						$netidv6 &&
							(!$prefixlen || !FS::$secMgr->isIPv6NetworkAddr($netidv6) ||
							$prefixlen < 1 || $prefixlen > 126)) {
							$this->log(2,"Add/Edit subnet: subnet '".$netid."': invalid IPv6 infos");
							FS::$iMgr->ajaxEchoError("err-subnet-bad-ipv6-infos");
							return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netmask","netid = '".$netid."'");
					if ($edit) {
						if (!$exist) {
							$this->log(2,"Add/Edit subnet: subnet '".$netid."' doesn't exist");
							FS::$iMgr->ajaxEchoError("err-subnet-not-exists");
							return;
						}
					}
					else {
						if ($exist) {
							$this->log(1,"Add/Edit subnet: subnet '".$netid."' already exists");
							FS::$iMgr->ajaxEchoErrorNC("err-subnet-already-exists");
							return;
						}
					}

					if ($dleasetime && $mleasetime && $dleasetime > $mleasetime) {
						$this->log(1,"Add/Edit subnet: default lease time > max lease time");
						FS::$iMgr->ajaxEchoErrorNC("err-dlease-sup-mlease");
						return;
					}

					if ($router) {
						$netobj = new FSNetwork();
						$netobj->setNetAddr($netid);
						$netobj->setNetMask($netmask);
						if (!$netobj->isUsableIP($router)) {
							$this->log(1,"Add/Edit subnet: router '".$router."' not in subnet '".$netid."/".$netmask."'");
							FS::$iMgr->ajaxEchoErrorNC("err-router-not-in-subnet");
							return;
						}
					}

					if ($vlanid != 0 && FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid","netid != '".$netid."' AND vlanid = '".$vlanid."'")) {
						$this->log(1,"Add/Edit subnet: vlan '".$vlanid."' already used");
						FS::$iMgr->ajaxEchoErrorNC("err-vlan-already-used");
						return;
					}

					if ($subnetclusters) {
						$count = count($subnetclusters);
						for ($i=0;$i<$count;$i++) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","clustername = '".$subnetclusters[$i]."'")) {
								$this->log(2,"Add/Edit subnet: cluster '".$subnetclusters[$i]."' doesn't exist");
								FS::$iMgr->ajaxEchoErrorNC("err-cluster-not-exists");
								return;
							}
						}
						if (!$router || !$dns1 || !$domainname) {
							$this->log(1,"Add/Edit subnet: distribute needs routeur, dns and domain name");
							FS::$iMgr->ajaxEchoErrorNC("err-distrib-subnet-need-infos");
							return;
						}
					}

					if ($dhcpopts) {
						$count = count($dhcpopts);
						for ($i=0;$i<$count;$i++) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_option_group","optgroup","optgroup = '".$dhcpopts[$i]."'",array("group" => "optgroup"))) {
								$this->log(2,"Add/Edit subnet: option group '".$dhcpopts[$i]."' doesn't exist");
								FS::$iMgr->ajaxEchoErrorNC("err-dhcp-opts-group-not-exists");
								return;
							}
						}
					}

					FS::$dbMgr->BeginTr();
					if ($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid = '".$netid."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_v6_declared","netidv4 = '".$netid."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","subnet = '".$netid."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_optgroups","netid = '".$netid."'");
						FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_network","netid = '".$netid."'");
					}
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask,vlanid,subnet_short_name,subnet_desc,router,dns1,dns2,domainname,dleasetime,mleasetime",
						"'".$netid."','".$netmask."','".$vlanid."','".$shortname."','".$desc."','".$router."','".$dns1."','".$dns2."','".$domainname."','".$dleasetime."','".$mleasetime."'");

					if ($netidv6 && $prefixlen) {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_subnet_v6_declared","netid,prefixlen,netidv4",
						"'".$netidv6."','".$prefixlen."','".$netid."'");
					}

					if ($subnetclusters) {
						$count = count($subnetclusters);
						for ($i=0;$i<$count;$i++) {
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","clustername,subnet","'".$subnetclusters[$i]."','".$netid."'");
						}
					}

					if ($dhcpopts) {
						$count = count($dhcpopts);
						for ($i=0;$i<$count;$i++) {
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_subnet_optgroups","netid,optgroup","'".$netid."','".$dhcpopts[$i]."'");
						}
					}

					FS::$dbMgr->CommitTr();

					$js = "";
					$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid");
					// One record we must create table if it's not and edit
					if ($count == 1 && !$edit) {
						$jscontent = $this->showDeclaredNetTableHead()."</table>".FS::$iMgr->jsSortTable("declsubnettable");
						$js .= "$('#declsubnets').html('".FS::$secMgr->cleanForJS($jscontent).
							"'); $('#declsubnets').show('slow');";
					}

					if ($edit) {
						$js = "hideAndRemove('#ds".FS::$iMgr->formatHTMLId($netid)."tr'); setTimeout(function(){";
					}
					$jscontent = $this->tableDeclaredNetEntry($netid,$netmask,$desc,$shortname,
						$vlanid,$netidv6,$prefixlen);
					$js .= "$('".FS::$secMgr->cleanForJS($jscontent)."').insertAfter('#declsubnethead');";
					if ($edit) {
						$js .= "},1200);";
					}

					if ($edit) {
						$this->log(0,"Add/Edit subnet: subnet '".$netid."' edited");
					}
					else {
						$this->log(0,"Add/Edit subnet: subnet '".$netid."' added");
					}

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Remove DHCP subnet
				case 8:
					if (!FS::$sessMgr->hasRight("subnetmgmt")) {
						FS::$iMgr->echoNoRights("remove a DHCP subnet");
						return;
					}

					$netid = FS::$secMgr->checkAndSecuriseGetData("netid");
					if (!$netid || !FS::$secMgr->isIP($netid)) {
						$this->log(2,"Remove subnet: Bad datas given");
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}

					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid = '".$netid."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","subnet = '".$netid."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_optgroups","netid = '".$netid."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_range","subnet = '".$netid."'");
					FS::$dbMgr->CommitTr();

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "declsubnets",
						"sqltable" => "dhcp_subnet_v4_declared",
						"sqlattrid" => "netid",
						"trpfx" => "ds"));
					$js = $tMgr->removeLine($netid);

					$this->log(0,"Remove subnet: subnet '".$netid."' removed");
					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Add/Edit cluster
				case 9:
					if (!FS::$sessMgr->hasRight("servermgmt")) {
						FS::$iMgr->echoNoRights("modify a DHCP cluster");
						return;
					}

					$cname = FS::$secMgr->checkAndSecurisePostData("cname");
					$cmembers = FS::$secMgr->checkAndSecurisePostData("clustermembers");
					$cmode = FS::$secMgr->checkAndSecurisePostData("clustermode");
					$cmaster = FS::$secMgr->checkAndSecurisePostData("clustermaster");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if (!$cname || !$cmembers || !is_array($cmembers) || $cmode === NULL || $cmode < 0 ||
						$cmode > 2 || !$cmaster || $edit && $edit != 1) {
						if (!$cmembers || !is_array($cmembers)) {
							$this->log(1,"Add/Edit cluster: cluster need some members");
							FS::$iMgr->ajaxEchoErrorNC("err-cluster-need-members");
						}
						else {
							$this->log(2,"Add/Edit cluster: bad datas given");
							FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						}
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","clustername = '".$cname."'");
					if ($edit) {
						if (!$exist) {
							$this->log(2,"Add/Edit cluster: cluster '".$cname."' doesn't exist");
							FS::$iMgr->ajaxEchoError("err-cluster-not-exists");
							return;
						}
					}
					else {
						if ($exist) {
							$this->log(1,"Add/Edit cluster: cluster '".$cname."' already exists");
							FS::$iMgr->ajaxEchoErrorNC("err-cluster-already-exists");
							return;
						}
					}

					$count = count($cmembers);

					/*
					* ISC DHCP cluster require only 2 DHCP
					*/
					if (($cmode == 1 || $cmode == 2) && $count != 2) {
						$this->log(1,"Add/Edit cluster: selected mode require two members");
						FS::$iMgr->ajaxEchoErrorNC("err-clustermode-require-two");
						return;
					}

					for ($i=0;$i<$count;$i++) {
						/*
						* This variable is called for next test
						*/
						$dhcptype = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_servers","dhcptype","addr = '".$cmembers[$i]."'");
						if ($dhcptype === NULL) {
							$this->log(2,"Add/Edit cluster: dhcp server doesn't exist");
							FS::$iMgr->ajaxEchoErrorNC("err-dhcpserver-not-exists");
							return;
						}

						/*
						* Check if our cluster mode is compatible with DHCP list
						*/
						if (($cmode == 1 || $cmode == 2) && $dhcptype != 1) {
							$this->log(1,"Add/Edit cluster: selected mode require isc dhcpd");
							FS::$iMgr->ajaxEchoErrorNC("err-clustermode-require-iscdhcp");
							return;
						}
					}

					if ($cmaster) {
						/*
						* For ISC dhcpd cluster, master can't be none
						*/
						if (($cmode == 1 || $cmode == 2) && $cmaster == "none") {
							$this->log(1,"Add/Edit cluster: selected mode require a master");
							FS::$iMgr->ajaxEchoErrorNC("err-clustermaster-iscdhcp");
							return;
						}
						/*
						* Cluster master must be in cluster members
						*/
						if ($cmaster != "none" && !in_array($cmaster,$cmembers)) {
							$this->log(1,"Add/Edit cluster: selected master is not in members");
							FS::$iMgr->ajaxEchoErrorNC("err-clustermaster-not-in-members");
							return;
						}
					}

					FS::$dbMgr->BeginTr();
					if ($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername = '".$cname."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_cluster_options","clustername = '".$cname."'");
					}
					for ($i=0;$i<$count;$i++)
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername,dhcpaddr","'".$cname."','".$cmembers[$i]."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_cluster_options","clustername,clustermode,master","'".$cname."','".$cmode."','".$cmaster."'");
					FS::$dbMgr->CommitTr();

					$js = "";
					$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername");
					if ($count == 1 && !$edit) {
						$jscontent = $this->showTableHeadCluster()."</table>".FS::$iMgr->jsSortTable("clustertable");
						$js .= "$('#clusterdiv').html('".FS::$secMgr->cleanForJS($jscontent)."'); $('#clusterdiv').show('slow');";
					}
					if ($edit) {
						$js .= "hideAndRemove('#cl".FS::$iMgr->formatHTMLId($cname)."tr'); setTimeout(function() {";
					}
					$jscontent = $this->showDHCPClusterTableEntry($cname,$cmembers);
					$js .= "$('".FS::$secMgr->cleanForJS($jscontent)."').insertAfter('#clusterth');";
					if ($edit) {
						$js .= "},1200);";
					}

					if ($edit) {
						$this->log(0,"Add/Edit cluster: edited cluster '".$cname."'");
					}
					else {
						$this->log(0,"Add/Edit cluster: added cluster '".$cname."'");
					}

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Remove cluster
				case 10:
					if (!FS::$sessMgr->hasRight("servermgmt")) {
						FS::$iMgr->echoNoRights("remove a DHCP cluster");
						return;
					}

					$cname = FS::$secMgr->checkAndSecuriseGetData("cluster");
					if (!$cname) {
						$this->log(2,"Remove cluster: bad datas given");
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}

					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername","clustername = '".$cname."'")) {
						$this->log(2,"Remove cluster: cluster '".$cname."' doesn't exist");
						FS::$iMgr->ajaxEchoError("err-cluster-not-exists");
						return;
					}

					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_cluster","clustername = '".$cname."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_cluster","clustername = '".$cname."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_cluster_options","clustername = '".$cname."'");
					FS::$dbMgr->CommitTr();

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "clustertable",
						"sqltable" => "dhcp_cluster",
						"sqlattrid" => "clustername",
						"trpfx" => "cl"
					));
					$js = $tMgr->removeLine($cname);

					$this->log(0,"Remove cluster: cluster '".$cname."' removed");
					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Add/Edit IP informations
				case 11:
					if (!FS::$sessMgr->hasRight("ipmgmt")) {
						FS::$iMgr->echoNoRights("modify IP informations");
						return;
					}

					$ip = FS::$secMgr->checkAndSecurisePostData("ip");
					$mac = FS::$secMgr->checkAndSecurisePostData("mac");
					$hostname = FS::$secMgr->checkAndSecurisePostData("hostname");
					$reserv = FS::$secMgr->checkAndSecurisePostData("reserv");
					$comment = FS::$secMgr->checkAndSecurisePostData("comment");
					$ipopts = FS::$secMgr->checkAndSecurisePostData("ipopts");
					$expiration = FS::$secMgr->checkAndSecurisePostData("expiration");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if (!$ip || !FS::$secMgr->isIP($ip) ||
						$reserv && $reserv != "on" || $edit && $edit != 1) {
						$this->log(2,"Edit IP informations: bad datas given");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					if ($mac && !FS::$secMgr->isMacAddr($mac)) {
						$this->log(2,sprintf("Edit IP informations: bad mac '%s' given",$mac));
						FS::$iMgr->ajaxEchoErrorNC(sprintf($this->loc->s("err-bad-mac-addr"),$mac),"",true);
						return;
					}

					if ($hostname && !FS::$secMgr->isHostname($hostname)) {
						$this->log(2,sprintf("Edit IP informations: bad hostname '%s' given",$hostname));
						FS::$iMgr->ajaxEchoErrorNC(sprintf($this->loc->s("err-bad-hostname"),$hostname),"",true);
						return;
					}

					if ($comment && strlen($comment) > 500) {
						$this->log(2,sprintf("Edit IP informations: comment length too long",$comment));
						FS::$iMgr->ajaxEchoErrorNC(sprintf($this->loc->s("err-comment-too-long"),$comment),"",true);
						return;
					}

					// Format MAC addr
					$mac = strtolower(preg_replace("#[-]#",":",$mac));

					// Reservations needs MAC & hostname
					if ($reserv == "on" && (!$mac || !$hostname)) {
						$this->log(1,"Edit IP informations: reservation need mac address and hostname");
						FS::$iMgr->ajaxEchoErrorNC("err-reserv-need-fields");
						return;
					}

					// Check if modified IP is in a declared subnet
					$found = false;
					$netinfos = array();
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask");
					while (($data = FS::$dbMgr->Fetch($query)) && $found == false) {
						$netobj = new FSNetwork();
						$netobj->setNetAddr($data["netid"]);
						$netobj->setNetMask($data["netmask"]);
						if ($netobj->isUsableIP($ip)) {
							$found = true;
							$netinfos = array($data["netid"],$data["netmask"]);
						}
					}

					if (!$found) {
						$this->log(2,"Edit IP informations: IP isn't in a declared subnet");
						FS::$iMgr->ajaxEchoError("err-ip-not-in-declared-subnets");
						return;
					}

					$subnetu = preg_replace("#[.]#","_",$netinfos[0]);
					// Another test here because we have subnet
					if (!FS::$sessMgr->hasRight("ipmgmt") &&
						!FS::$sessMgr->hasRight($subnetu."_ipmgmt")) {
						FS::$iMgr->echoNoRights("modify IP informations);
						return;
					}

					$netobj = new FSNetwork();
					$netobj->setNetAddr($netinfos[0]);
					$netobj->setNetMask($netinfos[1]);

					/*
					 *  Check if MAC addr is not registered on another IP in the same subnet
					 * MAC 00:00:00:00:00:00 can be used to block some IPs on DHCP
					 */
					if ($mac && $mac != "00:00:00:00:00:00") {
						$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip","ip","macaddr = '".$mac."' AND ip != '".$ip."'");
						while($data = FS::$dbMgr->Fetch($query)) {
							if($netobj->isUsableIP($data["ip"])) {
								$this->log(1,"Edit IP informations: mac addr '".$mac."' already used in this subnet");
								FS::$iMgr->ajaxEchoErrorNC("err-mac-already-used-in-subnet");
								return;
							}
						}
					}

					if ($hostname) {
						$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_ip","ip","hostname = '".$hostname."' AND ip != '".$ip."'");
						if ($exist) {
							$this->log(1,"Edit IP informations: hostname '".$hostname."' already used");
							FS::$iMgr->ajaxEchoErrorNC("err-hostname-already-defined");
							return;
						}
					}

					// change comment form
					if ($comment) {
						$comment = preg_replace("#[\n\r]+#","\r",$comment);
					}

					if ($ipopts) {
						$count = count($ipopts);
						for ($i=0;$i<$count;$i++) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_option_group","optgroup","optgroup = '".$ipopts[$i]."'",array("group" => "optgroup"))) {
								$this->log(2,"Edit IP informations: dhcp option group '".$opopts[$i]."' doesn't exist");
								FS::$iMgr->ajaxEchoErrorNC("err-dhcp-opts-group-not-exists");
								return;
							}
						}
					}

					if ($expiration) {
						if (!preg_match("#^[0-9]{2}-[0-9]{2}-[0-9]{4}$#",$expiration)) {
							FS::$iMgr->ajaxEchoErrorNC("err-expiration-date-invalid");
							return;
						}

						$expirationtmp = preg_split("#[-]#",$expiration);

						$expirationtime = strtotime(sprintf("%s-%s-%s",$expirationtmp[0],$expirationtmp[1],$expirationtmp[2]));
						$nowtime = strtotime(sprintf("%s-%s-%s",date("d"),date("m"),date("Y")));
						if ($expirationtime < $nowtime) {
							FS::$iMgr->ajaxEchoErrorNC("err-expiration-date-must-be-today-or-more");
							return;
						}

						$expiration = sprintf("%s/%s/%s",$expirationtmp[1],$expirationtmp[0],$expirationtmp[2]);
					}

					FS::$dbMgr->BeginTr();

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_ip","ip = '".$ip."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_ipv4_optgroups","ipaddr = '".$ip."'");
					if ($mac || $hostname || $comment || $reserv) {
						if ($expiration) {
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_ip","ip,macaddr,hostname,comment,reserv,expiration_date","'".$ip."','".$mac."','".$hostname."','".
								$comment."','".($reserv ? 't':'f')."','".$expiration."'");
						}
						else {
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_ip","ip,macaddr,hostname,comment,reserv","'".$ip."','".$mac."','".$hostname."','".
								$comment."','".($reserv ? 't':'f')."'");
						}
					}
					if ($ipopts) {
						$count = count($ipopts);
						for ($i=0;$i<$count;$i++) {
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_ipv4_optgroups","ipaddr,optgroup","'".$ip."','".$ipopts[$i]."'");
						}
					}

					FS::$dbMgr->CommitTr();

					$this->calculateRanges($netinfos[0],$netobj);

					$this->log(0,"Edit IP informations: informations edited for IP '".$ip."'");

					FS::$iMgr->ajaxEchoOK("Done",(new dhcpIP())->genIPLine($ip,true));
					return;
				// Add/Edit Custom Option
				case 12:
					if (!FS::$sessMgr->hasRight("optionsmgmt")) {
						FS::$iMgr->echoNoRights("modify custom option");
						return;
					}
					$optname = FS::$secMgr->checkAndSecurisePostData("optname");
					$optcode = FS::$secMgr->checkAndSecurisePostData("optcode");
					$opttype = FS::$secMgr->checkAndSecurisePostData("opttype");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if (!$optname || !FS::$secMgr->isHostname($optname) || !$optcode || !FS::$secMgr->isNumeric($optcode) ||
						!$opttype || $edit && $edit != 1) {
						$this->log(2,"Add/Edit custom option: bad datas given");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					if ($optcode < 126) {
						$this->log(1,"Add/Edit custom option: option code '".$optcode."' protected");
						FS::$iMgr->ajaxEchoErrorNC("err-option-code-protected");
						return;
					}

					if ($optcode > 255) {
						$this->log(1,"Add/Edit custom option: option code '".$optcode."' is to high");
						FS::$iMgr->ajaxEchoErrorNC("err-option-code-lower-255");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_custom_option","protectrm",
						"optname = '".$optname."'");
					if ($edit) {
						if (!$exist) {
							$this->log(2,"Add/Edit custom option: option '".$optname."' doesn't exist");
							FS::$iMgr->ajaxEchoError("err-custom-option-not-exists");
							return;
						}
					}
					else {
						if ($exist) {
							$this->log(1,"Add/Edit custom option: option '".$optname."' already exists");
							FS::$iMgr->ajaxEchoErrorNC("err-custom-option-already-exists");
							return;
						}
					}

					FS::$dbMgr->BeginTr();
					if ($edit)
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_custom_option","optname = '".$optname."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_custom_option","optname,optcode,opttype",
						"'".$optname."','".$optcode."','".$opttype."'");
					FS::$dbMgr->CommitTr();

					if ($edit) {
						$this->log(0,"Add/Edit custom option: option '".$optname."' edited");
					}
					else {
						$this->log(0,"Add/Edit custom option: option '".$optname."' added");
					}

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "customoptslist",
						"sqltable" => "dhcp_custom_option",
						"sqlattrid" => "optname",
						"tableid" => "dhcpopttable",
						"firstlineid" => "dhcpoptftr",
						"attrlist" => array(array("option-name","optname",""), array("option-code","optcode",""),
							array("option-type","opttype","s",array("boolean" => "boolean",
								"uint8" => "uinteger-8", "uint16" => "uinteger-16", "uint32" => "uinteger-32",
								"int8" => "integer-8", "int16" => "integer-16", "int32" => "integer-32",
								"ip" => "IP-Addr", "text" => "text"))),
						"sorted" => true,
						"odivnb" => 9,
						"odivlink" => "optname=",
						"rmcol" => true,
						"rmlink" => "optname",
						"rmactid" => 13,
						"rmconfirm" => "confirm-remove-custom-option",
						"trpfx" => "dco"
					));
					$js = $tMgr->addLine($optname,$edit);

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Delete Custom Option
				case 13:
					if (!FS::$sessMgr->hasRight("optionsmgmt")) {
						FS::$iMgr->echoNoRights("remove custom option");
						return;
					}
					$optname = FS::$secMgr->checkAndSecuriseGetData("optname");
					if (!$optname || !FS::$secMgr->isHostname($optname)) {
						$this->log(2,"Delete custom option: bad datas given");
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}

					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_custom_option","optname",
						"optname = '".$optname."'")) {
						$this->log(2,"Delete custom option: custom option '".$optname."' doesn't exist");
						FS::$iMgr->ajaxEchoError("err-custom-option-not-exists");
						return;
					}
					// We remove option groups link with subnet if the only option is current option
					$toRemove = array();
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_option_group","COUNT(optalias) as ct,optgroup",
						"optgroup IN(SELECT optgroup FROM ".PGDbConfig::getDbPrefix()."dhcp_option_group WHERE optalias IN (SELECT optalias FROM ".
						PGDbConfig::getDbPrefix()."dhcp_option WHERE optname = '".$optname."'))",
						array("group" => "optgroup"));
					while ($data = FS::$dbMgr->Fetch($query)) {
						if ($data["ct"] == 1)
							$toRemove[] = $data["optgroup"];
					}

					FS::$dbMgr->BeginTr();

					// We need to remove link between option group and subnet if this is the last option
					$count = count($toRemove);
					for ($i=0;$i<$count;$i++)
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_optgroups","optgroup = '".$toRemove[$i]."'");

					// We need to remove group options linked to option linked to custom option
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_option_group","optalias IN (SELECT optalias FROM ".
						PGDbConfig::getDbPrefix()."dhcp_option WHERE optname = '".$optname."')");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_option","optname = '".$optname."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_custom_option","optname = '".$optname."'");
					FS::$dbMgr->CommitTr();

					$this->log(0,"Delete custom option: option '".$optname."' removed");

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "customoptslist",
						"sqltable" => "dhcp_custom_option",
						"sqlattrid" => "optname",
						"trpfx" => "dco"
					));
					$js = $tMgr->removeLine($optname);

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Add option
				case 14:
					if (!FS::$sessMgr->hasRight("optionsmgmt")) {
						FS::$iMgr->echoNoRights("modify DHCP option");
						return;
					}
					$optalias = FS::$secMgr->checkAndSecurisePostData("optalias");
					$optname = FS::$secMgr->checkAndSecurisePostData("optname");
					$optvalue = FS::$secMgr->checkAndSecurisePostData("optval");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if (!$optalias || !FS::$secMgr->isHostname($optalias) || !$optname || !FS::$secMgr->isHostname($optalias) ||
						!$optvalue) {
						$this->log(2,"Add/Edit option: bad datas given");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					$opttype = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_custom_option","opttype","optname= '".$optname."'");
					if (!$opttype) {
						$this->log(2,"Add/Edit option: custom option '".$optname."' doesn't exist");
						FS::$iMgr->ajaxEchoErrorNC("err-custom-option-not-exists");
						return;
					}

					switch($opttype) {
						case "boolean":
							if ($optvalue != "true" && $optvalue != "false") {
								$this->log(1,"Add/Edit option: option value invalid");
								FS::$iMgr->ajaxEchoErrorNC("err-option-value-invalid");
								return;
							}
							break;
						case "uint32":
							if (!FS::$secMgr->isNumeric($optvalue) || $optvalue < 0 || $optvalue > 4294967295) {
								$this->log(1,"Add/Edit option: option value invalid");
								FS::$iMgr->ajaxEchoErrorNC("err-option-value-invalid");
								return;
							}
						case "uint16":
							if (!FS::$secMgr->isNumeric($optvalue) || $optvalue < 0 || $optvalue > 65535) {
								$this->log(1,"Add/Edit option: option value invalid");
								FS::$iMgr->ajaxEchoErrorNC("err-option-value-invalid");
								return;
							}
						case "uint8":
							if (!FS::$secMgr->isNumeric($optvalue) || $optvalue < 0 || $optvalue > 255) {
								$this->log(1,"Add/Edit option: option value invalid");
								FS::$iMgr->ajaxEchoErrorNC("err-option-value-invalid");
								return;
							}
						case "int32":
							if (!FS::$secMgr->isNumeric($optvalue) || $optvalue < -2147483647 || $optvalue > 2147483647) {
								$this->log(1,"Add/Edit option: option value invalid");
								FS::$iMgr->ajaxEchoErrorNC("err-option-value-invalid");
								return;
							}
						case "int16":
							if (!FS::$secMgr->isNumeric($optvalue) || $optvalue < -32768 || $optvalue > 32767 ) {
								$this->log(1,"Add/Edit option: option value invalid");
								FS::$iMgr->ajaxEchoErrorNC("err-option-value-invalid");
								return;
							}
						case "int32":
							if (!FS::$secMgr->isNumeric($optvalue) || $optvalue < -256 || $optvalue > 255) {
								$this->log(1,"Add/Edit option: option value invalid");
								FS::$iMgr->ajaxEchoErrorNC("err-option-value-invalid");
								return;
							}
							break;
						case "ip":
							if (!FS::$secMgr->isIP($optvalue)) {
								$this->log(1,"Add/Edit option: option value invalid");
								FS::$iMgr->ajaxEchoErrorNC("err-option-value-invalid");
								return;
							}
							break;
						/* useless
						case text: default: break;
						*/
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_option","optalias","optalias = '".$optalias."'");
					if ($edit) {
						if (!$exist) {
							$this->log(2,"Add/Edit option: option '".$optalias."' doesn't exist");
							FS::$iMgr->ajaxEchoError("err-option-not-exists");
							return;
						}
					}
					else {
						if ($exist) {
							$this->log(1,"Add/Edit option: option '".$optalias."' already exists");
							FS::$iMgr->ajaxEchoError("err-option-already-exists");
							return;
						}
					}

					FS::$dbMgr->BeginTr();
					if ($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_option","optalias = '".$optalias."'");
					}
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_option","optalias,optname,optval","'".$optalias."','".$optname."','".$optvalue."'");
					FS::$dbMgr->CommitTr();

					$this->log(0,"Add/Edit option: option '".$optalias."' ".($edit ? "edited" : "added"));

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "doptslist",
						"tableid" => "dopttable",
						"firstlineid" => "doptftr",
						"sqltable" => "dhcp_option",
						"sqlattrid" => "optalias",
						"attrlist" => array(array("option-alias","optalias",""), array("option-name","optname",""),
							array("option-value","optval","")),
						"sorted" => true,
						"odivnb" => 11,
						"odivlink" => "optalias=",
						"rmcol" => true,
						"rmlink" => "optalias",
						"rmactid" => 15,
						"rmconfirm" => "confirm-remove-option",
						"trpfx" => "do",
					));
					$js = $tMgr->addLine($optname,$edit);

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Remove option
				case 15:
					if (!FS::$sessMgr->hasRight("optionsmgmt")) {
						FS::$iMgr->echoNoRights("remove DHCP option");
						return;
					}
					$optalias = FS::$secMgr->checkAndSecuriseGetData("optalias");
					if (!$optalias || !FS::$secMgr->isHostname($optalias)) {
						$this->log(2,"Remove option: bad datas given");
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}

					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_option","optalias",
						"optalias = '".$optalias."'")) {
						$this->log(2,"Remove option: option '".$optalias."' doesn't exist");
						FS::$iMgr->ajaxEchoError("err-option-not-exists");
						return;
					}

					// We remove option groups link with subnet if the only option is current option
					$toRemove = array();
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_option_group","COUNT(optalias) as ct,optgroup",
						"optgroup IN(SELECT optgroup FROM ".PGDbConfig::getDbPrefix()."dhcp_option_group WHERE optalias = '".$optalias."')",
						array("group" => "optgroup"));
					while ($data = FS::$dbMgr->Fetch($query)) {
						if ($data["ct"] == 1)
							$toRemove[] = $data["optgroup"];
					}

					FS::$dbMgr->BeginTr();

					// We need to remove link between option group and subnet if this is the last option
					$count = count($toRemove);
					for ($i=0;$i<$count;$i++)
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_optgroups","optgroup = '".$toRemove[$i]."'");

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_option_group","optalias = '".$optalias."'");
					// We need to remove group options linked to option
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_option","optalias = '".$optalias."'");
					FS::$dbMgr->CommitTr();

					$this->log(0,"Remove option: option '".$optalias."' removed");

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "doptslist",
						"sqltable" => "dhcp_option",
						"sqlattrid" => "optalias",
						"trpfx" => "do"
					));
					$js = $tMgr->removeLine($optalias);

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Add/edit option group
				case 16:
					if (!FS::$sessMgr->hasRight("optionsgrpmgmt")) {
						FS::$iMgr->echoNoRights("modify DHCP option group");
						return;
					}
					$optgroup = FS::$secMgr->checkAndSecurisePostData("optgroup");
					$options = FS::$secMgr->checkAndSecurisePostData("groupoptions");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if (!$optgroup || !FS::$secMgr->isHostname($optgroup) || !$options ||
						$edit && $edit != 1
					) {
						$this->log(2,"Add/Edit option group: bad datas given");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_option_group","optgroup","optgroup = '".$optgroup."'");
					if ($edit) {
						if (!$exist) {
							$this->log(2,"Add/Edit option group: option group '".$optgroup."' doesn't exist");
							FS::$iMgr->ajaxEchoError("err-dhcp-opts-group-not-exists");
							return;
						}
					}
					else {
						if ($exist) {
							$this->log(1,"Add/Edit option group: option group '".$optgroup."' already exists");
							FS::$iMgr->ajaxEchoErrorNC("err-dhcp-opts-group-already-exists");
							return;
						}
					}

					$count = count($options);
					for ($i=0;$i<$count;$i++) {
						if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_option","optalias","optalias = '".$options[$i]."'")) {
							FS::$iMgr->ajaxEchoErrorNC("err-option-not-exists");
							return;
						}
					}

					FS::$dbMgr->BeginTr();
					if ($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_option_group","optgroup  = '".$optgroup."'");
					}
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dhcp_option_group","optgroup,optalias","'".$optgroup."','".$options[$i]."'");
					}
					FS::$dbMgr->CommitTr();

					$this->log(0,"Add/Edit option group: option group '".$optgroup."' ".($edit ? "edited" : "added"));

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "dgoptslist",
						"tableid" => "dgopttable",
						"firstlineid" => "dgoptftr",
						"sqltable" => "dhcp_option_group",
						"sqlattrid" => "optgroup",
						"attrlist" => array(array("Groupname","optgroup",""), array("options","optalias","")),
						"sorted" => true,
						"odivnb" => 13,
						"odivlink" => "optgroup=",
						"rmcol" => true,
						"rmlink" => "optgroup",
						"rmactid" => 17,
						"rmconfirm" => "confirm-remove-option",
						"trpfx" => "do",
						"multiid" => true,
						));
					$js = $tMgr->addLine($optgroup,$edit);
					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Remove option group
				case 17:
					if (!FS::$sessMgr->hasRight("optionsgrpmgmt")) {
						FS::$iMgr->echoNoRights("remove DHCP option group");
						return;
					}
					$optgroup = FS::$secMgr->checkAndSecuriseGetData("optgroup");
					if (!$optgroup || !FS::$secMgr->isHostname($optgroup)) {
						$this->log(2,"Remove option group: bad datas given");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_option_group","optgroup","optgroup = '".$optgroup."'")) {
						$this->log(2,"Remove option group: option group '".$optgroup."' doesn't exists");
						FS::$iMgr->ajaxEchoError("err-dhcp-opts-group-not-exists");
						return;
					}

					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_option_group","optgroup  = '".$optgroup."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dhcp_subnet_optgroups","netid = '".$netid."'");
					FS::$dbMgr->CommitTr();

					$this->log(0,"Remove option group: option group '".$optgroup."' removed");

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "dgoptslist",
						"tableid" => "dgopttable",
						"firstlineid" => "dgoptftr",
						"sqltable" => "dhcp_option_group",
						"sqlattrid" => "optgroup",
						"attrlist" => array(array("Groupname","optgroup",""), array("options","optalias","")),
						"sorted" => true,
						"odivnb" => 13,
						"odivlink" => "optgroup=",
						"rmcol" => true,
						"rmlink" => "optgroup",
						"rmactid" => 17,
						"rmconfirm" => "confirm-remove-option",
						"trpfx" => "do",
						"multiid" => true,
						));
					$js = $tMgr->removeLine($optgroup);
					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Ip range management
				case 18:
					if (!FS::$sessMgr->hasRight("rangemgmt")) {
						FS::$iMgr->echoNoRights("modify IP range");
						return;
					}
					$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");
					$startip = FS::$secMgr->checkAndSecurisePostData("startip");
					$endip = FS::$secMgr->checkAndSecurisePostData("endip");
					$action = FS::$secMgr->checkAndSecurisePostData("rangeact");

					if (!$subnet || !FS::$secMgr->isIP($subnet) ||
						!$startip || !FS::$secMgr->isIP($startip) ||
						!$endip || !FS::$secMgr->isIP($endip) ||
						!$action) {
						$this->log(2,"IP range management: bad datas given");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					// Another test here because we have subnet
					$subnetu = preg_replace("#[.]#","_",$subnet);
					if (!FS::$sessMgr->hasRight("rangemgmt") &&
						!FS::$sessMgr->hasRight($subnetu."_rangemgmt")) {
						FS::$iMgr->echoNoRights("modify IP range");
						return;
					}

					// Action 1: add / Action 2: delete
					if ($action != 1 && $action != 2) {
						$this->log(2,"IP range management: bad action given '".$action."'");
						FS::$iMgr->ajaxEchoErrorNC("err-bad-range-action");
						return;
					}

					// Subnet must be declared
					$netmask = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netmask","netid = '".$subnet."'");
					if (!$netmask) {
						$this->log(2,"IP range management: subnet '".$subnet."' doesn't exist");
						FS::$iMgr->ajaxEchoErrorNC("err-subnet-not-exists");
						return;
					}

					$netobj = new FSNetwork();
					$netobj->setNetAddr($subnet);
					$netobj->setNetMask($netmask);

					// Start IP must be in subnet usable IPs
					if (ip2long($startip) < $netobj->getFirstUsableIPLong() ||
						ip2long($startip) > $netobj->getLastUsableIPLong()) {
						$this->log(1,"IP range management: starting IP is not in subnet");
						FS::$iMgr->ajaxEchoErrorNC("err-startip-not-in-range");
						return;
					}

					// End IP must be in subnet usable IPs
					if (ip2long($endip) < $netobj->getFirstUsableIPLong() ||
						ip2long($endip) > $netobj->getLastUsableIPLong()) {
						$this->log(1,"IP range management: ending IP is not in subnet");
						FS::$iMgr->ajaxEchoErrorNC("err-endip-not-in-range");
						return;
					}

					// Start IP must be <= End IP
					if (ip2long($startip) > ip2long($endip)) {
						$this->log(1,"IP range management: starting IP is greater than ending IP");
						FS::$iMgr->ajaxEchoErrorNC("err-startip-lower-endip");
						return;
					}

					$this->calculateRanges($subnet,$netobj,$action,$startip,$endip);

					$this->log(0,"IP range management: range modified. action '".$action."' / startIP: '".$startip.
						"' / endIP: '".$endip."'");

					$js = "$('#netshowcont').html('".FS::$secMgr->cleanForJS(preg_replace("[\n]","",$this->showSubnetIPList($subnet)))."');";
					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Import fixed adresses
				case 19:
					$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");
					$ipObj = new dhcpIP();
					if ($subnetObj = $ipObj->injectIPCSV()) {
						$this->calculateRanges($subnet,$subnetObj);
						FS::$iMgr->js("$('#netshowcont').html('".FS::$secMgr->cleanForJS(preg_replace("[\n]","",$this->showSubnetIPList($subnet)))."');");
					}
					return;
				// Import reserv from cache
				case 20:
					$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");
					$ipObj = new dhcpIP();
					if ($subnetObj = $ipObj->importIPFromCache()) {
						$this->calculateRanges($subnet,$subnetObj);
						$rstateId = "sb".FS::$iMgr->formatHTMLId($ipObj->getIP())."rsttd";
						FS::$iMgr->js("$('#".$rstateId."').html('".FS::$secMgr->cleanForJS($this->loc->s("Reserved-by-ipmanager"))."');");
						FS::$iMgr->ajaxEchoOK("Done");
					}
					return;
				// Remove IP informations
				case 21:
					(new dhcpIP())->Remove();
					return;
			}
		}

		private $switchList;
	};

	}

	$module = new iIPManager();
?>
