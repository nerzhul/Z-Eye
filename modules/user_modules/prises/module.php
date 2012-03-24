<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iPriseMgmt extends genModule{
		function iPriseMgmt($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h3>Gestion des prises</h4>";
			$piece = FS::$secMgr->checkAndSecuriseGetData("piece");
			if($piece != NULL)
				$output .= $this->showPieceInfos();
			else
				$output .= $this->showMain();
			$output .= "</div>";
			return $output;
		}

		public function showPieceInfos() {
			$output = "<h4>Informations sur la pièce ";
			$piece = FS::$secMgr->checkAndSecuriseGetData("piece");
			$output .= $piece."</h4>";
			$output .= "<a class=\"monoComponentt_a\" href=\"m-34.html\">Retour</a>";

			$found = 0;
			$tmpoutput = "<table class=\"standardTable\"><tr><th>#Prise</th><th>Switch</th><th>Port</th><th>Commentaire</th></tr>";
			$query = FS::$dbMgr->Select("fss_piece_prises","prise,comment","piece = '".$piece."'");
			while($data = mysql_fetch_array($query)) {
				if($found == 0) $found = 1;
				$tmpoutput .= "<tr><td>".$data["prise"]."</td><td>";
				$query2 = FS::$dbMgr->Select("fss_switch_port_prises","ip,port","prise = '".$piece.".".$data["prise"]."'");
				if($data2 = mysql_fetch_array($query2)) {
					$dev = FS::$pgdbMgr->GetOneData("device","name","ip = '".$data2["ip"]."'");
					$tmpoutput .= "<a class=\"monoComponentt_a\" href=\"index.php?mod=33&d=".$dev."\">".$dev."</a></td>";
					$convport = preg_replace("#\/#","-",$data2["port"]);
					$tmpoutput .= "<td><a class=\"monoComponentt_a\" href=\"index.php?mod=33&d=".$dev."#".$convport."\">".$data2["port"]."</a></td><td>";
				}
				else
					$tmpoutput .= " - </td><td>Non câblé</td><td>";
				$tmpoutput .= $data["comment"]."</td></tr>";

			}

			if($found == 1) $output .= $tmpoutput."</table>";
			else $output .= FS::$iMgr->printError("Aucune prise trouvée");
			return $output;
		}

		public function showMain() {
			$output = "<h4>Liste des pièces</h4>";
			$output .= "<script type=\"text/javascript\">";
                        $output .= "function modifyPiece(src,sbmit,piece_,nbprise_) { ";
                        $output .= "if(sbmit == true) { ";
                        $output .= "$.post('index.php?at=3&mod=34&act=1', { piece: piece_, nbpr: document.getElementsByName(nbprise_)[0].value }, function(data) { ";
                        $output .= "$(src+'l').html(data); $(src+' a').toggle(); ";
                        $output .= "}); } ";
                        $output .= "else $(src).toggle(); }";
                        $output .= "</script>";
			$batarr = array();
			$query = mysql_query("SELECT piece,MAX(prise) as m FROM fss_piece_prises GROUP BY piece ORDER by piece");
			while($data = mysql_fetch_array($query)) {
				if(!isset($batarr[$data["piece"][0]])) {
					$batarr[$data["piece"][0]] = array();
				}
				if(!isset($batarr[$data["piece"][0]][$data["piece"][1]]))
                                        $batarr[$data["piece"][0]][$data["piece"][1]] = "<h4>Bâtiment ".$data["piece"][0]." (".$data["piece"][1].($data["piece"][1] == 1 ? "er" : "ème")." étage) </h4><table class=\"standardTable\"><tr><th>Pièce</th><th>Nombre de prises</th></tr>";

				$npiece = preg_replace("#\.#","-",$data["piece"]);
				$batarr[$data["piece"][0]][$data["piece"][1]] .= "<tr><td><a class=\"monoComponentt_a\" href=\"index.php?mod=34&piece=".$data["piece"]."\">".$data["piece"]."</a></td><td><center>";
				$batarr[$data["piece"][0]][$data["piece"][1]] .= "<div id=\"nbpr_".$npiece."\">";
                                $batarr[$data["piece"][0]][$data["piece"][1]] .= "<a onclick=\"javascript:modifyPiece('#nbpr_".$npiece." a',false);\"><div id=\"nbpr_".$npiece."l\" class=\"modport\">";
                                $batarr[$data["piece"][0]][$data["piece"][1]] .= $data["m"];
                                $batarr[$data["piece"][0]][$data["piece"][1]] .= "</div></a><a style=\"display: none;\">";
                                $batarr[$data["piece"][0]][$data["piece"][1]] .= FS::$iMgr->addInput("nbpr-".$npiece,$data["m"],3,3);
                                $batarr[$data["piece"][0]][$data["piece"][1]] .= "<input class=\"buttonStyle\" type=\"button\" value=\"OK\" onclick=\"javascript:modifyPiece('#nbpr_".$npiece."',true,'".$data["piece"]."','nbpr-".$npiece."');\" />";
                                $batarr[$data["piece"][0]][$data["piece"][1]] .= "</a></div>";
				$batarr[$data["piece"][0]][$data["piece"][1]] .= "</td></tr>";
			}
			foreach($batarr as $key) {
				foreach($key as $key2 => $value)
					$output .= $value."</table>";
			}
			return $output;
		}
		
		private function updatePiecePrise($piece,$nbpr) {
			if($nbpr == NULL || $piece == NULL || !FS::$secMgr->isNumeric($nbpr) || strlen($piece) > 10) {
				return "ERROR";
			}
			FS::$dbMgr->Delete("fss_piece_prises","piece = '".$piece."'");
			for($i=1;$i<=$nbpr;$i++)
				FS::$dbMgr->Insert("fss_piece_prises","piece,prise,comment","'".$piece."','".$i."',''");
			return $nbpr;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$nbpr = FS::$secMgr->checkAndSecurisePostData("nbpr");
					$piece = FS::$secMgr->checkAndSecurisePostData("piece");
					echo $this->updatePiecePrise($piece,$nbpr);
					return;
				default: break;
			}
		}
	};
?>
