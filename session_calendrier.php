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
	setEventMessage($langs->trans('PFSessionNotFound'), 'errors');
	
	header('Location: ' . dol_buildpath('/planformation/session.php', 1) . '?action=list');
	exit;
}

switch ($action) {
	case 'addtimeslot':
	case 'savetimeslot':

		if($session->statut == 0) {
			$type = GETPOST('type');
			$heure_debut = GETPOST('heure_debut', 'alpha');
			$heure_fin = GETPOST('heure_fin', 'alpha');

			if($type == 'creneau') {
				$date = GETPOST('date', 'alpha');

				$creneauId = GETPOST('timeslot', 'int');

				if(! empty($creneauId)) { // Édition créneau
					$session->addCreneau($PDOdb, $formation, $date, $heure_debut, $heure_fin, $creneauId);
				} else {
					$session->addCreneau($PDOdb, $formation, $date, $heure_debut, $heure_fin);
				}
			} else {
				$date_recurrence = GETPOST('date_recurrence', 'alpha');
				$nb_semaines = GETPOST('nb_semaines', 'int');

				if($nb_semaines > 0) {
					$week_start = isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1;

					$TDateRecurrence = explode('/', $date_recurrence);

					$dateRecurrenceTimeStamp = dol_mktime(0, 0, 0, $TDateRecurrence[1], $TDateRecurrence[0], $TDateRecurrence[2], true); // heures, minutes, secondes, MOIS, jour, année, isGMT

					$jourRecurrence = date('w', $dateRecurrenceTimeStamp); // 0 => dimanche, 1 => lundi, etc.

					$noDayChosen = true;

					for($i = 0; $i < 7; $i++) { // Pour chaque jour
						$day = ($i + $jourRecurrence) % 7; // on calcule le jour qui serait renvoyé par date('w', ...);
	
						$dateFirstWeek = $dateRecurrenceTimeStamp + $i * 24 * 60 * 60; // Le timestamp du jour de la semaine courant lors de la semaine de départ

						if(! empty($_REQUEST['day'.$day])) {
							$noDayChosen = false;
	
							for($s = 0; $s < $nb_semaines; $s++) { // Pour chaque semaine
								$dateTimeStamp = $dateFirstWeek + $s * 7 * 24 * 60 * 60; // Le timestamp du jour de la semaine courant lors de la semaine $s
								$date = date('d/m/Y', $dateTimeStamp);
	
								$session->addCreneau($PDOdb, $formation, $date, $heure_debut, $heure_fin);
							}
						}
					}

					if($noDayChosen) {
						setEventMessage($langs->trans('PFNoRecurringDayChosen'), 'errors');
					}
				} else {
					setEventMEssage($langs->trans('PFYouMustSetAtLeast1WeekRecurrence'), 'errors');
				}
			}
		} else {
			setEventMessage($langs->trans('PFTryingToEditAValidatedSession'), 'errors');
		}

		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $session->rowid);
		exit;
	break;

	case 'edittimeslot':
		if($session->statut == 0) {
			_list ( $PDOdb, $session, $formation, $creneau, 'edittimeslot');
		} else {
			setEventMessage($langs->trans('PFTryingToEditAValidatedSession'), 'errors');
		}
	break;

	case 'deletetimeslot':
		if($session->statut == 0) {
			$timeslotRowid = GETPOST ( 'timeslot' );
			
			if ($timeslotRowid > 0) {
				$creneau->load($PDOdb, $timeslotRowid);
	
				$session->duree_planifiee -= ($creneau->fin - $creneau->debut) / 3600;
	
				$creneau->delete($PDOdb);
				$session->save($PDOdb);
			}
		} else {
			setEventMessage($langs->trans('PFTryingToEditAValidatedSession'), 'errors');
		}

		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $session->rowid);
		exit;
	break;

	case 'list' :
	default :
		_list ( $PDOdb, $session, $formation, $creneau );
}


function _list(&$PDOdb, &$session, &$formation, &$creneau, $mode = 'view') {
	global $langs, $conf;
	
	_header_list ( $session, 'calendar' );
	
	print load_fiche_titre ( $langs->trans ( 'PFSessionCalendar' ), '' );
	
	$TCreneaux = $session->getCreneaux ( $PDOdb );

	$timeSlotToEdit = GETPOST('timeslot', 'int');

	print '<table class="liste centpercent">';
	print '<tr class="liste_titre">';
	print '<th class="liste_titre" style="width:30%">' . $langs->trans ( 'Date' ) . '</th>';
	print '<th class="liste_titre">' . $langs->trans ( 'PFStartTime' ) . '</th>';
	print '<th class="liste_titre">' . $langs->trans ( 'PFEndTime' ) . '</th>';
	print '<th class="liste_titre">' . $langs->trans ( 'Duration' ) . '</th>';
	print '<th class="liste_titre">&nbsp;</th>';
	print '</tr>';


	print '<tr><td colspan="5"><i><b>' . $langs->trans('PFSessionStart') . '</b> : ' . dol_print_date($session->date_debut, '%A %d %B %Y'). '</i></td></tr>';

	$nbCreneaux = count ( $TCreneaux );
	
	$dureeTotaleSecs = 0;

	foreach ( $TCreneaux AS $c ) {
		$actionsButtons = '';



		$date_debut = dol_stringtotime(str_replace(' ', 'T', $c->debut), 0);
		$date_fin = dol_stringtotime(str_replace(' ', 'T', $c->fin), 0);
		$duree = $date_fin - $date_debut;

		$dureeTotaleSecs += $duree;

		print '<tr>';

		if($session->statut == 0 && $mode = 'edittimeslot' && $c->rowid == $timeSlotToEdit) {
			$formCore = new TFormCore ( $_SERVER ['PHP_SELF'] . '?id=' . $session->id, 'formEditTimeSlot'.$c->rowid, 'POST' );

			$actionButtons.= $formCore->hidden('action', 'savetimeslot') . $formCore->hidden('timeslot', $c->rowid) . $formCore->hidden('type', 'creneau');
			$actionButtons.= $formCore->btImg($langs->trans('Modify'), 'savetimeslot', dol_buildpath('/theme/eldy/img/tick.png', 1));
			$actionButtons.= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $session->id . '">' . img_picto($langs->trans('Cancel'), 'close') . '</a>';

			print '<td style="padding-left:40px">' . $formCore->calendrier('', 'date', dol_print_date($date_debut, '%d/%m/%Y')) . '</td>';
			print '<td>' . $formCore->timepicker('', 'heure_debut', dol_print_date($date_debut, '%R')). '</td>';
			print '<td>' . $formCore->timepicker('', 'heure_fin', dol_print_date($date_fin, '%R')). '</td>';
			print '<td></td>';
			print '<td align="right">' . $actionButtons . '</td>';

			$formCore->end();
		} else {
			if($session->statut == 0) {
				$actionButtons = '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $session->id . '&action=edittimeslot&timeslot=' . $c->rowid . '">' . img_picto ( '', 'edit' ) . '</a>';
				$actionButtons.= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $session->id . '&action=deletetimeslot&timeslot=' . $c->rowid . '">' . img_picto ( '', 'delete' ) . '</a>';
			}

			print '<td style="padding-left:40px">' . dol_print_date($date_debut, '%A %d %B %Y') . '</td>';
			print '<td>' . dol_print_date($date_debut, '%R'). '</td>';
			print '<td>' . dol_print_date($date_fin, '%R'). '</td>';
			print '<td>' . secondesToHHMM($duree). '</td>';
			print '<td align="right">' . $actionButtons . '</td>';
		}

		print '</tr>';
	}

	if ($nbCreneaux == 0) {
		print '<tr><td colspan="5" align="center">';
		print $langs->trans ( 'PFSessionNoTimeSlot' );
		print '</td></tr>';
		print '<tr><td colspan="5"><i><b>' . $langs->trans('PFSessionEnd') . '</b> : ' . dol_print_date($session->date_fin, '%A %d %B %Y'). '</i></td></tr>';
	} else {
		print '<tr class="impair">';
		print '<td colspan="2"><i><b>' . $langs->trans('PFSessionEnd') . '</b> : ' . dol_print_date($session->date_fin, '%A %d %B %Y'). '</i></td>';
		print '<td align="right"><b>' . $langs->trans('PFTotalTimePlanned') . '</b> :</td>';
		print '<td>' . secondesToHHMM($dureeTotaleSecs). ' (' . $langs->trans('PFOverTimePlannedForThisFormation', secondesToHHMM(3600 * $formation->duree)) . ')</td>';
		print '<td></td>';
		print '</tr>';
	}


	// Ajout nouveau créneau

	if($session->statut == 0) {

		print '<tr class="liste_titre"><td colspan="5">' . $langs->trans ( 'PFAddNewSessionTimeSlot' ) . '</td></tr>';
	
		if($session->duree_planifiee < $formation->duree) {
/*
			print '<tr class="liste_titre">';
			print '<th class="liste_titre">' . $langs->trans ( 'Date' ) . '</th>';
			print '<th class="liste_titre">' . $langs->trans ( 'PFStartTime' ) . '</th>';
			print '<th class="liste_titre">' . $langs->trans ( 'PFEndTime' ) . '</th>';
			print '<th class="liste_titre">' . '' . '</th>';
			print '<th class="liste_titre">&nbsp;</th>';
			print '</tr>';
*/		
			$formCore = new TFormCore ( $_SERVER ['PHP_SELF'] . '?id=' . $session->id, 'formAddTimeSlot', 'POST' );
		
			print '<tr>';

			print '<td>';
			print '<input type="radio" name="type" id="type_creneau" value="creneau" checked /> <label for="type_creneau">' . $langs->trans('AddASingleTimeSlotOn') . '</label> ';
			print $formCore->calendrier('', 'date', ''). '...<br /><br />';
			print '<input type="radio" name="type" id="type_recurrence" value="recurrence" /> <label for="type_recurrence">'; 
			print $langs->trans('PFAddATimeSlotEvery') . '<br />';

			print '<ul style="list-style-type:none">';
			$week_start = isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1;
			for($i = $week_start; $i < $week_start + 7; $i++) {
				$day = $i % 7;
				print '<li><input type="checkbox" name="day' . $day . '" id="day' . $day . '" /> <label for="day' . $day . '">' . $langs->trans('PFDay'.$day.'Plural') . '</label></li>';
			}
			print '</ul>';
			
			print $langs->trans('PFduringNWeeks', $formCore->texte('', 'nb_semaines', 1, 2));
			print ' ' . $langs->trans('PFfromDate') . ' ' . $formCore->calendrier('', 'date_recurrence', '');
			print '...</label> ';
			print '</td>';

			print '<td>... ' . $langs->trans('PFfromTime') . ' ' . $formCore->timepicker('', 'heure_debut', '') . '...</td>';
			print '<td>... ' . $langs->trans('PFtoTime') . ' '. $formCore->timepicker('', 'heure_fin', ''). '</td>';
			print '<td>' . '' . '</td>';
			print '<td align="right">' . $formCore->hidden ( 'action', 'addtimeslot' ) . $formCore->hidden ( 'fk_session', $session->rowid ) . $formCore->btsubmit ( $langs->trans ( 'Add' ), 'addtimeslot' ) . '</td>';
			print '</tr>';

			$formCore->end();
		
		} else {
			print '<tr><td colspan="5" align="center">' . $langs->trans ( 'PFFormationTimePlanned' ) . '</td></tr>';
		}
	}
	
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