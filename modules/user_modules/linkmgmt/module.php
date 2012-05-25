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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iLinkMgmt extends genModule{
		function iLinkMgmt() { parent::genModule(); }
		
		public function Load() {
			$output = "";
			if($do = FS::$secMgr->checkGetData("do")) {
				if($do == 1)
					$output .= $this->showLinkForm();
				else
					$output .= $this->showLinkForm(true);
			}
			else {
				$output .= "<div id=\"monoComponent\">
					<h3>Gestion des liens</h3>";
					$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=1\">Nouveau lien</a>
					<table class=\"standardTable\" width=\"55%\">
					<tr><th width=\"40px\">Id</th><th width=\"90px\"><center>Type</center></th><th><center>Args</center></th><th width=\"15px\"></th></tr>";
					$query = FS::$dbMgr->Select("fss_http_links","id,type,args","","id",1);
					while($data = mysql_fetch_array($query)) {
						$output .= "<tr><td><center><a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=2&link=".$data["id"]."\">".$data["id"]."</a></center></td><td><center>";
						if($data["type"] == 0)
							$output .= "Normal";
						else if($data["type"] == 1)
							$output .= "Action";
						else if($data["type"] == 2)
							$output .= "Module";
						else if($data["type"] == 3)
							$output .= "JavaScript";
						else if($data["type"] == 4)
							$output .= "Rewrite Module";
						else
							$output .= "Rewrite Autres";
						$output .= "</center></td><td><center>".$data["args"]."</center></td>";
						$output .= "<td><a href=\"index.php?mod=".$this->mid."&act=3&link=".$data["id"]."\">";
						$output .= FS::$iMgr->addImage("styles/images/cross.png",15,15);
						$output .= "</a></td></tr>";
					}
					$output .= "</table></div>";				
			}
			return $output;
		}
		
		public function showLinkForm($edit = false) {
			$output = "<div id=\"monoComponent\">
				<h3>";
			$output .= $edit ? "Edition d'un lien" : "Création d'un lien";
			$output .= "</h3>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".($edit ? 2 : 1));
			$lnk = NULL;
			if($edit) {
				$lid = FS::$secMgr->checkGetData("link");
				FS::$secMgr->SecuriseStringForDB($lid);
				$output .= FS::$iMgr->addHidden("link_id",$lid);
				$lnk = new HTTPLink($lid);
				$lnk->Load();
			}
			
			$output .= "Type ";
			$output .= FS::$iMgr->addList("type");
			$output .= FS::$iMgr->addElementToList("Normal",0,($lnk && $lnk->getType() == 0) ? true : false);
			$output .= FS::$iMgr->addElementToList("Action",1,($lnk && $lnk->getType() == 1) ? true : false);
			$output .= FS::$iMgr->addElementToList("Module",2,($lnk && $lnk->getType() == 2) ? true : false);
			$output .= FS::$iMgr->addElementToList("JavaScript",3,($lnk && $lnk->getType() == 3) ? true : false);	
			$output .= FS::$iMgr->addElementToList("Rewrite Module",4,($lnk && $lnk->getType() == 4) ? true : false);
			$output .= FS::$iMgr->addElementToList("Rewrite Autres",5,($lnk && $lnk->getType() == 5) ? true : false);		
			$output .= "</select><br />Arguments ";
			
			$output .= FS::$iMgr->addInput("args",$lnk ? $lnk->getArgs() : "",25,130);
			$output .= "<hr>";
			$output .= FS::$iMgr->addSubmit("reg","Enregistrer");
			$output .= "</form></div>";
			return $output;
		}
		
		public function RegisterLink() {
			$link = new HTTPLink(0);
			FS::$secMgr->SecuriseStringForDB($_POST["args"]);
			FS::$secMgr->SecuriseStringForDB($_POST["type"]);
			$link->setArgs($_POST["args"]);
			$link->setType($_POST["type"]);
			$link->Create();
		}
		
		public function EditLink() {
			FS::$secMgr->SecuriseStringForDB($_POST["link_id"]);
			FS::$secMgr->SecuriseStringForDB($_POST["args"]);
			FS::$secMgr->SecuriseStringForDB($_POST["type"]);
			$link = new HTTPLink($_POST["link_id"]);
			$link->setArgs($_POST["args"]);
			$link->setType($_POST["type"]);
			$link->SaveToDB();
		}
		
		public function RemoveLink() {
			FS::$secMgr->SecuriseStringForDB($_GET["link"]);
			$link = new HTTPLink($_GET["link"]);
			$link->Delete();
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1: // new
					$this->RegisterLink();
					header("Location: m-".$this->mid.".html");
					break;
				case 2: // edit
					$this->EditLink();
					header("Location: m-".$this->mid.".html");
					break;
				case 3: // del	
					$this->RemoveLink();
					header("Location: m-".$this->mid.".html");
					break;
				default: break;
			}
		}
	};
?>
