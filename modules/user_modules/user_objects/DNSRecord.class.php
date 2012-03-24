<?php
	define('DNS_RECORD_TABLE',"fss_dns_records");
	define('DNS_RECORD_EXT_TABLE',"fss_dns_records_extended");
	
	/*
	1: A
	2: AAA
	3: CNAME
	4: MX
	5: SRV
	*/
	class DNSRecord {
		function DNSRecord() {
			$this->id = 0;
			$this->name = "";
			$this->type = 0;
			$this->value = "";
			$this->zoneid = 0;
			$this->priority = 0;
			$this->srvport = 0;
			$this->srvweight = 0;
			$this->srvprotocol = 0;
		}
		
		public function Load() {
			$query = FS::$dbMgr->Select(DNS_RECORD_TABLE,"name,type,value,zoneid","id = '".$this->id."'");
			if($data = mysql_fetch_array($query)) {
				$this->name = $data["name"];
				$this->type = $data["type"];
				$this->value = $data["value"];
				$this->zoneid = $data["zoneid"];
				if($this->type == 4 || $this->type == 5) {
					$query2 = FS::$dbMgr->Select(DNS_RECORD_EXT_TABLE,"priority,srvport,srvweight,srvprotocol","id = '".$this->id."'");
					if($data2 = mysql_fetch_array($query2)) {
						$this->priority = $data["priority"];
						$this->srvport = $data["srvport"];
						$this->srvweight = $data["srvweight"];
						$this->srvprotocol = $data["srvprotocol"];	
					}
				}				
				return true;
			}
			else
				return false;
		}
		
		public function Exists() {
			$query = FS::$dbMgr->Select(DNS_RECORD_TABLE,"id","name = '".$this->name."' AND type = '".$this->type."' AND value = '".$this->value."' AND zoneid = '".$this->zoneid."'");
			if($data = mysql_fetch_array($query))
				return true;
			return false;	
		}
		
		public function Save() {
			if(FS::$dbMgr->GetOneData(DNS_RECORD_TABLE,"name","id = '".$this->id."'")) {
				FS::$dbMgr->Update(DNS_RECORD_TABLE,"name = '".$this->name."', type = '".$this->type."', value = '".$this->value."', zoneid = '".$this->zoneid."'","id = '".$this->id."'");
				FS::$dbMgr->Delete(DNS_RECORD_EXT_TABLE,"id = '".$this->id."'");
				if($this->type == 4 || $this->type == 5)
					FS::$dbMgr->Insert(DNS_RECORD_EXT_TABLE,"id,priority,srvport,srvweight,srvprotocol","'".$this->id."','".$this->priority."','".$this->srvport."','".$this->srvweight."','".$this->srvprotocol."'");
			}
			else {
				FS::$dbMgr->Insert(DNS_RECORD_TABLE,"name,type,value,zoneid","'".$this->name."','".$this->type."','".$this->value."','".$this->zoneid."'");
				FS::$dbMgr->Delete(DNS_RECORD_EXT_TABLE,"id = '".$this->id."'");
				if($this->type == 4 || $this->type == 5)
					FS::$dbMgr->Insert(DNS_RECORD_EXT_TABLE,"id,priority,srvport,srvweight,srvprotocol","'".$this->id."','".$this->priority."','".$this->srvport."','".$this->srvweight."','".$this->srvprotocol."'");
			}
		}
		
		public function Delete() {
			FS::$dbMgr->Delete(DNS_RECORD_TABLE,"id = '".$this->id."'");
			FS::$dbMgr->Delete(DNS_RECORD_EXT_TABLE,"id = '".$this->id."'");
		}
		
		/* Setters */
		public function setId($id) { $this->id = $id; }
		public function setName($name) { $this->name = $name; }
		public function setType($type) { $this->type = $type; }
		public function setValue($value) { $this->value = $value; }
		public function setZone($zone) { $this->zoneid = $zone; }
		public function setPriority($prio) { $this->priority = $prio; }
		public function setServerPort($port) { $this->srvport = $port; }
		public function setServerWeight($w) { $this->srvweight = $w; }
		public function setServerProtocol($prot) { $this->srvprotocol = $prot; }
		
		/* Getters */
		public function getId() { return $this->id; }
		public function getName() { return $this->name; }
		public function getType() { return $this->type; }
		public function getValue() { return $this->value; }
		public function getZone() { return $this->zoneid; }
		public function getPriority() { return $this->priority; }
		public function getServerPort() { return $this->srvport; }
		public function getServerWeight() { return $this->srvweight; }
		public function getServerProtocol() { return $this->srvprotocol; }
		
		/* Attributes */
		private $id;
		private $name;
		private $type;
		private $value;
		private $zoneid;
		
		/* Extended attributes */
		private $priority;
		private $srvport;
		private $srvweight;
		private $srvprotocol;
	};
?>
