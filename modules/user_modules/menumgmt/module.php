<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/Menu.FS.class.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/MenuElement.FS.class.php");
	class iMenuMgmt extends genModule{
		function iMenuMgmt() { parent::genModule(); }
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
				$output .= "<div id=\"monoComponent\">
					<h3>Gestion des menus</h3>";
					$output .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=1\">Nouveau menu</a>
					<table class=\"standardTable\">
					<tr><th width=\"20px\">Id</th><th width=\"200px\">Nom</th><th>Accréditation</th><th>Connecté</th><th></th><th></th></tr>";
					$query = FS::$dbMgr->Select("fss_menus","id,name,ulevel,isconnected","","id",2);
					while($data = mysql_fetch_array($query)) {
						$output .= "<tr><td>".$data["id"]."</td><td>".$data["name"]."</td><td>".$data["ulevel"]."</td><td>";
						if($data["isconnected"] == -1)
							$output .= "Non";
						else if($data["isconnected"] == 1)
							$output .= "Oui";
						else
							$output .= "Les deux";						
						$output .= "</td><td><a href=\"index.php?mod=".$this->mid."&do=2&menu=".$data["id"]."\">";
						$output .= FS::$iMgr->addImage("styles/images/pencil.gif",15,15);
						$output .= "</a></td><td><a href=\"index.php?mod=".$this->mid."&act=3&menu=".$data["id"]."\">";
						$output .= FS::$iMgr->addImage("styles/images/cross.png",15,15);
						$output .= "</a></td></tr>";
					}
					
					$output .= "</table>
					<h3>Gestion des éléments de menu</h3>
					<a class=\"monoComponentt_a\" href=\"index.php?mod=".$this->mid."&do=4\">Nouvel élément de menu</a>
					<table class=\"standardTable\">
					<tr><th width=\"20px\">Id</th><th width=\"200px\">Nom</th><th>Lien</th><th>Accréditation</th><th>Connecté</th><th></th><th></th></tr>";
					$query = FS::$dbMgr->Select("fss_menu_items","id,title,link,ulevel,isconnected","","id",2);
					while($data = mysql_fetch_array($query)) {
						$output .= "<tr><td>".$data["id"]."</td><td>".$data["title"]."</td><td>";
						$link2 = new HTTPLink($data["link"]);
						$output .= $link2->getIt()."</td><td>".$data["ulevel"]."</td><td>";
						if($data["isconnected"] == -1)
							$output .= "Non";
						else if($data["isconnected"] == 1)
							$output .= "Oui";
						else
							$output .= "Les deux";						
						$output .= "</td><td><a href=\"index.php?mod=".$this->mid."&do=5&im=".$data["id"]."\">";
						$output .= FS::$iMgr->addImage("styles/images/pencil.gif",15,15);
						$output .= "</a></td><td><a href=\"index.php?mod=".$this->mid."&act=6&im=".$data["id"]."\">";
						$output .= FS::$iMgr->addImage("styles/images/cross.png",15,15);
						$output .= "</a></td></tr>";
					}
					$output .= "</table></div>";				
			}
			return $output;
		}
		
		public function showMenuElmForm($edit = false) {
			$output = "<div id=\"monoComponent\"><h4>";
			$output .= $edit ? "Edition de l'élément" : "Création d'un élément";
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
			$output .= "Nom ";
			$output .= FS::$iMgr->addInput("name",$menuEl ? $menuEl->getName() : "");
			$output .= "<hr>Lien ";
			$link = new HTTPLink(0);
			$output .= $link->CreateSelect($menuEl ? $menuEl->getLink() : 0);
			$output .= "<hr>Accréditation ";
			$output .= FS::$iMgr->addInput("ulevel",$menuEl ? $menuEl->getUlevel() : "");
			$output .= "<hr>Connecté ? ";
			$output .= FS::$iMgr->addList("isconnected");
			$output .= FS::$iMgr->addElementToList("Non",-1,$menuEl && $menuEl->getConnected() == -1 ? true : false);
			$output .= FS::$iMgr->addElementToList("Oui",1,$menuEl && $menuEl->getConnected() == 1 ? true : false);
			$output .= FS::$iMgr->addElementToList("Les deux",0,$menuEl && $menuEl->getConnected() == 0 ? true : false);
			$output .= "</select><hr>";
			$output .= FS::$iMgr->addSubmit("reg","Enregistrer");
			$output .= "</form></div>";
			return $output;
		}
		
		public function showMenuForm($edit = false) {
			$output = "<div id=\"monoComponent\">
				<h3>";
			$output .= $edit ? "Edition du menu" : "Création d'un menu";
			$output .= "</h3>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=".($edit ? 2 : 1));
			$menu = NULL;
			if($edit) {
				$mid = FS::$secMgr->checkGetData("menu");
				FS::$secMgr->SecuriseStringForDB($mid);
				$output .= FS::$iMgr->addHidden("menu_id",$mid);
				$menu = new Menu();
				$menu->setId($mid);
				$menu->Load();
			}
			$output .= "Nom ";
			$output .= FS::$iMgr->addInput("name",$menu ? $menu->getName() : "");
			$output .= "<hr>Accréditation ";
			$output .= FS::$iMgr->addInput("ulevel",$menu ? $menu->getUlevel() : "");
			$output .= "<hr>Connecté ? ";
			$output .= FS::$iMgr->addList("isconnected");
			$output .= FS::$iMgr->addElementToList("Non",-1,$menu && $menu->getConnected() == -1 ? true : false);
			$output .= FS::$iMgr->addElementToList("Oui",1,$menu && $menu->getConnected() == 1 ? true : false);
			$output .= FS::$iMgr->addElementToList("Les deux",0,$menu && $menu->getConnected() == 0 ? true : false);
			$output .= "</select>";
			$output .= "<hr>";
			$output .= FS::$iMgr->addSubmit("reg","Enregistrer");
			$output .= "</form>";
			
			if($edit) {
				$output .= "<h3>Gestion des éléments de ce menu</h3>
				<h4>Ajouter un élément</h4>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=7");
				$output .= "<center>Elément ";
				$menuEl = new MenuElement();
				$output .= $menuEl->CreateSelect();
				$output .= " Place ";
				$output .= FS::$iMgr->addInput("order","0",2,2);
				$output .= FS::$iMgr->addHidden("menu",$mid);
				$output .= FS::$iMgr->addSubmit("reg","Enregistrer");				
				$output .= "</center></form>
				<h4>Modifier les éléments</h4>
				<table class=\"standardTable\">
				<tr><th>Elément</th><th>Ordre</th><th></th></tr>";
				$query = FS::$dbMgr->Select("fss_menu_link","id_menu_item,`order`","id_menu = '".$mid."'","order");
				while($data = mysql_fetch_array($query)) {
					$query2 = FS::$dbMgr->Select("fss_menu_items","id,title","id = '".$data["id_menu_item"]."'");
					if($data2 = mysql_fetch_array($query2)) {
							$output .= "<tr><td>".$data2["title"]."</td><td>".$data["order"]."</td><td>
							<a href=\"index.php?mod=".$this->mid."&act=8&menu=".$mid."&elem=".$data2["id"]."\">";
							$output .= FS::$iMgr->addImage("styles/images/cross.png",15,15);
							$output .= "</a></td></tr>";
					}
				}
			}
			$output .= "</table></div>";
			return $output;
		}
		
		public function RegisterMenu() {
			$menu = new Menu();
			FS::$secMgr->SecuriseStringForDB($_POST["name"]);
			FS::$secMgr->SecuriseStringForDB($_POST["ulevel"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			$menu->setName($_POST["name"]);
			$menu->setUlevel($_POST["ulevel"]);
			$menu->setConnected($_POST["isconnected"]);
			$menu->Create();
		}
		
		public function EditMenu() {
			$menu = new Menu();
			FS::$secMgr->SecuriseStringForDB($_POST["menu_id"]);
			FS::$secMgr->SecuriseStringForDB($_POST["name"]);
			FS::$secMgr->SecuriseStringForDB($_POST["ulevel"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			$menu->setId($_POST["menu_id"]);
			$menu->Load();
			$menu->setName($_POST["name"]);
			$menu->setUlevel($_POST["ulevel"]);
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
			FS::$secMgr->SecuriseStringForDB($_POST["ulevel"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			FS::$secMgr->SecuriseStringForDB($_POST["link_id"]);
			$menuEl->setName($_POST["name"]);
			$menuEl->setULevel($_POST["ulevel"]);
			$menuEl->setConn($_POST["isconnected"]);
			$menuEl->setLink($_POST["link_id"]);
			$menuEl->Create();
		}
		
		public function EditMenuElement() {
			$menuEl = new MenuElement();
			FS::$secMgr->SecuriseStringForDB($_POST["name"]);
			FS::$secMgr->SecuriseStringForDB($_POST["ulevel"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			FS::$secMgr->SecuriseStringForDB($_POST["link_id"]);
			$menuEl->setId($_POST["menu_elmt"]);
			$menuEl->Load();
			$menuEl->setName($_POST["name"]);
			$menuEl->setULevel($_POST["ulevel"]);
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
			FS::$dbMgr->Insert("fss_menu_link","id_menu,id_menu_item,`order`","'".$_POST["menu"]."','".$_POST["m_elem_id"]."','".$_POST["order"]."'");
		}
		
		public function RemoveElementFromMenu() {
			$menuid = FS::$secMgr->checkGetData("menu");
			$itemid = FS::$secMgr->checkGetData("elem");
			FS::$secMgr->SecuriseStringForDB($menuid);
			FS::$secMgr->SecuriseStringForDB($itemid);
			FS::$dbMgr->Delete("fss_menu_link","id_menu = '".$menuid."' AND id_menu_item = '".$itemid."'");
			
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