<?php
	/*
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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

    require_once(dirname(__FILE__)."/../../../lib/FSS/objects/Locales.FS.class.php");
    
	class lRadius extends zLocales {
		function lRadius() {
			$this->locales = array(
				"fr" => array(
					"Account-nb" => "Nombre de comptes",
					"Administrate" => "Administrer",
					"Already-valid" => "Toujours valide",
					"entitlement" => "Intitulé",
					"err-bad-tab" => "Onglet invalide !",
					"err-delete" => "Echec de la suppression, données invalides !",
					"err-exist" => "Le groupe/utilisateur inscrit est déjà référencé !",
					"err-exist2" => "Certains utilisateurs n'ont pas été ajoutés car déjà existants !",
					"err-invalid-tab" => "Onglet invalide !",
					"err-invalid-table" => "La table référencée n'est pas valide !",
					"err-miss-data" => "Certaines données entrées sont manquantes ou invalides !",
					"err-no-server" => "Aucun serveur radius référencé",
					"err-not-exist" => "Serveur radius non référencé !",
					"From" => "Du",
					"Identifier" => "Identifiant de connexion",
					"Manage" => "Gérer",
					"Name" => "Nom",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux, ou la page indisponible",
					"Period" => "Période",
					"Prefix" => "Préfixe",
					"Profil" => "Profil",
					"random-name" => "Nom aléatoire",
					"Save" => "Enregistrer",
					"Subname" => "Prénom",
					"title-deleg" => "Outil de délégation de l'authentification",
					"title-usermgmt" => "Gestion des utilisateurs/Groupes Radius",
					"To" => "Au",
					"Value" => "Valeur",
					"Validity" => "Validité",
				),
				"en" => array(
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
				)
			);
		}
	};
?>
