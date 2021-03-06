<?php
        /*
        * Copyright (c) 2010-2014, Loïc BLOT, CNRS <http://www.unix-experience.fr>
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

	class AbstractSQLMgr {
		function __construct() {
			$this->dbDriver = "";
			$this->dbName = "";
			$this->dbPort = "";
			$this->dbHost = "";
			$this->dbPass = "";
			$this->dbUser = "";
			$this->dbLink = null;
			$this->dbType = "";
			$this->sqlQuery = "";
			$this->PDO = null;
		}

		function __destruct() {
			$this->PDO = null;
		}
		public function initForZEye() {
			$this->setConfig("pg",PGDbConfig::getDbName(),PGDbConfig::getDbPort(),PGDbConfig::getDbHost(),PGDbConfig::getDbUser(),
				PGDbConfig::getDbPwd());
		}

		public function Connect() {
			try {
				$this->PDO = new PDO($this->dbDriver.":dbname=".$this->dbName.";host=".$this->dbHost.";port=".$this->dbPort,$this->dbUser,
					$this->dbPass);
				return $this->PDO;
			}
			catch (PDOException $e) {
				echo $e."<br />";
				return NULL;
			}
		}

		public function Select($table,$fields,$cond = "",$options = array()) {
			$sql = sprintf("SELECT %s FROM %s",$fields,$table);
			
			// Where clause
			if (strlen($cond) > 0) {
				$sql = sprintf("%s WHERE %s",$sql,$cond);
			}
			
			// Group By clause
			if (isset($options["group"]) && strlen($options["group"]) > 0) {
				$sql = sprintf("%s GROUP BY %s",$sql,$options["group"]);
			}
			
			// Order cause
			if (isset($options["order"]) && strlen($options["order"]) > 0) {
				$sql = sprintf("%s ORDER BY %s",$sql,$options["order"]);
				
				// Desc/Asc clause
				if (isset($options["ordersens"])) {
					if ($options["ordersens"] == 1) {
						$sql = sprintf("%s DESC",$sql);
					}
					else if ($options["ordersens"] == 2) {
						$sql = sprintf("%s ASC",$sql);
					}
				}
			}
			
			// Limit clause
			if (isset($options["limit"]) && $options["limit"] > 0) {
				if (isset($options["startidx"]) && $options["startidx"] > 0) {
					$sql = sprintf("%s LIMIT %s,%s",$sql,$options["startidx"],($options["startidx"]+$options["limit"]));
				}
				else {
					$sql = sprintf("%s LIMIT %s",$sql,$options["limit"]);
				}
			}
			$this->sqlQuery = $sql;
			return $this->PDO->query($sql);
		}

		public function GetOneEntry($table,$fields,$cond = "",$options = array()) {
	
			// Force this limit
			$options["limit"] = 1;

			$query = $this->Select($table,$fields,$cond,$options);
			if ($data = $this->Fetch($query)) {
				return $data;
			}
			return NULL;
		}

		public function GetOneData($table,$field,$cond = "",$options = array()) {

			// Force this limit
			$options["limit"] = 1;

			$query = $this->Select($table,$field,$cond,$options);
			if ($data = $this->Fetch($query)) {
				$splstr = preg_split("#[\.]#",$field);
				$splstr = preg_replace("#`#","",$splstr);
				return $data[$splstr[count($splstr)-1]];
			}
			return NULL;
		}

		public function getArray($table,$field,$cond = "",$options = array()) {
			$results = array();

			$query = $this->Select($table,$field,$cond,$options);
			while ($data = $this->Fetch($query)) {
				$results[] = $data[$field];
			}
			return $results;
		}

		public function GetMax($table,$field,$cond = "") {
			$query = $this->Select($table,"MAX(".$field.") as mx",$cond);
			if ($data = $this->Fetch($query)) {
				$splstr = preg_split("#[\.]#",$field);
				$splstr = preg_replace("#`#","",$splstr);
				return $data["mx"];
			}
			return NULL;
		}

		public function GetMin($table,$field,$cond = "") {
			$query = $this->Select($table,"MIN(".$field.") as mn",$cond);
			if ($data = $this->Fetch($query)) {
				 $splstr = preg_split("#[\.]#",$field);
				 $splstr = preg_replace("#`#","",$splstr);
				 return $data["mn"];
			}
			return NULL;
		}

		public function Sum($table,$field,$cond = "") {
			$query = $this->Select($table,"SUM(".$field.") as mx",$cond);
			if ($data = $this->Fetch($query)) {
				$splstr = preg_split("#[\.]#",$field);
				$splstr = preg_replace("#`#","",$splstr);
				return $data["mx"];
			}
			return NULL;
		}

		public function Count($table,$field,$cond = "") {
			$query = $this->Select($table,"COUNT(".$field.") as ct",$cond);
			if ($data = $this->Fetch($query)) {
				$splstr = preg_split("#[\.]#",$field);
				$splstr = preg_replace("#`#","",$splstr);
				return $data["ct"];
			}
			return NULL;
		}

		public function Insert($table,$keys,$values) {
			$sql = sprintf("INSERT INTO %s(%s) VALUES (%s)",$table,$keys,$values);
			return $this->PDO->query($sql);
		}

		public function Fetch(&$query,$shErr=true) {
			if (!$query) {
				echo FS::$iMgr->printError(sprintf(_("err-sql-query-failed"),$this->sqlQuery),true);
				return NULL;
			}
			return $query->fetch();
		}

		public function Delete($table,$cond = "") {
			$sql = sprintf("DELETE FROM %s",$table);
			
			if (strlen($cond) > 0) {
				$sql = sprintf("%s WHERE %s",$sql,$cond);
			}
			$this->sqlQuery = $sql;

			return $this->PDO->query($sql);
		}

		public function Update($table,$mods,$cond = "") {
			$sql = sprintf("UPDATE %s SET %s",$table,$mods);
			
			if (strlen($cond) > 0) {
				$sql = sprintf("%s WHERE %s",$sql,$cond);
			}
			$this->sqlQuery = $sql;
			return $this->PDO->query($sql);
		}

		public function setConfig($dbtype,$dbn,$dbport,$dbh,$dbu,$dbp) {
			if ($dbn == $this->dbName && $dbport == $this->dbPort && $dbh == $this->dbHost && $dbu == $this->dbUser
				&& $this->dbLink && $this->dbType == $dbtype)
				return 1;
			if ($dbtype != "pg" && $dbtype != "my")
				return 2;
			if ($dbtype == "pg") {
				$this->dbDriver = "pgsql";
			}
			else if ($dbtype == "my") {
				$this->dbDriver = "mysql";
			}
			$this->dbName = $dbn;
			$this->dbPort = $dbport;
			$this->dbHost = $dbh;
			$this->dbUser = $dbu;
			$this->dbPass = $dbp;
			$this->dbType = $dbtype;
			return 0;
		}

		public function BeginTr() {
			return $this->PDO->beginTransaction();
		}

		public function CommitTr() {
			return $this->PDO->commit();
		}

		public function RollbackTr() {
			return $this->PDO->rollback();
		}

		public function isPDOOK() {
			return ($this->PDO != null);
		}

		private $dbDriver;
		private $dbName;
		private $dbPort;
		private $dbHost;
		private $dbPass;
		private $dbUser;
		private $dbLink;
		private $dbType;

		private $PDO;

		// Buffer for debug purposes
		private $sqlQuery;
	};
?>
