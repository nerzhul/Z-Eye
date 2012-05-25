<?php
	/* TODO: bipolar API implementation */
	class FSRGraph {
		function FSRGraph() {
			$this->type = 0;
			$this->name = "";
			$this->properties = array();
			$this->values = "";
		}
		
		public function create() {
			if($this->type == 1)
				$output = $this->createBar();
			
			return $output;
		}
		
		private function createBipolar() {
			if(!is_array($this->values) || count($this->values) != 2)
				return "";
				
			$output = "<script type=\"text/javascript\">var ".$this->name." = new RGraph.Bipolar('".$this->name."', [".$this->values[0]."], [".$this->values[1]."]);";
			
			foreach($this->properties as $key => $value) {
				switch($key) {
					case "gutter.center":
					case "margin":
					case "title.left":
					case "title.right":
						$output .= $this->name.".Set('chart.".$key."', ".$value.");";
						break;
					default: break;
				}
				
				if($this->isMarginKey($key) || $this->isTitleKey($key) || $this->isColorKey($key))
					$output .= $this->name.".Set('chart.".$key."', ".$value.");";
			}
			$output .= $this->name.".Draw();</script>";
			return $output;
		}
		private function createBar() {
			$output = "<script type=\"text/javascript\">var ".$this->name." = new RGraph.Bar('".$this->name."', [".$this->values."]);";

			foreach($this->properties as $key => $value) {
				switch($key) {
					case "adjustable":
					case "annotatable":
					case "annotate.color":
					case "axis.color":
					case "background.barcolor1":
					case "background.barcolor2":
					case "background.grid":
					case "background.grid.color":
					case "background.grid.hsize":
					case "background.grid.vsize":
					case "background.grid.width":
					case "background.grid.border":
					case "background.grid.hlines":
					case "background.grid.vlines":
					case "background.grid.autofit":
					case "background.grid.autofit.numhlines":
					case "background.grid.autofit.numvlines":
					case "background.grid.autofit.align":
					case "background.hbars":
					case "background.image":
					case "colors.sequential":
					case "colors.reverse":
					case "contextmenu":
					case "crosshairs":
					case "crosshairs.linewidth":
					case "crosshairs.color":
					case "hmargin":
					case "highlight.stroke":
					case "highlight.fill":
					case "grouping":
					case "key":
					case "key.background":
					case "key.halign":
					case "key.position":
					case "key.position.gutter.boxed":
					case "key.position.x":
					case "key.position.y":
					case "key.shadow":
					case "key.shadow.color":
					case "key.shadow.blur":
					case "key.shadow.offsetx":
					case "key.shadow.offsety":
					case "key.rounded":
					case "key.color.shape":
					case "key.linewidth":
					case "labels":
					case "labels.above":
					case "labels.above.decimals":
					case "labels.above.size":
					case "labels.above.angle":
					case "labels.ingraph":
					case "line":
					case "noaxes":
					case "noxaxis":
					case "noyaxis":
					case "noendxtick":
					case "numyticks":
					case "resizable":
					case "resize.handle.background":
					case "scale.formatter":
					case "scale.decimals":
					case "scale.point":
					case "scale.round":
					case "shadow":
					case "shadow.blur":
					case "shadow.color":
					case "shadow.offsetx":
					case "shadow.offsety":
					case "text.color":
					case "text.size":
					case "text.angle":
					case "text.font":					
					case "title.xaxis":
					case "title.yaxis":
					case "title.xaxis.pos":
					case "title.yaxis.pos":
					case "tooltips":
					case "tooltips.effect":
					case "tooltips.event":
					case "tooltips.css.class":	
					case "tooltips.override":
					case "tooltips.highlight":
					case "units.pre":
					case "units.post":
					case "variant":
					case "xlabels.offset":
					case "xaxispos":
					case "yaxispos":
					case "ylabels":
					case "ylabels.count":
					case "ylabels.specific":
					case "ymax":
					case "zoom.mode":
					case "zoom.factor":
					case "zoom.fade.in":
					case "zoom.fade.out":
					case "zoom.hdir":
					case "zoom.vdir":
					case "zoom.delay":
					case "zoom.frames":
					case "zoom.shadow":
					case "zoom.thumbnail.width":
					case "zoom.thumbnail.height":
					case "zoom.background":
						$output .= $this->name.".Set('chart.".$key."', ".$value.");";
						break;
					default: break;	
				}
				if($this->isMarginKey($key) || $this->isTitleKey($key) || $this->isColorKey($key))
					$output .= $this->name.".Set('chart.".$key."', ".$value.");";
			}
			$output .= $this->name.".Draw();</script>";
			return $output;
		}
		
		private function isMarginKey($key) {
			switch($key) {
				case "gutter.bottom":
				case "gutter.left":
				case "gutter.right":
				case "gutter.top":
					return true;
				default:
					return false;
			}
		}
		
		private function isTitleKey($key) {
			switch($key) {
				case "title.hpos":
				case "title.vpos":
				case "title":
				case "title.background":
				case "title.color":
					return true;
				default:
					return false;
			}
		}
		
		private function isColorKey($key) {
			switch($key) {
				case "colors":
				case "strokestyle":
					return true;
				default:
					return false;
			}
		}
		
		public function setProperty($name,$value) { $this->properties[$name] = $value; }
		public function setType($t) { $this->type = $t; }
		public function setName($n) { $this->name = $n; }
		public function setValues($val) { $this->values = $val; }
		
		/* Attributes */
		private $type;
		private $name;
		private $properties;
		private $values;
	};
?>
