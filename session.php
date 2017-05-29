<?php

require_once('config.php');

// Security check
if ($user->societe_id)
	$socid = $user->societe_id;
	
$result = restrictedArea($user, 'planformation', 0, 'planformation');

require_once('./class/sessionformation.class.php');
require_once('./class/formation.class.php');

$langs->load('planformation@planformation');

$PDOdb = new TPDOdb;

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originId = GETPOST('originid', 'int');

$session = new TSessionFormation;
$formation = new TFormation;

if(! empty($id)) {
	if(! $session->load($PDOdb, $id)) {
		setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
		_list($PDOdb, $session, $formation);
		exit;
	}
	
	if(! empty($session->fk_formation)) {
		$formation->load($PDOdb, $session->fk_formation);
	}
} else {
	if($action == 'new' && $origin == 'formation' && ! empty($originId)) {
		$formation->load($PDOdb, $originId);
	}
}


switch($action) {
	case 'add':
	case 'save':
		$session->set_values($_REQUEST);
		
		if($action == 'add') {
			$session->fk_user_creation = $user->id;
			$session->ref = $session->getNextRef();
			$session->duree_planifiee = 0;
		} else {
			$session->fk_user_modification = $user->id;
		}
		
		$session->save($PDOdb);
		
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id='. $session->rowid);
		exit;
		break;
		
	case 'new':
	case 'edit':
		if($session->statut == 1) {
			setEventMessage($langs->trans('PFTryingToEditAValidatedSession'), 'errors');
			
			_card($PDOdb, $session, $formation, 'view');
		} else {
			_card($PDOdb, $session, $formation, 'edit');
		}
		break;
		
	case 'info':
		_info($PDOdb, $session);
		break;
		
	case 'list':
		_list($PDOdb, $session, $formation);
		break;
		
	case 'delete':
		$session->delete($PDOdb);
		
		header('Location: ' . $_SERVER['PHP_SELF'] . '?action=list');
		exit;
		break;
		
	case 'validate':
		$session->validate($PDOdb);
		
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id='. $session->rowid);
		exit;
		break;
		
	case 'reopen':
		$session->reopen($PDOdb);
		
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id='. $session->rowid);
		exit;
		break;
		
	default:
		if(empty($id)) {
			_list($PDOdb, $session, $formation);
		} else {
			_card($PDOdb, $session, $formation, 'view');
		}
}


function _list(&$PDOdb, &$session, &$formation) {
	global $langs, $conf;
	
	llxHeader('', $langs->trans('PFFormationSessionList'));
	
	$list = new TListviewTBS('session');
	
	$sql = "SELECT s.rowid, ref, fk_formation, f.title AS formation, IF(s.statut = 1, '" . $langs->trans('Validated') . "', '" . $langs->trans('Draft') . "') AS statut, date_debut, date_fin, fk_opca, soc.nom AS opca, budget";
	$sql.= " FROM " . $session->get_table() . " AS s";
	$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "planform_formation AS f ON (f.rowid=s.fk_formation)";
	$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe AS soc ON (soc.rowid=s.fk_opca)";
	
	
	$TOrder = array('rowid' => 'ASC');
	
	$page = GETPOST('page', 'int');
	$orderDown = GETPOST('orderDown', 'alpha');
	$orderUp = GETPOST('orderUp', 'alpha');
	
	if(! empty($orderDown))
		$TOrder = array($orderDown => 'DESC');
		
	if(! empty($orderUp))
		$TOrder = array($orderUp => 'ASC');
			
			
	$form = new TFormCore($_SERVER['PHP_SELF'] . '?action=list', 'session_list', 'POST');
	
	echo $list->render($PDOdb, $sql, array(
			'liste' => array(
					'titre' => $langs->trans('PFFormationSessionList')
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
					, 'statut' => $langs->trans('Status')
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
					, 'statut' => array(
							'recherche' => array(
									0 => 'Draft'
									, 1 => 'Validated'
							)
							, 'table' => 's'
							, 'to_translate' => 'yes'
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
	
	llxFooter();
}


function _info(&$PDOdb, &$session) {
	global $langs;
	
	require_once (DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php');
	
	_header_card($session, 'info');
	
	$session->date_creation = $session->date_cre;
	$session->date_modification = $session->date_maj;
	$session->user_creation = $session->fk_user_creation;
	$session->user_modification = $session->fk_user_modification;
	
	dol_print_object_info($session);
	
	llxFooter();
}



function _card(&$PDOdb, &$session, &$formation, $mode = 'view') {
	global $langs, $form, $db;
	
	_header_card($session, 'session');
	
	
	$TBS = new TTemplateTBS;
	
	$TDataSession = array();
	
	$url = $_SERVER['PHP_SELF'];
	
	if(! empty($session->rowid)) {
		$url.= '?id=' . $session->rowid;
	}
	
	$formCore = new TFormCore($url, 'edit_session', 'POST');
	
	
	$btSave = '<button type="submit" class="butAction">' . $langs->trans('Save') . '</button>';
	$btCancel = '<a class="butAction" href="javascript:history.back()">' . $langs->trans('Cancel') . '</a>';
	
	$btModifier = '<a class="butAction" href="' . dol_buildpath('/planformation/session.php?id=' . $session->rowid . '&action=edit', 1) . '">' . $langs->trans('Modify') . '</a>';
	$btValider = '<a class="butAction" href="' . dol_buildpath('/planformation/session.php?id=' . $session->rowid . '&action=validate', 1) . '" onclick="javascript:return confirm(\'' . $langs->trans('PFSessionValidateConfirm') . '\')">' . $langs->trans('Validate') . '</a>';
	$btSupprimer = '<a class="butAction" href="' . dol_buildpath('/planformation/session.php?id=' . $session->rowid . '&action=delete', 1) . '" onclick="javascript:return confirm(\'' . $langs->trans('PFSessionDeleteConfirm') . '\')">' . $langs->trans('Delete') . '</a>';
	$btRouvrir = '<a class="butAction" href="' . dol_buildpath('/planformation/session.php?id=' . $session->rowid . '&action=reopen', 1) . '" onclick="javascript:return confirm(\'' . $langs->trans('PFSessionReopenConfirm') . '\')">' . $langs->trans('PFReopen') . '</a>';
	
	$TFormations = array('0' => '');
	
	$TFormationList = $formation->getAllWithCondition($PDOdb);
	foreach($TFormationList as $forma) {
		$TFormations[$forma['rowid']] = $forma['title'];
	}
	
	if(empty($formation->rowid)) {
		$TDataSession['formation'] = $formCore->combo('', 'fk_formation', $TFormations, '');
	} else {
		$TDataSession['formation'] = '<a href="' . dol_buildpath('/planformation/formation.php?id='.$formation->rowid, 1) . '">' . $formation->title . '</a> ';
		$TDataSession['formation'].= $formCore->hidden('fk_formation', $formation->rowid);
	}
	
	
	$opcaId = empty($session->fk_opca) ? $formation->fk_opca : $session->fk_opca;
	
	
	$TDataSession['statut'] = ($session->statut == 1) ? $langs->trans('Validated') : $langs->trans('Draft');
	$TDataSession['duree_planifiee'] = secondesToHHMM(3600 * $session->duree_planifiee);
	if(! empty($formation->rowid)) {
		$TDataSession['duree_planifiee'] .= ' (' . $langs->trans('PFOverTimePlannedForThisFormation', secondesToHHMM(3600 * $formation->duree)) . ')';
	}
	$TDataSession['budget_consomme'] = price($session->budget_consomme, 1, $langs, 1, -1, -1, 'auto');
	$TDataSession['prise_en_charge_acceptee'] = (round(10 * $session->prise_en_charge_acceptee) / 10) . '&nbsp;%';
	$TDataSession['prise_en_charge_reelle'] = (round(10 * $session->prise_en_charge_reelle) / 10) . '&nbsp;%';
	
	$TInterneExterne = array(
			0 => $langs->trans('PFExternal')
			, 1 => $langs->trans('PFInternal')
	);
	
	if($mode == 'edit') {
		$TDataSession['ref'] = empty($session->ref) ? $session->getNextRef() : $session->ref.$formCore->hidden('rowid', $session->rowid);
		$TDataSession['budget'] = $formCore->texte('', 'budget', $session->budget, 20, 20, ' style="width:100px"');
		$TDataSession['date_debut'] = $formCore->doliCalendar('date_debut', $session->date_debut);
		$TDataSession['date_fin'] = $formCore->doliCalendar('date_fin', $session->date_fin);
		$TDataSession['opca'] = $form->select_company($opcaId, 'fk_opca', 's.fournisseur = 1');
		$TDataSession['prise_en_charge_estimee'] = $formCore->texte('', 'prise_en_charge_estimee', $session->prise_en_charge_estimee, 20, 20, ' style="width:100px"') . '&nbsp;%';
		$TDataSession['interne_externe'] = $formCore->combo('', 'is_interne', $TInterneExterne, $session->is_interne);
		
		$submitAction = empty($session->rowid) ? 'add' : 'save';
		
		$buttons = $formCore->hidden('action', $submitAction) . $btSave. ' ' . $btCancel;
	} else {
		$TDataSession['ref'] = $session->ref;
		$TDataSession['budget'] = price($session->budget, 1, $langs, 1, -1, -1, 'auto');
		$TDataSession['date_debut'] = dol_print_date($session->date_debut);
		$TDataSession['date_fin'] = dol_print_date($session->date_fin);
		$TDataSession['prise_en_charge_estimee'] = (round(10 * $session->prise_en_charge_estimee) / 10) . '&nbsp;%';
		$TDataSession['interne_externe'] = $TInterneExterne[$session->is_interne];
		
		$opca = new Societe($db);
		
		if(! empty($opcaId)) {
			$opca->fetch($opcaId);
			$TDataSession['opca'] = $opca->getNomUrl(1);
		} else {
			$TDataSession['opca'] = $langs->trans('PFNoOPCASelected');
		}
		
		if($session->statut == 0) {
			$buttons = $btModifier . ' ' . $btValider. ' ' . $btSupprimer;
		} else {
			$buttons = $btRouvrir . ' ' . $btSupprimer;
		}
	}
	
	
	print $TBS->render('./tpl/session.tpl.php', array(), array(
			'session' => $TDataSession
			, 'trans' => array(
					'ref' => $langs->trans('Reference')
					, 'formation' => $langs->trans('PFFormation')
					, 'statut' => $langs->trans('Status')
					, 'interne_externe' => $langs->trans('PFInternalExternal')
					, 'date_debut' => $langs->trans('DateStart')
					, 'date_fin' => $langs->trans('DateEnd')
					, 'duree_planifiee' => $langs->trans('PFPlannedTime')
					, 'opca' => $langs->trans('OPCA')
					, 'budget' => $langs->trans('PFBudget')
					, 'budget_consomme' => $langs->trans('PFUsedBudget')
					, 'prise_en_charge_estimee' => $langs->trans('PFEstimatedFunding')
					, 'prise_en_charge_acceptee' => $langs->trans('PFApprovedFunding')
					, 'prise_en_charge_reelle' => $langs->trans('PFActualFunding')
			)
			, 'buttons' => $buttons
	));
	
	$formCore->end();
	
	llxFooter();
}



function _header_card(&$session, $active) {
	global $langs;
	
	dol_include_once('/planformation/lib/planformation.lib.php');
	
	llxHeader('', $langs->trans("PFFormationSession"),'','',0,0);
	
	$head = session_prepare_head($session);
	dol_fiche_head($head, $active, $langs->trans('PFFormationSession'), 0);
}
