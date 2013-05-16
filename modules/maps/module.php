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

	final class iMaps extends FSModule{
		function __construct($locales) { parent::__construct($locales); }

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-maps"));

			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";

			if(!FS::isAJAXCall()) {
				$output .= FS::$iMgr->h1("title-maps");
				$output .= FS::$iMgr->tabPan(array(
					array(3,"mod=".$this->mid,$this->loc->s("icinga-map")),
					array(2,"mod=".$this->mid,$this->loc->s("net-map")),
					array(1,"mod=".$this->mid,$this->loc->s("net-map-full")),
					array(4,"mod=".$this->mid,"Sigma")
					),$sh);
			} else {
				$device = FS::$secMgr->checkAndSecuriseGetData("d");
				if($device != NULL)
					$output .= $this->showDeviceWeatherMap($device);
				else if($sh == 1)
					$output .= $this->showGeneralFullWeatherMap();
				else if($sh == 2)
					$output .= $this->showGeneralLightWeatherMap();
				else if($sh == 3)
					$output .= $this->showIcingaMap();
				else if($sh == 4)
					$output .= $this->showSigmaMap();
				else
					$output .= FS::$iMgr->printError($this->loc->s("err-no-tab"));
			}
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
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=4");
			$output .= FS::$iMgr->submit("",$this->loc->s("Import-Network-Nodes"))."</form>";

			//$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=5");
			$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=6");
			$output .= FS::$iMgr->submit("",$this->loc->s("Import-Icinga-Nodes"))."</form>";
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

		private function showDeviceWeatherMap($device) {
			$output = FS::$iMgr->h3($this->loc->s("link-state")." ".$device,true);
			$output .= FS::$iMgr->img("datas/weathermap/".$device.".png");
			return $output;	
		}
		private function showGeneralLightWeatherMap() {
			return FS::$iMgr->imgWithZoom2("datas/weathermap/main-nowifi-tiny.svg",$this->loc->s("net-map-full"),"netmapL","datas/weathermap/main-nowifi.png");
		}
		
		private function showGeneralFullWeatherMap() {
			return FS::$iMgr->imgWithZoom2("datas/weathermap/main-tiny.svg",$this->loc->s("net-map-full"),"netmapF","datas/weathermap/main.png");
		}

		private function showIcingaMap() {
			return FS::$iMgr->imgWithZoom2("cgi-bin/icinga/statusmap.cgi?host=all&createimage&layout=5",$this->loc->s("icinga-map"),"netmapI");
		}

		private function placeNode($node,$nodelist,$pX=NULL,$pY=NULL) {
			if($pX === NULL)
				$pX = rand(1,200);
			if($pY === NULL)
				$pY = rand(1,200);

			if($nodelist[$node]["placed"] == false && !FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."map_nodes","node_label","mapname 
= 'mainmap' AND nodename = '".$node."'")) {
				$color = "007308";
				$linkCount = count($nodelist[$node]["links"]);
				$totalLinks = $linkCount;
				$size = round(10 + 1.5*$linkCount);
				if($size > 20) $size = 20;
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."map_nodes","mapname,nodename,node_x,node_y,node_label,node_size,node_color",
					"'mainmap','".$node."','".$pX."','".$pY."','".$nodelist[$node]["label"]."','".$size."','".$color."'");
				$nodelist[$node]["placed"] = true;

				foreach($nodelist as $node2 => $values) {
					if($node == $node2 || in_array($node2,$nodelist[$node]["links"]))
						continue;
					if(in_array($node,$values["links"]))
						$totalLinks++;
				}

				for($i=0;$i<$linkCount;$i++) {
					$node2 = $nodelist[$node]["links"][$i];
					if(isset($nodelist[$node2]) && $nodelist[$node2]["placed"] == false) {
						$dist = 10*$totalLinks+$size;
						if(count($nodelist[$node2]["links"]) < 2)
							$dist = round($dist/4);
						$pX2 = round($pX + $dist * cos(2*$i*pi()/$totalLinks));
						$pY2 = round($pY + $dist * sin(2*$i*pi()/$totalLinks));
						$this->placeNode($node2,$nodelist,$pX2,$pY2);
					}

					// Insert edges
					if(!FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."map_edges","edge_size","mapname = 'mainmap' AND node1 = '".$node."' AND node2 = '".$nodelist[$node]["links"][$i]."'")) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."map_edges","mapname,edgename,node1,node2,edge_color,edge_size",
							"'mainmap','".rand(1,10000000)."','".$node."','".$nodelist[$node]["links"][$i]."','000000','".rand(1,10)."'");
					}
				}

				$cursorid = $totalLinks - $linkCount + 1;
				foreach($nodelist as $node2 => $values) {
					if($node == $node2 || in_array($node2,$nodelist[$node]["links"]))
						continue;
					if(in_array($node,$values["links"])) {
						$dist = 10*$totalLinks+$size;
						if(count($nodelist[$node2]["links"]) < 2)
							$dist = round($dist/4);
						$pX2 = round($pX + $dist * cos(2*($totalLinks-$cursorid)*pi()/$totalLinks));
						$pY2 = round($pY + $dist * sin(2*($totalLinks-$cursorid)*pi()/$totalLinks));
						$this->placeNode($node2,$nodelist);
						$cursorid--;
					}
				}
			}
		}

		private function ImportNodes($nodelist) {
			if(!is_array($nodelist))
				return;

			foreach($nodelist as $node => $values) {
				$this->placeNode($node,$nodelist);
			}
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
				// Import network nodes
				case 4:
					$nodelist = array();
					$query = FS::$dbMgr->Select("device","ip,name");
					while($data = FS::$dbMgr->Fetch($query)) {
						$linklist = array();
						$query2 = FS::$dbMgr->Select("device_port","remote_id","remote_id != '' AND ip = '".$data["ip"]."'");
						while($data2 = FS::$dbMgr->Fetch($query2))
							array_push($linklist,$data2["remote_id"]);
						$nodelist[$data["name"]] = array("label" => $data["name"],"links" => $linklist, "placed" => false);
					}

					$query = FS::$dbMgr->Select("device_port","remote_id","remote_id NOT IN(SELECT name FROM device)");
					while($data = FS::$dbMgr->Fetch($query)) {
						if(array_key_exists($data["remote_id"],$nodelist))
							continue;
						$nodelist[$data["remote_id"]] = array("label" => $data["remote_id"],"links" => array());
					} 
					
					$this->ImportNodes($nodelist);
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					return;
				// Import icinga nodes
				case 5:
					$nodelist = array();
					$query = FS::$dbMgr->Select("z_eye_icinga_hosts","name,addr");
					while($data = FS::$dbMgr->Fetch($query)) {
						$linklist = array();
						$query2 = FS::$dbMgr->Select("z_eye_icinga_host_parents","parent","name = '".$data["name"]."'");
						while($data2 = FS::$dbMgr->Fetch($query2))
							array_push($linklist,$data2["parent"]);
						$nodelist[$data["name"]] = array("label" => $data["addr"],"links" => $linklist, "placed" => false);
					}
					$this->ImportNodes($nodelist);
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					return;
				// test
				case 6:
					FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."map_nodes");
					FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."map_edges");
					$nodelist = array(
				"ttot" => array("label" => "ttot", "links" => array("esx1","test2","test3","test4","test5","test6","test7","test8","test9","test10"), "placed" => false),
				"test2" => array("label" => "test2", "links" => array("ttot","test3","esx2"), "placed" => false),
				"test3" => array("label" => "test3", "links" => array("test2","ttot","test4"), "placed" => false),
				"test4" => array("label" => "test4", "links" => array("ttot","test3","test6"), "placed" => false),
				"test5" => array("label" => "test5", "links" => array("ttot","test3","test6"), "placed" => false),
				"vm1" => array("label" => "vm".$i, "links" => array(), "placed" => false),
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
					for($i=2;$i<=13;$i++)
						$nodelist["vm".$i] = array("label" => "vm".$i, "links" => array(), "placed" => false);
					for($i=1;$i<=7;$i++)
						$nodelist["2vm".$i] = array("label" => "2vm".$i, "links" => array(), "placed" => false);

					$this->ImportNodes($nodelist);
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					return;
			}
		}
	};
?>
