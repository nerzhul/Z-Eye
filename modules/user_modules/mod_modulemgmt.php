<?php
	require_once(dirname(__FILE__)."/generic_module.php");
	require_once(dirname(__FILE__)."/../Module.class.php");
	class iModuleMgmt extends genModule{
		function iModuleMgmt($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "";
			if($do = FS::$secMgr->checkAndSecuriseGetData("do")) {
				if($do == 1)
					$output .= $this->showModuleForm();
				else
					$output .= $this->showModuleForm(true);
			}
			else {
				$output .= "<div id=\"monoComponent\">
					<h3>Gestion des modules</h3>";
					$link = new HTTPLink(31);
					$output .= "<a class=\"monoComponentt_a\" href=\"".$link->getIt()."\">Nouveau module</a>
					<table class=\"standardTable\">
					<tr><th width=\"20px\">Id</th><th width=\"200px\">Name</th><th>Path</th><th>Level</th><th>Connecté</th><th></th><th></th></tr>";
					$query = FS::$dbMgr->Select("fss_modules","id,name,path,ulevel,isconnected","","id",2);
					while($data = mysql_fetch_array($query)) {
						$output .= "<tr><td>".$data["id"]."</td><td>".$data["name"]."</td><td>".$data["path"]."</td><td>".$data["ulevel"]."</td><td>";
						if($data["isconnected"] == -1)
							$output .= "Non";
						else if($data["isconnected"] == 1)
							$output .= "Oui";
						else
							$output .= "Les deux";						
						$output .= "</td><td><a href=\"index.php?mod=17&do=2&modl=".$data["id"]."\">";
						$output .= $this->iMgr->addImage("styles/images/pencil.gif",15,15);
						$output .= "</a></td><td><a href=\"index.php?mod=17&act=3&modl=".$data["id"]."\">";
						$output .= $this->iMgr->addImage("styles/images/cross.png",15,15);
						$output .= "</a></td></tr>";
					}
					
				$output .= "</table></div>";				
			}
			return $output;
		}
		
		public function showModuleForm($edit = false) {
			$output = "<div id=\"monoComponent\">
				<h3>";
			$output .= $edit ? "Edition du module" : "Création d'un module";
			$output .= "</h3>";
			$link = new HTTPLink($edit ? 34 : 33);
			$output .= $this->iMgr->addForm($link->getIt());
			$moduleO = NULL;
			if($edit) {
				$modid = FS::$secMgr->checkAndSecuriseGetData("modl");
				$output .= $this->iMgr->addHidden("module_id",$modid);
				$moduleO = new Module();
				$moduleO->Load($modid);
			}
			
			$output .= "Nom ";
			$output .= $this->iMgr->addInput("name",$moduleO ? $moduleO->getName() : "");
			$output .= "<hr>Chemin ";
			$output .= $this->iMgr->addInput("path",$moduleO ? $moduleO->getPath() : "");
			$output .= "<hr>Accréditation ";
			$output .= $this->iMgr->addInput("ulevel",$moduleO ? $moduleO->getUlevel() : 0);
			$output .= "<hr>Connexion ";
			
			$output .= $this->iMgr->addList("isconnected");
			$output .= $this->iMgr->addElementToList("Non",-1,$moduleO && $moduleO->getConnected() == -1 ? true : false);
			$output .= $this->iMgr->addElementToList("Oui",1,$moduleO && $moduleO->getConnected() == 1 ? true : false);
			$output .= $this->iMgr->addElementToList("Les deux",0,$moduleO && $moduleO->getConnected() == 0 ? true : false);
			
			$output .= "</select>";
			$output .= "<hr>";
			$output .= $this->iMgr->addSubmit("reg","Enregistrer");
			$output .= "</form></div>";
			return $output;
		}
		
		public function RegisterModule() {
			$moduleO = new Module();
			FS::$secMgr->SecuriseStringForDB($_POST["name"]);
			FS::$secMgr->SecuriseStringForDB($_POST["path"]);
			FS::$secMgr->SecuriseStringForDB($_POST["ulevel"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			$moduleO->setName($_POST["name"]);
			$moduleO->setPath($_POST["path"]);
			$moduleO->setUlevel($_POST["ulevel"]);
			$moduleO->setConnected($_POST["isconnected"]);
			$moduleO->Create();
		}
		
		public function EditModule() {
			$moduleO = new Module();
			FS::$secMgr->SecuriseStringForDB($_POST["module_id"]);
			FS::$secMgr->SecuriseStringForDB($_POST["name"]);
			FS::$secMgr->SecuriseStringForDB($_POST["path"]);
			FS::$secMgr->SecuriseStringForDB($_POST["ulevel"]);
			FS::$secMgr->SecuriseStringForDB($_POST["isconnected"]);
			$moduleO->Load($_POST["module_id"]);
			$moduleO->setName($_POST["name"]);
			$moduleO->setPath($_POST["path"]);
			$moduleO->setUlevel($_POST["ulevel"]);
			$moduleO->setConnected($_POST["isconnected"]);
			$moduleO->SaveToDB();
		}
		
		public function RemoveModule() {
			$moduleO = new Module();
			FS::$secMgr->SecuriseStringForDB($_GET["modl"]);
			$moduleO->Load($_GET["modl"]);
			$moduleO->Delete();
		}
	};
?>
