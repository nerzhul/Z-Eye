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
    
	class lLinkMgmt extends FSLocales {
		function lLinkMgmt() {
			parent::FSLocales();
			$locales = array(
				"fr" => array(
					"Action" => "Action",
					"confirm-remove-link" => "Êtes vous sûr de vouloir supprimer le lien ",
					"link-create" => "Création d'un lien",
					"link-edit" => "Edition d'un lien",
					"menu-title" => "Gestion des liens HTTP (dev)",
					"Module" => "Module",
					"New-link" => "Nouveau lien",
					"Normal" => "Normal",
					"rewr-mod" => "Rewrite Module",
					"rewr-other" => "Rewrite Autres",
					"Save" => "Enregistrer",
					"title-link" => "Gestion des liens",
				),
				"en" => array(
					"Action" => "Action",
					"confirm-remove-link" => "Are you sure you want to remove link ",
					"link-create" => "Link creation",
					"link-edit" => "Link edition",
					"menu-title" => "HTTP links management (dev)",
					"Module" => "Module",
					"New-link" => "New link",
					"Normal" => "Normal",
					"rewr-mod" => "Rewrite Module",
					"rewr-other" => "Rewrite Others",
					"Save" => "Save",
					"title-link" => "Link management",
				)
			);
			$this->concat($locales);
		}
	};
?>
