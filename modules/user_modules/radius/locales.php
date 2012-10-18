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
					"Account" => "Compte",
					"Account-nb" => "Nombre de comptes",
					"Acct-expiration-date" => "Date d'expiration du compte",
					"Acct-start-date" => "Date de début du compte",
					"Add" => "Ajouter",
					"Administrate" => "Administrer",
					"advanced-tools" => "Options avancées",
					"Already-valid" => "Toujours valide",
					"Auth-Type" => "Type d'authentification",
					"auto-import-dhcp" => "Import Auto DHCP",
					"Cancel" => "Annuler",
					"Creation-date" => "Date de création du compte",
					"Delete" => "Supprimer",
					"Delete-accounting" => "Supprimer l\\'accounting",
					"Delete-logs?" => "Supprimer les logs",
					"Delete-profil" => "Êtes vous sûr de vouloir supprimer le profil",
					"Delete-subnet-import" => "Êtes vous sûr de vouloir supprimer l\'importation du subnet",
					"DHCP-zone" => "Zone DHCP",
					"enable-autoclean" => "Activer le nettoyage automatique",
					"entitlement" => "Intitulé",
					"err-bad-tab" => "Onglet invalide !",
					"err-delete" => "Echec de la suppression, données invalides !",
					"err-end-before-start" => "La date de fin est située avant la date d'activation du compte !",
					"err-exist" => "Le groupe/utilisateur inscrit est déjà référencé !",
					"err-exist2" => "Certains utilisateurs n'ont pas été ajoutés car déjà existants !",
					"err-field-missing" => "Certains champs sont manquants ou invalides !",
					"err-invalid-auth-server" => "Serveur d'authentification invalide !",
					"err-invalid-tab" => "Onglet invalide !",
					"err-invalid-table" => "La table référencée n'est pas valide !",
					"err-miss-data" => "Certaines données entrées sont manquantes ou invalides !",
					"err-no-server" => "Aucun serveur radius référencé",
					"err-no-subnet-for-import" => "Aucun subnet DHCP ou Profil Radius disponible pour la synchronisation",
					"err-no-user" => "Utilisateur inexistant !",
					"err-not-exist" => "Serveur radius non référencé !",
					"expiration-field" => "Champ expiration",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux, ou la page indisponible",
					"From" => "Du",
					"Generation-type" => "Type de génération",
					"Group" => "Groupe",
					"Identifier" => "Identifiant de connexion",
					"Infinite" => "Infinie",
					"Mac-addr" => "Adresse MAC",
					"Manage" => "Gérer",
					"mass-account-deleg" => "Comptes en masse (Deleg)",
					"mass-import" => "Import de masse",
					"mass-import-restriction" => "<b>Note: </b>Les noms d'utilisateurs ne peuvent pas contenir d'espace.<br />Les mots de passe doivent être en clair.<br />Caractère de formatage: <b>,</b>",
					"mono-account-deleg" => "Compte individuel (Deleg)",
					"Name" => "Nom",
					"New-Attribute" => "Nouvel attribut",
					"New-Group" => "Nouveau Groupe",
					"New-Profil" => "Nouveau profil",
					"New-User" => "Nouvel Utilisateur",
					"None" => "Aucun",
					"ok-user" => "Utilisateur créé avec succès !",
					"Password" => "Mot de passe",
					"Permanent" => "Permanent",
					"Period" => "Période",
					"Prefix" => "Préfixe",
					"Profil" => "Profil",
					"Profilname" => "Nom du profil",
					"Profils" => "Profils",
					"Pwd-Type" => "Type de mot de passe",
					"Radius-profil" => "Profil Radius",
					"random-name" => "Nom aléatoire",
					"Save" => "Enregistrer",
					"SQL-table" => "Table SQL",
					"Subname" => "Prénom",
					"sure-delete-user" => "Êtes vous sûr de vouloir supprimer l\'utilisateur",
					"Target" => "Cible",
					"Temporary" => "Temporaire",
					"title-auto-import" => "Nouvel Import Automatique",
					"title-auto-import2" => "Imports automatiques existants",
					"title-cleanusers" => "Nettoyage des utilisateurs",
					"title-deleg" => "Outil de délégation de l'authentification",
					"title-mass-import" => "Import d'utilisateurs en masse",
					"title-profillist" => "Liste des profils",
					"title-userlist" => "Liste des Utilisateurs",
					"title-usermod" => "Modification de l'utilisateur",
					"title-usermgmt" => "Gestion des utilisateurs/Groupes Radius",
					"To" => "Au",
					"User" => "Utilisateur",
					"user-field" => "Champ utilisateur",
					"User-nb" => "Nombre d'utilisateurs",
					"Userlist-CSV" => "Liste des utilisateurs (format CSV)",
					"User-type" => "Type d'utilisateur",
					"Users" => "Utilisateurs",
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
