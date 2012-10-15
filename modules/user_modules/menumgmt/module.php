<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/Menu.FS.class.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/MenuElement.FS.class.php");
	
	class iMenuMgmt extends genModule{
		function iMenuMgmt() { parent::genModule(); $this->loc = new lLogs(); }
		public function Load() {
			$output = "";
			if($do = FS::$secMgr->checkGetData("do")) {
				if($do == 1)
					$output .= $this->showMenuForm();
				else if($do == 5) // form élément
					$output .= $this->showMenuElmForm(true);
				else if($do == 4) // form élément add
					$output .= $this->showMenuElmForm();
				else
					$output .= $this->showMenuForm(true);
			}
			else {
			$output .= "<h3>".$this->loc->s("title-menu-mgmt")."</h3>";
					$output .= "<a href=\"index.php?mod=".$this->mid."&do=1\">".$this->loc->s("New-Menu")."</a>
					<table class=\"standardTable\">
					<tr><th width=\"20px\">Id</th><th width=\"200px\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Connected")."</th><th></th><th></th></tr>";
					$query = FS::$pgdbMgr->Select("z_eye_menus","id,name,isconnected","","id",2);
					while($data = pg_fetch_array($query)) {
						$output .= "<tr><td>".$data["id"]."</td><td>".$data["name"]."</td><td>";
						if($data["isconnected"] == -1)
							$output .= $this->loc->s("No");
						else if($data["isconnected"] == 1)
							$output .= $this->loc->s("Yes");
						else
							$output .= $this->loc->s("Both");						
						$output .= "</td><td><a href=\"index.php?mod=".$this->mid."&do=2&menu=".$data["id"]."\">";
						$output .= FS::$iMgr->img("styles/images/pencil.gif",15,15);
						$output .= "</a></td><td><a href=\"index.php?mod=".$this->mid."&act=3&menu=".$data["id"]."\">";
						$output .= FS::$iMgr->img("styles/images/cross.png",15,15);
						$output .= "</a></td></tr>";
					}
					
					$output .= "</table>
					<h3>".$this->loc->s("title-menu-node-mgmt")."</h3>
					<a href=\"index.php?mod=".$this->mid."&do=4\">".$this->loc->s("New-menu-elmt")."</a>
					<table class=\"standardTable\">
					<tr><th width=\"20px\">Id</th><th width=\"200px\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Link")."</th><th>".$this->loc->s("Connected")."</th><th></th><th></th></tr>";
					$query = FS::$pgdbMgr->Select("z_eye_menu_items","id,title,link,isconnected","","id",2);
					while($data = pg_fetch_array($query)) {
						$output .= "<tr><td>".$data["id"]."</td><td>".$data["title"]."</td><td>";
						$link2 = new HTTPLink($data["link"]);
						$output .= $link2->getIt()."</td><td>";
						if($data["isconnected"] == -1)
							$output .= $this->loc->s("No");
						else if($data["isconnected"] == 1)
							$output .= $this->loc->s("Yes");
						else
							$output .= $this->loc->s("Both");						
						$output .= "</td><td><a href=\"index.php?mod=".$this->mid."&do=5&im=".$data["id"]."\">";
						$output .= FS::$iMgr->img("styles/images/pencil.gif",15,15);
						$output .= "</a></td><td><a href=\"index.php?mod=".$this->mid."&act=6&im=".$data["id"]."\">";
						$output .= FS::$iMgr->img("styles/images/cross.png",15,15);
						$output .= "</a></td></tr>";
					}
					$output .= "</table>";				
			}
			return $output;
		}
		
		public function showMenuElmForm($edit = false) {
			$output = "<h4>";
			$output .= $edit ? $this->loc->s("elmt-edit") : $this->loc->s("elmt-create");
			$output .= "</h4>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".($edit ? 5 : 4));
			$menuEl = NULL;
			if($edit) {
				$meid = FS::$secMgr->checkGetData("im");
				FS::$secMgr->SecuriseStringForDB($meid);
				$output .= FS::$iMgr->addHidden("menu_elmt",$meid);
				$menuEl = new MenuElement();
				$menuEl->setId($meid);
				$menuEl->Load();
			}
			$output .= $this->loc->s("Name")." ";
			$output .= FS::$iMgr->input("name",$menuEl ? $menuEl->getName() : "");
			$output .= "<hr>".$this->loc->s("Link")." ";
			$link = new HTTPLink(0);
			$output .= $link->CreateSelect($menuEl ? $menuEl->getLink() : 0);
			$output .= "<hr>".$this->loc->s("Connected")." ? ";
			$output .= FS::$iMgr->addList("isconnected");
			$output .= FS::$iMgr->addElementToList($this->loc->s("No"),-1,$menuEl && $menuEl->getConnected() == -1 ? true : false);
			$output .= FS::$iMgr->addElementToList($this->loc->s("Yes"),1,$menuEl && $menuEl->getConnected() == 1 ? true : false);
			$output .= FS::$iMgr->addElementToList($this->loc->s("Both"),0,$menuEl && $menuEl->getConnected() == 0 ? true : false);
			$output .= "</select><hr>";
			$output .= FS::$iMgr->submit("",$this->loc->s("Save"));
			$output .= "</form>";
			return $output;
		}
		
		public function showMenuForm($edit = false) {
			$output = "<h3>";
			$output .= $edit ? $this->loc->s("menu-edit") : $this->loc->s("menu-create");
			$output .= "</h3>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".($edit ? 2 : 1));
			$menu = NULL;
			if($edit) {
				$mid = FS::$secMgr->checkAndSecuriseGetData("menu");
				$output .= FS::$iMgr->addHidden("menu_id",$mid);
				$menu = new Menu();
				$menu->setId($mid);
				$menu->Load();
			}
			$output .= $this->loc->s("Name")." ";
			$output .= FS::$iMgr->input("name",$menu ? $menu->getName() : "");
			$output .= "<hr>".$this->loc->s("Connected")." ? ";
			$output .= FS::$iMgr->addList("isconnected");
			$output .= FS::$iMgr->addElementToList($this->loc->s("No"),-1,$menu && $menu->getConnected() == -1 ? true : false);
			$output .= FS::$iMgr->addElementToList($this->loc->s("Yes"),1,$menu && $menu->getConnected() == 1 ? true : false);
			$output .= FS::$iMgr->addElementToList($this->loc->s("Both"),0,$menu && $menu->getConnected() == 0 ? true : false);
			$output .= "</select>";
			$output .= "<hr>";
			$output .= FS::$iMgr->submit("",$this->loc->s("Save"));
			$output .= "</form>";
			
			if($edit) {
				$output .= "<h3>".$this->loc->s("title-menu-node-mgmt")."</h3>
				<h4>".$this->loc->s("add-elmt")."</h4>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=7");
				$output .= "<center>".$this->loc->s("elmt")." ";
				$menuEl = new MenuElement();
				$output .= $menuEl->CreateSelect();
				$output .= " Place ";
				$output .= FS::$iMgr->input("order","0",2,2);
				$output .= FS::$iMgr->addHidden("menu",$mid);
				$output .= FS::$iMgr->submit("",$this->loc->s("Save"));				
				$output .= "</center></form>
				<h4>".$this->loc->s("mod-elmt")."</h4>
				<table class=\"standardTable\">
				<tr><th>".$this->loc->s("elmt")."</th><th>".$this->loc->s("Order")."</th><th></th></tr>";
				$query = FS::$pgdbMgr->Select("z_eye_menu_link","id_menu_item,\"order\"","id_menu = '".$mid."'","\"order\"");
				while($data = pg_fetch_array($query)) {
					$query2 = FS::$pgdbMgr->Select("z_eye_menu_items","id,title","id = '".$data["id_menu_item"]."'");
					if($data2 = pg_fetch_array($query2)) {
							$output .= "<tr><td>".$data2["title"]."</td><td>".$data["order"]."</td><td>
							<a href=\"index.php?mod=".$this->mid."&act=8&menu=".$mid."&elem=".$data2["id"]."\">";
							$output .= FS::$iMgr->img("styles/images/cross.png",15,15);
							$output .= "</a></td></tr>";
					}
				}
			}
			$output .= "</table>";
			return $output;
		}
		
		public function RegisterMenu() {
			$menu = new Menu();
			FS::$secMgr->SecuriseStringForDB($_POST["name"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			$menu->setName($_POST["name"]);
			$menu->setConnected($_POST["isconnected"]);
			$menu->Create();
		}
		
		public function EditMenu() {
			$menu = new Menu();
			FS::$secMgr->SecuriseStringForDB($_POST["menu_id"]);
			FS::$secMgr->SecuriseStringForDB($_POST["name"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			$menu->setId($_POST["menu_id"]);
			$menu->Load();
			$menu->setName($_POST["name"]);
			$menu->setConnected($_POST["isconnected"]);
			$menu->SaveToDB();
		}
		
		public function RemoveMenu() {
			$menu = new Menu();
			FS::$secMgr->SecuriseStringForDB($_GET["menu"]);
			$menu->setId($_GET["menu"]);
			$menu->Delete();
		}
		
		public function addMenuElement() {
			$menuEl = new MenuElement();
			FS::$secMgr->SecuriseStringForDB($_POST["name"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			FS::$secMgr->SecuriseStringForDB($_POST["link_id"]);
			$menuEl->setName($_POST["name"]);
			$menuEl->setConn($_POST["isconnected"]);
			$menuEl->setLink($_POST["link_id"]);
			$menuEl->Create();
		}
		
		public function EditMenuElement() {
			$menuEl = new MenuElement();
			FS::$secMgr->SecuriseStringForDB($_POST["name"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			FS::$secMgr->SecuriseStringForDB($_POST["link_id"]);
			$menuEl->setId($_POST["menu_elmt"]);
			$menuEl->Load();
			$menuEl->setName($_POST["name"]);
			$menuEl->setConn($_POST["isconnected"]);
			$menuEl->setLink($_POST["link_id"]);
			$menuEl->SaveToDB();
		}
		
		public function RemoveMenuElement() {
			$menuEl = new MenuElement();
			$menuEl->setId(FS::$secMgr->checkGetData("im"));
			$menuEl->Load();
			$menuEl->Delete();
		}
		
		public function addElementToMenu() {
			FS::$secMgr->SecuriseStringForDB($_POST["menu"]);
			FS::$secMgr->SecuriseStringForDB($_POST["order"]);
			FS::$secMgr->SecuriseStringForDB($_POST["m_elem_id"]);
			FS::$pgdbMgr->Insert("z_eye_menu_link","id_menu,id_menu_item,\"order\"","'".$_POST["menu"]."','".$_POST["m_elem_id"]."','".$_POST["order"]."'");
		}
		
		public function RemoveElementFromMenu() {
			$menuid = FS::$secMgr->checkGetData("menu");
			$itemid = FS::$secMgr->checkGetData("elem");
			FS::$secMgr->SecuriseStringForDB($menuid);
			FS::$secMgr->SecuriseStringForDB($itemid);
			FS::$pgdbMgr->Delete("z_eye_menu_link","id_menu = '".$menuid."' AND id_menu_item = '".$itemid."'");
			
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1: // new
					$this->RegisterMenu();
					header("Location: m-".$this->mid.".html");
					break;
				case 2: // edit
					$this->EditMenu();
					header("Location: m-".$this->mid.".html");
					break;
				case 3: // del	
					$this->RemoveMenu();
					header("Location: m-".$this->mid.".html");
					break;
				case 4: // add elm
					$this->addMenuElement();
					header("Location: m-".$this->mid.".html");
					break;
				case 5: // edit elem
					$this->EditMenuElement();
					header("Location: m-".$this->mid.".html");
					break;
				case 6: // del elem
					$this->RemoveMenuElement();
					header("Location: m-".$this->mid.".html");
					break;
				case 7: // add elmtomenu
					$this->addElementToMenu();
					header("Location: m-".$this->mid.".html");
					break;								
				case 8: // del elmtomenu
					$this->RemoveElementFromMenu();
					header("Location: m-".$this->mid.".html");
					break;
				default: break;
			}
		}
	};
?>
