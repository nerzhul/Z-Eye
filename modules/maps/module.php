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

	require_once(dirname(__FILE__)."/../icinga/icingaBroker.api.php");

	final class iMaps extends FSModule{
		function __construct($locales) {
			parent::__construct($locales);
			$this->icingaAPI = new icingaBroker();
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-maps"));

			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";

			if(!FS::isAJAXCall()) {
				$output .= FS::$iMgr->h1("title-maps");
				$output .= FS::$iMgr->tabPan(array(
					array(3,"mod=".$this->mid,$this->loc->s("icinga-map")),
					array(1,"mod=".$this->mid,$this->loc->s("net-map-full")),
					array(4,"mod=".$this->mid,"Sigma"),
					array(5,"mod=".$this->mid,"Springy")
					),$sh);
			} else {
				if($sh == 1)
					$output .= $this->showNetworkMap();
				else if($sh == 3)
					$output .= $this->showIcingaMap();
				else if($sh == 4)
					$output .= $this->showSigmaMap();
				else if($sh == 5)
					$output .= $this->showSpringyMap();
				else
					$output .= FS::$iMgr->printError($this->loc->s("err-no-tab"));
			}
			return $output;
		}

		private function showSpringyMap() {
			$output = FS::$iMgr->canvas("springy",1000,1000); 
			
			$js = "var graph = new Springy.Graph({'repulsion': 800});";
			$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."map_nodes","nodename,node_label,node_x,node_y,node_size,node_color");
			while($data = FS::$dbMgr->Fetch($query)) {
				$js .= "var n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data["node_label"]))." = graph.newNode({'label':'".$data["node_label"]."'});";
			}

			$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."map_edges","edgename,node1,node2,edge_color,edge_size");
			while($data = FS::$dbMgr->Fetch($query)) {
				$js .= "graph.newEdge(n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data["node1"])).",n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data["node2"])).");";
			}
			$js .= "$('#springy').springy({ graph: graph });";
			FS::$iMgr->js($js);

			return $output;
		}

		private function showSigmaMap() {
			$output	= FS::$iMgr->opendiv(1,$this->loc->s("Add-Node"),array("line" => true));
			$output	.= FS::$iMgr->opendiv(2,$this->loc->s("Add-Edge"),array("line" => true));
			$output	.= FS::$iMgr->opendiv(3,$this->loc->s("Import"));
			$output .= "<div id=\"sigmap\" style=\"display:inline-block;text-align:left; width:100%; height:800px;\"></div>";
			
			$js = "var sigInst = sigma.init(document.getElementById('sigmap')).drawingProperties({
					defaultLabelColor: '#000',
					defaultEdgeType: 'curve'
				});";

			$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."map_nodes","nodename,node_label,node_x,node_y,node_size,node_color");
			while($data = FS::$dbMgr->Fetch($query)) {
				$js .= "sigInst.addNode('n".$data["nodename"]."',{ 'x': ".$data["node_x"].", 'y': ".$data["node_y"].", 'label': '".$data["node_label"]."',
					'size': ".$data["node_size"].", 'color': '#".$data["node_color"]."' });";
			}
			
			$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."map_edges","edgename,node1,node2,edge_color,edge_size");
			while($data = FS::$dbMgr->Fetch($query)) {
				$js .= "sigInst.addEdge('e".$data["edgename"]."','n".$data["node1"]."','n".$data["node2"]."',{'color': '#".$data["edge_color"]."',
					'size': '".$data["edge_size"]."'});";
			}
			$js .= " sigInst.bind('overnodes',function(event){
				var nodes = event.content;
				var neighbors = {};
				sigInst.iterEdges(function(e){
					if(nodes.indexOf(e.source)>=0 || nodes.indexOf(e.target)>=0){
						neighbors[e.source] = 1;
						neighbors[e.target] = 1;
					}
				}).iterNodes(function(n){
					if(!neighbors[n.id]){
						n.hidden = 1;
					}else{
						n.hidden = 0;
					}
				}).draw(2,2,2);
			}).bind('outnodes',function(){
				sigInst.iterEdges(function(e){
					e.hidden = 0;
				}).iterNodes(function(n){
					n.hidden = 0;
				}).draw(2,2,2);
			});
			sigInst.draw();";
			FS::$iMgr->js($js);
			return $output;
		}

		// This form imports nodes from devices or icinga
		private function showImportForm() {
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=5");
			$output .= FS::$iMgr->submit("","Import de test")."</form>";
			return $output;
		}
		
		private function showNodeForm($name = "") {
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=1");
			$output .= "<table>";
			$output .= FS::$iMgr->idxIdLine("Name","nname",$name,array("length" => 60));
			$output .= FS::$iMgr->idxLine("Label","nlabel","",array("length" => 60));
			$output .= FS::$iMgr->idxLine($this->loc->s("PositionX"),"nposx","",array("type" => "num", "length" => 4, "size" => 4));
			$output .= FS::$iMgr->idxLine($this->loc->s("PositionY"),"nposy","",array("type" => "num", "length" => 4, "size" => 4));
			$output .= FS::$iMgr->idxLine($this->loc->s("Size"),"nsize","",array("type" => "num", "length" => 2, "size" => 2));
			$output .= FS::$iMgr->idxLine($this->loc->s("Color"),"ncolor","000000",array("type" => "color", "length" => 6, "size" => 6));
			$output .= FS::$iMgr->aeTableSubmit($name == "");
			return $output;

		}

		private function showEdgeForm($name = "") {
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=3");
			$output .= "<table>";
			$output .= FS::$iMgr->idxIdLine("Name","ename",$name,array("length" => 60));
			$output .= "<tr><td>".$this->loc->s("Source-node")."</td><td>".$this->showNodeList("node1",NULL)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Dest-node")."</td><td>".$this->showNodeList("node2",NULL)."</td></tr>";
			//$output .= FS::$iMgr->idxLine("Label","elabel","",array("length" => 60));
			$output .= FS::$iMgr->idxLine($this->loc->s("Size"),"esize","",array("type" => "num", "length" => 2, "size" => 2));
			$output .= FS::$iMgr->idxLine($this->loc->s("Color"),"ecolor","000000",array("type" => "color", "length" => 6, "size" => 6));
			$output .= FS::$iMgr->aeTableSubmit($name == "");
			return $output;
		}

		private function showNodeList($name,$label,$selected = "") {
			$output = FS::$iMgr->select($name,"",$label);
			$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."map_nodes","nodename,node_label","mapname = 'mainmap'");
			while($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["node_label"]." (".$data["nodename"].")",$data["nodename"],$selected == $data["nodename"]);
			}
			$output .= "</select>";
			return $output;
		}

		private function showNetworkMap() {
			$output = FS::$iMgr->canvas("springy-map",1000,1000); 
			
			$js = "var graph = new Springy.Graph({repulsion: 500});";
			$js2 = "";
			
			// Bufferize host states to colorize map
			$hostCrit = array();
			$hostOK = array();
			$iStates = $this->icingaAPI->readStates(array("plugin_output","current_state","state_type"));

			// Loop hosts
			foreach($iStates as $host => $hostvalues) {
				// Loop types
				foreach($hostvalues as $hos => $hosvalues) {
					if($hos == "hoststatus") {
						if($hosvalues["current_state"] == 1) {
							if(!in_array($host,$hostCrit)) 
								array_push($hostCrit,$host);
						}
						else {
							if(!in_array($host,$hostOK)) 
								array_push($hostOK,$host);
						}
					}
				}
			}

			$query = FS::$dbMgr->Select("device","ip,name");
			while($data = FS::$dbMgr->Fetch($query)) {
				// Generate all links between this device and others
				$query2 = FS::$dbMgr->Select("device_port","remote_id","remote_id != '' AND ip = '".$data["ip"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					$js2 .= "graph.newEdge(n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data["name"])).",n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data2["remote_id"])).");";
				}

				$js .= "var n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data["name"]))." = graph.newNode({'label':'".$data["name"]."'";
				if(in_array($data["name"],$hostCrit))
					$js .= ",'serviceColor':'red','hostColor':'#660000'";
				else if(in_array($data["name"],$hostOK))
					$js .= ",'serviceColor':'green','hostColor':'#003300'";

				$js .= "});";
			}

			$nodelist = array();
			$query = FS::$dbMgr->Select("device_port","remote_id","remote_id NOT IN(SELECT name FROM device)");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(in_array($data["remote_id"],$nodelist))
					continue;
				array_push($nodelist,$data["remote_id"]);
				$js .= "var n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data["remote_id"]))." = graph.newNode({'label':'".$data["remote_id"]."'});";
			} 
					
			$js .= $js2;
			$js .= "$('#springy-map').springy({ graph: graph });";
			FS::$iMgr->js($js);
			return $output;
		}

		private function showIcingaMap() {
			// We store problematic hosts for renderer
			$hostStatusWarn = array();
			$serviceStatusWarn = array();
			$hostStatusCrit = array();
			$serviceStatusCrit = array();
			$iStates = $this->icingaAPI->readStates(array("plugin_output","current_state","state_type"));
			// Loop hosts
			foreach($iStates as $host => $hostvalues) {
				// Loop types
				foreach($hostvalues as $hos => $hosvalues) {
					if($hos == "servicestatus") {
						// Loop sensors
						foreach($hosvalues as $sensor => $svalues) {
							$this->totalicinga++;
							if($svalues["current_state"] > 0) {
								$outstate = "";
								$stylestate = "";
								if($svalues["current_state"] == 1) {
									if(!in_array($host,$serviceStatusWarn)) 
										array_push($serviceStatusWarn,$host);
								}
								else if($svalues["current_state"] == 2) {
									if(!in_array($host,$serviceStatusCrit)) 
										array_push($serviceStatusCrit,$host);
								}
									
								$this->hsicinga++;
							}
						}
					}
					else if($hos == "hoststatus") {
						$this->totalicinga++;
						if($hosvalues["current_state"] > 0) {
							$this->hsicinga++;
							$outstate = "";
							$stylestate = "";
							if($hosvalues["current_state"] == 1) {
								if(!in_array($host,$hostStatusCrit)) 
									array_push($hostStatusCrit,$host);
							}
						}
					}
				}
			}
			$output = FS::$iMgr->canvas("springy-icinga",1000,1000); 
			
			$js = "var graph = new Springy.Graph({repulsion: 500});";
			$js2 = "";

			$nodelist = array();
			$query = FS::$dbMgr->Select("z_eye_icinga_hosts","name,addr");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!array_key_exists($data["name"],$nodelist))
					$linklist = array();
				$query2 = FS::$dbMgr->Select("z_eye_icinga_host_parents","parent","name = '".$data["name"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					// edges after nodes
					if(!array_key_exists($data["name"],$nodelist)) {
						$js2 .= "graph.newEdge(n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data["name"])).",n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data2["parent"])).",{'directional':false});";
						array_push($linklist,$data2["parent"]);
					}
					else if(!in_array($nodelist[$data["name"]]["links"],$data2["parent"])) {
						$js2 .= "graph.newEdge(n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data["name"])).",n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data2["parent"])).",{'directional':false});";
						array_push($nodelist[$data["name"]]["links"],$data2["parent"]);
					}
				}
				if(!array_key_exists($data["name"],$nodelist)) {
					$js .= "var n".preg_replace("#[-]#","",FS::$iMgr->formatHTMLId($data["name"]))." = graph.newNode({'label':'".$data["name"]."'";
					if(in_array($data["name"],$serviceStatusCrit))
						$js .= ",'serviceColor': 'red'";
					else if(in_array($data["name"],$serviceStatusWarn))
						$js .= ",'serviceColor': 'orange'";
					else
						$js .= ",'serviceColor': 'green'";
					if(in_array($data["name"],$hostStatusCrit))
						$js .= ",'hostColor': '#660000'";
					else if(in_array($data["name"],$hostStatusWarn))
						$js .= ",'hostColor': 'orange'";
					else
						$js .= ",'hostColor': '#003300'";
					$js .= "});";
					$nodelist[$data["name"]] = array("label" => $data["addr"],"links" => $linklist, "placed" => false);
				}
			}
			$js .= $js2;
			$js .= "$('#springy-icinga').springy({ graph: graph });";

			FS::$iMgr->js($js);
			return $output;
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->showNodeForm();
				case 2: return $this->showEdgeForm();
				case 3: return $this->showImportForm();
				default: return;
			}
		}
		
		public function handlePostDatas($act) {
			// @ TODO: rights
			switch($act) {
				// Add/modify nodes
				case 1:
					$name = FS::$secMgr->checkAndSecurisePostData("nname");
					$label = FS::$secMgr->checkAndSecurisePostData("nlabel");
					$posx = FS::$secMgr->checkAndSecurisePostData("nposx");
					$posy = FS::$secMgr->checkAndSecurisePostData("nposy");
					$size = FS::$secMgr->checkAndSecurisePostData("nsize");
					$color = FS::$secMgr->checkAndSecurisePostData("ncolor");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$name || !$label || !$posx || !FS::$secMgr->isNumeric($posx) || $posx < 0 || !$posy || !FS::$secMgr->isNumeric($posy) || $posy < 0 || 
						!$size || !FS::$secMgr->isNumeric($size) || $size < 1 || !$color || $edit && $edit != 1) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."map_nodes","node_label","mapname = 'mainmap' AND nodename = '".$name."'");
					if($edit) {
						if(!$exist) {
							FS::$iMgr->ajaxEcho("err-node-not-exists");
							return;
						}
					}
					else {
						if($exist) {
							FS::$iMgr->ajaxEcho("err-node-exists");
							return;
						}
					}

					FS::$dbMgr->BeginTr();
					if($edit) FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."map_nodes","mapname = 'mainmap' AND nodename = '".$name."'");

					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."map_nodes","mapname,nodename,node_x,node_y,node_label,node_size,node_color",
						"'mainmap','".$name."','".$posx."','".$posy."','".$label."','".$size."','".$color."'");
					FS::$dbMgr->CommitTr();
					FS::$iMgr->ajaxEcho("Done");
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					return;
				// Add/edit edges
				case 3:
					$name = FS::$secMgr->checkAndSecurisePostData("ename");
					//$label = FS::$secMgr->checkAndSecurisePostData("elabel");
					$node1 = FS::$secMgr->checkAndSecurisePostData("node1");
					$node2 = FS::$secMgr->checkAndSecurisePostData("node2");
					$size = FS::$secMgr->checkAndSecurisePostData("esize");
					$color = FS::$secMgr->checkAndSecurisePostData("ecolor");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$name /*|| !$label*/ || !$node1|| !$node2 || !$size || !FS::$secMgr->isNumeric($size) || $size < 1 || !$color  ||
						$edit && $edit != 1) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					if($node1 == $node2) {
						FS::$iMgr->ajaxEcho("err-src-equal-dest");
						return;
					}

					$node1exist = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."map_nodes","node_label","mapname = 'mainmap' AND nodename = '".$node1."'");
					$node2exist = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."map_nodes","node_label","mapname = 'mainmap' AND nodename = '".$node2."'");
					if(!$node1exist || !$node2exist) {
						FS::$iMgr->ajaxEcho("err-node-not-exists");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."map_edges","node1","mapname = 'mainmap' AND edgename = '".$name."'");
					if($edit) {
						if(!$exist) {
							FS::$iMgr->ajaxEcho("err-edge-not-exists");
							return;
						}
					}
					else {
						if($exist) {
							FS::$iMgr->ajaxEcho("err-edge-exists");
							return;
						}
					}

					FS::$dbMgr->BeginTr();
					if($edit) FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."map_edges","mapname = 'mainmap' AND edgename = '".$name."'");

					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."map_edges","mapname,edgename,node1,node2,edge_color,edge_size",
						"'mainmap','".$name."','".$node1."','".$node2."','".$color."','".$size."'");
					FS::$dbMgr->CommitTr();
					FS::$iMgr->ajaxEcho("Done");
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					return;
				// test
				case 5:
					FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."map_nodes");
					FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."map_edges");
					$nodelist = array(
				"ttot" => array("label" => "ttot", "links" => array("esx1","test2","test3","test4","test5","test6","test7","test8","test9","test10"), "placed" => false),
				"test2" => array("label" => "test2", "links" => array("ttot","test3","esx2"), "placed" => false),
				"test3" => array("label" => "test3", "links" => array("test2","ttot","test4"), "placed" => false),
				"test4" => array("label" => "test4", "links" => array("ttot","test3","test6"), "placed" => false),
				"test5" => array("label" => "test5", "links" => array("ttot","test3","test6"), "placed" => false),
				"test6" => array("label" => "test6", "links" => array(), "placed" => false),
				"test7" => array("label" => "test7", "links" => array("test11"), "placed" => false),
				"test8" => array("label" => "test8", "links" => array(), "placed" => false),
				"test10" => array("label" => "test10", "links" => array(), "placed" => false),
				"test11" => array("label" => "test11", "links" => array("test2","test7","test9"), "placed" => false),
				"test12" => array("label" => "test12", "links" => array("test3","test10","test11"), "placed" => false),
				"esx1" => array("label" => "esx1", "links" => array("vm1","vm2","vm3","vm4","vm5","vm6","vm7","vm8","vm9","vm10","vm11","vm12","vm13"), "placed" => false),
				"esx2" => array("label" => "esx2", "links" => array("2vm1","2vm2","2vm3","2vm4","2vm5","2vm6","2vm7"), "placed" => false),
				"test9" => array("label" => "test9", "links" => array(), "placed" => false)
					);
					for($i=1;$i<=13;$i++)
						$nodelist["vm".$i] = array("label" => "vm".$i, "links" => array(), "placed" => false);
					for($i=1;$i<=7;$i++)
						$nodelist["2vm".$i] = array("label" => "2vm".$i, "links" => array(), "placed" => false);

					$this->ImportNodes($nodelist);
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					return;
			}
		}
		private $icingaAPI;
	};
?>
