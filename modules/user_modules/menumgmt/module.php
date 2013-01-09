<?php
	/*
	* Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
		function iMenuMgmt() { parent::genModule(); $this->loc = new lMenuMgmt(); }
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
					$query = FS::$dbMgr->Select("z_eye_menus","id,name,isconnected","","id",2);
					while($data = FS::$dbMgr->Fetch($query)) {
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
					$query = FS::$dbMgr->Select("z_eye_menu_items","id,title,link,isconnected","","id",2);
					while($data = FS::$dbMgr->Fetch($query)) {
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
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=".($edit ? 5 : 4));
			$menuEl = NULL;
			if($edit) {
				$meid = FS::$secMgr->checkGetData("im");
				FS::$secMgr->SecuriseStringForDB($meid);
				$output .= FS::$iMgr->hidden("menu_elmt",$meid);
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
			$output .= FS::$iMgr->select("isconnected");
			$output .= FS::$iMgr->selElmt($this->loc->s("No"),-1,$menuEl && $menuEl->getConnected() == -1 ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("Yes"),1,$menuEl && $menuEl->getConnected() == 1 ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("Both"),0,$menuEl && $menuEl->getConnected() == 0 ? true : false);
			$output .= "</select><hr>";
			$output .= FS::$iMgr->submit("",$this->loc->s("Save"));
			$output .= "</form>";
			return $output;
		}
		
		public function showMenuForm($edit = false) {
			$output = "<h3>";
			$output .= $edit ? $this->loc->s("menu-edit") : $this->loc->s("menu-create");
			$output .= "</h3>";
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=".($edit ? 2 : 1));
			$menu = NULL;
			if($edit) {
				$mid = FS::$secMgr->checkAndSecuriseGetData("menu");
				$output .= FS::$iMgr->hidden("menu_id",$mid);
				$menu = new Menu();
				$menu->setId($mid);
				$menu->Load();
			}
			$output .= $this->loc->s("Name")." ";
			$output .= FS::$iMgr->input("name",$menu ? $menu->getName() : "");
			$output .= "<hr>".$this->loc->s("Connected")." ? ";
			$output .= FS::$iMgr->select("isconnected");
			$output .= FS::$iMgr->selElmt($this->loc->s("No"),-1,$menu && $menu->getConnected() == -1 ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("Yes"),1,$menu && $menu->getConnected() == 1 ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("Both"),0,$menu && $menu->getConnected() == 0 ? true : false);
			$output .= "</select>";
			$output .= "<hr>";
			$output .= FS::$iMgr->submit("",$this->loc->s("Save"));
			$output .= "</form>";
			
			if($edit) {
				$output .= "<h3>".$this->loc->s("title-menu-node-mgmt")."</h3>
				<h4>".$this->loc->s("add-elmt")."</h4>";
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=7");
				$output .= "<center>".$this->loc->s("elmt")." ";
				$menuEl = new MenuElement();
				$output .= $menuEl->CreateSelect();
				$output .= " Place ";
				$output .= FS::$iMgr->input("order","0",2,2);
				$output .= FS::$iMgr->hidden("menu",$mid);
				$output .= FS::$iMgr->submit("",$this->loc->s("Save"));				
				$output .= "</center></form>
				<h4>".$this->loc->s("mod-elmt")."</h4>
				<table class=\"standardTable\">
				<tr><th>".$this->loc->s("elmt")."</th><th>".$this->loc->s("Order")."</th><th></th></tr>";
				$query = FS::$dbMgr->Select("z_eye_menu_link","id_menu_item,\"order\"","id_menu = '".$mid."'","\"order\"");
				while($data = FS::$dbMgr->Fetch($query)) {
					$query2 = FS::$dbMgr->Select("z_eye_menu_items","id,title","id = '".$data["id_menu_item"]."'");
					if($data2 = FS::$dbMgr->Fetch($query2)) {
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
			$name = FS::$secMgr->checkAndSecurisePostData("name");
			$isco = FS::$secMgr->checkAndSecurisePostData("isconnected");
			$menu->setName($name);
			$menu->setConnected($isco);
			$menu->Create();
			FS::$log->i(FS::$sessMgr->getUserName(),"menumgmt",0,"Menu '".$name."' added");
		}
		
		public function EditMenu() {
			$menu = new Menu();
			$menuid = FS::$secMgr->checkAndSecurisePostData("menu_id");
			$name = FS::$secMgr->checkAndSecurisePostData("name");
			$isco = FS::$secMgr->checkAndSecurisePostData("isconnected");
			$menu->setId($menuid);
			$menu->Load();
			$menu->setName($name);
			$menu->setConnected($isco);
			$menu->SaveToDB();
			FS::$log->i(FS::$sessMgr->getUserName(),"menumgmt",0,"Menu '".$name."' (".$menuid.") edited (connected: ".$isco.")");
		}
		
		public function RemoveMenu() {
			$menu = new Menu();
			$name = FS::$secMgr->checkAndSecuriseGetData("menu");
			$menu->setId($name);
			$menu->Delete();
			FS::$log->i(FS::$sessMgr->getUserName(),"menumgmt",0,"Menu '".$name."' removed");
		}
		
		public function addMenuElement() {
			$menuEl = new MenuElement();
			$name = FS::$secMgr->checkAndSecurisePostData("name");
			$isco = FS::$secMgr->checkAndSecurisePostData("isconnected");
			$lid = FS::$secMgr->checkAndSecurisePostData("link_id");
			$menuEl->setName($name);
			$menuEl->setConn($isco);
			$menuEl->setLink($lid);
			$menuEl->Create();
			FS::$log->i(FS::$sessMgr->getUserName(),"menumgmt",0,"Create menu element '".$lid."' '".$name."' (isco: ".$isco.")");
		}
		
		public function EditMenuElement() {
			$menuEl = new MenuElement();
			$name = FS::$secMgr->checkAndSecurisePostData("name");
			$isco = FS::$secMgr->checkAndSecurisePostData("isconnected");
			$lid = FS::$secMgr->checkAndSecurisePostData("link_id");
			$menuEl->setId($_POST["menu_elmt"]);
			$menuEl->Load();
			$menuEl->setName($name);
			$menuEl->setConn($isco);
			$menuEl->setLink($lid);
			$menuEl->SaveToDB();
			FS::$log->i(FS::$sessMgr->getUserName(),"menumgmt",0,"Menu element '".$name."' edited (isco: ".$isco.")");
		}
		
		public function RemoveMenuElement() {
			$menuEl = new MenuElement();
			$im = FS::$secMgr->checkAndSecuriseGetData("im");
			$menuEl->setId($im);
			$menuEl->Load();
			$menuEl->Delete();
			FS::$log->i(FS::$sessMgr->getUserName(),"menumgmt",0,"Removed menu element id '".$im."'");
		}
		
		public function addElementToMenu() {
			$menu = FS::$secMgr->checkAndSecurisePostData("menu");
			$order = FS::$secMgr->checkAndSecurisePostData("order");
			$melemid = FS::$secMgr->checkAndSecurisePostData("m_elem_id");
			FS::$dbMgr->Insert("z_eye_menu_link","id_menu,id_menu_item,\"order\"","'".$menu."','".$melemid."','".$order."'");
			FS::$log->i(FS::$sessMgr->getUserName(),"menumgmt",0,"Added element ".$melemid." to menu ".$menu." (order: ".$order.")");
		}
		
		public function RemoveElementFromMenu() {
			$menuid = FS::$secMgr->checkAndSecuriseGetData("menu");
			$itemid = FS::$secMgr->checkAndSecuriseGetData("elem");
			FS::$dbMgr->Delete("z_eye_menu_link","id_menu = '".$menuid."' AND id_menu_item = '".$itemid."'");
			FS::$log->i(FS::$sessMgr->getUserName(),"menumgmt",0,"Removed element '".$itemid."' from menu '".$menuid."'");
			
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
