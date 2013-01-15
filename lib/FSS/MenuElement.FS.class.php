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

	require_once("NamedObject.FS.class.php");
	class MenuElement extends NamedObject {
		function MenuElement() {}

		public function Load() {
			$query = FS::$dbMgr->Select("z_eye_menu_items","id,title,link,isconnected","id = '".$this->id."'");
			if($data = pg_fetch_array($query)) {
				$this->name = $data["title"];
				$this->link = $data["link"];
				$this->isconnected = $data["isconnected"];
			}
		}

		public function Create() {
			$id = FS::$dbMgr->GetMax("z_eye_menu_items","id")+1;
			FS::$dbMgr->Insert("z_eye_menu_items","id,title,link,isconnected","'".$id."','".$this->name."','".$this->link."','".$this->isconnected."'");
		}

		public function SaveToDB() {
			FS::$dbMgr->Update("z_eye_menu_items","title = '".$this->name."', link = '".$this->link."', isconnected = '".$this->isconnected."'","id = '".$this->id."'");
		}

		public function Delete() {
			FS::$dbMgr->Delete("z_eye_menu_items","id = '".$this->id."'");
			FS::$dbMgr->Delete("z_eye_menu_link","id_menu_item = '".$this->id."'");
		}

		public function CreateSelect($idsel = 0) {
			$output = FS::$iMgr->select("m_elem_id");
			$query = FS::$dbMgr->Select("z_eye_menu_items","id");
			while($data = pg_fetch_array($query)) {
				$this->id = $data["id"];
				$this->Load();
				$output .= FS::$iMgr->selElmt($this->getName(),$data["id"], $idsel > 0 && $idsel == $data["id"] ? true : false);
			}
			$output .= "</select>";
			return $output;
		}

		public function setLink($link) { $this->link = $link; }
		public function setConn($conn) { $this->isconnected = $conn; }
		public function getLink() { return $this->link; }
		public function getConnected() { return $this->isconnected; }

		private $link;
		private $isconnected;

	};

?>
