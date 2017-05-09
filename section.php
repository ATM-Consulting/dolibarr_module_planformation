<?php
/* <Plan Formation>
 * Copyright (C) 2016 Florian HENRY <florian.henry@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once ('config.php');

// Security check
if ($user->societe_id)
	$socid = $user->societe_id;
$result = restrictedArea($user, 'planformation', 0, 'planformation', 'section');

require ('./class/planformation.class.php');
require ('./class/dictionnaire.class.php');
require ('./class/pfusergroup.class.php');

$langs->trans('planformation@planformation');

$PDOdb = new TPDOdb();
//$PDOdb->debug = true;

$tbs = new TTemplateTBS();
$pfs = new TSection();
$sectionP = new TSectionPlanFormation();
$typeFin = new TTypeFinancement();

$action = GETPOST('action');

// Pour que le bouton "Annuler" de la fiche d'un dossier annule et ne sauvegarde pas...
if (isset($_REQUEST['cancel']) && $_REQUEST['cancel'] == "Annuler") {
	$action = "";
}

if (! empty($action)) {
	switch ($action) {
		case 'default' :
			_list($PDOdb, $pfs);
			break;
		case 'list' :
			_list($PDOdb, $pfs);
			break;
		case 'add' :
		case 'new' :
			$pfs->set_values($_REQUEST);
			_card($PDOdb, $pfs, 'edit');

			break;
		case 'edit' :

			if ($pfs->load($PDOdb, GETPOST('id', 'int'))) {
				_card($PDOdb, $pfs, 'edit');
			} else {
				setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
			}
			break;

		case 'save' :
			$pfs->load($PDOdb, GETPOST('id', 'int'));
				$pfs->set_values($_REQUEST);
                                
                                $pfs->save($PDOdb, GETPOST('budget'), GETPOST('fk_section_parente'), GETPOST('plan_id'));
				_card($PDOdb, $pfs, 'view');

			break;

		case 'delete' :

			if ($pfs->load($PDOdb, GETPOST('id', 'int'))) {
                            if($pfs->isDeletable($PDOdb))
                                $pfs->delete($PDOdb);
                            else
                                setEventMessage($langs->trans('NotDeletable'), 'errors');
                                
				_list($PDOdb, $pfs);
			} else {
				setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
			}

			break;
		case 'info' :
			if ($pfs->load($PDOdb, GETPOST('id', 'int'))) {
				_info($PDOdb, $pfs);
			} else {
				setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
			}

			break;
                case 'addsection' :
                        if ($pfs->load($PDOdb, GETPOST('fk_section_parente', 'int'))) {
				$pfs_link = new TSectionPlanFormation();
                                $pfs_link->fk_planform = $_REQUEST['plan_id'];
                                $pfs_link->fk_section = GETPOST('fk_section', 'int');
                                $pfs_link->fk_section_parente = GETPOST('fk_section_parente', 'int');
                                
                                $pfs_link->save($PDOdb);
                                _card($PDOdb, $pfs, 'view');
			} else {
				setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
			}
                    
                        break;
	}
} elseif (isset($_REQUEST['id'])) {
	$pfs->load($PDOdb, $_REQUEST['id']);

	_card($PDOdb, $pfs, 'view');
} else {
	_list($PDOdb, $pfs);
}

llxFooter();

/**
 *
 * @param TPDOdb $PDOdb
 * @param TPlanFormation $pf
 */
function _list(TPDOdb &$PDOdb, TSection &$pfs) {
	global $langs, $db, $conf, $user, $action;

	llxHeader('', $langs->trans('PFPlanFormationList'));

	$r = new TListviewTBS('planform');

	$TOrder = array (
			's.ref' => 'DESC'
	);
	if (isset($_REQUEST['orderDown']))
		$TOrder = array (
				$_REQUEST['orderDown'] => 'DESC'
		);
	if (isset($_REQUEST['orderUp']))
		$TOrder = array (
				$_REQUEST['orderUp'] => 'ASC'
		);

	echo $r->render($PDOdb, $pfs->getSQLFetchAll(), array (
			'limit' => array (
					'page' => (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1),
					'nbLine' => $conf->liste_limit
			), // Limite dolibarr modifiable sur fiche user
			'link' => array (
					'ref' => img_picto('', 'object_dir') . ' <a href="?id=@ID@">@val@</a>'
			),
			'hide' => array (
					'ID','fk_user_modification','fk_user_creation','entity','fk_usergroup'
			),
			'title' => $pfs->getTrans(),
			'liste' => array (
					'titre' => $langs->trans('PFSectionList'),
					'image' => img_picto('', 'planformation@planformation', '', 0),
					'messageNothing' => $langs->transnoentities('NoRecDossierToDisplay')
			),
			'orderBy' => $TOrder
	));

	$PDOdb->execute($sql);
}

/**
 *
 * @param TPDOdb $PDOdb
 * @param TPlanFormation $pf
 * @param string $mode
 */
function _info(TPDOdb &$PDOdb, TSection &$pfs) {
	global $db, $langs, $user, $conf;

	dol_include_once('/planformation/lib/planformation.lib.php');
	require_once (DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php');

	llxHeader('', $langs->trans("PFPlanFormation"));
	$head = planformation_section_prepare_head($pfs);
	dol_fiche_head($head, 'info', $langs->trans("PFSectionCard"), 0);

	$pfs->date_creation = $pfs->date_cre;
	$pfs->date_modification = $pfs->date_maj;
	$pfs->user_creation = $pfs->fk_user_creation;
	$pfs->user_modification = $pfs->fk_user_modification;
	print '<table width="100%"><tr><td>';
	dol_print_object_info($pfs);
	print '</td></tr></table>';
	print '</div>';
}

/**
 *
 * @param TPDOdb $PDOdb
 * @param TPlanFormation $pf
 * @param string $mode
 */
function _card(TPDOdb &$PDOdb, TSection &$pfs, $mode = '') {
	global $db, $langs, $user, $conf;

	dol_include_once('/planformation/lib/planformation.lib.php');

	llxHeader('', $langs->trans("PFSection"));
	$head = planformation_section_prepare_head($pfs);
	dol_fiche_head($head, 'section', $langs->trans("PFSectionCard"), 0);

	$formDoli = new Form($db);
	$formCore = new TFormCore($_SERVER['PHP_SELF'], 'formscore', 'POST');
	$formCore->Set_typeaff($mode);

	echo $formCore->hidden('id', $pfs->getId());
	echo $formCore->hidden('action', 'save');
	echo $formCore->hidden('entity', getEntity());
	echo $formCore->hidden('plan_id', $_REQUEST['plan_id']);
	
	$TBS = new TTemplateTBS();

	//Find all existing user group
	$usergroupsArray=array();
	$usergroups=new TPFUserGroup($db);
	$result=$usergroups->fetchAll('ASC','t.nom',0,0);
	if ($result<0) {
		setEventMessages(null, $usergroups->errors,'errors');
	} else {
		if (is_array($usergroups->lines) && count($usergroups->lines)>0) {
			foreach($usergroups->lines as $line) {
				$usergroupsArray[$line->id]=$line->nom;
			}
		}
	}
        $planId = GETPOST('plan_id');
        
        $btSave = $formCore->btsubmit($langs->trans('Valid'), 'save');
        
        if(!empty($planId)) {
            $btCancel = '<a class="butAction" href="' . dol_buildpath('/planformation/section.php?id=' . $pfs->rowid . '&plan_id=' .  GETPOST('plan_id'), 1) . '">' . $langs->trans('Cancel') . '</a>';
            $btModifier = '<a class="butAction" href="' . dol_buildpath('/planformation/section.php?id=' . $pfs->rowid . '&plan_id=' . GETPOST('plan_id') . '&action=edit', 1) . '">' . $langs->trans('PFSectionEdit') . '</a>';
            $btRetour = '<a class="butAction" href="' . dol_buildpath('/planformation/planformation.php?id=' . $planId, 1) . '">' . $langs->trans('BackToList') . '</a>';
	}
        else {
            $btRetour = '<a class="butAction" href="' . dol_buildpath("/planformation/section.php?action=list", 1) . '">' . $langs->trans('BackToList') . '</a>';
            $btCancel = '<a class="butAction" href="' . dol_buildpath('/planformation/section.php?id=' . $pfs->rowid, 1) . '">' . $langs->trans('Cancel') . '</a>';
            $btModifier = '<a class="butAction" href="' . dol_buildpath('/planformation/section.php?id=' . $pfs->rowid . '&action=edit', 1) . '">' . $langs->trans('PFSectionEdit') . '</a>';
        }
	
	$btDelete = "<input type=\"button\" id=\"action-delete\" value=\"" . $langs->trans('Delete') . "\" name=\"cancel\" class=\"butActionDelete\" onclick=\"if(confirm('" . 'Etes vous sûr de supprimer ?'./*. $langs->trans('PFDeleteConfirm') .*/ "'))document.location.href='?action=delete&id=" . $pfs->rowid . "'\" />";

	// Fill form with title and data
	$data = $pfs->getTrans('title');
	$planformSection = new TSectionPlanFormation();
        $planformSection->loadByCustom($PDOdb, array('fk_planform' => $_REQUEST['plan_id'], 'fk_section' => $_REQUEST['id']));
	
	
        $data['budget_title'] = 'Budget';
        $data['fk_section_parente_title'] = 'Section Parente';
        $data['plan_id'] = GETPOST('plan_id', 'int');
		
	if ($mode == 'edit') {
		$data['titre'] = load_fiche_titre($pfs->getId() > 0 ? $langs->trans("PFSectionEdit") : $langs->trans("PFSectionNew"), '');
		$data['title'] = $formCore->texte('', 'title', $pfs->title, 30, 255);
                $data['budget'] = $formCore->texte('', 'budget', $planformSection->budget, 30, 255);
                
                if(GETPOST('action') !== 'new')
                    $availableSection = $planformSection->getAvailableParentSection($PDOdb);
                
                $data['fk_section_parente'] = $formCore->combo("", 'fk_section_parente', $availableSection, $planformSection->fk_section_parente);
                
		if ($conf->global->PF_SECTION_ADDON == 'mod_planformation_section_universal') {
			$data['ref'] = $formCore->texte('', 'ref', $pfs->ref, 15, 255);
		} elseif ($conf->global->PF_SECTION_ADDON == 'mod_planformation_section_simple') {
			$result = $pfs->getNextNumRef();
			if ($result == - 1) {
				setEventMessages(null, $pf->errors, 'errors');
			}
			$data['ref'] = $result;
			echo $formCore->hidden('ref', $result);
		}
		$data['fk_usergroup'] = $formCore->combo('', 'fk_usergroup', $usergroupsArray, empty($pfs->fk_usergroup)?'':$pfs->fk_usergroup);

		$buttons = $btCancel . $btSave;
	} else {
		$data['titre'] = load_fiche_titre($langs->trans("PFSectionCard"), '');
		$data['title'] = $pfs->title;
                $data['budget'] = $planformSection->budget;
                $data['ref'] = $formCore->texte('', 'ref', $pfs->ref, 15);
		$data['fk_usergroup'] =  $usergroupsArray[$pfs->fk_usergroup];
		$buttons = $btRetour . ' ' . $btModifier . ' ' . $btDelete;
                $sectionParente = new TSection();
                $sectionParente->load($PDOdb, $planformSection->fk_section_parente);
                
                $data['fk_section_parente'] = $sectionParente->title;
	}
	// Todo mandatory fields

	print $TBS->render('./tpl/section.tpl.php', array (),

	array (
			'section' => $data,
			'view' => array (
					'mode' => $mode
			),
			'buttons' => array (
					'buttons' => $buttons
			)
	));

	echo $formCore->end_form();
}
