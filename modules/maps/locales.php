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
    
	class lMaps extends FSLocales {
		function __construct() {
			parent::__construct();
			$locales = array(
				"fr" => array(
					"Add-Edge" => "Ajouter un lien",
					"Add-Node" => "Ajouter un noeud",
					"Color" => "Couleur",
					"Dest-node" => "Noeud de destination",
					"err-edge-exists" => "Ce lien existe déjà",
					"err-edge-not-exists" => "Ce lien n'existe pas",
					"err-no-tab" => "Onglet invalide",
					"err-node-exists" => "Ce noeud existe déjà",
					"err-node-not-exists" => "Ce noeud n'existe pas",
					"err-src-equal-dest" => "La source et la destination sont identiques",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux, ou la page indisponible",
					"icinga-map" => "Etat des systèmes",
					"Import" => "Importer",
					"Import-Icinga-Nodes" => "Importer les noeuds Icinga",
					"Import-Network-Nodes" => "Importer les noeuds réseau",
					"link-state" => "Etat des liens de",
					"menu-name" => "Supervision",
					"menu-title" => "Cartes",
					"net-map" => "Réseau",
					"net-map-full" => "Réseau (complet)",
					"PositionX" => "Position X",
					"PositionY" => "Position Y",
					"Size" => "Taille",
					"Source-node" => "Noeud source",
					"title-maps" => "Cartes",
				),
				"en" => array(
					"Add-Edge" => "Add edge",
					"Add-Node" => "Add node",
					"Color" => "Color",
					"Dest-node" => "Destination node",
					"err-edge-exists" => "This edge already exists",
					"err-edge-not-exists" => "This edge doesn't exists",
					"err-no-tab" => "Bad tab",
					"err-node-exists" => "This node already exists",
					"err-node-not-exists" => "This node doesn't exists",
					"err-src-equal-dest" => "Source and destination are same",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"icinga-map" => "System states",
					"Import" => "Import",
					"Import-Icinga-Nodes" => "Import icinga nodes",
					"Import-Network-Nodes" => "Import network nodes",
					"link-state" => "Links state of",
					"menu-name" => "Supervision",
					"menu-title" => "Maps",
					"net-map" => "Network",
					"net-map-full" => "Network (full)",
					"PositionX" => "Position X",
					"PositionY" => "Position Y",
					"Size" => "Size",
					"Source-node" => "Source node",
					"title-maps" => "Maps",
				)
			);
			$this->concat($locales);
		}
	};
?>
