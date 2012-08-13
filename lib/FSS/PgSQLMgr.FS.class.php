<?php
	
	/** This code is Property of Frost Sapphire Studios, all rights reserved.
	*	All modification is stricty forbidden without Frost Sapphire Studios Agreement
	**/
	require_once(dirname(__FILE__)."/../../config/pgdb.conf.php");
	require_once(dirname(__FILE__)."/../../config/global.conf.php");
	require_once(dirname(__FILE__)."/../../config/".Config::getSysLang().".lang.php");
	class FSPostgreSQLMgr {
		function FSPostgreSQLMgr() {
			$this->dbName = PGDbConfig::getDbName();
			$this->dbPort = PGDbConfig::getDbPort();
			$this->dbHost = PGDbConfig::getDbHost();
			$this->dbPass = PGDbConfig::getDbPwd();
			$this->dbUser = PGDbConfig::getDbUser();
			$this->dbLink = NULL;
		}
		
		public function Connect() {
			$this->dbLink = pg_connect("host=".$this->dbHost. " port=".$this->dbPort." dbname=".$this->dbName." user=".$this->dbUser." password=".$this->dbPass);
			if(!$this->dbLink) {
				$iMgr = new FSInterfaceMgr($this);
				$iMgr->printError(Localization::$PGSQL_CONNECT_ERROR);
				exit(1);
			}
		}
		
		public function Select($table,$fields,$cond = "",$order = "",$ordersens = 0, $limit = 0, $startidx = 0) {
			$sql = "SELECT ".$fields." FROM ".$table."";
			if(strlen($cond) > 0)
				$sql .= " WHERE ".$cond;
			if(strlen($order) > 0) {
				$sql .= " ORDER BY ".$order."";
				if($ordersens == 1)
					$sql .= " DESC";
				else if($ordersens == 2)
					$sql .= " ASC";	
			}
			if($limit > 0) {
				if($startidx > 0)
					$sql .= " LIMIT ".$startidx.",".$limit;
				else
					$sql .= " LIMIT ".$limit;

			}
			return pg_query($this->dbLink,$sql);
		}
		
		public function GetOneData($table,$field,$cond = "",$order= "",$ordersens = 0, $limit = 0, $startidx = 0) {
			$query = $this->Select($table,$field,$cond,$order,$ordersens,$limit,$startidx);
			if($data = pg_fetch_array($query)) {
				$splstr = preg_split("#[\.]#",$field);
				$splstr = preg_replace("#`#","",$splstr);
				return $data[$splstr[count($splstr)-1]];
			}
			return NULL;
		}
		
		public function GetMax($table,$field,$cond = "") {
			$query = $this->Select($table,"MAX(".$field.") as mx",$cond);
                        if($data = pg_fetch_array($query)) {
                                $splstr = preg_split("#[\.]#",$field);
                                $splstr = preg_replace("#`#","",$splstr);
                                return $data["mx"];
                        }
			return -1;
		}

		public function Sum($table,$field,$cond = "") {
                        $query = $this->Select($table,"SUM(".$field.") as mx",$cond);
                        if($data = pg_fetch_array($query)) {
                                $splstr = preg_split("#[\.]#",$field);
                                $splstr = preg_replace("#`#","",$splstr);
                                return $data["mx"];
                        }
                        return -1;
                }
		
		public function Count($table,$field,$cond = "") {
			$query = $this->Select($table,"COUNT(".$field.") as ct",$cond);
			if($data = pg_fetch_array($query)) {
                                $splstr = preg_split("#[\.]#",$field);
                                $splstr = preg_replace("#`#","",$splstr);
                                return $data["ct"];
                        }
			return 0;
		}
		
		public function Insert($table,$keys,$values) {
			$sql = "INSERT INTO ".$table."(".$keys.") VALUES (".$values.");";
			pg_query($sql);
		}

		public function Delete($table,$cond = "") {
			$sql = "DELETE FROM ".$table."";
			if(strlen($cond) > 0)
				$sql .= " WHERE ".$cond;
			pg_query($this->dbLink,$sql);
		}
		
		public function Update($table,$mods,$cond = "") {
			$sql = "UPDATE ".$table." SET ".$mods."";
			if(strlen($cond) > 0)
				$sql .= " WHERE ".$cond;
			pg_query($this->dbLink,$sql);
		}

		public function setConfig($dbn,$dbport,$dbh,$dbu,$dbp) {
                        $this->dbName = $dbn;
                        $this->dbPort = $dbport;
                        $this->dbHost = $dbh;
                        $this->dbUser = $dbu;
                        $this->dbPass = $dbp;
                }

		private $dbName;
		private $dbPort;
		private $dbHost;
		private $dbPass;
		private $dbUser;
		private $dbLink;
	};
?>
