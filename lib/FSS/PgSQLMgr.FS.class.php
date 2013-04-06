<?php
        /*
        * Copyright (c) 2010-2013, Loïc BLOT, CNRS <http://www.unix-experience.fr>
        * All rights reserved.
        *
        * Redistribution and use in source and binary forms, with or without
        * modification, are permitted provided that the following conditions are met:
        *
        * 1. Redistributions of source code must retain the above copyright notice, this
        *    list of conditions and the following disclaimer.
        * 2. Redistributions in binary form must reproduce the above copyright notice,
        *    this list of conditions and the following disclaimer in the documentation
        *    and/or other materials provided with the distribution.
        *
        * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
        * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
        * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
        * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
        * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
        * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
        * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
        * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
        * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
        * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
        *
        * The views and conclusions contained in the software and documentation are those
        * of the authors and should not be interpreted as representing official policies,
        * either expressed or implied, of the FreeBSD Project.
        */

	require_once(dirname(__FILE__)."/../../config/pgdb.conf.php");
	require_once(dirname(__FILE__)."/../../config/global.conf.php");
	class FSPostgreSQLMgr {
		function FSPostgreSQLMgr() {
			$this->dbName = "";
			$this->dbPort = "";
			$this->dbHost = "";
			$this->dbPass = "";
			$this->dbUser = "";
			$this->dbLink = NULL;
		}

		public function Connect() {
			$this->dbLink = pg_connect("host=".$this->dbHost. " port=".$this->dbPort." dbname=".$this->dbName." user=".$this->dbUser." password=".$this->dbPass);
			if(!$this->dbLink) {
				$iMgr = new FSInterfaceMgr($this);
				echo $iMgr->printError("Unable to connect to PG database");
				return 1;
			}
			return 0;
		}

		public function Select($table,$fields,$cond = "",$order = "",$ordersens = 0, $limit = 0, $startidx = 0, $options = array()) {
			$sql = "SELECT ".$fields." FROM ".$table."";
			if(strlen($cond) > 0)
				$sql .= " WHERE ".$cond;
			if(isset($options["group"]) && strlen($options["group"]) > 0) 
				$sql .= " GROUP BY ".$options["group"];
			if(strlen($order) > 0) {
				$sql .= " ORDER BY ".$order;
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

		public function GetMin($table,$field,$cond = "") {
                        $query = $this->Select($table,"MIN(".$field.") as mn",$cond);
                        if($data = pg_fetch_array($query)) {
                                        $splstr = preg_split("#[\.]#",$field);
                                        $splstr = preg_replace("#`#","",$splstr);
                                        return $data["mn"];
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
			return NULL;
		}

		public function Fetch(&$query) {
			return pg_fetch_array($query);
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
			if($dbn == $this->dbName && $dbport == $this->dbPort && $dbh == $this->dbHost && $dbu == $this->dbUser
				&& $this->dbLink)
				return 1;
                        $this->dbName = $dbn;
                        $this->dbPort = $dbport;
                        $this->dbHost = $dbh;
                        $this->dbUser = $dbu;
                        $this->dbPass = $dbp;
			return 0;
                }

		public function BeginTr() {
			pg_query("BEGIN;");
		}

		public function CommitTr() {
			pg_query("COMMIT;");
		}

		public function RollbackTr() {
			pg_query("ROLLBACK;");
		}

		private $dbName;
		private $dbPort;
		private $dbHost;
		private $dbPass;
		private $dbUser;
		private $dbLink;
	};
?>
