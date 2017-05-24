<?php

require_once('config.php');

// Security check
if ($user->societe_id)
	$socid = $user->societe_id;

$result = restrictedArea($user, 'planformation', 0, 'planformation');
	
require_once('./class/formation.class.php');

$langs->load('planformation@planformation');
	
$PDOdb = new TPDOdb;

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

$formation = new TFormation($PDOdb);

if(! empty($id)) {
	if(! $formation->load($PDOdb, $id)) {
		setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
		_list($PDOdb, $formation);
		exit;
	}
}


switch($action) {
	case 'add':
	case 'save':
		$formation->set_values($_REQUEST);

		if($action == 'add') {
			$formation->fk_user_creation = $user->id;
		} else {
			$formation->fk_user_modification = $user->id;
		}

		$formation->save($PDOdb);

		header('Location: ' . $_SERVER['PHP_SELF'] . '?id='. $formation->rowid);
		exit;
	break;

	case 'new':
	case 'edit':
		_card($PDOdb, $formation, 'edit');
	break;

	case 'info':
		_info($PDOdb, $formation);
	break;

	case 'list':
		_list($PDOdb, $formation);
	break;

	default:
		if(empty($id)) {
			_list($PDOdb, $formation);
		} else {
			_card($PDOdb, $formation, 'view');
		}
}



function _list(&$PDOdb, &$formation) {
	global $langs, $conf;

	llxHeader('', $langs->trans('PFFormationList'));

	$list = new TListviewTBS('formation');

	$sql = "SELECT rowid, title, CONCAT(duree, ' h') AS duree, budget_total FROM " . $formation->get_table();

	$TOrder = array('rowid' => 'ASC');

	$page = GETPOST('page', 'int');
	$orderDown = GETPOST('orderDown', 'alpha');
	$orderUp = GETPOST('orderUp', 'alpha');

	if(! empty($orderDown))
		$TOrder = array($orderDown => 'DESC');

	if(! empty($orderUp))
		$TOrder = array($orderUp => 'ASC');


	$form = new TFormCore($_SERVER['PHP_SELF'] . '?action=list', 'formation_list', 'POST');

	echo $list->render($PDOdb, $sql, array(
		'liste' => array (
				'titre' => $langs->trans('PFFormationList')
				, 'image' => img_picto('', 'planformation@planformation', '', 0)
				, 'messageNothing' => $langs->transnoentities('NoRecDossierToDisplay')
		)
		, 'limit' => array (
				'page' => (! empty($page)) ? $page : 1
				, 'nbLine' => $conf->liste_limit
		)
		, 'link' => array (
				'rowid' => '<a href="?id=@val@">@val@</a>'
		)
		, 'title' => array(
				'rowid' => 'ID'
				, 'title' => $langs->trans('Title')
				, 'duree' => $langs->trans('Duration')
				, 'budget_total' => $langs->trans('PFTotalBudget')
		)
		, 'search' => array (
				'title' => array (
				 		'recherche' => true,
						'table' => $formation->get_table()
				)
		)
		, 'orderBy' => $TOrder
	));

	$form->end();

	llxFooter();
}


function _info(&$PDOdb, &$formation) {
	global $langs;

	_header_card($formation, 'info');

	echo 'INFO';

	// TODO remplir...

	llxFooter();
}



function _card(&$PDOdb, &$formation, $mode = 'view') {
	global $langs;

	_header_card($formation, 'formation');


	$TBS = new TTemplateTBS;

	$TDataFormation = array();

	$url = $_SERVER['PHP_SELF'];

	if(! empty($formation->rowid)) {
		$url.= '?id=' . $formation->rowid;
	}

	$form = new TFormCore($url, 'edit_formation', 'POST');


	$btSave = '<button type="submit" class="butAction">' . $langs->trans('Save') . '</button>';
	$btCancel = '<a class="butAction" href="javascript:history.back()">' . $langs->trans('Cancel') . '</a>';

	$btModifier = '<a class="butAction" href="' . dol_buildpath('/planformation/formation.php?id=' . $formation->rowid . '&action=edit', 1) . '">' . $langs->trans('Modify') . '</a>';


	$TDataFormation['budget_total'] = price(0, 1, $langs, 1, -1, -1, 'auto');

	if($mode == 'edit') {
		$TDataFormation['id'] = empty($formation->rowid) ? $formation->getNextId($PDOdb) : $formation->rowid.$form->hidden('rowid', $formation->rowid);
		$TDataFormation['title'] = $form->texte('', 'title', $formation->title, 64);
		$TDataFormation['duree'] = $form->texte('', 'duree', $formation->duree, 5) . ' h';

		$submitAction = empty($formation->rowid) ? 'add' : 'save';

		$buttons = $form->hidden('action', $submitAction) . $btSave. ' ' . $btCancel;
	} else {
		$TDataFormation['id'] = $formation->rowid;
		$TDataFormation['title'] = $formation->title;
		$TDataFormation['duree'] = $formation->duree . ' h';

		$buttons = $btModifier;
	}


	print $TBS->render('./tpl/formation.tpl.php', array(), array(
			'formation' => $TDataFormation
			, 'trans' => array(
					'id' => 'ID'
					, 'title' => $langs->transnoentitiesnoconv('Title')
					, 'duree' => $langs->transnoentitiesnoconv('Duration')
					, 'budget_total' => $langs->transnoentitiesnoconv('PFTotalBudget')
			)
			, 'buttons' => $buttons
	));

	$form->end();

	_list_sessions($PDOdb, $formation);

	llxFooter();
}



function _header_card(&$formation, $active) {
	global $langs;
	
	dol_include_once('/planformation/lib/planformation.lib.php');
	
	llxHeader('', $langs->trans("PFFormation"),'','',0,0);
	
	$head = formation_prepare_head($formation);
	dol_fiche_head($head, $active, $langs->trans('PFFormation'), 0);
}



function _list_sessions(&$PDOdb, &$formation) {
	global $langs;

	$list = new TListviewTBS('session');
	
	$sql = "SELECT s.rowid, ref, s.fk_formation, f.title AS formation, date_debut, date_fin, fk_opca, soc.nom AS opca, budget";
	$sql.= " FROM " . MAIN_DB_PREFIX . "planform_session AS s";
	$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "planform_formation AS f ON (f.rowid=s.fk_formation)";
	$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe AS soc ON (soc.rowid=s.fk_opca)";
	$sql.= " WHERE fk_formation = " . $formation->rowid;
	
	
	$TOrder = array('rowid' => 'ASC');
	
	$page = GETPOST('page', 'int');
	$orderDown = GETPOST('orderDown', 'alpha');
	$orderUp = GETPOST('orderUp', 'alpha');
	
	if(! empty($orderDown))
		$TOrder = array($orderDown => 'DESC');
		
	if(! empty($orderUp))
		$TOrder = array($orderUp => 'ASC');
			
			
	$form = new TFormCore($_SERVER['PHP_SELF'] . '?id=' . $formation->rowid, 'session_list', 'POST');

	echo $list->render($PDOdb, $sql, array(
			'liste' => array(
					'titre' => $langs->trans('PFThisFormationSessionList')
					, 'image' => img_picto('', 'planformation@planformation', '', 0)
					, 'messageNothing' => $langs->transnoentities('NoRecDossierToDisplay')
			)
			, 'limit' => array(
					'page' => (! empty($page)) ? $page : 1
					, 'nbLine' => $conf->liste_limit
			)
			, 'link' => array(
					'ref' => '<a href="?id=@rowid@">@val@</a>'
					, 'formation' => '<a href="' . dol_buildpath('/planformation/formation.php' , 1) . '?id=@fk_formation@">@val@</a>'
					, 'opca' => img_picto('', 'object_company', '', 0). ' <a href="?id=@fk_opca@">@val@</a>'
			)
			, 'type' => array(
					'date_debut' => 'date'
					, 'date_fin' => 'date'
			)
			, 'hide' => array('rowid', 'fk_formation', 'fk_opca')
			, 'title' => array(
					'ref' => $langs->trans('Reference')
					, 'formation' => $langs->trans('PFFormation')
					, 'date_debut' => $langs->trans('DateStart')
					, 'date_fin' => $langs->trans('DateEnd')
					, 'budget' => $langs->trans('PFBudget')
					, 'opca' => $langs->trans('OPCA')
			)
			, 'search' => array(
					'ref' => array(
							'recherche' => true
					)
					, 'formation' => array(
							'recherche' => true
							, 'table' => 'f'
							, 'field' => 'title'
					)
					, 'date_debut' => array(
							'recherche' => 'calendars'
					)
					, 'date_fin' => array(
							'recherche' => 'calendars'
					)
					, 'opca' => array(
							'recherche' => true
							, 'table' => 'soc'
							, 'field' => 'nom'
					)
			)
			, 'orderBy' => $TOrder
	));
	
	$form->end();

	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . dol_buildpath('/planformation/session.php', 1) . '?action=new&origin=formation&originid='. $formation->rowid .'">';
	print $langs->trans('PFCreateSessionOfThisFormation');
	print '</a>';
	print '</div>';
}