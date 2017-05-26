<?php
/* <Plan Formation>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
/**
 * Class TFormation
 */

class TSessionFormation extends TObjetStd
{
	
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	
	/**
	 * __construct
	 */
	function __construct() {
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX . 'planform_session');

		parent::add_champs('ref', array('type'=>'string','index'=>true));
		parent::add_champs('fk_formation', array('type'=>'integer','index'=>true));
		parent::add_champs('fk_user_modification,fk_user_creation,entity,statut', array('type'=>'integer','index'=>true));
		parent::add_champs('fk_opca,is_interne', array('type'=>'integer'));
		parent::add_champs('budget,budget_consomme,prise_en_charge_estimee,prise_en_charge_acceptee,prise_en_charge_reelle,duree_planifiee', array('type'=>'float'));
		parent::add_champs('date_debut,date_fin', array('type'=>'date'));
		
		parent::_init_vars();
		parent::start();

	}


	function getNextId(&$PDOdb) {

		$sql = "SELECT MAX(rowid) AS maxid FROM ".$this->get_table();

		$PDOdb->Execute($sql);
		$res = $PDOdb->Get_line();

		return $res->maxid + 1;
	}

	
	/**
	 * Returns the reference to the following non used plan formation used depending on the active numbering module
	 * defined into LEAD_ADDON
	 *
	 * @param int $fk_user Id
	 * @param Societe $objsoc Object
	 * @return string Reference libre pour la lead
	 */
	function getNextRef($fk_user = null, Societe $objsoc = null) {
		global $conf, $langs;
		$langs->load("planformation@planformation");
		
		$dirmodels = array_merge(array (
				'/'
		), ( array ) $conf->modules_parts['models']);
		
		if (! empty($conf->global->PF_SESSION_ADDON)) {
			foreach ( $dirmodels as $reldir ) {
				$dir = dol_buildpath($reldir . "core/modules/planformation/");
				if (is_dir($dir)) {
					$handle = opendir($dir);
					if (is_resource($handle)) {
						$var = true;
						
						while ( ($file = readdir($handle)) !== false ) {
							if ($file == $conf->global->PF_SESSION_ADDON . '.php') {
								$file = substr($file, 0, dol_strlen($file) - 4);
								require_once $dir . $file . '.php';
								
								$module = new $file();
								
								// Chargement de la classe de numerotation
								$classname = $conf->global->PF_SESSION_ADDON;
								
								$obj = new $classname();
								
								$numref = "";
								$numref = $obj->getNextValue($fk_user, $objsoc, $this);
								
								if ($numref != "") {
									return $numref;
								} else {
									$this->error = $obj->error;
									return "";
								}
							}
						}
					}
				}
			}
		} else {
			$langs->load("errors");
			$this->errors[]= $langs->trans("Error") . " " . $langs->trans("ErrorModuleSetupNotComplete");
			return -1;
		}
		
		return null;
	}

	function getParticipants(&$PDOdb) {
		$TRes = array();

		if($this->rowid <= 0) {
			return false;
		}

		$sql = "SELECT p.rowid, p.fk_user, firstname, lastname";
		$sql.= " FROM " . MAIN_DB_PREFIX . "planform_session_participant AS p";
		$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "user AS u ON (u.rowid=p.fk_user)";
		$sql.= " WHERE u.statut = 1";
		$sql.= " AND fk_session = " . $this->rowid;

		$res = $PDOdb->Execute($sql);
		
		if($res) {
			for($i = 0; $i < $res->rowCount(); $i++) {
				$TRes[] = $PDOdb->Get_line();
			}
		}
		
		return $TRes;
	}
	
	function getUsersNotSignedUp(&$PDOdb) {
		$TRes = array();
		
		if($this->rowid <= 0) {
			return false;
		}
		
		$sql = "SELECT rowid, firstname, lastname";
		$sql.= " FROM " . MAIN_DB_PREFIX . "user";
		$sql.= " WHERE statut = 1";
		$sql.= " AND rowid NOT IN (SELECT fk_user AS rowid FROM " . MAIN_DB_PREFIX . "planform_session_participant WHERE fk_session = " . $this->rowid. ")";

		$res = $PDOdb->Execute($sql);
		
		if($res) {
			for($i = 0; $i < $res->rowCount(); $i++) {
				$TRes[] = $PDOdb->Get_line();
			}
		}
		
		return $TRes;
	}

	function getCreneaux(&$PDOdb) {
		$TRes = array();

		if($this->rowid <= 0) {
			return false;
		}

		$sql = "SELECT rowid, debut, fin";
		$sql.= " FROM " . MAIN_DB_PREFIX . "planform_session_creneau";
		$sql.= " WHERE fk_session = " . $this->rowid;
		$sql.= " ORDER BY debut ASC";

		$res = $PDOdb->Execute($sql);
		
		if($res) {
			for($i = 0; $i < $res->rowCount(); $i++) {
				$TRes[] = $PDOdb->Get_line();
			}
		}

		return $TRes;
	}

	function hasCreneauxBetween(&$PDOdb, $date_debut, $date_fin, $except = 0) {

		$sql = "SELECT count(rowid) AS nb";
		$sql.= " FROM " . MAIN_DB_PREFIX . "planform_session_creneau";
		$sql.= " WHERE fk_session = " . $this->rowid;
		$sql.= " AND debut < '" . $date_fin . "'";
		$sql.= " AND fin > '" . $date_debut . "'";

		if(! empty($except)) {
			$sql.= "AND rowid != ".$except;
		}

		$res = $PDOdb->Execute($sql);

		if($res) {
			$PDOdb->Get_line();
			$nb = $PDOdb->Get_field('nb');

			if($nb > 0) {
				return true;
			}
		}

		return false;
	}

	function addCreneau(&$PDOdb, $formation, $date, $heure_debut, $heure_fin, $idCreneau = 0) {
		global $langs;

		if(strcmp($heure_debut, $heure_fin) < 0) {
			$TDate = explode('/', $date);
			
			$dateSQL = $TDate[2] . '-' . $TDate[1] . '-' . $TDate[0] . ' ';
			$date_debut = $dateSQL.$heure_debut.':00';
			$date_fin = $dateSQL.$heure_fin.':00';
	
			if(! $this->hasCreneauxBetween($PDOdb, $date_debut, $date_fin, $idCreneau)) {
				$THeureDebut = explode(':', $heure_debut);
				$THeureFin = explode(':', $heure_fin);

				$creneau = new TCreneauSession;
				$ancienneDuree = 0;

				if(! empty($idCreneau)) {
					$creneau->load($PDOdb, $idCreneau);
					$ancienneDuree = $creneau->fin - $creneau->debut;
				}

				$ancienneDureeHeures = $ancienneDuree / 3600;
				
				$creneau->fk_session = $this->rowid;

				$newDebutCreneau = dol_mktime($THeureDebut[0], $THeureDebut[1], 0, $TDate[1], $TDate[0], $TDate[2]); // heures, minutes, secondes, MOIS, jour, année
				$newFinCreneau = dol_mktime($THeureFin[0], $THeureFin[1], 0, $TDate[1], $TDate[0], $TDate[2]);

				if($newDebutCreneau >= $this->date_debut && $newFinCreneau <= ($this->date_fin + 86400)) { // date_fin -> le jour de la fin à minuit, on rajoute un jour en rajoutant 86400s
					$nouvelleDureeHeures = ($newFinCreneau - $newDebutCreneau) / 3600;

					if($this->duree_planifiee - $ancienneDureeHeures + $nouvelleDureeHeures <= $formation->duree) {
						$creneau->debut = $newDebutCreneau;
						$creneau->fin = $newFinCreneau;

						$this->duree_planifiee -= $ancienneDureeHeures;
						$this->duree_planifiee += $nouvelleDureeHeures;

						$this->save($PDOdb);
						$creneau->save($PDOdb);
					} else {
						setEventMessage($langs->trans('PFSessionTooMuchTimePlanned'), 'errors');
					}
				} else {
					setEventMessage($langs->trans('PFTimeSlotOutOfSessionDates'), 'errors');
				}
			} else {
				setEventMessage($langs->trans('PFTimeSlotOverlap'), 'errors');
			}
		} else {
			setEventMessage($langs->trans('PFStartTimeMustBeBeforeEndTime'), 'errors');
		}
	}

	function validate(&$PDOdb) {
		global $langs;

		if($this->statut == 0) {
			$TParticipants = $this->getParticipants($PDOdb);

			if(count($TParticipants) > 0) {
				$TCreneaux = $this->getCreneaux($PDOdb);

				if(count($TCreneaux) > 0) {
					$this->statut = 1;
					$this->save($PDOdb);
				} else {
					setEventMessage($langs->trans('PFCannotValidateASessionWithNoTimeSlot'), 'errors');
				}
			} else {
				setEventMessage($langs->trans('PFCannotValidateASessionWithNoAttendees'), 'errors');
			}
		} else {
			setEventMessage($langs->trans('PFTryingToValidateANonDraftSession'), 'errors');
		}
	}

	function reopen(&$PDOdb) {
		global $langs;

		if($this->statut == 1) {
			$this->statut = 0;
			$this->save($PDOdb);
		} else {
			setEventMessage($langs->trans('PFTryingToReopenANonValidatedSession'), 'errors');
		}
	}
}
