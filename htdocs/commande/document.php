<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/commande/document.php
 *	\ingroup    order
 *	\brief      Page de gestion des documents attachees a une commande
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT .'/commande/class/commande.class.php';


$langs->load('companies');
$langs->load('other');

$action		= GETPOST('action');
$confirm	= GETPOST('confirm');
$id			= GETPOST('id','int');
$ref		= GETPOST('ref');

// Security check
if ($user->societe_id)
{
	$action='';
	$socid = $user->societe_id;
}
$result=restrictedArea($user,'commande',$id,'');

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$object = new Commande($db);


/*
 * Actions
 */

// Envoi fichier
if (GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if ($object->fetch($id))
    {
        $object->fetch_thirdparty();
	    $upload_dir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($object->ref);
	    dol_add_file_process($upload_dir,0,1,'userfile');
    }
}

// Delete
else if ($action == 'confirm_deletefile' && $confirm == 'yes')
{
	if ($object->fetch($id))
    {
        $langs->load("other");
        $object->fetch_thirdparty();

    	$upload_dir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($object->ref);
    	$file = $upload_dir . '/' . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
    	$ret=dol_delete_file($file,0,0,0,$object);
    	if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
    	else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
    	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
    	exit;
    }
}


/*
 * View
 */

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id, $ref))
    {
    	$object->fetch_thirdparty();

    	$upload_dir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($object->ref);

		$head = commande_prepare_head($object);
		dol_fiche_head($head, 'documents', $langs->trans('CustomerOrder'), 0, 'order');


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}


		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/commande/liste.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';
		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
		print "</table>\n";
		print "</div>\n";

    	/*
		 * Confirmation suppression fichier
		 */
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&urlfile='.urlencode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
			
		}

		// Affiche formulaire upload
		$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/commande/document.php?id='.$object->id,'',0,0,$user->rights->commande->creer,50,$object);


		// List of document
		$param='&id='.$object->id;
		$formfile->list_of_documents($filearray,$object,'commande',$param);
	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	header('Location: index.php');
}


llxFooter();

$db->close();
?>