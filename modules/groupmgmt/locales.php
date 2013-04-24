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
    
	class lGroupMgmt extends FSLocales {
		function lGroupMgmt() {
			parent::FSLocales();
			$locales = array(
				"fr" => array(
					"Add" => "Ajouter",
					"confirm-removegrp" => "Êtes vous sûr de vouloir supprimer le groupe ",
					"Delete" => "Supprimer",
					"err-already-exist" => "Ce groupe existe déjà !",
					"err-bad-data" => "Données invalides.",
					"err-not-exist" => "Ce groupe n'existe pas !",
					"Groupname" => "Nom du groupe",
					"menu-name" => "Admin Z-Eye",
					"menu-title" => "Gestion des groupes Z-Eye",
					"New-group" => "Nouveau Groupe",
					"Rule" => "Règle",
					"Save" => "Enregistrer",
					"sure-delete" => "Êtes vous sûr de vouloir supprimer le groupe",
					"title-edit" => "Edition du groupe",
					"title-mgmt" => "Gestion des groupes",
					"title-opts" => "Options des modules",
					"User-nb" => "Nombre d'utilisateurs",
				),
				"en" => array(
					"Add" => "Add",
					"confirm-removegrp" => "Are you sure to want to remove group ",
					"Delete" => "Remove",
					"err-already-exist" => "This group already exists !",
					"err-bad-data" => "Invalid data(s).",
					"err-not-exist" => "This group doesn't exists !",
					"Groupname" => "Groupname",
					"menu-name" => "Z-Eye Admin",
					"menu-title" => "Z-Eye groups management",
					"New-group" => "New group",
					"Rule" => "Rule",
					"Save" => "Save",
					"sure-delete" => "Are you sure to remove group",
					"title-edit" => "Group edition",
					"title-mgmt" => "Groups management",
					"title-opts" => "Modules' options",
					"User-nb" => "User number",
				)
			);
			$this->concat($locales);
		}
	};
?>
