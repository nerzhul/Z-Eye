<?php
	define("DNS_ZONE_TABLE","fss_dns_zones");
	define("DNS_ZONE_MASTER_TABLE","fss_dns_zone_master");
	define("DNS_ZONE_SLAVE_TABLE","fss_dns_zone_slave");
	class DNSZone {
		function DNSZone() {
			$this->id = 0;
			$this->zonename = "";
			$this->type = 1;
			$this->refresh = 3600;
			$this->retry = 600;
			$this->expire = 86400;
			$this->minimum = 600;
			$this->soa = "localhost";
			$this->hostmaster = "root.localhost";
			$this->serial = date("Ymd")."00";
			$this->masterip1 = "127.0.0.1";
			$this->masterip2 = "127.0.0.1";
		}
		
		public function Load() {
			$query = FS::$dbMgr->Select(DNS_ZONE_TABLE,"zonename, type","id = '".$this->id."'");
			if($data = mysql_fetch_array($query)) {
				$this->zonename = $data["zonename"];
				$this->type = $data["type"];
				if($this->type == 1) {
					$query2 = FS::$dbMgr->Select(DNS_ZONE_MASTER_TABLE,"refresh,retry,expire,minimum,serial,soa,hostmaster","zoneid = '".$this->id."'");
					if($data = mysql_fetch_array($query2)) {
						$this->refresh = $data["refresh"];
						$this->retry = $data["retry"];
						$this->expire = $data["expire"];
						$this->minimum = $data["minimum"];
						$this->serial = $data["serial"];
						$this->soa = $data["soa"];
						$this->hostmaster = $data["hostmaster"];
					}
					else
						return false;
				}
				else {
					$query2 = FS::$dbMgr->Select(DNS_ZONE_SLAVE_TABLE,"masterip1,masterip2","zoneid = '".$this->id."'");
					if($data2 = mysql_fetch_array($query2)) {
						$this->masterip1 = $data2["masterip1"];
						$this->masterip2 = $data2["masterip2"];
					}
					else
						return false;
				}
				return true;	
			}
			return false;
		}
		public function Save() {
			if(FS::$dbMgr->GetOneData(DNS_ZONE_TABLE,"zonename","id = '".$this->id."'")) {
				$this->incrementSerial();
				FS::$dbMgr->Update(DNS_ZONE_TABLE,"zonename = '".$this->zonename."', type = '".$this->type."'","id = '".$this->id."'");
				if($this->type == 1)
					FS::$dbMgr->Update(DNS_ZONE_MASTER_TABLE,"refresh = '".$this->refresh."', retry = '".$this->retry."', expire = '".$this->expire."', minimum = '".$this->minimum."', serial = '".$this->serial."', soa = '".$this->soa."', hostmaster = '".$this->hostmaster."'","zoneid = '".$this->id."'");
				else
					FS::$dbMgr->Update(DNS_ZONE_SLAVE_TABLE,"masterip1 = '".$this->masterip1."', masterip2 = '".$this->masterip2."'","zoneid = '".$this->id."'");
			}
			else {
				FS::$dbMgr->Insert(DNS_ZONE_TABLE,"zonename, type","'".$this->zonename."','".$this->type."'");
				$id = FS::$dbMgr->GetOneData(DNS_ZONE_TABLE,"id","zonename = '".$this->zonename."'");
				FS::$dbMgr->Insert(DNS_ZONE_MASTER_TABLE,"zoneid, refresh, retry, expire, minimum, serial, soa, hostmaster","'".$id."','".$this->refresh."','".$this->retry."','".$this->expire."','".$this->minimum."','".$this->serial."','".$this->soa."','".$this->hostmaster."'");
				FS::$dbMgr->Insert(DNS_ZONE_SLAVE_TABLE,"zoneid, masterip1, masterip2","'".$id."','".$this->masterip1."','".$this->masterip2."'");
			}
		}	
		
		public function Delete() {
			FS::$dbMgr->Delete(DNS_ZONE_TABLE,"id = '".$this->id."'");
			FS::$dbMgr->Delete(DNS_ZONE_MASTER_TABLE,"zoneid = '".$this->id."'");
			FS::$dbMgr->Delete(DNS_ZONE_SLAVE_TABLE,"zoneid = '".$this->id."'");
		}
		
		public function incrementSerial() {
			$date = date("Ymd");
			$serialdate = substr($this->serial,0,8);
			if($date > $serialdate)
				$this->serial = $date."00";
			else {
				$id = substr($this->serial,8,2);
				$id++;
				$this->serial = $serialdate.$id;
			}
		}
		
		/* setters */
		public function setId($id) { $this->id = $id; }
		public function setZoneName($zone) { $this->zonename = $zone; }
		public function setType($type) { $this->type = $type; }
		public function setRefresh($ref) { $this->refresh = $ref; }
		public function setRetry($retry) { $this->retry = $retry; }
		public function setExpire($exp) { $this->expire = $exp; }
		public function setMinimum($min) { $this->minimum = $min; }
		public function setSOA($soa) { $this->soa = $soa; }
		public function setHostMaster($hm) { $this->hostmaster = $hm; }
		public function setDNS1($dns1) { $this->masterip1 = $dns1; }
		public function setDNS2($dns2) { $this->masterip2 = $dns2; }
		
		/* getters */
		public function getId() { return $this->id; }
		public function getZoneName() { return $this->zonename; }
		public function getType() { return $this->type; }
		public function getRefresh() { return $this->refresh; }
		public function getRetry() { return $this->retry; }
		public function getExpire() { return $this->expire; }
		public function getMinimum() { return $this->minimum; }
		public function getSerial() { return $this->serial; }
		public function getSOA() { return $this->soa; }
		public function getHostMaster() { return $this->hostmaster; }
		public function getDNS1() { return $this->masterip1; }
		public function getDNS2() { return $this->masterip2; }
		
		/* attributes */
		private $id;
		private $zonename;
		private $type;
		private $refresh;
		private $retry;
		private $expire;
		private $minimum;
		private $serial;
		private $soa;
		private $hostmaster;
		private $masterip1;
		private $masterip2;
	};
?>
