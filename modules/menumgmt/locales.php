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
    
	class lMenuMgmt extends FSLocales {
		function lMenuMgmt() {
			parent::FSLocales();
			$locales = array(
				"fr" => array(
					"add-elmt" => "Ajouter un élément",
					"Both" => "Les deux",
					"confirm-removeitem" => "Voulez vous supprimer le lien du menu suivant ",
					"confirm-removemenu" => "Êtes vous sûr de vouloir supprimer le menu ",
					"confirm-removemenuitem" => "Êtes vous sûr de vouloir supprimer l'élément de menu ",
					"Connected" => "Connecté",
					"elmt" => "Elément",
					"elmt-create" => "Création d'un élément",
					"elmt-edit" => "Edition de l'élément",
					"Link" => "Lien",
					"menu-create" => "Création d'un menu",
					"menu-edit" => "Edition du menu",
					"menu-title" => "Gestion des menus (dev)",
					"mod-elmt" => "Modifier les éléments",
					"Name" => "Nom",
					"New-Menu" => "Nouveau menu",
					"New-menu-elmt" => "Nouvel élément de menu",
					"No" => "Non",
					"Order" => "Ordre",
					"Save" => "Enregistrer",
					"title-menu-mgmt" => "Gestion des menus",
					"title-menu-node-mgmt" => "Gestion des éléments de menu",
					"Yes" => "Oui",
				),
				"en" => array(
					"add-elmt" => "Add an element",
					"Both" => "Both",
					"confirm-removeitem" => "Are you sure to want to remove following menu link ",
					"confirm-removemenu" => "Are you sure to want to remove menu ",
					"confirm-removemenuitem" => "Are you sure to want to remove menu item ",
					"Connected" => "Connected",
					"elmt" => "Element",
					"elmt-create" => "Element creation",
					"elmt-edit" => "Element edition",
					"Link" => "Link",
					"menu-create" => "Menu creation",
					"menu-edit" => "Menu edition",
					"menu-edit" => "Menu management (dev)",
					"mod-elmt" => "Modify elements",
					"Name" => "Name",
					"New-Menu" => "New menu",
					"New-menu-elmt" => "New menu element",
					"No" => "No",
					"Order" => "Order",
					"Save" => "Save",
					"title-menu-mgmt" => "Menu management",
					"title-menu-node-mgmt" => "Menu elements management",
					"Yes" => "Yes",
				)
			);
			$this->concat($locales);
		}
	};
?>
