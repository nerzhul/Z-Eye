<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
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
			$max = $this->GetOneData($table,"MAX(".$field.")",$cond);
			if($max == NULL)
				return 1;
			return $max;
		}
		
		public function Count($table,$field,$cond = "") {
			$count = $this->GetOneData($table,"COUNT(".$field.")",$cond);
			if($count == NULL)
				return 0;
			return $count;
		}
		
		public function Insert($table,$keys,$values) {
			$sql = "INSERT INTO ".$table."(".$keys.") VALUES (".$values.");";
			pg_query($sql);
		}
		
		public function InsertIgnore($table,$keys,$values) {
			$sql = "INSERT IGNORE INTO ".$table."(".$keys.") VALUES (".$values.");";
			pg_query($this->dbLink,$sql);
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
		
		
		private $dbName;
		private $dbPort;
		private $dbHost;
		private $dbPass;
		private $dbUser;
		private $dbLink;
	};
?>