<?php
require_once ('config.php');

// Security check
if ($user->societe_id) {
	$socid = $user->societe_id;
}

$result = restrictedArea ( $user, 'planformation', 0, 'planformation' );

require_once ('./class/sessionformation.class.php');
require_once ('./class/formation.class.php');
require_once ('./class/creneausession.class.php');
require_once (dol_buildpath('/core/lib/date.lib.php', 0));

$langs->load ( 'planformation@planformation' );

$PDOdb = new TPDOdb ();

$id = GETPOST ( 'id', 'int' );
$action = GETPOST ( 'action', 'alpha' );

$session = new TSessionFormation;
$formation = new TFormation;
$creneau = new TCreneauSession;

if (! empty ( $id )) {
	if (! $session->load ( $PDOdb, $id )) {
		setEventMessage ( $langs->trans ( 'ImpossibleLoadElement' ), 'errors' );
		_list($PDOdb, $session, $formation, $creneau);
		exit;
	}
	
	if (! empty ( $session->fk_formation )) {
		$formation->load ( $PDOdb, $session->fk_formation );
	}
} else {
	// TODO Gérer l'erreur
	exit;
}

switch ($action) {
	/*
	 * case 'add':
	 * case 'save':
	 * $session->set_values($_REQUEST);
	 *
	 * if($action == 'add') {
	 * $session->fk_user_creation = $user->id;
	 * $session->ref = $session->getNextRef();
	 * } else {
	 * $session->fk_user_modification = $user->id;
	 * }
	 *
	 * $session->save($PDOdb);
	 *
	 * header('Location: ' . $_SERVER['PHP_SELF'] . '?id='. $session->rowid);
	 * exit;
	 * break;
	 *
	 * case 'new':
	 * case 'edit':
	 * _card($PDOdb, $session, $formation, 'edit');
	 * break;
	 *
	 */
	
	case 'addtimeslot' :
		$date = GETPOST('date', 'alpha');
		$heure_debut = GETPOST('heure_debut', 'alpha');
		$heure_fin = GETPOST('heure_fin', 'alpha');

		if(strcmp($heure_debut, $heure_fin) < 0) {
			$TDate = explode('/', $date);

			$dateSQL = $TDate[2] . '-' . $TDate[1] . '-' . $TDate[0] . ' ';
			$date_debut = $dateSQL.$heure_debut.':00';
			$date_fin = $dateSQL.$heure_fin.':00';

			if(! $session->hasCreneauxBetween($PDOdb, $date_debut, $date_fin)) {
				$THeureDebut = explode(':', $heure_debut);
				$THeureFin = explode(':', $heure_fin);

				$creneau->fk_session = $session->rowid;
				$creneau->debut = dol_mktime($THeureDebut[0], $THeureDebut[1], 0, $TDate[1], $TDate[0], $TDate[2]);
				$creneau->fin = dol_mktime($THeureFin[0], $THeureFin[1], 0, $TDate[1], $TDate[0], $TDate[2]);

				$creneau->save($PDOdb);
			} else {
				setEventMessage($langs->trans('PFTimeSlotOverlap'), 'errors');
			}
		} else {
			setEventMessage($langs->trans('PFStartTimeMustBeBeforeEndTime'), 'errors');
		}

		_list ( $PDOdb, $session, $formation, $creneau );
	break;

	case 'deletetimeslot' :
		$timeslotRowid = GETPOST ( 'timeslot' );
		
		if ($timeslotRowid > 0) {
			$creneau->load ( $PDOdb, $timeslotRowid );
			$creneau->delete ( $PDOdb );
		}
		
		_list ( $PDOdb, $session, $formation, $creneau );
	break;

	case 'list' :
	default :
		_list ( $PDOdb, $session, $formation, $creneau );
}


function _list(&$PDOdb, &$session, &$formation, &$creneau) {
	global $langs, $conf;
	
	_header_list ( $session, 'calendar' );
	
	print load_fiche_titre ( $langs->trans ( 'PFSessionCalendar' ), '' );
	
	$TCreneaux = $session->getCreneaux ( $PDOdb );

	print '<table class="liste centpercent">';
	print '<tr class="liste_titre">';
	print '<th class="liste_titre">' . $langs->trans ( 'Date' ) . '</th>';
	print '<th class="liste_titre">' . $langs->trans ( 'Start' ) . '</th>';
	print '<th class="liste_titre">' . $langs->trans ( 'End' ) . '</th>';
	print '<th class="liste_titre">&nbsp;</th>';
	print '</tr>';
	
	$nbCreneaux = count ( $TCreneaux );

	$duree = 0;

	foreach ( $TCreneaux AS $c ) {
		$actionsButtons = '';

		// if($pf->statut == 0) {
		$actionButtons = '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $session->id . '&action=deletetimeslot&timeslot=' . $c->rowid . '">' . img_picto ( '', 'delete' ) . '</a>';
		// }
		
		print '<tr>';
		print '<td>' . dol_print_date(dol_stringtotime(str_replace(' ', 'T', $c->debut), 0), '%A %d %B %Y') . '</td>';
		print '<td>' . dol_print_date(dol_stringtotime(str_replace(' ', 'T', $c->debut), 0), '%R'). '</td>';
		print '<td>' . dol_print_date(dol_stringtotime(str_replace(' ', 'T', $c->fin), 0), '%R'). '</td>';
		print '<td align="right">' . $actionButtons . '</td>';
		print '</tr>';

		// }
	}

	if ($nbCreneaux == 0) {
		print '<tr class="impair"><td colspan="4" align="center">';
		print $langs->trans ( 'PFSessionNoTimeSlot' );
		print '</td></tr>';
	}
	
	// Ajout nouveau créneau
	

	print '<tr class="liste_titre"><td colspan="4">' . $langs->trans ( 'PFAddNewSessionTimeSlot' ) . '</td></tr>';
	print '<tr class="liste_titre">';
	print '<th class="liste_titre">' . $langs->trans ( 'Date' ) . '</th>';
	print '<th class="liste_titre">' . $langs->trans ( 'Start' ) . '</th>';
	print '<th class="liste_titre">' . $langs->trans ( 'End' ) . '</th>';
	print '<th class="liste_titre">&nbsp;</th>';
	print '</tr>';

	$formCore = new TFormCore ( $_SERVER ['PHP_SELF'] . '?id=' . $session->id, 'formAddAttendee', 'POST' );

	print '<tr class="impair">';
	print '<td>' . $formCore->calendrier('', 'date', '') . '</td>';
	print '<td>' . $formCore->timepicker('', 'heure_debut', '') . '</td>';
	print '<td>' . $formCore->timepicker('', 'heure_fin', ''). '</td>';
	print '<td align="right">' . $formCore->hidden ( 'action', 'addtimeslot' ) . $formCore->hidden ( 'fk_session', $session->rowid ) . $formCore->btsubmit ( $langs->trans ( 'Add' ), 'addtimeslot' ) . '</td>';
	print '</tr>';

	$formCore->end();
	
	// }
	
	print "</table>";

	llxFooter();
}


function _header_list(&$session, $active) {
	global $langs;
	
	dol_include_once ( '/planformation/lib/planformation.lib.php' );
	
	llxHeader ( '', $langs->trans ( 'PFCalendar' ), '', '', 0, 0 );
	
	$head = session_prepare_head ( $session );
	dol_fiche_head ( $head, $active, $langs->trans ( 'PFFormationSession' ), 0 );
}
