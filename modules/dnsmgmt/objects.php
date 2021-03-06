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

	require_once(dirname(__FILE__)."/../ipmanager/objects.php");

	final class dnsZone extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_zones";
			$this->sqlAttrId = "zonename";
			$this->readRight = "zone_read";
			$this->writeRight = "zone_write";
			$this->errNotExists = "err-zone-not-exists";
			$this->errAlreadyExists = "err-zone-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
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
			));
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(9,_("add-zone"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($aclname = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printNoRight("show zone form");
			}

			if (!$this->Load($aclname)) {
				return FS::$iMgr->printError($this->errNotExists);
			}

			$ztsel = FS::$iMgr->select("zonetype"/*JS*/).
				FS::$iMgr->selElmt(_("Classic"),"1",$this->zonetype == 1).
				FS::$iMgr->selElmt(_("Slave-only"),"2",$this->zonetype == 2).
				FS::$iMgr->selElmt(_("Forward-only"),"3",$this->zonetype == 3).
				"</select>";

			// Generate textarea output for forwarders
			$forwardlist = "";
			$count = count($this->forwarders);
			for ($i=0;$i<$count;$i++) {
				$forwardlist .= $this->forwarders[$i];
				if ($i != $count - 1) {
					$forwardlist .= "\n";
				}
			}

			// Generate textarea output for masters 
			$masterlist = "";
			$count = count($this->masters);
			for ($i=0;$i<$count;$i++) {
				$masterlist .= $this->masters[$i];
				if ($i != $count - 1) {
					$masterlist .= "\n";
				}
			}

			$cluster = new dnsCluster();
			$clusterlist = $cluster->getSelect(array("name" => "clusters", "multi" => false,
				"selected" => $this->clusters));

			$acl = new dnsACL();
			$transferlist = $acl->getSelect(array("name" => "transfer", "multi" => true,
				"noneelmt" => true, "heritedelmt" => true, "anyelmt" => true, "selected" => $this->transferAcls));

			$updatelist = $acl->getSelect(array("name" => "update", "multi" => true,
				"noneelmt" => true, "heritedelmt" => true, "anyelmt" => true, "selected" => $this->updateAcls));

			$querylist = $acl->getSelect(array("name" => "query", "multi" => true,
				"noneelmt" => true, "heritedelmt" => true, "anyelmt" => true, "selected" => $this->queryAcls));

			$notifylist = $acl->getSelect(array("name" => "notify", "multi" => true,
				"noneelmt" => true, "heritedelmt" => true, "anyelmt" => true, "selected" => $this->notifyAcls));

			$output = FS::$iMgr->cbkForm("11")."<table>".
				FS::$iMgr->idxLines(array(
					array("Zone","zonename",array("type" => "idxedit", "value" => $this->zonename,
						"length" => "256", "edit" => $this->zonename != "")),
					array("Description","description",array("value" => $this->description)),
					array("Clusters","",array("type" => "raw", "value" => $clusterlist)),
					array("Zone-type","",array("type" => "raw", "value" => $ztsel)),
					array("Forwarders","forwarders",array("type" => "area",
						"value" => $forwardlist, "height" => 150, "width" => 200)),
					array("Masters","masters",array("type" => "area",
						"value" => $masterlist, "height" => 150, "width" => 200)),
					array("allow-transfer","",array("type" => "raw", "value" => $transferlist)),
					array("allow-notify","",array("type" => "raw", "value" => $notifylist)),
					array("allow-update","",array("type" => "raw", "value" => $updatelist)),
					array("allow-query","",array("type" => "raw", "value" => $querylist)),
					array("soa-ttl-refresh","ttlretry",array("type" => "num", "value" => $this->ttlRefresh, "tooltip" => "tooltip-soattl-refresh")),
					array("soa-ttl-retry","ttlrefresh",array("type" => "num", "value" => $this->ttlRetry, "tooltip" => "tooltip-soattl-retry")),
					array("soa-ttl-expire","ttlexpire",array("type" => "num", "value" => $this->ttlExpire, "tooltip" => "tooltip-soattl-expire")),
					array("soa-ttl-minimum","ttlminimum",array("type" => "num", "value" => $this->ttlMinimum, "tooltip" => "tooltip-soattl-minimum")),
				)).
				FS::$iMgr->aeTableSubmit($this->zonename != "");

			return $output;
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId,
					$this->sqlAttrId." ILIKE '%".$search."%'", array("limit" => 10));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dnszone",$data[$this->sqlAttrId]);
				}
			}
			else {
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId.",description,zonetype",
					$this->sqlAttrId." ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}
					
					$output .= $data[$this->sqlAttrId]."<br /><b>"._("Description")."</b>: ".$data["description"]."<br /><b>".
						_("Zone-type")."</b>: ";
					switch ($data["zonetype"]) {
						case 1:
							$output .= _("Classic");
							break;
						case 2:
							$output .= _("Slave-only");
							break;
						case 3:
							$output .= _("Forward-only");
							break;
					}
					FS::$searchMgr->incResultCount();
				}
				
				if ($found) {
					$this->storeSearchResult($output,"title-dns-zone");
				}
			}
		}

		protected function Load($name = "") {
			$this->zonename = $name;
			$this->description = "";
			$this->zonetype = 0;
			$this->clusters = array();
			$this->forwarders = array();
			$this->masters = array();
			$this->transferAcls = array("herited");
			$this->updateAcls = array("herited");
			$this->queryAcls = array("herited");
			$this->notifyAcls = array("herited");
			$this->ttlRefresh = 3600;
			$this->ttlRetry = 180;
			$this->ttlExpire = 864000;
			$this->ttlMinimum = 3600;

			if ($this->zonename) {
				$query = FS::$dbMgr->Select($this->sqlTable,"description,zonetype,ttlrefresh,ttlretry,ttlexpire,ttlminimum",$this->sqlAttrId." = '".$this->zonename."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->description = $data["description"];
					$this->zonetype = $data["zonetype"];

					$this->ttlRefresh = $data["ttlrefresh"];
					$this->ttlRetry = $data["ttlretry"];
					$this->ttlExpire = $data["ttlexpire"];
					$this->ttlMinimum = $data["ttlminimum"];

					$this->clusters = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_clusters","clustername",
						$this->sqlAttrId." = '".$this->zonename."'");
					$this->forwarders = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_forwarders","zoneforwarder",
						$this->sqlAttrId." = '".$this->zonename."'");
					$this->masters = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_masters","zonemaster",
						$this->sqlAttrId." = '".$this->zonename."'");

					$this->transferAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_allow_transfer","aclname",
						$this->sqlAttrId." = '".$this->zonename."'");
					if (count($this->transferAcls) == 0) {
						$this->transferAcls = array("herited");
					}
					$this->updateAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_allow_update","aclname",
						$this->sqlAttrId." = '".$this->zonename."'");
					if (count($this->updateAcls) == 0) {
						$this->updateAcls = array("herited");
					}
					$this->queryAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_allow_query","aclname",
						$this->sqlAttrId." = '".$this->zonename."'");
					if (count($this->queryAcls) == 0) {
						$this->queryAcls = array("herited");
					}
					$this->notifyAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_allow_notify","aclname",
						$this->sqlAttrId." = '".$this->zonename."'");
					if (count($this->notifyAcls) == 0) {
						$this->notifyAcls = array("herited");
					}
					return true;
				}
				return false;
			}
			return true;
		}

		protected function removeFromDB($zonename) {
			FS::$dbMgr->Delete($this->sqlTable,"zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_clusters","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_forwarders","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_masters","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_allow_transfer","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_allow_update","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_allow_query","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_allow_notify","zonename = '".$zonename."'");
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify a zone");
				return;
			} 

			$zonename = FS::$secMgr->checkAndSecurisePostData("zonename");
			$description = FS::$secMgr->checkAndSecurisePostData("description");
			$zonetype = FS::$secMgr->checkAndSecurisePostData("zonetype");
			$clusters = FS::$secMgr->checkAndSecurisePostData("clusters");
			$forwarders = FS::$secMgr->checkAndSecurisePostData("forwarders");
			$masters = FS::$secMgr->checkAndSecurisePostData("masters");
			$transferAcls = FS::$secMgr->checkAndSecurisePostData("transfer");
			$updateAcls = FS::$secMgr->checkAndSecurisePostData("update");
			$queryAcls = FS::$secMgr->checkAndSecurisePostData("query");
			$notifyAcls = FS::$secMgr->checkAndSecurisePostData("notify");
			$ttlRefresh = FS::$secMgr->checkAndSecurisePostData("ttlrefresh");
			$ttlRetry = FS::$secMgr->checkAndSecurisePostData("ttlretry");
			$ttlExpire = FS::$secMgr->checkAndSecurisePostData("ttlexpire");
			$ttlMinimum = FS::$secMgr->checkAndSecurisePostData("ttlminimum");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			$fwdarr = array();
			$masterarr = array();

			if (!$zonename || !$description || !$zonetype || !FS::$secMgr->isNumeric($zonetype) ||
				!$clusters || $transferAcls && !is_array($transferAcls) ||
				$updateAcls && !is_array($updateAcls) || $queryAcls && !is_array($queryAcls) ||
				$notifyAcls && !is_array($notifyAcls) ||
				!$ttlRefresh || !FS::$secMgr->isNumeric($ttlRefresh) ||
				!$ttlRetry || !FS::$secMgr->isNumeric($ttlRetry) ||
				!$ttlExpire || !FS::$secMgr->isNumeric($ttlExpire) ||
				!$ttlMinimum || !FS::$secMgr->isNumeric($ttlMinimum) ||
				$edit && $edit != 1) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!FS::$secMgr->isDNSName($zonename)) {
				FS::$iMgr->ajaxEchoErrorNC("err-invalid-zonename");
				return;
			}

			$exists = $this->exists($zonename);
			if ($edit) {
				if (!$exists) {
					FS::$iMgr->ajaxEchoError($this->errNotExists);
					return;
				}
			}
			else {
				if ($exists) {
					FS::$iMgr->ajaxEchoErrorNC($this->errAlreadyExists);
					return;
				}
			}

			if ($zonetype < 1 && $zonetype > 3) {
				FS::$iMgr->ajaxEchoError("err-bad-zonetype");
				return;
			}

			$cluster = new dnsCluster();
			// It's a simple value a this time. Must be multi value for forward & slave only
			// JS ?
			if (!$cluster->exists($clusters)) {
				FS::$iMgr->ajaxEchoError($cluster->getErrNotExists());
				return;
			}

			if ($forwarders) {
				$fwdarr = FS::$secMgr->getIPList($forwarders);
				if (!$fwdarr) {
					FS::$iMgr->ajaxEchoErrorNC("err-some-ip-invalid");
					return;
				}
			}

			if ($masters) {
				$masterarr = FS::$secMgr->getIPList($masters);
				if (!$masterarr) {
					FS::$iMgr->ajaxEchoErrorNC("err-some-ip-invalid");
					return;
				}
			}

			if ($queryAcls) {
				$count = count($queryAcls);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($queryAcls[$i])) {
						FS::$iMgr->ajaxEchoError($acl->getErrNotExists());
						return;
					}
				}
			}

			if ($notifyAcls) {
				$count = count($notifyAcls);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($notifyAcls[$i])) {
						FS::$iMgr->ajaxEchoError($acl->getErrNotExists());
						return;
					}
				}
			}

			if ($updateAcls) {
				$count = count($updateAcls);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($updateAcls[$i])) {
						FS::$iMgr->ajaxEchoError($acl->getErrNotExists());
						return;
					}
				}
			}

			if ($transferAcls) {
				$count = count($transferAcls);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($transferAcls[$i])) {
						FS::$iMgr->ajaxEchoError($acl->getErrNotExists());
						return;
					}
				}
			}

			FS::$dbMgr->BeginTr();

			if ($edit) {
				$this->removeFromDB($zonename);
			}

			FS::$dbMgr->Insert($this->sqlTable,$this->sqlAttrId.",description,zonetype,ttlrefresh,ttlretry,ttlexpire,ttlminimum",
				"'".$zonename."','".$description."','".$zonetype."','".$ttlRefresh."','".$ttlRetry."','".$ttlExpire.
				"','".$ttlMinimum."'");

			FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_clusters",$this->sqlAttrId.",clustername",
				"'".$zonename."','".$clusters."'");

			$count = count($fwdarr);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_forwarders",$this->sqlAttrId.",zoneforwarder",
					"'".$zonename."','".$fwdarr[$i]."'");
			}

			$count = count($masterarr);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_masters",$this->sqlAttrId.",zonemaster",
					"'".$zonename."','".$masterarr[$i]."'");
			}

			$count = count($transferAcls);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_allow_transfer",$this->sqlAttrId.",aclname",
					"'".$zonename."','".$transferAcls[$i]."'");
			}

			$count = count($updateAcls);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_allow_update",$this->sqlAttrId.",aclname",
					"'".$zonename."','".$updateAcls[$i]."'");
			}

			$count = count($queryAcls);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_allow_query",$this->sqlAttrId.",aclname",
					"'".$zonename."','".$queryAcls[$i]."'");
			}

			$count = count($notifyAcls);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_allow_notify",$this->sqlAttrId.",aclname",
					"'".$zonename."','".$notifyAcls[$i]."'");
			}

			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($zonename,$edit);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("remove a zone");
				return;
			} 

			$zonename = FS::$secMgr->checkAndSecuriseGetData("zonename");

			if (!$zonename) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!$this->exists($zonename)) {
				FS::$iMgr->ajaxEchoError($this->errNotExists);
				return;
			}

			FS::$dbMgr->BeginTr();
			$this->removeFromDB($zonename);
			FS::$dbMgr->CommitTr();

			$this->log(0,"Removing zone '".$zonename."'");

			$js = $this->tMgr->removeLine($zonename);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}
		private $zonename;
		private $description;
		private $zonetype;
		private $clusters;

		private $forwarders;
		private $masters;

		// ACLS
		private $transferAcls;
		private $updateAcls;
		private $queryAcls;
		private $notifyAcls;

		// TTL for SOA record
		private $ttlRefresh;
		private $ttlRetry;
		private $ttlExpire;
		private $ttlMinimum;
	};

	final class dnsACL extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_acls";
			$this->sqlAttrId = "aclname";
			$this->readRight = "acl_read";
			$this->writeRight = "acl_write";
			$this->errNotExists = "err-acl-not-exists";
			$this->errAlreadyExists = "err-acl-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "dnsacl",
				"sqltable" => "dns_acls",
				"sqlattrid" => "aclname",
				"attrlist" => array(array("ACL","aclname",""), array("Desc","description","")),
				"sorted" => true,
				"odivnb" => 6,
				"odivlink" => "aclname=",
				"rmcol" => true,
				"rmlink" => "aclname",
				"rmactid" => 8,
				"rmconfirm" => "confirm-remove-acl",
			));
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(5,_("add-acl"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($aclname = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printNoRight("show ACL form");
			}

			if (!$this->Load($aclname)) {
				return FS::$iMgr->printError($this->errNotExists);
			}

			$output = FS::$iMgr->cbkForm("7")."<table>".
				FS::$iMgr->idxLines(array(
					array("acl-name","aclname",array("type" => "idxedit", "value" => $this->aclname,
						"length" => "32", "edit" => $this->aclname != "")),
					array("Description","description",array("value" => $this->description))
				));

			// TSIG list
			$selected = array("none");
			if (count($this->tsigs) > 0) {
				$selected = $this->tsigs;
			}

			$tsig = new dnsTSIGKey();
			$tsiglist = $tsig->getSelect(array("name" => "tsiglist", "multi" => true,
				"exclude" => $this->aclname, "noneelmt" => true, "selected" => $selected));
			if ($tsiglist != NULL) {
				$output .= FS::$iMgr->idxLine("tsig-to-include","",array("type" => "raw", "value" => $tsiglist));
			}

			// Subnet list
			$selected = array("none");
			if (count($this->networks) > 0) {
				$selected = $this->networks;
			}

			$sObj = new dhcpSubnet();
			$subnetlist = $sObj->getSelect(array("name" => "subnetlist", "multi" => true,
				"exclude" => $this->aclname, "noneelmt" => true, "selected" => $selected));
			if ($subnetlist != NULL) {
				$output .= FS::$iMgr->idxLine("subnets-to-include","",array("type" => "raw", "value" => $subnetlist));
			}

			// IP List
			$list = "";
			$count = count($this->ips);
			if ($count > 0) {
				for ($i=0;$i<$count;$i++) {
					$list .= $this->ips[$i];
					if ($i < $count-1)
						$list .= "\n";
				}
			}

			$output .= FS::$iMgr->idxLine("ip-to-include","iplist",array("type" => "area", "tooltip" => "tooltip-ipinclude",
				"width" => 300, "height" => "150", "length" => 1024, "value" => $list));

			// ACL list
			$selected = array("none");
			if (count($this->acls) > 0) {
				$selected = $this->acls;
			}

			$acllist = $this->getSelect(array("name" => "acllist", "multi" => true,
				"exclude" => $this->aclname, "noneelmt" => true, "selected" => $selected));
			if ($acllist != NULL) {
				$output .= FS::$iMgr->idxLine("acls-to-include","",array("type" => "raw", "value" => $acllist));
			}

			// DNS Name List
			$list = "";
			$count = count($this->dnsnames);
			if ($count > 0) {
				for ($i=0;$i<$count;$i++) {
					$list .= $this->dnsnames[$i];
					if ($i < $count-1)
						$list .= "\n";
				}
			}

			$output .= FS::$iMgr->idxLine("dns-to-include","dnslist",array("type" => "area", "tooltip" => "tooltip-dnsinclude",
				"width" => 300, "height" => "150", "length" => 4096, "value" => $list));

			$output .= FS::$iMgr->aeTableSubmit($this->aclname != "");

			return $output;
		}

		public function getSelect($options = array()) {
			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$sqlcond = (isset($options["exclude"])) ? $this->sqlAttrId." != '".$options["exclude"]."'" : "";
			$none = (isset($options["noneelmt"]) && $options["noneelmt"] == true);
			$herited = (isset($options["heritedelmt"]) && $options["heritedelmt"] == true);
			$any = (isset($options["anyelmt"]) && $options["anyelmt"] == true);
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			if ($none) {
				$output .= FS::$iMgr->selElmt(_("None"),"none",
					in_array("none",$selected));
			}
			if ($herited) {
				$output .= FS::$iMgr->selElmt(_("Herited"),"herited",
					in_array("herited",$selected));
			}
			if ($any) {
				$output .= FS::$iMgr->selElmt(_("Any"),"any",
					in_array("any",$selected));
			}

			$elements = FS::$iMgr->selElmtFromDB($this->sqlTable,$this->sqlAttrId,array("sqlcond" => $sqlcond,
				"sqlopts" => array("order" => $this->sqlAttrId),"selected" => $selected));
			if ($elements == "" && $none == false) {
				return NULL;
			}
				
			$output .= $elements."</select>";
			return $output;
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId,
					$this->sqlAttrId." ILIKE '%".$search."%'", array("limit" => 10));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dnsacl",$data[$this->sqlAttrId]);
				}
			}
			else {
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId.",description",
					$this->sqlAttrId." ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}
					
					$output .= $data[$this->sqlAttrId]."<br /><b>"._("Description")."</b>: ".$data["description"];
					FS::$searchMgr->incResultCount();
				}
				
				if ($found) {
					$this->storeSearchResult($output,"title-dns-acl");
				}
			}
		}

		protected function Load($name = "") {
			$this->aclname = $name;
			$this->description = "";
			$this->ips = array();
			$this->networks = array();
			$this->tsigs = array();
			$this->acls = array();
			$this->dnsnames = array();

			if ($this->aclname) {
				if ($desc = FS::$dbMgr->GetOneData($this->sqlTable,"description","aclname = '".$this->aclname."'")) {
					$this->description = $desc;
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_ip","ip","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->ips[] = $data["ip"];
					}
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_network","netid","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->networks[] = $data["netid"];
					}
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_tsig","tsig","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->tsigs[] = $data["tsig"];
					}
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_acl","aclchild","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->acls[] = $data["aclchild"];
					}
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_dnsname","dnsname","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->dnsnames[] = $data["dnsname"];
					}
				}
				else {
					return false;
				}
			}
			return true;
		}

		protected function removeFromDB($aclname) {
			FS::$dbMgr->Delete($this->sqlTable,"aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_ip","aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_network","aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_tsig","aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_acl","aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_dnsname","aclname = '".$aclname."'");
		}

		protected function exists($id) {
			if ($id == "none" || $id == "herited" || $id == "any") {
				return true;
			}

			if (FS::$dbMgr->GetOneData($this->sqlTable,$this->sqlAttrId,$this->sqlAttrId." = '".$id."'")) {
				return true;
			}
			return false;
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify an ACL");
				return;
			} 

			$aclname = FS::$secMgr->checkAndSecurisePostData("aclname");
			$description = FS::$secMgr->checkAndSecurisePostData("description");
			$tsiglist = FS::$secMgr->checkAndSecurisePostData("tsiglist");
			$subnetlist = FS::$secMgr->checkAndSecurisePostData("subnetlist");
			$acllist = FS::$secMgr->checkAndSecurisePostData("acllist");
			$iplist = FS::$secMgr->checkAndSecurisePostData("iplist");
			$dnslist = FS::$secMgr->checkAndSecurisePostData("dnslist");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			$iplistarr = array();
			$dnslistarr = array();

			if (!$aclname || !$description) {
				FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
				$this->log(2,"Some datas are invalid or wrong for modify dns ACL");
				return;
			}

			if ($aclname == "none" || $aclname == "herited" || $aclname == "any") {
				FS::$iMgr->ajaxEchoErrorNC("err-acl-name-protected");
				$this->log(2,"ACL name '".$aclname."' is protected");
				return;
			}

			$exists = $this->exists($aclname);
			if ($edit) {	
				if (!$exists) {
					$this->log(1,"Unable to edit acl '".$aclname."': not exists");
					FS::$iMgr->ajaxEchoError($this->errNotExists);
					return;
				}
			}
			else {
				if ($exists) {
					$this->log(1,"Unable to add acl '".$aclname ."': already exists");
					FS::$iMgr->ajaxEchoError($this->errAlreadyExists);
					return;
				}
			}
			$rulefound = false;

			if ($tsiglist && is_array($tsiglist)) {
				if (!in_array("none",$tsiglist)) {
					$count = count($tsiglist);
					for ($i=0;$i<$count;$i++) {
						$tsig = new dnsTSIGKey();
						if (!$tsig->Load($tsiglist[$i])) {
							FS::$iMgr->ajaxEchoErrorNC("err-tsig-key-not-exists");
							return;
						}
						$rulefound = true;
					}
				}
			}

			if ($subnetlist && is_array($subnetlist)) {
				if (!in_array("none",$subnetlist)) {
					$count = count($subnetlist);
					for ($i=0;$i<$count;$i++) {
						$subnet = new dhcpSubnet();
						if (!$subnet->Load($subnetlist[$i])) {
							FS::$iMgr->ajaxEchoErrorNC("err-subnet-not-exists");
							return;
						}
						$rulefound = true;
					}
				}
			}

			if ($acllist && is_array($acllist)) {
				if (!in_array("none",$acllist)) {
					$count = count($acllist);
					for ($i=0;$i<$count;$i++) {
						$acl = new dnsACL();
						if (!$acl->Load($acllist[$i])) {
							FS::$iMgr->ajaxEchoErrorNC("err-acl-not-exists");
							return;
						}
						$rulefound = true;
					}
				}
			}

			if ($iplist) {
				$iplistarr = FS::$secMgr->getIPList($iplist);
				if (!$iplistarr) {
					FS::$iMgr->ajaxEchoErrorNC("err-some-ip-invalid");
					return;
				}
				$rulefound = true;
			}
			if ($dnslist) {
				$dnslistarr = FS::$secMgr->getDNSNameList($dnslist);
				if (!$dnslistarr) {
					FS::$iMgr->ajaxEchoErrorNC("err-some-dns-invalid");
					return;
				}
				$rulefound = true;
			}

			if (!$rulefound) {
				FS::$iMgr->ajaxEchoErrorNC("err-no-rule-specified");
				return;
			}

			FS::$dbMgr->BeginTr();

			if ($edit) {
				$this->removeFromDB($aclname);
			}

			FS::$dbMgr->Insert($this->sqlTable,"aclname,description","'".$aclname."','".$description."'");

			if ($tsiglist && is_array($tsiglist)) {
				if (!in_array("none",$tsiglist)) {
					$count = count($tsiglist);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_tsig","aclname,tsig",
							"'".$aclname."','".$tsiglist[$i]."'");
					}
				}
			}

			if ($subnetlist && is_array($subnetlist)) {
				if (!in_array("none",$subnetlist)) {
					$count = count($subnetlist);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_network","aclname,netid",
							"'".$aclname."','".$subnetlist[$i]."'");
					}
				}
			}

			if ($acllist && is_array($acllist)) {
				if (!in_array("none",$acllist)) {
					$count = count($acllist);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_acl","aclname,aclchild",
							"'".$aclname."','".$acllist[$i]."'");
					}
				}
			}

			if ($iplist) {
				$count = count($iplistarr);
				for ($i=0;$i<$count;$i++) {
					if ($iplistarr[$i] == "") {
						continue;
					}

					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_ip","aclname,ip",
						"'".$aclname."','".$iplistarr[$i]."'");
				}
			}

			if ($dnslist) {
				$count = count($dnslistarr);
				for ($i=0;$i<$count;$i++) {
					if ($dnslistarr[$i] == "") {
						continue;
					}
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_dnsname","aclname,dnsname",
						"'".$aclname."','".$dnslistarr[$i]."'");
				}
			}

			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($aclname,$edit);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("remove an ACL");
				return;
			} 

			$aclname = FS::$secMgr->checkAndSecuriseGetData("aclname");
			if (!$aclname) {
				FS::$dbMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if ($aclname == "none" || $aclname == "herited" || $aclname == "any") {
				FS::$iMgr->ajaxEchoErrorNC("err-acl-name-protected");
				$this->log(2,"ACL name '".$aclname."' is protected");
				return;
			}
			$exists = $this->exists($aclname);
			if (!$exists) {
				$this->log(1,"Unable to remove acl '".$aclname."': not exists");
				FS::$iMgr->ajaxEchoError($this->errNotExists);
				return;
			}

			FS::$dbMgr->BeginTr();
			$this->removeFromDB($aclname);
			FS::$dbMgr->CommitTr();
			
			$js = $this->tMgr->removeLine($aclname);
			FS::$iMgr->ajaxEchoOK("Done",$js);
			return;
		}

		private $aclname;
		private $description;
		private $ips;
		private $networks;
		private $tsigs;
		private $acls;
		private $dnsnames;
	};

	final class dnsCluster extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_clusters";
			$this->sqlAttrId = "clustername";
			$this->readRight = "read";
			$this->writeRight = "write";
			$this->errNotExists = "err-cluster-not-exists";
			$this->errAlreadyExists = "err-cluster-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "dnsclustr",
				"sqltable" => "dns_clusters",
				"sqlattrid" => "clustername",
				"attrlist" => array(array("Name","clustername",""), array("Desc","description","")), 
				"sorted" => true,
				"odivnb" => 8,
				"odivlink" => "clustername=",
				"rmcol" => true,
				"rmlink" => "clustername",
				"rmactid" => 10,
				"rmconfirm" => "confirm-remove-cluster",
			));
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(7,_("add-cluster"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($clustername = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printNoRight("show cluster form");
			}
			
			if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_servers","addr")) {
				return FS::$iMgr->printError("err-one-dns-server-required");
			}

			if (!$this->Load($clustername)) {
				return FS::$iMgr->printError($this->errNotExists);
			}

			$acls = new dnsACL();
			$recurselist = $acls->getSelect(array("name" => "recurse", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->recurseAcls));
			$transferlist = $acls->getSelect(array("name" => "transfer", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->transferAcls));
			$notifylist = $acls->getSelect(array("name" => "notify", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->notifyAcls));
			$updatelist = $acls->getSelect(array("name" => "update", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->updateAcls));
			$querylist = $acls->getSelect(array("name" => "query", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->queryAcls));

			$server = new dnsServer();
			$masters = $server->getSelect(array("name" => "masters", "multi" => true,
				"selected" => $this->masterMembers));
			$slaves = $server->getSelect(array("name" => "slaves", "multi" => true,
				"selected" => $this->slaveMembers));
			$caches = $server->getSelect(array("name" => "caches", "multi" => true,
				"selected" => $this->cachingMembers));

			$output = FS::$iMgr->cbkForm("9").FS::$iMgr->tip("tip-dnscluster")."<table>".
				FS::$iMgr->idxLines(array(
					array("clustername","clustername",array("type" => "idxedit", "value" => $this->clustername,
						"length" => "64", "edit" => $this->clustername != "")),
						array("Desc","description",array("value" => $this->description, "length" => "128")),
						array("master-servers","",array("type" => "raw", "value" => $masters)),
						array("slave-servers","",array("type" => "raw", "value" => $slaves)),
						array("caching-servers","",array("type" => "raw", "value" => $caches)),
						array("allow-recurse","",array("type" => "raw", "value" => $recurselist)),
						array("allow-transfer","",array("type" => "raw", "value" => $transferlist)),
						array("allow-notify","",array("type" => "raw", "value" => $notifylist)),
						array("allow-update","",array("type" => "raw", "value" => $updatelist)),
						array("allow-query","",array("type" => "raw", "value" => $querylist)),
						array("enable-dnssec","dnssecen",array("type" => "chk", "value" => $this->dnssecEnable)),
						array("enable-dnssec-validation","dnssecval",
							array("type" => "chk", "value" => $this->dnssecValidate)),
				)).
				FS::$iMgr->aeTableSubmit($clustername == "");

			return $output;
		}

		public function getSelect($options = array()) {
			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$sqlcond = (isset($options["exclude"])) ? $this->sqlAttrId." != '".$options["exclude"]."'" : "";
			$none = (isset($options["noneelmt"]) && $options["noneelmt"] == true);
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			if ($none) {
				$output .= FS::$iMgr->selElmt(_("None"),"none",
					in_array("none",$selected));
			}

			$elements = FS::$iMgr->selElmtFromDB($this->sqlTable,$this->sqlAttrId,array("sqlcond" => $sqlcond,
				"sqlopts" => array("order" => $this->sqlAttrId),"selected" => $selected));
			if ($elements == "" && $none == false) {
				return NULL;
			}
				
			$output .= $elements."</select>";
			return $output;
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId,
					$this->sqlAttrId." ILIKE '%".$search."%'", array("limit" => 10));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dnscluster",$data[$this->sqlAttrId]);
				}
			}
			else {
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId.",description",
					$this->sqlAttrId." ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}
					
					$output .= $data[$this->sqlAttrId]."<br /><b>"._("Description")."</b>: ".$data["description"];
					FS::$searchMgr->incResultCount();
				}
				
				if ($found) {
					$this->storeSearchResult($output,"title-dns-cluster");
				}
			}
		}

		protected function Load($clustername = "") {
			$this->clustername = $clustername;
			$this->description = "";
			$this->masterMembers = array();
			$this->slaveMembers = array();
			$this->cachingMembers = array();
			// Default options
			$this->recurseAcls = array("none");
			$this->transferAcls = array("none");
			$this->notifyAcls = array("none");
			$this->updateAcls = array("none");
			$this->queryAcls = array("any");
			$this->dnssecEnable = false;
			$this->dnssecValidate = false;

			if ($this->clustername) {
				$query = FS::$dbMgr->Select($this->sqlTable,"description,dnssec_enable,dnssec_validation",
					$this->sqlAttrId."= '".$this->clustername."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->description = $data["description"];

					$this->masterMembers = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_masters","server",
						$this->sqlAttrId." = '".$this->clustername."'");
					$this->slaveMembers = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_slaves","server",
						$this->sqlAttrId." = '".$this->clustername."'");
					$this->cachingMembers = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_caches","server",
						$this->sqlAttrId." = '".$this->clustername."'");

					$this->recurseAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->recurseAcls) == 0) {
						$this->recurseAcls = array("none");
					}

					$this->transferAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->transferAcls) == 0) {
						$this->transferAcls = array("none");
					}

					$this->notifyAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->notifyAcls) == 0) {
						$this->notifyAcls = array("none");
					}

					$this->updateAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_update","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->updateAcls) == 0) {
						$this->updateAcls = array("none");
					}

					$this->queryAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_query","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->queryAcls) == 0) {
						$this->queryAcls = array("none");
					}

					$this->dnssecEnable = ($data["dnssec_enable"] == 't');
					$this->dnssecValidate = ($data["dnssec_validation"] == 't');
					return true;
				}
				return false;
			}
			return true;
		}

		protected function removeFromDB($name) {
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_masters",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_slaves",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_caches",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_update",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_query",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete($this->sqlTable,$this->sqlAttrId." = '".$name."'");
		}
		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify a cluster");
				return;
			} 

			$clustername = FS::$secMgr->checkAndSecurisePostData("clustername");
			$description = FS::$secMgr->checkAndSecurisePostData("description");
			$masters = FS::$secMgr->checkAndSecurisePostData("masters");
			$slaves = FS::$secMgr->checkAndSecurisePostData("slaves");
			$caches = FS::$secMgr->checkAndSecurisePostData("caches");
			$recurse = FS::$secMgr->checkAndSecurisePostData("recurse");
			$transfer = FS::$secMgr->checkAndSecurisePostData("transfer");
			$notify = FS::$secMgr->checkAndSecurisePostData("notify");
			$update = FS::$secMgr->checkAndSecurisePostData("update");
			$query = FS::$secMgr->checkAndSecurisePostData("query");
			$dnssecEnable = FS::$secMgr->checkAndSecurisePostData("dnssecen");
			$dnssecValidate = FS::$secMgr->checkAndSecurisePostData("dnssecval");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if (!$clustername || !$description || $masters && !is_array($masters) ||
				$slaves && !is_array($slaves) || $caches && !is_array($caches) ||
				$recurse && !is_array($recurse) || $transfer && !is_array($transfer) ||
				$notify && !is_array($notify) || $update && !is_array($update) ||
				$query && !is_array($query) || $edit && $edit != 1) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}
		
			// Verify cluster existence
			$exists = $this->exists($clustername);
			if ($edit) {
				if (!$exists) {
					$this->log(1,"Unable to edit cluster '".$clustername."': not exists");
					FS::$iMgr->ajaxEchoError($this->errNotExists);
					return;
				}
			}
			else {
				if ($exists) {
					$this->log(1,"Unable to add cluster '".$clustername."': already exists");
					FS::$iMgr->ajaxEchoError($this->errAlreadyExists);
					return;
				}
			}

			$masterfound = false;
			// Verify servers (exist and no duplicates)
			if ($masters) {
				$count = count($masters);
				for ($i=0;$i<$count;$i++) {
					$server = new dnsServer();
					if (!$server->exists($masters[$i])) {
						FS::$iMgr->ajaxEchoError($server->getErrNotExists());
						return;
					}
					if ($slaves) {
						if (in_array($masters[$i],$slaves)) {
							FS::$iMgr->ajaxEchoErrorNC("err-cluster-member-only-one-category");
							return;
						}
					}
					if ($caches) {
						if (in_array($masters[$i],$caches)) {
							FS::$iMgr->ajaxEchoErrorNC("err-cluster-member-only-one-category");
							return;
						}
					}
					$masterfound = true;
				}
			}

			// No master found, stop it.
			if (!$masterfound) {
				FS::$iMgr->ajaxEchoErrorNC("err-cluster-need-master");
				return;
			}

			if ($slaves) {
				$count = count($slaves);
				for ($i=0;$i<$count;$i++) {
					$server = new dnsServer();
					if (!$server->exists($slaves[$i])) {
						FS::$iMgr->ajaxEchoError($server->getErrNotExists());
						return;
					}
					// Slave - master already checked
					if ($caches) {
						if (in_array($slaves[$i],$caches)) {
							FS::$iMgr->ajaxEchoErrorNC("err-cluster-member-only-one-category");
							return;
						}
					}
				}
			}

			if ($caches) {
				$count = count($caches);
				for ($i=0;$i<$count;$i++) {
					$server = new dnsServer();
					if (!$server->exists($caches[$i])) {
						FS::$iMgr->ajaxEchoError($server->getErrNotExists());
						return;
					}
					// All duplicated have been checked at this time
				}
			}

			if ($recurse) {
				$count = count($recurse);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($recurse[$i])) {
						FS::$iMgr->ajaxEchoError($acl->getErrNotExists());
						return;
					}
				}
			}

			if ($notify) {
				$count = count($notify);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($notify[$i])) {
						FS::$iMgr->ajaxEchoError($acl->getErrNotExists());
						return;
					}
				}
			}
			if ($transfer) {
				$count = count($transfer);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($transfer[$i])) {
						FS::$iMgr->ajaxEchoError($acl->getErrNotExists());
						return;
					}
				}
			}
			if ($query) {
				$count = count($query);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($query[$i])) {
						FS::$iMgr->ajaxEchoError($acl->getErrNotExists());
						return;
					}
				}
			}
			if ($update) {
				$count = count($update);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($update[$i])) {
						FS::$iMgr->ajaxEchoError($acl->getErrNotExists());
						return;
					}
				}
			}

			FS::$dbMgr->BeginTr();
			if ($edit) {
				$this->removeFromDB($clustername);
			}
			FS::$dbMgr->Insert($this->sqlTable,$this->sqlAttrId.",description,dnssec_enable,dnssec_validation",
				"'".$clustername."','".$description."','".($dnssecEnable == true ? 't' : 'f')."','".
				($dnssecValidate == true ? 't' : 'f')."'");

			$count = count($masters);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_masters",$this->sqlAttrId.",server",
					"'".$clustername."','".$masters[$i]."'");
			}

			if ($slaves) {
				$count = count($slaves);
				for ($i=0;$i<$count;$i++) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_slaves",$this->sqlAttrId.",server",
						"'".$clustername."','".$slaves[$i]."'");
				}
			}

			if ($caches) {
				$count = count($caches);
				for ($i=0;$i<$count;$i++) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_caches",$this->sqlAttrId.",server",
						"'".$clustername."','".$caches[$i]."'");
				}
			}

			if ($recurse) {
				if (in_array("none",$recurse)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$recurse)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($recurse);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$recurse[$i]."'");
					}
				}
			}

			if ($transfer) {
				if (in_array("none",$transfer)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$transfer)) {

					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($transfer);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$transfer[$i]."'");
					}
				}
			}

			if ($notify) {
				if (in_array("none",$notify)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$notify)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($notify);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$notify[$i]."'");
					}
				}
			}

			if ($update) {
				if (in_array("none",$update)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_update",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$update)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_update",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($update);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_update",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$update[$i]."'");
					}
				}
			}

			if ($query) {
				if (in_array("none",$query)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_query",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$query)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_query",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($query);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_query",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$query[$i]."'");
					}
				}
			}

			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($clustername,$edit);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("remove a cluster");
				return;
			} 

			$clustername = FS::$secMgr->checkAndSecuriseGetData("clustername");

			if (!$clustername) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!$this->exists($clustername)) {
				FS::$iMgr->ajaxEchoError($this->errNotExists);
				return;
			}

			FS::$dbMgr->BeginTr();
			$this->removeFromDb($clustername);
			FS::$dbMgr->CommitTr();

			$this->log(0,"Removing cluster '".$clustername."'");

			$js = $this->tMgr->removeLine($clustername);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}

		private $clustername;
		private $description;
		private $masterMembers;
		private $slaveMembers;
		private $cachingMembers;
		private $recurseAcls;
		private $transferAcls;
		private $notifyAcls;
		private $updateAcls;
		private $queryAcls;
		private $dnssecEnable;
		private $dnssecValidate;
	}
			
	final class dnsServer extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_servers";
			$this->sqlAttrId = "addr";
			$this->readRight = "read";
			$this->writeRight = "write";
			$this->errNotExists = "err-server-not-exists";
			$this->errAlreadyExists = "err-server-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "dnssrv",
				"sqltable" => "dns_servers",
				"sqlattrid" => "addr",
				"attrlist" => array(array("Addr","addr",""), array("Login","sshuser",""), array("named-conf-path","namedpath",""),
					array("machine-FQDN","nsfqdn","")),
				"sorted" => true,
				"odivnb" => 2,
				"odivlink" => "addr=",
				"rmcol" => true,
				"rmlink" => "addr",
				"rmactid" => 4,
				"rmconfirm" => "confirm-remove-server",
			));
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(1,_("add-server"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($addr = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printNoRight("show server form");
			}

			if (!$this->Load($addr)) {
				return FS::$iMgr->printError($this->errNotExists);
			}

			$tsiglistTransfer = (new dnsTSIGKey())->getSelect(
				array("noneelmt" => true, "name" => "tsigupdate", "selected" => array($this->transferTSIG)));
				
			$tsiglistUpdate = (new dnsTSIGKey())->getSelect(
				array("noneelmt" => true, "name" => "tsigtransfer", "selected" => array($this->updateTSIG)));
			
			$output = FS::$iMgr->cbkForm("3").
				FS::$iMgr->tip("tip-dnsserver")."<table>".
				FS::$iMgr->idxLines(array(
					array("ip-addr","saddr",array("type" => "idxedit", "value" => $this->addr,
						"length" => "128", "edit" => $this->addr != "")),
					array("ssh-user","slogin",array("value" => $this->sshUser)),
					array("Password","spwd",array("type" => "pwd")),
					array("Password-repeat","spwd2",array("type" => "pwd"))
				));
				
			if ($tsiglistTransfer) {
				$output .= FS::$iMgr->idxLine("tsig-transfer","",
					array("type" => "raw", "value" => $tsiglistTransfer, "tooltip" => "tooltip-tsig-transfer"));
			}
			if ($tsiglistUpdate) {
				$output .= FS::$iMgr->idxLine("tsig-update","",
					array("type" => "raw", "value" => $tsiglistUpdate, "tooltip" => "tooltip-tsig-update"));
			}
			
			$output .= FS::$iMgr->idxLines(array(
					array("named-conf-path","namedpath",array("value" => $this->namedPath,"tooltip" => "tooltip-rights")),
					array("chroot-path","chrootnamed",array("value" => $this->chrootPath,"tooltip" => "tooltip-chroot")),
					array("machine-FQDN","nsfqdn",array("value" => $this->machineFQDN,"tooltip" => "tooltip-machine-FQDN",
						"star" => 1)),
					array("named-zeye-path","zeyenamedpath",array("value" => $this->zeyeNamedPath,"tooltip" => "tooltip-zeyenamed-path",
						"star" => 1)),
					array("masterzone-path","mzonepath",array("value" => $this->masterZonePath,"tooltip" => "tooltip-masterzone-path",
						"star" => 1)),
					array("slavezone-path","szonepath",array("value" => $this->slaveZonePath,"tooltip" => "tooltip-slavezone-path",
						"star" => 1))
				)).
				FS::$iMgr->aeTableSubmit($addr == "");
			
			return $output;
		}

		public function getSelect($options = array()) {
			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$sqlcond = (isset($options["exclude"])) ? $this->sqlAttrId." != '".$options["exclude"]."'" : "";
			$none = (isset($options["noneelmt"]) && $options["noneelmt"] == true);
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			if ($none) {
				$output .= FS::$iMgr->selElmt(_("None"),"none",
					in_array("none",$selected));
			}

			$elements = FS::$iMgr->selElmtFromDB($this->sqlTable,$this->sqlAttrId,array("sqlcond" => $sqlcond,
				"sqlopts" => array("order" => $this->sqlAttrId),"selected" => $selected));
			if ($elements == "" && $none == false) {
				return NULL;
			}
				
			$output .= $elements."</select>";
			return $output;
		}

		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId,
					$this->sqlAttrId." ILIKE '%".$search."%'", array("limit" => 10));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dnsserver",$data[$this->sqlAttrId]);
				}
			}
			else {
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId.",nsfqdn",
					$this->sqlAttrId." ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}
					
					$output .= $data[$this->sqlAttrId]."<br /><b>"._("machine-FQDN")."</b>: ".$data["nsfqdn"];
					FS::$searchMgr->incResultCount();
				}
				
				if ($found) {
					$this->storeSearchResult($output,"title-dns-server");
				}
			}
		}
		
		protected function Load($addr = "") {
			$this->addr = $addr;
			$this->sshUser = ""; $this->namedPath = ""; $this->chrootPath = "";
			$this->masterZonePath = ""; $this->slaveZonePath = "";
			$this->machineFQDN = "";

			if ($this->addr) {
				$query = FS::$dbMgr->Select($this->sqlTable,
					"sshuser,namedpath,chrootpath,mzonepath,szonepath,zeyenamedpath,nsfqdn,tsigtransfer,tsigupdate",
					$this->sqlAttrId." = '".$addr."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->sshUser = $data["sshuser"];
					$this->namedPath = $data["namedpath"];
					$this->chrootPath = $data["chrootpath"];
					$this->zeyeNamedPath = $data["zeyenamedpath"];
					$this->masterZonePath = $data["mzonepath"];
					$this->slaveZonePath = $data["szonepath"];
					$this->machineFQDN = $data["nsfqdn"];
					$this->transferTSIG = $data["tsigtransfer"];
					$this->updateTSIG = $data["tsigupdate"];
					return true;
				}
				return false;
			}
			return true;
		}

		protected function removeFromDB($name) {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlTable,"addr = '".$name."'");
			FS::$dbMgr->CommitTr();
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify a server");
				return;
			} 

			$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
			$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
			$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
			$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
			$namedpath = FS::$secMgr->checkAndSecurisePostData("namedpath");
			$chrootnamed = FS::$secMgr->checkAndSecurisePostData("chrootnamed");
			$machineFQDN = FS::$secMgr->checkAndSecurisePostData("nsfqdn");
			$zeyenamedpath = FS::$secMgr->checkAndSecurisePostData("zeyenamedpath");
			$mzonepath = FS::$secMgr->checkAndSecurisePostData("mzonepath");
			$szonepath = FS::$secMgr->checkAndSecurisePostData("szonepath");
			$tsigtransfer = FS::$secMgr->checkAndSecurisePostData("tsigtransfer");
			$tsigupdate  = FS::$secMgr->checkAndSecurisePostData("tsigupdate");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if (!$saddr || !FS::$secMgr->isIP($saddr) || !$slogin || !$spwd || !$spwd2 || $spwd != $spwd2 ||
				!$namedpath || !FS::$secMgr->isPath($namedpath) ||
					($chrootnamed && !FS::$secMgr->isPath($chrootnamed)) ||
					($zeyenamedpath && !FS::$secMgr->isPath($zeyenamedpath)) ||
					($mzonepath && !FS::$secMgr->isPath($chrootnamed.$mzonepath)) ||
					($szonepath && !FS::$secMgr->isPath($chrootnamed.$szonepath)) ||
					($machineFQDN && !FS::$secMgr->isDNSName($machineFQDN))
				) {
				$this->log(2,"Some datas are invalid or wrong for add server");
				FS::$iMgr->ajaxEchoError("err-miss-bad-fields");
				return;
			}

			if (($zeyenamedpath && (!$mzonepath || !$szonepath || !$machineFQDN)) ||
				($mzonepath && (!$zeyenamedpath || !$szonepath || !$machineFQDN)) ||
				($szonepath && (!$zeyenamedpath || !$mzonepath || !$machineFQDN)) ||
				($machineFQDN && (!$mzonepath || !$szonepath || !$zeyenamedpath))) {
				FS::$iMgr->ajaxEchoErrorNC("err-zeyenamedpath-together");
				return;
			}

			if ($zeyenamedpath == $namedpath) {
				FS::$iMgr->ajaxEchoErrorNC("err-named-zeyenamed-different");
				return;
			}

			$ssh = new SSH($saddr);
			if (!$ssh->Connect()) {
				FS::$iMgr->ajaxEchoError("err-unable-conn");
				return;
			}
			if (!$ssh->Authenticate($slogin,$spwd)) {
				FS::$iMgr->ajaxEchoErrorNC("err-bad-login");
				return;
			}
			
			if (!$ssh->isRemoteReadable($namedpath)) {
				FS::$iMgr->ajaxEchoErrorNC("err-namedconf-not-readable");
				return;
			}
			
			if (!$ssh->isRemoteWritable($zeyenamedpath)) {
				FS::$iMgr->ajaxEchoErrorNC("err-z-eye-not-writable");
				return;
			}
			
			if (!$ssh->isDirectory($chrootnamed."/".$mzonepath)) {
				FS::$iMgr->ajaxEchoErrorNC("err-masterdir-not-readable");
				return;
			}
			
			if (!$ssh->isDirectory($chrootnamed."/".$szonepath)) {
				FS::$iMgr->ajaxEchoErrorNC("err-slavedir-not-readable");
				return;
			}
		
			$exists = $this->exists($saddr);
			if ($edit) {	
				if (!$exists) {
					$this->log(1,"Unable to edit server '".$saddr."': not exists");
					FS::$iMgr->ajaxEchoError($this->errNotExists);
					return;
				}
			}
			else {
				if ($exists) {
					$this->log(1,"Unable to add server '".$saddr."': already exists");
					FS::$iMgr->ajaxEchoError($this->errAlreadyExists);
					return;
				}
			}
			
			if ($tsigtransfer && $tsigtransfer != "none") {
				if (!(new dnsTSIGKey())->exists($tsigtransfer)) {
					$this->log(1,"Unable to add server '".$saddr."': tsig key '".$tsigtransfer."' doesn't exists");
				}
			}
			else {
				$tsigtransfer = "";
			}
			
			if ($tsigupdate && $tsigupdate != "none") {
				if (!(new dnsTSIGKey())->exists($tsigupdate)) {
					$this->log(1,"Unable to add server '".$saddr."': tsig key '".$tsigupdate."' doesn't exists");
				}
			}
			else {
				$tsigupdate = "";
			}

			FS::$dbMgr->BeginTr();

			if ($edit) {
				FS::$dbMgr->Delete($this->sqlTable,"addr = '".$saddr."'");
			}
			FS::$dbMgr->Insert($this->sqlTable,
				"addr,sshuser,sshpwd,namedpath,chrootpath,mzonepath,szonepath,zeyenamedpath,nsfqdn,tsigtransfer,tsigupdate",
				"'".$saddr."','".$slogin."','".$spwd."','".$namedpath."','".$chrootnamed."','".$mzonepath.
				"','".$szonepath."','".$zeyenamedpath."','".$machineFQDN."','".$tsigtransfer."','".$tsigupdate."'");

			FS::$dbMgr->CommitTr();

			$this->log(0,"Add/Edit server '".$saddr."'");

			$js = $this->tMgr->addLine($saddr,$edit);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("remove a server");
				return;
			} 

			$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
			
			if (!$addr) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!$this->exists($addr)) {
				FS::$iMgr->ajaxEchoError($this->errNotExists);
				return;
			}
			
			$this->removeFromDB($addr);
			$this->log(0,"Removing server '".$addr."'");

			$js = $this->tMgr->removeLine($addr);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}
		private $addr;
		private $sshUser;
		private $transferTSIG;
		private $updateTSIG;
		private $chrootPath;
		private $namedPath;
		private $machineFQDN;
		private $zeyeNamedPath;
		private $masterZonePath;
		private $slaveZonePath;
	};

	final class dnsTSIGKey extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_tsig";
			$this->sqlAttrId = "keyalias";
			$this->readRight = "read";
			$this->writeRight = "write";
			$this->errNotExists = "err-tsig-key-not-exists";
			$this->errAlreadyExists = "err-tsig-key-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "tsig",
				"sqltable" => "dns_tsig",
				"sqlattrid" => "keyalias",
				"attrlist" => array(array("key-alias","keyalias",""), array("key-id","keyid",""),
					array("algorithm","keyalgo","sr",array(1 => "HMAC-MD5", 2 => "HMAC-SHA1", 3 => "HMAC-SHA256")),
					array("Value","keyvalue","")),
				"sorted" => true,
				"odivnb" => 4,
				"odivlink" => "keyalias=",
				"rmcol" => true,
				"rmlink" => "keyalias",
				"rmactid" => 6,
				"rmconfirm" => "confirm-remove-tsig",
			));
		}

		public function getSelect($options = array()) {
			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$sqlcond = (isset($options["exclude"])) ? $this->sqlAttrId." != '".$options["exclude"]."'" : "";
			$none = (isset($options["noneelmt"]) && $options["noneelmt"] == true);
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			if ($none) {
				$output .= FS::$iMgr->selElmt(_("None"),"none",
					in_array("none",$selected));
			}

			$found = false;
			$elements = FS::$iMgr->selElmtFromDB($this->sqlTable,$this->sqlAttrId,array("sqlcond" => $sqlcond,
				"sqlopts" => array("order" => $this->sqlAttrId), "selected" => $selected));
			if ($elements == "" && $none == false) {
				return NULL;
			}
				
			$output .= $elements."</select>";
			return $output;
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId,
					$this->sqlAttrId." ILIKE '%".$search."%'", array("limit" => 10));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dnstsig",$data[$this->sqlAttrId]);
				}
			}
			else {
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId.",keyid,keyalgo",
					$this->sqlAttrId." ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}
					
					$output .= $data[$this->sqlAttrId]."<br /><b>"._("key-id")."</b>: ".$data["keyid"].
						"<br /><b>"._("algorithm")."</b>: ".$data["keyalgo"];
					FS::$searchMgr->incResultCount();
				}
				
				if ($found) {
					$this->storeSearchResult($output,"title-dns-tsig");
				}
			}
		}

		protected function Load($name = "") {
			$this->name = $name;
			$this->keyid = ""; $this->keyvalue = ""; $this->keyalgo = "";

			if ($this->name) {
				if ($data = FS::$dbMgr->GetOneEntry($this->sqlTable,"keyid,keyalgo,keyvalue",
					"keyalias = '".$name."'")) {
					$this->keyid = $data["keyid"];
					$this->keyalgo = $data["keyalgo"];
					$this->keyvalue = $data["keyvalue"];
					return true;
				}
				return false;
			}
			return true;
		}

		protected function removeFromDB($name) {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlTable,"keyalias = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_tsig","keyalias = '".$aclname."'");
			FS::$dbMgr->CommitTr();
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(3,_("define-tsig-key"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($name = "") {
			if (!$this->canRead()) {
				return FS::$iMgr->printNoRight("show TSIG key form");
			}

			if (!$this->Load($name)) {
				return FS::$iMgr->printError($this->errNotExists);
			}

			$output = FS::$iMgr->cbkForm("5")."<table>".
				FS::$iMgr->idxLines(array(
					array("key-alias","keyalias",array("value" => $this->name, "type" => "idxedit", "length" => 64,
						"edit" => $this->name != "")),
					array("key-id","keyid",array("length" => 32, "value" => $this->keyid)),
					array("algorithm","",array("type" => "raw", "value" => FS::$iMgr->select("keyalgo").
						FS::$iMgr->selElmt("HMAC-MD5",1,$this->keyalgo == 1).FS::$iMgr->selElmt("HMAC-SHA1",2,$this->keyalgo == 2).
						FS::$iMgr->selElmt("HMAC-SHA256",3,$this->keyalgo == 3)."</select>")),
					array("Value","keyvalue",array("length" => 128, "size" => 30, "value" => $this->keyvalue))
				)).
				FS::$iMgr->aeTableSubmit($this->name == "");

			return $output;
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify a TSIG key");
				return;
			} 

			$keyalias = FS::$secMgr->checkAndSecurisePostData("keyalias");
			$keyid = FS::$secMgr->checkAndSecurisePostData("keyid");
			$keyalgo = FS::$secMgr->checkAndSecurisePostData("keyalgo");
			$keyvalue = FS::$secMgr->checkAndSecurisePostData("keyvalue");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if (!$keyalias || !$keyid || !$keyalgo || !FS::$secMgr->isNumeric($keyalgo) || !$keyvalue ||
				$edit && $edit != 1) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}

			if (!FS::$secMgr->isBase64($keyvalue)) {
				FS::$iMgr->ajaxEchoErrorNC("err-tsig-not-base64");
				return;
			}

			$exist = $this->exists($keyalias);
			if ($edit) {
				if (!$exist) {
					FS::$iMgr->ajaxEchoError($this->errNotExists);
					return;
				}
			}
			else {
				if ($exist) {
					FS::$iMgr->ajaxEchoError($this->errAlreadyExists);
					return;
				}
				$exist = FS::$dbMgr->GetOneEntry($this->sqlTable,"keyalias","keyid = '".$keyid.
					"' AND keyalgo = '".$keyalgo."' AND keyvalue = '".$keyvalue."'");
				if ($exist) {
					FS::$iMgr->ajaxEchoError("err-tsig-key-exactly-same");
					return;
				}
			}
			
			if (!FS::$secMgr->isHostname($keyid)) {
				FS::$iMgr->ajaxEchoError("err-tsig-key-id-invalid");
				return;
			}

			if ($keyalgo < 1 || $keyalgo > 3) {
				FS::$iMgr->ajaxEchoError("err-tsig-key-algo-invalid");
				return;
			}

			FS::$dbMgr->BeginTr();
			if ($edit) {
				FS::$dbMgr->Delete($this->sqlTable,"keyalias = '".$keyalias."'");
			}
			FS::$dbMgr->Insert($this->sqlTable,"keyalias,keyid,keyalgo,keyvalue","'".$keyalias."','".
				$keyid."','".$keyalgo."','".$keyvalue."'");
			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($keyalias,$edit);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("remove a TSIG key");
				return;
			} 
			$keyalias = FS::$secMgr->checkAndSecuriseGetData("keyalias");
			if (!$keyalias) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}
			
			if (!$this->exists($keyalias)) {
				FS::$iMgr->ajaxEchoError($this->errNotExists);
				return;
			}

			$this->removeFromDB($keyalias);

			$js = $this->tMgr->removeLine($keyalias);
			FS::$iMgr->ajaxEchoOK("Done",$js);
		}

		private $name;
		private $keyid;
		private $keyvalue;
		private $keyalgo;
	};
	
	final class dnsRecord extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlCacheTable = PGDbConfig::getDbPrefix()."dns_zone_record_cache";
			$this->readRight = "read";
			$this->writeRight = "write";
		}
		
		public function search($search, $autocomplete = false) {
			if ($autocomplete) {
				if (FS::$secMgr->isDNSName($search)) {
					$out = shell_exec("/usr/bin/dig +short ".$search);
					if ($out != NULL) {
						$found = false;
						$spl = preg_split("#[\n]#",$out);
						for ($i=0;$i<count($spl) && !$found;$i++) {
							if (strlen($spl[$i]) > 0) {
								$found = true;
								FS::$searchMgr->addAR("dnsrecord",$search);
							}
						}
					}
				}
				
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"recval","recval ILIKE '".$search."%'",
					array("order" => "recval","limit" => "10","group" => "recval"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dnsrecord",$data["recval"]);
				}
				
				$searchsplit = preg_split("#\.#",$search);
				$count = count($searchsplit);
				if ($count > 1) {
					$query = FS::$dbMgr->Select($this->sqlCacheTable,"record,zonename","record ILIKE '".$search."' AND zonename ILIKE '".$search."%'",
						array("order" => "record,zonename","limit" => "10"));
					while ($data = FS::$dbMgr->Fetch($query)) {
						FS::$searchMgr->addAR("dnsrecord",$data["record"].".".$data["zonename"]);
					}
				}
				else if ($count == 1) {
					$query = FS::$dbMgr->Select($this->sqlCacheTable,"record,zonename","record ILIKE '".$search."%'",
						array("order" => "record,zonename","limit" => "10"));
					while ($data = FS::$dbMgr->Fetch($query)) {
						FS::$searchMgr->addAR("dnsrecord",$data["record"].".".$data["zonename"]);
					}
				}
			}
			else {
				$output = "";
				
				if (FS::$secMgr->isDNSName($search)) {
					if (shell_exec("/usr/bin/dig +short ".$search." | /usr/bin/wc -l | /usr/bin/awk '{print $1}'") > 0) {
						$out = shell_exec("/usr/bin/dig ".$search);
						if ($out != NULL) {
							$output .= preg_replace("#[\n]#","<br />",$out);
							FS::$searchMgr->incResultCount();
							$this->storeSearchResult($output,"title-dns-resolution");
						}
					}
				}
				
				$curserver = "";
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"zonename,record,server","recval ILIKE '".$search."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					if ($curserver != $data["server"]) {
						$curserver = $data["server"];
						$output .= FS::$iMgr->h4($data["server"],true);
					}
					if ($data["record"] == "@") {
						$output .= $data["zonename"].FS::$iMgr->hr();
					}
					else {
						$output .= $data["record"].".".$data["zonename"].FS::$iMgr->hr();
					}
					// Resolve with DIG to search what the DNS thinks
					if (!FS::$secMgr->isIP($search) && !FS::$secMgr->isHostname($search)) {
						if ($data["server"]) {
							$out = shell_exec("/usr/bin/dig @".$data["server"]." +short ".$search);
							if ($out != NULL) {
								$output .= FS::$iMgr->h4("dig-results").
									preg_replace("#[\n]#",FS::$iMgr->hr(),$out);
								FS::$searchMgr->incResultCount();
							}
						}
					}
				}

				if ($found) {
					$this->storeSearchResult($output,"title-dns-assoc");
				}
				
				$output = "";
				$found = false;
				
				$searchsplit = preg_split("#\.#",$search);
				$count = count($searchsplit);
				if ($count > 1) {
					$hostname = $searchsplit[0];
					$dnszone = "";
					for ($i=1;$i<$count;$i++) {
						$dnszone .= $searchsplit[$i];
						if ($i != $count-1)
							$dnszone .= ".";
					}
					$curserver = "";
					$query = FS::$dbMgr->Select($this->sqlCacheTable,"rectype,recval,server",
						"record ILIKE '".$hostname."' AND zonename ILIKE '".$dnszone."'",
						array("order" => "server"));
					$output = "";
					while ($data = FS::$dbMgr->Fetch($query)) {
						if ($found == false) {
							$found = true;
						}
						if ($curserver != $data["server"]) {
							$curserver = $data["server"];
							$output .= FS::$iMgr->h3($data["server"],true);
						}
						switch($data["rectype"]) {
							case "A": $output .= _("ipv4-addr").": "; break;
							case "AAAA": $output .= _("ipv6-addr").": "; break;
							case "CNAME": $output .= _("Alias").": "; break;
							default: $output .= _("Other")." (".$data["rectype"]."): "; break;
						}
						if (FS::$secMgr->isIP($data["recval"])) {
							$output .= FS::$iMgr->aLink($this->mid."&s=".$data["recval"], $data["recval"]);
						}
						else {
							$output .= $data["recval"];
						}
						$output .= "<br />";
						if ($data["server"]) {
							$out = shell_exec("/usr/bin/dig @".$data["server"]." +short ".$search);
							if ($out != NULL) {
								$output .= FS::$iMgr->h4("dig-results");
								$output .= preg_replace("#[\n]#","<br />",$out);
							}
						}
						FS::$searchMgr->incResultCount();
					}
					if ($found) {
						$this->storeSearchResult($output,"title-dns-records");
					}
				}
			}
		}
		
		public function showForm($zonename = "") {
			if (!$this->canWrite()) {
				return FS::$iMgr->printNoRight("show DNS record form");
			}
			
			$recname = FS::$secMgr->checkAndSecuriseGetData("recname");
			$rectype = FS::$secMgr->checkAndSecuriseGetData("rectype");
			$recval = FS::$secMgr->checkAndSecuriseGetData("recvalue");
			
			if (!$recname) {
				$recname = "";
			}
			
			if (!$rectype) {
				$rectype = "";
			}
			
			if (!$recval) {
				$recval = "";
			}
			
			$recttl = 86400;
			
			$selRT = "";
			if ($rectype == "") {
				$selRT = FS::$iMgr->select("rectype").
					FS::$iMgr->selElmt("A","A",$rectype == "A").
					FS::$iMgr->selElmt("AAAA","AAAA",$rectype == "AAAA").
					FS::$iMgr->selElmt("CNAME","CNAME",$rectype == "CNAME").
					FS::$iMgr->selElmt("MX","MX",$rectype == "MX").
					FS::$iMgr->selElmt("SRV","SRV",$rectype == "SRV").
					FS::$iMgr->selElmt("TXT","TXT",$rectype == "TXT").
					FS::$iMgr->selElmt("NS","NS",$rectype == "NS").
					FS::$iMgr->selElmt("PTR","PTR",$rectype == "PTR").
					"</select>";
			}
			else {
				$selRT = $rectype.FS::$iMgr->hidden("rectype",$rectype).
					FS::$iMgr->hidden("orecval",$recval);
			}
				
			$output = sprintf("%s<table>%s%s</table></form>",
				FS::$iMgr->cbkForm("13"),
				FS::$iMgr->idxLines(array(
					array("Record","recname",array("type" => "idxedit", "value" => $recname,
						"edit" => $rectype != "")),
					array("DNS-zone","",array("type" => "raw", 
						"value" => $zonename.FS::$iMgr->hidden("zonename",$zonename))),
					array("Record-Type","rectype",array("type" => "raw", "value" => $selRT)),
					array("Record-TTL","recttl",array("type" => "num", "value" => $recttl)),
					array("Value","recval",array("value" => $recval)),
				)),
				FS::$iMgr->aeTableSubmit($zonename != "")
			);
			return $output;
		}
		
		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify a record");
				return;
			}
			
			$zonename = FS::$secMgr->checkAndSecurisePostData("zonename");
			$record = FS::$secMgr->checkAndSecurisePostData("recname");
			$rectype = FS::$secMgr->checkAndSecurisePostData("rectype");
			$recttl = FS::$secMgr->checkAndSecurisePostData("recttl");
			$recvalue = FS::$secMgr->checkAndSecurisePostData("recval");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			$oldvalue = FS::$secMgr->checkAndSecurisePostData("orecval");
			
			if (!$zonename || !FS::$secMgr->isDNSName($zonename) ||
				!$record || !FS::$secMgr->isDNSName($record) ||
				!$recttl || !FS::$secMgr->isNumeric($recttl) ||
				!$rectype || !$recvalue || !FS::$secMgr->isDNSRecordCoherent($rectype,$recvalue) ||
				$edit && ($edit != 1 || !$oldvalue)) {
				FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
				return;
			}
			
			// Now we send updates to all masters of the cluster
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_clusters",
				"clustername","zonename = '".$zonename."'");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_cluster_masters",
					"server","clustername = '".$data["clustername"]."'");
				while ($data2 = FS::$dbMgr->Fetch($query2)) {
					$date = date("Ymdhis");
					$cmd = "";
					
					// Load TSIG keys
					if ($tsigka = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_servers",
						"tsigupdate","addr = '".$data2["server"]."'")) {
						$tsigkid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_tsig",
							"keyid","keyalias = '".$tsigka."'");
						$tsigkv = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_tsig",
							"keyvalue","keyalias = '".$tsigka."'");
						if ($tsigkid && $tsigkv) {
							$cmd = sprintf("/usr/bin/nsupdate -y %s:%s %s",
								$tsigkid, $tsigkv, "/tmp/dnsrecmod-".$date);
						}
						else {
							FS::$iMgr->ajaxEchoError("err-tsig-key-id-invalid");
							return;
						}
					}
					else {
						$cmd = sprintf("/usr/bin/nsupdate %s",
							"/tmp/dnsrecmod-".$date);
					}
					
					$file = fopen("/tmp/dnsrecmod-".$date,"w+");
					if (!$file) {
						FS::$iMgr->ajaxEchoError("err-unable-write-file");
						return;
					}
					fwrite($file,sprintf("server %s\n",$data2["server"]));
					if ($edit) {
						fwrite($file,sprintf("update delete %s.%s. %s %s\n",
							$record,$zonename,$rectype,$oldvalue));
					}
					fwrite($file,sprintf("update add %s.%s. %s %s %s\nsend\n",
						$record,$zonename,$recttl,$rectype,$recvalue));
					
					fclose($file);
					
					shell_exec($cmd);
				}
			}
			FS::$iMgr->ajaxEchoOK("Done");
		}
		
		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("remove a record");
				return;
			}
			
			$zonename = FS::$secMgr->checKAndSecuriseGetData("zn");
			$record = FS::$secMgr->checkAndSecuriseGetData("rc");
			$rectype = FS::$secMgr->checkAndSecuriseGetData("rct");
			$recvalue = FS::$secMgr->checkAndSecuriseGetData("rcv");
			
			if (!$zonename || !FS::$secMgr->isDNSName($zonename) ||
				!$record || !FS::$secMgr->isDNSName($record) ||
				!$rectype || !$recvalue || !FS::$secMgr->isDNSRecordCoherent($rectype,$recvalue)) {
				FS::$iMgr->ajaxEchoError("err-bad-datas");
				return;
			}
			
			// Now we send remove to all masters of the cluster
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_clusters",
				"clustername","zonename = '".$zonename."'");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_cluster_masters",
					"server","clustername = '".$data["clustername"]."'");
				while ($data2 = FS::$dbMgr->Fetch($query2)) {
					$date = date("Ymdhis");
					$cmd = "";
					
					// Load TSIG keys
					if ($tsigka = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_servers",
						"tsigupdate","addr = '".$data2["server"]."'")) {
						$tsigkid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_tsig",
							"keyid","keyalias = '".$tsigka."'");
						$tsigkv = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_tsig",
							"keyvalue","keyalias = '".$tsigka."'");
						if ($tsigkid && $tsigkv) {
							$cmd = sprintf("/usr/bin/nsupdate -y %s:%s %s",
								$tsigkid, $tsigkv, "/tmp/dnsrecmod-".$date);
						}
						else {
							FS::$iMgr->ajaxEchoError("err-tsig-key-id-invalid");
							return;
						}
					}
					else {
						$cmd = sprintf("/usr/bin/nsupdate %s",
							"/tmp/dnsrecmod-".$date);
					}
					
					$file = fopen("/tmp/dnsrecmod-".$date,"w+");
					if (!$file) {
						FS::$iMgr->ajaxEchoError("err-unable-write-file");
						return;
					}
					fwrite($file,sprintf("server %s\n",$data2["server"]));
					fwrite($file,sprintf("update delete %s.%s. %s %s\nsend\n",
						$record,$zonename,$rectype,$recvalue));
					
					fclose($file);
					
					shell_exec($cmd);
				}
			}
			FS::$iMgr->ajaxEchoOK("Done");
		}
	};
?>
