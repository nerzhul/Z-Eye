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

	require_once(dirname(__FILE__)."/PgSQLMgr.FS.class.php");
	require_once(dirname(__FILE__)."/MySQLMgr.FS.class.php");
	require_once(dirname(__FILE__)."/../../config/pgdb.conf.php");
	require_once(dirname(__FILE__)."/../../config/global.conf.php");
	class AbstractSQLMgr {
		function AbstractSQLMgr() {
			$this->dbName = "";
			$this->dbPort = "";
			$this->dbHost = "";
			$this->dbPass = "";
			$this->dbUser = "";
			$this->dbLink = NULL;
			$this->dbType = "";
		}

		public function initForZEye() {
			$this->setConfig("pg",PGDbConfig::getDbName(),PGDbConfig::getDbPort(),PGDbConfig::getDbHost(),PGDbConfig::getDbUser(),
                        	PGDbConfig::getDbPwd());
		}

		public function Connect() {
			return $this->dbMgr->Connect();
		}

		public function Select($table,$fields,$cond = "",$order = "",$ordersens = 0, $limit = 0, $startidx = 0) {
			return $this->dbMgr->Select($table,$fields,$cond,$order,$ordersens,$limit,$startidx);
		}

		public function GetOneData($table,$field,$cond = "",$order= "",$ordersens = 0, $limit = 0, $startidx = 0) {
			return $this->dbMgr->GetOneData($table,$field,$cond,$order,$ordersens,$limit,$startidx);
		}

		public function GetMax($table,$field,$cond = "") {
			return $this->dbMgr->GetMax($table,$field,$cond);
		}

		public function GetMin($table,$field,$cond = "") {
			return $this->dbMgr->GetMin($table,$field,$cond);
                }

		public function Sum($table,$field,$cond = "") {
			return $this->dbMgr->Sum($table,$field,$cond);
		}

		public function Count($table,$field,$cond = "") {
			return $this->dbMgr->Count($table,$field,$cond);
		}

		public function Insert($table,$keys,$values) {
			return $this->dbMgr->Insert($table,$keys,$values);
		}

		public function Fetch(&$query) {
			return $this->dbMgr->Fetch($query);
		}

		public function Delete($table,$cond = "") {
			return $this->dbMgr->Delete($table,$cond);
		}

		public function Update($table,$mods,$cond = "") {
			return $this->dbMgr->Update($table,$mods,$cond);
		}

		public function setConfig($dbtype,$dbn,$dbport,$dbh,$dbu,$dbp) {
			if($dbn == $this->dbName && $dbport == $this->dbPort && $dbh == $this->dbHost && $dbu == $this->dbUser
				&& $this->dbLink && $this->dbType == $dbtype)
				return 1;
			if($dbtype != "pg" && $dbtype != "my")
				return 2;
			if($dbtype == "pg")
				$this->dbMgr = new FSPostgreSQLMgr();
			else if($dbtype == "my")
				$this->dbMgr = new FSMySQLMgr();
			$this->dbMgr->setConfig($dbn,$dbport,$dbh,$dbu,$dbp);
                        $this->dbName = $dbn;
                        $this->dbPort = $dbport;
                        $this->dbHost = $dbh;
                        $this->dbUser = $dbu;
                        $this->dbPass = $dbp;
			$this->dbType = $dbtype;
			return 0;
                }

		public function BeginTr() {
			return $this->dbMgr->BeginTr();
		}

		public function CommitTr() {
			return $this->dbMgr->CommitTr();
		}

		public function RollbackTr() {
			return $this->dbMgr->RollbackTr();
		}

		private $dbName;
		private $dbPort;
		private $dbHost;
		private $dbPass;
		private $dbUser;
		private $dbLink;
		private $dbType;
		private $dbMgr;
	};
?>
