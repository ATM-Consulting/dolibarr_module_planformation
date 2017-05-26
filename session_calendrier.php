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
	case 'addtimeslot':
		if($session->statut == 0) {
			$type = GETPOST('type');
			$heure_debut = GETPOST('heure_debut', 'alpha');
			$heure_fin = GETPOST('heure_fin', 'alpha');

			if($type == 'creneau') {
				$date = GETPOST('date', 'alpha');
		
				$session->addCreneau($PDOdb, $formation, $date, $heure_debut, $heure_fin);
			} else {

// TODO NETTOYAGE !

//var_dump($_REQUEST);
				$date_recurrence = GETPOST('date_recurrence', 'alpha');
				$nb_semaines = GETPOST('nb_semaines', 'int');

				if($nb_semaines > 0) {
//var_dump($date_recurrence);
//var_dump($nb_semaines);
					$week_start = isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1;
//var_dump($week_start);

					$TDateRecurrence = explode('/', $date_recurrence);

					$dateRecurrenceTimeStamp = dol_mktime(0, 0, 0, $TDateRecurrence[1], $TDateRecurrence[0], $TDateRecurrence[2], true); // heures, minutes, secondes, MOIS, jour, année, isGMT
//var_dump(date('l d/m/Y', $dateRecurrenceTimeStamp));

					$jourRecurrence = date('w', $dateRecurrenceTimeStamp); // 0 => dimanche, 1 => lundi, etc.
//var_dump($jourRecurrence);

					$noDayChosen = true;

					for($i = 0; $i < 7; $i++) { // Pour chaque jour en partant de la date de départ de la récurrence
					//for($j = $jourRecurrence; $j < $jourRecurrence + 7; $j++) { // Pour chaque jour en partant de la date de départ de la récurrence
						$day = ($i + $jourRecurrence) % 7; // on récupère le jour revoyé par date('w', ...);
	
						$dateFirstWeek = $dateRecurrenceTimeStamp + $i * 24 * 60 * 60;

//var_dump($_REQUEST['day'.$day]);
//var_dump(date('l d/m/Y', $dateFirstWeek));
						if(! empty($_REQUEST['day'.$day])) {
							$noDayChosen = false;
	
							for($s = 0; $s < $nb_semaines; $s++) { // Pour chaque semaine
//var_dump($s);
								$dateTimeStamp = $dateFirstWeek + $s * 7 * 24 * 60 * 60;
								$date = date('d/m/Y', $dateTimeStamp);
//var_dump($date);
//var_dump(date('l d/m/Y', $dateTimeStamp). ' ' . $heure_debut . '-' . $heure_fin);
	
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

/*
				$timestampDebutSemaine1 = $dateRecurrenceTimeStamp - ($jourRecurrence - $week_start) * 3600 * 24;

				if($week_start > $jourRecurrence) { // 
					
				}

var_dump(date('l d/m/Y', $timestampDebutSemaine1));


				for($s = 0; $s <= $nb_recurrence; $s++) { // on traite nb_recurrence + 1 semaines si ça déborde


				}
*/			
			}
		} else {
			setEventMessage($langs->trans('PFTryingToEditAValidatedSession'), 'errors');
		}

		_list ( $PDOdb, $session, $formation, $creneau );
	break;

	case 'deletetimeslot' :
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

		if($session->statut == 0) {
			$actionButtons = '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $session->id . '&action=deletetimeslot&timeslot=' . $c->rowid . '">' . img_picto ( '', 'delete' ) . '</a>';
		}

		$date_debut = dol_stringtotime(str_replace(' ', 'T', $c->debut), 0);
		$date_fin = dol_stringtotime(str_replace(' ', 'T', $c->fin), 0);
		$duree = $date_fin - $date_debut;

		$dureeTotaleSecs += $duree;

		print '<tr>';
		print '<td style="padding-left:40px">' . dol_print_date($date_debut, '%A %d %B %Y') . '</td>';
		print '<td>' . dol_print_date($date_debut, '%R'). '</td>';
		print '<td>' . dol_print_date($date_fin, '%R'). '</td>';
		print '<td>' . secondesToHHMM($duree). '</td>';
		print '<td align="right">' . $actionButtons . '</td>';
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