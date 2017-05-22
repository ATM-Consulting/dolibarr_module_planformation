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
require_once '../../core/lib/treeview.lib.php';

// Security check
if ($user->societe_id)
	$socid = $user->societe_id;
$result = restrictedArea($user, 'planformation', 0, 'planformation');

require ('./class/planformation.class.php');
require ('./class/dictionnaire.class.php');
require ('./class/pfusergroup.class.php');

$langs->load('planformation@planformation');

$PDOdb = new TPDOdb();

// $PDOdb->debug = true;

$tbs = new TTemplateTBS();
$pf = new TPlanFormation();
$typeFin = new TTypeFinancement();

$action = GETPOST('action');
$id = GETPOST('id', 'int');


if(! empty($id)) {
	if(! $pf->load($PDOdb, $id)) {
		setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
		_list($PDOdb, $pf);
		exit;
	}
}


switch ($action) {
	case 'add':
	case 'new':
		$pf->set_values($_REQUEST);

		_card($PDOdb, $pf, $typeFin, 'edit');
	break;

	case 'setopcaanswer':
		_card($PDOdb, $pf, $typeFin, 'setopcaanswer');
	break;

	case 'saveopcaanswer':
		//$pf->set_values($_REQUEST);

		$answer = GETPOST('answer', 'int');

		$url = $_SERVER['PHP_SELF'] . '?id=' . $pf->id;

		switch($answer) {
			case '0':
				$pf->set_values($_REQUEST);
				$pf->validate($PDOdb);
			break;

			case '1':
				$pf->set_values($_REQUEST);
				$pf->abandon($PDOdb);
			break;

			case '2':
				$pf->set_values($_REQUEST);
				$pf->rework($PDOdb);
			break;

			case '-1':
			default:
				$url .= '&action=setopcaanswer';
				setEventMessage('PFSetAnAnswer', 'errors');
		}

		header('Location: '.$url);
		exit;
	break;

	case 'info':
		_info($PDOdb, $pf);
	break;

	case 'edit':
		_card($PDOdb, $pf, $typeFin, 'edit');
	break;

	case 'save':
		$pf->set_values($_REQUEST);
		$pf->save($PDOdb);
		_card($PDOdb, $pf, $typeFin, 'view');
	break;
	
	case 'delete':
		$pf->delete($PDOdb);

		_list($PDOdb, $pf);
	break;

	case 'addsection':
	case 'savesection':
		$section = new TSectionPlanFormation();
		$sectionId = GETPOST('section', 'int');

		if(! empty($sectionId)) {
			$section->load($PDOdb, $sectionId);
		}

		$section->title = GETPOST('title', 'alpha');
		$section->fk_usergroup = GETPOST('fk_usergroup', 'int');
		$section->fk_planform = $pf->id;
		$section->fk_section = GETPOST('fk_section', 'int');
		$section->fk_section_parente = GETPOST('fk_section_mere', 'int');
		$section->budget = GETPOST('budget', 'int');

		if(! empty($section->fk_section_parente)) {
			$sectionParente = new TSectionPlanFormation();
			$sectionParente->load($PDOdb, $section->fk_section_parente);

			$budgetRestant = $sectionParente->getRemainingBudget($PDOdb);

		} else {
			$budgetRestant = $pf->getRemainingBudget($PDOdb);
		}

		if($budgetRestant >= $section->budget) {
			$section->save($PDOdb);
		} else {
			setEventMessage('PFBudgetOverflow', 'errors');
		}

		header('Location: '.$_SERVER['PHP_SELF'] . '?id=' . $pf->id);
		exit;
	break;

	case 'deletesection':
		$sectionId = GETPOST('section', 'int');
		$section = new TSectionPlanFormation();
		if($section->load($PDOdb, $sectionId)) {
			if($section->fk_planform == $id) { 
				$section->delete($PDOdb);
			}
		}

		_card($PDOdb, $pf, $typeFin, 'view');
	break;

	case 'editsection':
		_card($PDOdb, $pf, $typeFin, 'editsection');
	break;

	case 'delete_link':
		$sectionId = GETPOST('section_id', 'int');

		if(! empty($sectionId)) {
			$link_pfs = new TSectionPlanFormation();
			$link_pfs->loadByCustom ($PDOdb, array(
				'fk_planform' => $pf->rowid,
				'fk_section' => $sectionId
			));

			$link_pfs->delete($PDOdb);

			header('Location: '.$_SERVER['PHP_SELF'] . '?id=' . $pf->id);
			exit;
		}

		// TODO setEventMessage

		_card($PDOdb, $pf, $typeFin, 'view');
	break;

	case 'propose':
		$pf->propose($PDOdb);

		_card($PDOdb, $pf, $typeFin, 'view');
	break;

	case 'rework':
		$pf->rework($PDOdb);

		_card($PDOdb, $pf, $typeFin, 'view');
	break;
		
	case 'validate':
		$pf->validate($PDOdb);
		
		_card($PDOdb, $pf, $typeFin, 'view');
	break;
		
	case 'abandon':
		$pf->abandon($PDOdb);
		
		_card($PDOdb, $pf, $typeFin, 'view');
	break;
		
	case 'reopen':
		$pf->reopen($PDOdb);
		
		_card($PDOdb, $pf, $typeFin, 'view');
	break;
	
	case 'list':
		_list($PDOdb, $pf);
	break;

	default:
		if (! empty ($id))
			_card($PDOdb, $pf, $typeFin, 'view');
		else
			_list($PDOdb, $pf);
}


/**
 *
 * @param TPDOdb $PDOdb
 * @param TPlanFormation $pf
 */
function _list(TPDOdb &$PDOdb, TPlanFormation &$pf) {
	global $langs, $db, $conf, $user, $action;

	llxHeader('', $langs->trans('PFPlanFormationList'));

	$r = new TListviewTBS('planform');

	$TOrder = array (
			'planform.date_start' => 'DESC'
	);
	if (isset($_REQUEST['orderDown']))
		$TOrder = array (
				$_REQUEST['orderDown'] => 'DESC'
		);
	if (isset($_REQUEST['orderUp']))
		$TOrder = array (
				$_REQUEST['orderUp'] => 'ASC'
		);

	$formCore = new TFormCore($_SERVER['PHP_SELF'], 'formscore', 'GET');

	echo $r->render($PDOdb, $pf->getSQLFetchAll(), array (
			'limit' => array (
					'page' => (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1),
					'nbLine' => $conf->liste_limit
			),
			'link' => array (
					'ref' => img_picto('', 'object_planformation@planformation') . ' <a href="?id=@ID@">@val@</a>'
					, 'opca' => img_picto('', 'object_company') . ' <a href="'. dol_buildpath('/societe.php', 1).'?id=@fk_opca@">@val@</a>'
			),
			'type' => array (
					'date_start' => 'date',
					'date_end' => 'date'
			),
			'hide' => array (
					'ID',
					'fk_type_financement',
					'type_fin_code',
					'fk_user_modification',
					'fk_user_creation',
					'entity',
					'fk_opca',
					'budget_finance_accepte',
					'budget_finance_reel',
					'type_fin_label'
					
			),
			'title' => $pf->getTrans(),
			'liste' => array (
					'titre' => $langs->trans('PFPlanFormationList'),
					'image' => img_picto('', 'planformation@planformation', '', 0),
					'messageNothing' => $langs->transnoentities('NoRecDossierToDisplay')
			),
			'search' => array (
					'date_start' => array (
							'recherche' => 'calendars',
							'table' => 'planform'
					),
					'date_end' => array (
							'recherche' => 'calendars',
							'table' => 'planform'
					),
					'title' => array (
							'recherche' => true,
							'table' => 'planform'
					),
					'ref' => array (
							'recherche' => true,
							'table' => 'planform'
					),
					'opca' => array (
							'recherche' => true,
							'table' => 'soc',
							'field' => 'nom'
					)
			),
			'orderBy' => $TOrder
	));

	$formCore->end();
	
	llxFooter();
}


/**
 *
 * @param TPDOdb $PDOdb
 * @param TPlanFormation $pf
 * @param string $mode
 */
function _info(TPDOdb &$PDOdb, TPlanFormation &$pf) {
	global $db, $langs, $user, $conf;

	dol_include_once('/planformation/lib/planformation.lib.php');
	require_once (DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php');

	llxHeader('', $langs->trans('PFPlanFormation'));
	$head = planformation_prepare_head($pf);
	dol_fiche_head($head, 'info', $langs->trans('PFPlanFormation'), 0);

	$pf->date_creation = $pf->date_cre;
	$pf->date_modification = $pf->date_maj;
	$pf->user_creation = $pf->fk_user_creation;
	$pf->user_modification = $pf->fk_user_modification;
	print '<table width="100%"><tr><td>';
	dol_print_object_info($pf);
	print '</td></tr></table>';
	print '</div>';

	llxFooter();
}


/**
 *
 * @param TPDOdb $PDOdb
 * @param TPlanFormation $pf
 * @param string $mode
 */
function _card(TPDOdb &$PDOdb, TPlanFormation &$pf, TTypeFinancement &$typeFin, $mode = 'view') {
	global $db, $langs, $user, $conf;

	dol_include_once('/planformation/lib/planformation.lib.php');

	$arrayofjs = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
	$arrayofcss = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');
	
	llxHeader('', $langs->trans("PFPlanFormation"),'','',0,0,$arrayofjs,$arrayofcss);

	$head = planformation_prepare_head($pf);
	dol_fiche_head($head, 'planformation', $langs->trans('PFPlanFormation'), 0);


	$formCore = new TFormCore($_SERVER['PHP_SELF'] . '?id=' . $pf->id, 'formscore', 'POST');


	$formCore->Set_typeaff($mode);
        
	if ($pf->getId() <= 0) {
		echo $formCore->hidden('fk_user_creation', $user->id);
	} else {
		echo $formCore->hidden('fk_user_creation', $pf->fk_user_creation);
	}
	echo $formCore->hidden('fk_user_modification', $user->id);

	$TBS = new TTemplateTBS();


	$btSave = '<button type="submit" class="butAction">' . $langs->trans('Save') . '</button>';
	$btCancel = '<a class="butAction" href="'. $_SERVER['PHP_SELF'] .'?id='. $pf->rowid .'">' . $langs->trans('Cancel') . '</a>'; //$formCore->btsubmit($langs->trans('Valid'), 'save');

	$btRetour = '<a class="butAction" href="' . dol_buildpath("/planformation/planformation.php?action=list", 1) . '">' . $langs->trans('BackToList') . '</a>';
	$btModifier = '<a class="butAction" href="' . dol_buildpath('/planformation/planformation.php?id=' . $pf->rowid . '&action=edit', 1) . '">' . $langs->trans('Modify') . '</a>';

	$btReponseOPCA = '<a class="butAction" href="'. $_SERVER['PHP_SELF'] . '?id=' . $pf->rowid.'&action=setopcaanswer">'.$langs->trans('SetAcceptedRefused').'</a>';

	$btProposer = '<a class="butAction" href="'. $_SERVER['PHP_SELF'] . '?id=' . $pf->rowid.'&action=propose" onclick="javascript:return confirm(\'' . $langs->trans('PFProposeConfirm') . '\')">'.$langs->trans('PFPropose').'</a>';
	$btValider = '<a class="butAction" href="'. $_SERVER['PHP_SELF'] . '?id=' . $pf->rowid.'&action=validate" onclick="javascript:return confirm(\'' . $langs->trans('PFValidateConfirm') . '\')">'.$langs->trans('Validate').'</a>';
	$btAbandonner = '<a class="butAction" href="'. $_SERVER['PHP_SELF'] . '?id=' . $pf->rowid.'&action=abandon" onclick="javascript:return confirm(\'' . $langs->trans('PFAbandonConfirm') . '\')">'.$langs->trans('PFAbandon').'</a>';
	$btRouvrir = '<a class="butAction" href="'. $_SERVER['PHP_SELF'] . '?id=' . $pf->rowid.'&action=reopen" onclick="javascript:return confirm(\'' . $langs->trans('PFReopenConfirm') . '\')">'.$langs->trans('PFReopen').'</a>';
	$btRetravailler = '<a class="butAction" href="'. $_SERVER['PHP_SELF'] . '?id=' . $pf->rowid.'&action=rework" onclick="javascript:return confirm(\'' . $langs->trans('PFReworkConfirm') . '\')">'.$langs->trans('PFRework').'</a>';

	$btDelete = '<a class="butAction" href="'. $_SERVER['PHP_SELF'] . '?id=' . $pf->rowid.'&action=delete" onclick="javascript:return confirm(\'' . $langs->trans('PFDeleteConfirm') . '\')">' . $langs->trans('Delete') . '</a>'; //"\" name=\"cancel\" class=\"butActionDelete\" onclick=\"if(confirm('" . $langs->trans('PFDeleteConfirm') . "'))document.location.href='?action=delete&id=" . $pf->rowid . "'\" />";

	// Load type fin data
	$result = $typeFin->fetchAll($PDOdb, $typeFin->get_table());
	if ($result < 0) {
		setEventMessages(null, $typeFin->errors, 'errors');
	}

	// Fill form with title and data
	$data = $pf->getTrans('title');


	switch($pf->statut) {
		case 0:
			$buttons = $btRetour . ' ' . $btModifier . ' ' . $btProposer . ' ' . $btAbandonner . ' ' . $btDelete;
			$data['statut'] = 'Brouillon';
		break;
		case 1:
			$buttons = $btRetour . ' ' . $btReponseOPCA . ' ' . $btAbandonner . ' ' . $btDelete;
			$data['statut'] = 'En attente';
		break;
		case 2:
			$buttons = $btRetour . ' ' . $btRouvrir. ' ' . $btDelete;
			$data['statut'] = 'Validée';
		break;
		case 3:
			$buttons = $btRetour . ' ' . $btRouvrir. ' ' . $btDelete;
			$data['statut'] = 'Abandonnée';
		break;
	}



	if ($mode == 'edit') {
		
		dol_include_once('/core/class/html.form.class.php');
		
		$form = new Form($db);

		$data['titre'] = load_fiche_titre($pf->getId() > 0 ? $langs->trans("PFPlanFormationEdit") : $langs->trans("PFPlanFormationNew"), '');
		$data['title'] = $formCore->texte('', 'title', $pf->title, 30, 255);
		$data['type_fin_label'] = $formCore->combo('', 'fk_type_financement', $typeFin->lines, '');
		$data['date_start'] = $formCore->doliCalendar('date_start', $pf->date_start);
		$data['date_end'] = $formCore->doliCalendar('date_end', $pf->date_end);

		$data['opca'] = $form->select_company($pf->fk_opca, 'fk_opca', 's.fournisseur = 1');
		// Ici
		$data['budget_previsionnel'] = $formCore->texte('', 'budget_previsionnel', $pf->budget_previsionnel, 30, 255);
		$data['budget_finance_reel'] = price($pf->budget_finance_reel, 1, $langs, 1, -1, -1, 'auto');
		$data['budget_finance_accepte'] = price($pf->budget_finance_accepte, 1, $langs, 1, -1, -1, 'auto');
		$data['budget_finance_reel'] = price($pf->budget_finance_reel, 1, $langs, 1, -1, -1, 'auto');
		$data['budget_consomme'] = price($pf->budget_consomme, 1, $langs, 1, -1, -1, 'auto');
		// Jusque là

		if ($conf->global->PF_ADDON == 'mod_planformation_universal') {
			$data['ref'] = $formCore->texte('', 'ref', $pf->ref, 15, 255);
		} elseif ($conf->global->PF_ADDON == 'mod_planformation_simple') {
			$result = $pf->getNextNumRef();
			if ($result == - 1) {
				setEventMessages(null, $pf->errors, 'errors');
			}
			$data['ref'] = $result;
			echo $formCore->hidden('action', 'save');
			echo $formCore->hidden('ref', $result);
		}

		$buttons = $btSave . ' ' . $btCancel;
	} else {
		$data['titre'] = load_fiche_titre($langs->trans("PFPlanFormationCard"), '');
		$data['type_fin_label'] = $typeFin->lines[$pf->fk_type_financement];
		$data['date_start'] = dol_print_date($pf->date_start);
		$data['date_end'] = dol_print_date($pf->date_end);
		$data['title'] = $pf->title;

		$opca = new Societe($db);
		
		if(! empty($pf->fk_opca)) {
			$opca->fetch($pf->fk_opca);
			$data['opca'] = $opca->getNomUrl(1);
		} else {
			$data['opca'] = $langs->trans('PFNoOPCASelected');
		}

		// Ici
		$data['budget_previsionnel'] = price($pf->budget_previsionnel, 1, $langs, 1, -1, -1, 'auto');
		$data['budget_finance_accepte'] = price($pf->budget_finance_accepte, 1, $langs, 1, -1, -1, 'auto');
		$data['budget_finance_reel'] = price($pf->budget_finance_reel, 1, $langs, 1, -1, -1, 'auto');
		$data['budget_consomme'] = price($pf->budget_consomme, 1, $langs, 1, -1, -1, 'auto');
		// Jusque là
		$data['ref'] = $formCore->texte('', 'ref', $pf->ref, 15);
	}
		
	// Todo mandatory fields
	print $TBS->render('./tpl/planformation.tpl.php', array(), array(
		'planformation' => $data
		,'view' => array (
			'mode' => $mode 
		)
		,'buttons' => array (
			'buttons' => $buttons 
		) 
	));

	$formCore->end();

	if($pf->statut == 1 && $mode == 'setopcaanswer') {
		_formReponseOPCA($PDOdb, $pf);
	}

	if($mode != 'edit') {
		_listPlanFormSection($PDOdb, $pf, $typeFin, $mode);
	}

	llxFooter();
}


function _formReponseOPCA(&$PDOdb, &$pf) {
	global $langs;
	print load_fiche_titre($langs->trans("PFSetOPCAResponse"), '');

	$formCore = new TFormCore($_SERVER['PHP_SELF'] . '?id=' . $pf->rowid, 'setopcaanswer', 'POST');

	$TReponses = array(
		-1 => ''
		, 0 => $langs->trans('PFAccepted')
		, 1 => $langs->trans('PFRefused')
		, 2 => $langs->trans('PFPartialAnswer')
	);

	print $formCore->hidden('action', 'saveopcaanswer');
	print $formCore->hidden('rowid', $pf->rowid);

	print '<table class="border" width="100%">';

	print '<tr><td class="titlefieldcreate">' . $langs->trans('PFAnswerDate') . '</td>';
	print '<td>' . $formCore->doliCalendar('date_reponse', dol_now());

	print '<tr><td class="titlefieldcreate">' . $langs->trans('PFAnswer') . '</td>';
	print '<td>' . $formCore->combo('', 'answer', $TReponses, -1) . '</td></tr>';
	
	print '<tr><td class="titlefieldcreate">' . $langs->trans('PFApprovedFundedBudget') . '</td>';
	print '<td>' . $formCore->texte('', 'budget_finance_accepte', '', 12) . '</td></tr>';
	
	print '</table>';

	print '<div class="tabsAction">';
	print '<button type="submit" class="butAction">' . $langs->trans('Validate') . '</button>';
	print ' <a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $pf->rowid . '">' . $langs->trans('Cancel') . '</a>';
	print '</div>';

	$formCore->end();
}


/**
 *
 * @param TPDOdb $PDOdb
 * @param TPlanFormation $pf
 * @param TTypeFinancement $typeFin
 */
function _listPlanFormSection(TPDOdb &$PDOdb, TPlanFormation &$pf, TTypeFinancement &$typeFin, $mode) {
	global $db, $langs;

	$obj = new TSectionPlanFormation();
	$TSections = array();
	$obj->getAllSection($PDOdb, $TSections, $pf->id);

	print load_fiche_titre($langs->trans("ListOfPFSection"). ' ('.$langs->trans("HierarchicView").')', '');
	

	$data = array(array(
		'rowid' => 0
		,'fk_menu' => -1
		,'title' => 'racine'
	));

	
	$sectionId = GETPOST('section', 'int');

	foreach($TSections as $section) {

		if($mode == 'editsection' && ! empty($sectionId) && $sectionId == $section['rowid']) {
			$formCore = new TFormCore;
			$form = new Form($db);

			$sectionsKeyVal = array('0' => $langs->trans('PFNoMotherSection'));
			
			foreach($pf->TSectionPlanFormation as $sectionPF) {
				$sectionsKeyVal[$sectionPF->id] = $sectionPF->title;
			}

			$entry = $formCore->begin_form($_SERVER['PHP_SELF'] . '?id=' . $pf->id, 'formeditsection', 'POST');
			$entry.= '<table class="nobordernopadding centpercent"><tr>';
			$entry.= '<td>' . img_picto('', 'object_dir') . ' ' . $formCore->texte('', 'title', $section['title'], 64). '</td>';
			$entry.= '<td width="300px">' . $formCore->combo('', 'fk_section_mere', $sectionsKeyVal, $section['fk_section_parente'], 1, '', ' style="min-width:150px"'). '</td>';
			$entry.= '<td width="250px">' . $form->select_dolgroups($section['fk_usergroup'], 'fk_usergroup') .'</td>';
			$entry.= '<td width="150px">' . $formCore->texte('', 'budget', $section['budget'], 20, 20, ' style="width:100px"') . '</td>';
			$entry.= '<td align="right" width="100px">' . $formCore->hidden('action', 'savesection') . $formCore->btImg($langs->trans('Modify'), 'editsection', dol_buildpath('/theme/eldy/img/tick.png', 1)). '<a href="' . $_SERVER['PHP_SELF'] .'?id=' . $pf->id . '">' . img_picto($langs->trans('Cancel'), 'close') . '</a></td>';
			$entry.= '</tr></table>';
			$entry.= $formCore->end_form();
		} else {
			$actionsButtons = '';

			if($pf->statut == 0) {
				$actionButtons = '<a href="planformation.php?id=' . $pf->id . '&action=editsection&section=' . $section['rowid']. '">' . img_picto('', 'edit') . '</a>
					<a href="planformation.php?id=' . $pf->id . '&action=deletesection&section=' . $section['rowid'] . '">' . img_picto('', 'delete') . '</a>';
			}

			$entry = '<table class="nobordernopadding centpercent"><tr>';
			$entry.= '<td colspan="2">' . img_picto('', 'object_dir') . ' ' . $section['title']. '</td>';
			$entry.= '<td width="250px">' . $section['groupe'] .'</td>';
			$entry.= '<td width="150px">' . price($section['budget'], 1, $langs, 1, -1, -1, 'auto') . '</td>';
			$entry.= '<td align="right" width="100px">' . $actionButtons . '</td>';
			$entry.= '</tr></table>';
		}
		
		$data[] = array(
				'rowid' => $section['rowid']
				,'fk_menu'=> $section['fk_section_parente']
				,'entry' => $entry
		);
	}


	print '<table class="liste centpercent">';
	print '<tr class="liste_titre">';
	if($mode == 'editsection' && ! empty($sectionId)) {
		print '<th class="liste_titre">' . $langs->trans('Title') . '</th>';
		print '<th class="liste_titre" width="300px">' . $langs->trans('PFMotherSection') . '</th>';
		
	} else {
		print '<th class="liste_titre" colspan="2">' . $langs->trans('Title') . '</th>';
	}
	print '<th class="liste_titre" width="250px">' . $langs->trans('PFUsergroup') . '</th>';
	print '<th class="liste_titre" width="150px">' . $langs->trans('PFBudget') . '</th>';
	print '<th class="liste_titre" align="right" width="100px">&nbsp;</th>';
	print '</tr>';

	$nbofentries = (count($data) - 1);

	if($nbofentries > 0) {
		print '<tr class="impair"><td colspan="5">';
		tree_recur($data, $data[0], 0);
		print '</td></tr>';
	} else {
		print '<tr class="impair"><td colspan="5" align="center">';
		print $langs->trans('NoSectionsInPF');
		print '</td></tr>';
	}


	// Ajout nouvelle section

	if($mode == 'view' && $pf->statut == 0) {
		print '<tr class="liste_titre"><td colspan="5">' . $langs->trans('AddNewPFSection') . '</td></tr>';

		$formCore = new TFormCore($_SERVER['PHP_SELF'] . '?id=' . $pf->id, 'formaddSection', 'POST');
	
		$sectionsKeyVal = array('0' => $langs->trans('PFNoMotherSection'));
	
		foreach($pf->TSectionPlanFormation as $sectionPF) {		
			$sectionsKeyVal[$sectionPF->id] = $sectionPF->title;
		}

		dol_include_once('/core/class/html.form.class.php');

		$form = new Form($db);

		print '<tr class="liste_titre">';
		print '<th class="liste_titre">' . $langs->trans('Title') . '</th>';
		print '<th class="liste_titre" width="300px">' . $langs->trans('PFMotherSection') . '</th>';
		print '<th class="liste_titre">' . $langs->trans('PFUsergroup') . '</th>';
		print '<th class="liste_titre">' . $langs->trans('PFBudget') . '</th>';
		print '<th class="liste_titre" align="right" width="100px">&nbsp;</th>';
		print '</tr>';

		print '<tr class="impair">';
		print '<td>' . $formCore->texte('', 'title', '', 64) . '</td>';
		print '<td>' . $formCore->combo('', 'fk_section_mere', $sectionsKeyVal, '0', 1, '', ' style="min-width:150px"'). '</td>';
		print '<td>' . $form->select_dolgroups('', 'fk_usergroup') .'</td>';
		print '<td>' . $formCore->texte('', 'budget', '', 20, 20, ' style="width:100px"') . '</td>';
		print '<td align="right">'. $formCore->hidden('action', 'addsection') . $formCore->btsubmit($langs->trans('Add'), 'addsection') . '</td>';
		print '</tr>';

		$formCore->end();
	}

	print "</table>";
}

