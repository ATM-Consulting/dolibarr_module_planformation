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

$langs->trans('planformation@planformation');

$PDOdb = new TPDOdb();

// $PDOdb->debug = true;

$tbs = new TTemplateTBS();
$pf = new TPlanFormation();
$typeFin = new TTypeFinancement();

$action = GETPOST('action');

// Pour que le bouton "Annuler" de la fiche d'un dossier annule et ne sauvegarde pas...
if (isset($_REQUEST['cancel']) && $_REQUEST['cancel'] == "Annuler") {
	$action = "";
}

if (! empty($action)) {
	switch ($action) {
		case 'default' :
			_list($PDOdb, $pf);
			break;
		case 'list' :
			_list($PDOdb, $pf);
			break;
		case 'add' :
		case 'new' :
			$pf->set_values($_REQUEST);
			_card($PDOdb, $pf, $typeFin, 'edit');

			break;
		case 'info' :
			if ($pf->load($PDOdb, GETPOST('id', 'int'))) {
				_info($PDOdb, $pf);
			} else {
				setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
			}

			break;
		case 'edit' :
			if ($pf->load($PDOdb, GETPOST('id', 'int'))) {
				_card($PDOdb, $pf, $typeFin, 'edit');
			} else {
				setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
			}
			break;

		case 'save' :
			$pf->load($PDOdb, GETPOST('id', 'int'));
			$pf->set_values($_REQUEST);

			$pf->save($PDOdb);
			_card($PDOdb, $pf, $typeFin, 'view');
			break;

		case 'delete' :

			if ($pf->load($PDOdb, GETPOST('id', 'int'))) {
				$pf->delete($PDOdb);
				_list($PDOdb, $pf);
			} else {
				setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
			}

			break;
		case 'addsection' :

			if ($pf->load($PDOdb, GETPOST('id', 'int'))) {
				$pfs_link = new TSectionPlanFormation();
				$pfs_link->fk_planform = $pf->getId();
				$pfs_link->fk_section = GETPOST('fk_section', 'int');

				$pfs_link->save($PDOdb);
				_card($PDOdb, $pf, $typeFin, 'view');
			} else {
				setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
			}

			break;
                case 'delete_link' :
                        if ($pf->load($PDOdb, GETPOST('plan_id', 'int'))) {
                            $link_pfs = new TSectionPlanFormation();
                            $link_pfs->loadByCustom($PDOdb, array('fk_planform' => $_REQUEST['plan_id'], 'fk_section' => $_REQUEST['section_id']));
                            $link_pfs->delete($PDOdb);
                            
                            _card($PDOdb, $pf, $typeFin, 'view');
			} else {
                            setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
			}
                        break;
	}
} elseif (isset($_REQUEST['id'])) {
	if ($pf->load($PDOdb, GETPOST('id', 'int'))) {
		_card($PDOdb, $pf, $typeFin, 'view');
	} else {
		//setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
		_list($PDOdb, $pf);
	}
} else {
	_list($PDOdb, $pf);
}

llxFooter();

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

	$formCore = new TFormCore($_SERVER['PHP_SELF'], 'formscore', 'POST');

	echo $r->render($PDOdb, $pf->getSQLFetchAll(), array (
			'limit' => array (
					'page' => (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1),
					'nbLine' => $conf->liste_limit
			),
			'link' => array (
					'ref' => img_picto('', 'object_planformation@planformation') . ' <a href="?id=@ID@">@val@</a>'
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
					'entity'
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
					)
			),
			'orderBy' => $TOrder
	));

	$formCore->end();
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

	llxHeader('', $langs->trans("PFPlanFormation"));
	$head = planformation_prepare_head($pf);
	dol_fiche_head($head, 'info', $langs->trans("PFPlanFormationCard"), 0);

	$pf->date_creation = $pf->date_cre;
	$pf->date_modification = $pf->date_maj;
	$pf->user_creation = $pf->fk_user_creation;
	$pf->user_modification = $pf->fk_user_modification;
	print '<table width="100%"><tr><td>';
	dol_print_object_info($pf);
	print '</td></tr></table>';
	print '</div>';
}

/**
 *
 * @param TPDOdb $PDOdb
 * @param TPlanFormation $pf
 * @param string $mode
 */
function _card(TPDOdb &$PDOdb, TPlanFormation &$pf, TTypeFinancement &$typeFin, $mode = '') {
	global $db, $langs, $user, $conf;

	dol_include_once('/planformation/lib/planformation.lib.php');
        
        $arrayofjs = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
        $arrayofcss = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');
	
        llxHeader('', $langs->trans("PFPlanFormation"),'','',0,0,$arrayofjs,$arrayofcss);
	$head = planformation_prepare_head($pf);
	dol_fiche_head($head, 'planformation', $langs->trans("PFPlanFormationCard"), 0);

	$formDoli = new Form($db);
	$formCore = new TFormCore($_SERVER['PHP_SELF'], 'formscore', 'POST');
	$formCore->Set_typeaff($mode);

	echo $formCore->hidden('id', $pf->getId());
	echo $formCore->hidden('action', 'save');
	echo $formCore->hidden('entity', getEntity());
        
	if ($pf->getId() <= 0) {
		echo $formCore->hidden('fk_user_creation', $user->id);
	} else {
		echo $formCore->hidden('fk_user_creation', $pf->fk_user_creation);
	}
	echo $formCore->hidden('fk_user_modification', $user->id);

	$TBS = new TTemplateTBS();

	$btRetour = '<a class="butAction" href="' . dol_buildpath("/planformation/planformation.php?action=list", 1) . '">' . $langs->trans('BackToList') . '</a>';
	$btCancel = $formCore->btsubmit($langs->trans('Cancel'), 'cancel');
	$btSave = $formCore->btsubmit($langs->trans('Valid'), 'save');
	$btModifier = '<a class="butAction" href="' . dol_buildpath('/planformation/planformation.php?id=' . $pf->rowid . '&action=edit', 1) . '">' . $langs->trans('PFPlanFormationEdit') . '</a>';
	$btDelete = "<input type=\"button\" id=\"action-delete\" value=\"" . $langs->trans('Delete') . "\" name=\"cancel\" class=\"butActionDelete\" onclick=\"if(confirm('" . $langs->trans('PFDeleteConfirm') . "'))document.location.href='?action=delete&id=" . $pf->rowid . "'\" />";

	// Load type fin data
	$result = $typeFin->fetchAll($PDOdb, $typeFin->get_table());
	if ($result < 0) {
		setEventMessages(null, $typeFin->errors, 'errors');
	}

	// Fill form with title and data
	$data = $pf->getTrans('title');

	if ($mode == 'edit') {
		$data['titre'] = load_fiche_titre($pf->getId() > 0 ? $langs->trans("PFPlanFormationEdit") : $langs->trans("PFPlanFormationNew"), '');
		$data['title'] = $formCore->texte('', 'title', $pf->title, 30, 255);
		$data['type_fin_label'] = $formCore->combo('', 'fk_type_financement', $typeFin->lines, '');
		$data['date_start'] = $formCore->doliCalendar('date_start', $pf->date_start);
		$data['date_end'] = $formCore->doliCalendar('date_end', $pf->date_end);
		// Ici
		$data['budget'] = $formCore->texte('', 'budget', $pf->budget, 30, 255);
		// Jusque là
		if ($conf->global->PF_ADDON == 'mod_planformation_universal') {
			$data['ref'] = $formCore->texte('', 'ref', $pf->ref, 15, 255);
		} elseif ($conf->global->PF_ADDON == 'mod_planformation_simple') {
			$result = $pf->getNextNumRef();
			if ($result == - 1) {
				setEventMessages(null, $pf->errors, 'errors');
			}
			$data['ref'] = $result;
			echo $formCore->hidden('ref', $result);
		}

		$buttons = $btCancel . $btSave;
	} else {
		$data['titre'] = load_fiche_titre($langs->trans("PFPlanFormationCard"), '');
		$data['type_fin_label'] = $typeFin->lines[$pf->fk_type_financement];
		$data['date_start'] = dol_print_date($pf->date_start);
		$data['date_end'] = dol_print_date($pf->date_end);
		$data['title'] = $pf->title;
		// Ici
		$data['budget'] = $pf->budget;
		// Jusque là
		$data['ref'] = $formCore->texte('', 'ref', $pf->ref, 15);
		$buttons = $btRetour . ' ' . $btModifier . ' ' . $btDelete;
	}

	// Todo mandatory fields

	print $TBS->render('./tpl/planformation.tpl.php', array (),

	array (
			'planformation' => $data,
			'view' => array (
					'mode' => $mode
			),
			'buttons' => array (
					'buttons' => $buttons
			)
	));

	$formCore->end();

	if ($mode == 'view') {
            

		// Combo box to add section on paln form
		$formCore = new TFormCore($_SERVER['PHP_SELF'], 'formaddSection', 'POST');
		echo $formCore->hidden('action', 'addsection');
		echo $formCore->hidden('id', $pf->getId());
		$pfs = new TSection();
		$availableSection = $pfs->getAvailableSection($PDOdb, $pf->getId());
		echo $formCore->combo($langs->trans('PFSelectSectionToAdd'), 'fk_section', $availableSection, '');
		echo $formCore->btsubmit($langs->trans('Add'), 'addsection');
		$formCore->end();

		_listPlanFormSection($PDOdb, $pf, $typeFin);
                
               /*
               * TreeView
               */
              
                $obj = new TSectionPlanFormation();
              $tab = array();
              $obj->getAllSection($PDOdb, $tab, $pf->id);
              
                print load_fiche_titre($langs->trans("ListOfPFSection"). ' ('.$langs->trans("HierarchicView").')', '');
               
                /*$data = array(
                   array(
                       'rowid'=>0
                        ,'fk_menu'=>-1
                        ,'title'=>'racine'
                   ),
                   array(
                    'rowid'=>1
                    ,'fk_menu'=>2
                    ,'entry'=>1
                   ),
                   array(
                        'rowid'=>2
                        ,'entry'=>2
                    )
                ,array(
                        'rowid'=>3
                        ,'fk_menu'=>2
                        ,'entry'=>3
                    )
                ,array(
                       'rowid'=>4,
                       'fk_menu'=>1
                        ,'entry'=>'<table class="nobordernopadding centpercent"><tr><td>coucou</td>'.
                    '<td width="50%">salut</td>'.
                    '<td align="right" width="20px;">lien</td>'.
                    '</tr></table>'
                    )
               );*/
                $data = array(array(
                       'rowid'=>0
                        ,'fk_menu'=>-1
                        ,'title'=>'racine'
                   ));
                foreach($tab as $section) {
                    $secName = TSectionPlanFormation::getSectionNameById($PDOdb, $section['fk_section']);
                    $secParenteName = TSectionPlanFormation::getSectionNameById($PDOdb, $section['fk_section_parente']);
                    
                    $data[] = array('rowid' => $section['fk_section'],
                                    (empty($section['fk_section_parente'])) ? '': 'fk_menu'=>$section['fk_section_parente'],
                                    'entry' => '<table class="nobordernopadding centpercent">
                                                <tr>
                                                    <td width="20%">'. "<a href='section.php?id=". $section['fk_section'] . "&plan_id=$pf->rowid'>" . $secName . "</a>" . '</td>
                                                    <td width="50%" style="text-align: center;">'. $secParenteName .'</td>
                                                    <td width="10%" style="text-align: center;">' . $section['budget'] . '</td>
                                                    <td align="right" width="20px;">'
                                                        ."<a href='planformation.php?section_id=".$section['fk_section']."&plan_id=$pf->rowid&action=delete_link'>" . img_picto('', 'delete') . "</a>"
                                                    .'</td>
                                                </tr>
                                                </table>'
                                    );
                }
               
               $nbofentries=(count($data) - 1);

                if ($nbofentries > 0)
                {
                        print '<table class="nobordernopadding centpercent">';
                        print '<tr><th width="21%">Titre</th><th width="50%">Parent</th><th width="10%">Budget</th><th align="right" width="20px;">Supprimer</th></tr>';
                        print '</table>';
                        print '<tr><td colspan="3">';
                        tree_recur($data,$data[0],0);
                        print '</td>';
                        print '<td></td>';
                        print '</tr>';
                }
                else
                {
                        print '<tr '.$bc[true].'>';
                        print '<td colspan="3">';
                        print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
                        print '<td valign="middle">';
                        print $langs->trans("NoCategoryYet");
                        print '</td>';
                        print '<td>&nbsp;</td>';
                        print '</table>';
                        print '</td>';
                        print '<td></td>';
                        print '</tr>';
                }

                print "</table>";

               
	}
}

/**
 *
 * @param TPDOdb $PDOdb
 * @param TPlanFormation $pf
 * @param string $mode
 */
function _listPlanFormSection(TPDOdb &$PDOdb, TPlanFormation &$pf, TTypeFinancement &$typeFin) {
	global $db, $langs;

	$pfs_link = new TSectionPlanFormation();
	$pfs = new TSection();

	$r = new TListviewTBS('planform_section');

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

	$formCore = new TFormCore($_SERVER['PHP_SELF'], 'formscore', 'POST');

	// Build user group array for filtered list combo
	$arrayUserGroups = array ();
	$usergroups = new TPFUserGroup($db);
	$result = $usergroups->fetchAll('ASC', 't.nom', 0, 0);
	if ($result < 0) {
		setEventMessages(null, $usergroups->errors, 'errors');
	} else {
		foreach ( $usergroups->lines as $line ) {
			$arrayUserGroups[$line->id] = $line->nom;
		}
	}
        
	echo $r->render($PDOdb, $pfs_link->getSQLFetchAll(array (
			'p.rowid' => $pf->rowid
	)), array (
			'limit' => array (
					'page' => (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1),
					'nbLine' => $conf->liste_limit
			),
			'link' => array (
					'ref' => img_picto('', 'object_planformation@planformation') . " <a href='section.php?id=@section_id@&plan_id=$pf->rowid'>@val@</a>",
                                        'supprimer' => "<a href='planformation.php?section_id=@section_id@&plan_id=$pf->rowid&action=delete_link'>" . img_picto('', 'delete') . "</a>"
			),
			'hide' => array (
					'ID',
					'section_id',
					'fk_usergroup',
					'fk_user_modification',
					'fk_user_creation',
					'entity',
					'planform_id'
			),
			'title' => $pfs->getTrans(),
			'liste' => array (
					'titre' => $langs->trans('PFPlanFormationSectionList'),
					'image' => img_picto('', 'planformation@planformation', '', 0),
					'messageNothing' => $langs->transnoentities('NoRecDossierToDisplay')
			),
			'search' => array (
					'title' => array (
							'recherche' => true,
							'table' => 's'
					),
					'ref' => array (
							'recherche' => true,
							'table' => 's'
					),
					'group_name' => array (
							'recherche' => $arrayUserGroups
					)
			),
			'orderBy' => $TOrder
                        
                
	));
}
