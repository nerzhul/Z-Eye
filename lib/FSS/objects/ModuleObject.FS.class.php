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

	abstract class FSMObj {
		function __construct() {
			$this->loc = FS::$iMgr->getLocales();
			$this->mod = FS::$iMgr->getCurModule();
			$this->mid = $this->mod->getModuleId();
			$this->readRight = "";
			$this->writeRight = "";
		}

		public function renderAll() { return ""; }
		public function showForm() { return ""; }

		protected function Load($id = "") {}
		protected function removeFromDB($id) {}

		protected function Modify() {}
		protected function Remove() {}

		protected function canRead() {
			return FS::$sessMgr->hasRight($this->readRight);
		}

		protected function canWrite() {
			return FS::$sessMgr->hasRight($this->writeRight);
		}

		protected function exists($id) {
			if (FS::$dbMgr->GetOneData($this->sqlTable,$this->sqlAttrId,$this->sqlAttrId." = '".$id."'")) {
				return true;
			}
			return false;
		}

		protected $loc;
		protected $mod;
		protected $mid;

		// SQL
		protected $sqlTable;
		protected $sqlAttrId;

		protected $readRight;
		protected $writeRight;

		// HTMLtableMgr
		protected $tMgr;

		// Error messages
		protected $errNotExists;
		protected $errAlreadyExists;
	};
?>
