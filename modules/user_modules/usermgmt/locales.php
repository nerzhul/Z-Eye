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

    require_once(dirname(__FILE__)."/../../../lib/FSS/objects/Locales.FS.class.php");
    
	class lUserMgmt extends zLocales {
		function lUserMgmt() {
			$this->locales = array(
				"fr" => array(
					"attr-mail" => "Attribut Mail",
					"attr-name" => "Attribut Nom",
					"attr-subname" => "Attribut Prénom",
					"attr-uid" => "Attribut UID",
					"base-dn" => "Base DN",
					"Cancel" => "Annuler",
					"Directory" => "Annuaire",
					"err-invalid-bad-data" => "Informations invalides ou manquantes",
					"err-invalid-user" => "Utilisateur invalide",
					"err-ldap-bad-data" => "Données LDAP invalides, impossible de se connecter au serveur",
					"err-ldap-exist" => "Serveur déjà renseigné",
					"err-ldap-not-exist" => "Ce serveur LDAP n'existe pas",
					"Extern" => "Externe",
					"filter-ldap" => "Filtre LDAP",
					"Group" => "Groupe",
					"Groups" => "Groupes",
					"inscription" => "Inscription",
					"Intern" => "Interne",
					"last-conn" => "Dernière connexion",
					"last-ip" => "Dernière IP",
					"ldap-addr" => "Adresse de l'annuaire LDAP",
					"ldap-filter" => "Filtre LDAP",
					"ldap-port" => "Port LDAP",
					"Mail" => "Mail",
					"Name" => "Nom",
					"new-directory" => "Nouvel Annuaire",
					"port" => "Port",
					"Remove" => "Supprimer",
					"root-dn" => "Root DN",
					"root-pwd" => "Root Pwd",
					"Save" => "Enregistrer",
					"Server" => "Serveur",
					"SSL" => "SSL",
					"Subname" => "Prénom",
					"sure-remove-directory" => "Êtes vous sûr de vouloir supprimer l\'annuaire",
					"sure-remove-user" => "Êtes vous sûr de vouloir supprimer l\'utilisateur",
					"title-directory" => "Edition d'annuaire",
					"title-directorymgmt" => "Gestion des annuaires",
					"title-user-dont-exist" => "L'utilisateur demandé n'existe pas !",
					"title-usermgmt" => "Gestion des utilisateurs",
					"title-user-mod" => "Modification de l'utilisateur",
					"User" => "Utilisateur",
					"User-type" => "Type d'utilisateur",
				),
				"en" => array(
					"attr-mail" => "Mail attribute",
					"attr-name" => "Name Attribute",
					"attr-subname" => "Subname Attribute",
					"attr-uid" => "UID Attribut",
					"base-dn" => "Base DN",
					"Cancel" => "Annuler",
					"Directory" => "Cancel",
					"err-invalid-bad-data" => "Some informations are missing or invalid",
					"err-invalid-user" => "Invalid user",
					"err-ldap-bad-data" => "Invalid LDAP datas, unable to connect to server",
					"err-ldap-exist" => "Server already exists",
					"err-ldap-not-exist" => "This LDAP server doesn't exist",
					"Extern" => "External",
					"Group" => "Group",
					"Groups" => "Groups",
					"inscription" => "Inscription",
					"Intern" => "Internal",
					"last-conn" => "Last connection",
					"last-ip" => "Last IP",
					"ldap-addr" => "LDAP IP address",
					"ldap-filter" => "LDAP filter",
					"ldap-port" => "LDAP port",
					"Mail" => "Mail",
					"Name" => "Name",
					"new-directory" => "New directory",
					"port" => "Port",
					"Remove" => "Remove",
					"root-dn" => "Root DN",
					"root-pwd" => "Root Pwd",
					"Save" => "Save",
					"Server" => "Server",
					"SSL" => "SSL",
					"Subname" => "Subname",
					"sure-remove-directory" => "Are you sure to remove directory",
					"sure-remove-user" => "Are you sure to remove user",
					"title-directory" => "Directory edition",
					"title-directorymgmt" => "Directory management",
					"title-user-dont-exist" => "This user doesn't exist !",
					"title-usermgmt" => "User management",
					"title-user-mod" => "User edition",
					"User" => "User",
					"User-type" => "User type",
				)
			);
		}
	};
?>
