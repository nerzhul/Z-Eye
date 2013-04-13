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

	require_once(dirname(__FILE__)."/locales.php");

	class iMaps extends FSModule{
		function iMaps() { parent::FSModule(); $this->loc = new lMaps(); }
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
			$output	= FS::$iMgr->opendiv($this->showNodeForm(),$this->loc->s("Add-Node"));
			$output .= "<div id=\"sigmap\" style=\"text-align:left; width:1280px; height:800px;\"></div>";
			
			$js = "var sigInst = sigma.init(document.getElementById('sigmap')).drawingProperties({
					defaultLabelColor: '#000'
				});";

			$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."map_nodes","nodename,node_label,node_x,node_y,node_size,node_color");
			while($data = FS::$dbMgr->Fetch($query)) {
				$js .= "sigInst.addNode('n".$data["nodename"]."',{ 'x': ".$data["node_x"].", 'y': ".$data["node_y"].", 'label': '".$data["node_label"]."',
					'size': ".$data["node_size"].", 'color': '#".$data["node_color"]."' });";
			}
			
			$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."map_edges","edgename,node1,node2");
			while($data = FS::$dbMgr->Fetch($query)) {
				$js .= "sigInst.addEdge('e".$data["edgename"]."','n".$data["node1"]."','n".$data["node2"]."');";
			}
			$js .= "sigInst.draw();";
			FS::$iMgr->js($js);
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
			$output .= "</table></form>";
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
		
		public function handlePostDatas($act) {
			switch($act) {
				// Add/modify nodes
				case 1:
					$name = FS::$secMgr->checkAndSecurisePostData("nname");
					$label = FS::$secMgr->checkAndSecurisePostData("nlabel");
					$posx = FS::$secMgr->checkAndSecurisePostData("nposx");
					$posy = FS::$secMgr->checkAndSecurisePostData("nposy");
					$size = FS::$secMgr->checkAndSecurisePostData("nsize");
					$color = FS::$secMgr->checkAndSecurisePostData("ncolor");

					if(!$name || !$label || !$posx || !FS::$secMgr->isNumeric($posx) || $posx < 0 || !$posy || !FS::$secMgr->isNumeric($posy) || $posy < 0 || 
						!$size || !FS::$secMgr->isNumeric($size) || $size < 0 || !$color) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."map_nodes","mapname,nodename,node_x,node_y,node_label,node_size,node_color",
						"'mainmap','".$name."','".$posx."','".$posy."','".$label."','".$size."','".$color."'");
					FS::$iMgr->ajaxEcho("Done");
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					return;
			}
		}
	};
?>
