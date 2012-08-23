<?php
        /*
        * Copyright (c) 2012, Loïc BLOT, CNRS
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

	require_once("NamedObject.FS.class.php");
	class Menu extends NamedObject {
		function Menu() {}

		public function Load() {
			$query = FS::$pgdbMgr->Select("z_eye_menus","name,isconnected","id = '".$this->id."'");
			if($data = pg_fetch_array($query)) {
				$this->name = $data["name"];
				$this->isconnected = $data["isconnected"];
			}
		}

		public function Create() {
			$id = FS::$pgdbMgr->GetMax("z_eye_menus","id")+1;
			FS::$pgdbMgr->Insert("z_eye_menus","id,name,isconnected","'".$id."','".$this->name."','".$this->isconnected."'");
		}

		public function SaveToDB() {
			FS::$pgdbMgr->Update("z_eye_menus","name = '".$this->name."', isconnected = '".$this->isconnected."'","id = '".$this->id."'");
		}

		public function Delete() {
			FS::$pgdbMgr->Delete("z_eye_menus","id = '".$this->id."'");
		}

		public function getConnected() { return $this->isconnected; }
		public function setConnected($conn) { $this->isconnected = $conn; }
		private $isconnected;
	};
?>
