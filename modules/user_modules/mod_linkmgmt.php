<?php
	require_once(dirname(__FILE__)."/generic_module.php");
	class iLinkMgmt extends genModule{
		function iLinkMgmt($iMgr) { parent::genModule($iMgr); }
		
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
					$link = new HTTPLink(30);
					$output .= "<a class=\"monoComponentt_a\" href=\"".$link->getIt()."\">Nouveau lien</a>
					<table class=\"standardTable\">
					<tr><th width=\"20px\">Id</th><th width=\"200px\">Type</th><th>Args</th><th></th><th></th></tr>";
					$query = FS::$dbMgr->Select("fss_http_links","id,type,args","","id",1);
					while($data = mysql_fetch_array($query)) {
						$output .= "<tr><td>".$data["id"]."</td><td>";
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
							
						$output .= "</td><td>".$data["args"]."</td><td><a href=\"index.php?mod=16&do=2&link=".$data["id"]."\">";
						$output .= $this->iMgr->addImage("styles/images/pencil.gif",15,15);
						$output .= "</a></td><td><a href=\"index.php?mod=16&act=3&link=".$data["id"]."\">";
						$output .= $this->iMgr->addImage("styles/images/cross.png",15,15);
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
			$link = new HTTPLink($edit ? 26 : 25);
			$output .= $this->iMgr->addForm($link->getIt());
			$lnk = NULL;
			if($edit) {
				$lid = FS::$secMgr->checkGetData("link");
				FS::$secMgr->SecuriseStringForDB($lid);
				$output .= $this->iMgr->addHidden("link_id",$lid);
				$lnk = new HTTPLink($lid);
				$lnk->Load();
			}
			
			$output .= "Type ";
			$output .= $this->iMgr->addList("type");
			$output .= $this->iMgr->addElementToList("Normal",0,($lnk && $lnk->getType() == 0) ? true : false);
			$output .= $this->iMgr->addElementToList("Action",1,($lnk && $lnk->getType() == 1) ? true : false);
			$output .= $this->iMgr->addElementToList("Module",2,($lnk && $lnk->getType() == 2) ? true : false);
			$output .= $this->iMgr->addElementToList("JavaScript",3,($lnk && $lnk->getType() == 3) ? true : false);	
			$output .= $this->iMgr->addElementToList("Rewrite Module",4,($lnk && $lnk->getType() == 4) ? true : false);
			$output .= $this->iMgr->addElementToList("Rewrite Autres",5,($lnk && $lnk->getType() == 5) ? true : false);	
			$output .= "</select><hr>Arguments ";
			
			$output .= $this->iMgr->addInput("args",$lnk ? $lnk->getArgs() : "",25,130);
			$output .= "<hr>";
			$output .= $this->iMgr->addSubmit("reg","Enregistrer");
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
	};
?>
