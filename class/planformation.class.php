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
 * Class TPlanFormation
 */

class TPlanFormation extends TObjetStd
{
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe


	/**
	 * __construct
	 */
	function __construct() {
		global $langs;

		parent::set_table(MAIN_DB_PREFIX . 'planform');
		parent::add_champs('fk_type_financement', array('type'=>'integer','index'=>true));
		parent::add_champs('date_start, date_end, date_demande, date_reponse', array('type'=>'date'));
		parent::add_champs('ref,title', array('type'=>'string'));
		parent::add_champs('budget_previsionnel,budget_finance_accepte,budget_finance_reel,budget_consomme', array('type'=>'float'));
		parent::add_champs('fk_opca', array('type' => 'integer'));
		parent::add_champs('fk_user_modification,fk_user_creation,entity', array('type'=>'integer','index'=>true));
		parent::add_champs('statut', array('type' => 'integer'));

		parent::_init_vars();
		parent::start();

		$dt = new DateTime();
		$dt->setDate($dt->format('Y') + 1, 1, 1);
		$this->date_start = $dt->getTimestamp();

		$dt->setDate($dt->format('Y'), 12, 31);
		$this->date_end = $dt->getTimestamp();

		$this->setChild('TSectionPlanFormation', 'fk_planform');
	}

	/**
	 *
	 * @return string
	 */
	public function getSQLFetchAll() {
		global $conf, $langs;

		require_once ('dictionnaire.class.php');

		$dict = new TTypeFinancement();

		$sql = 'SELECT planform.rowid as ID ,';
		$sql .= ' planform.ref, ';
		$sql .= ' planform.title, ';
		$sql .= ' planform.date_start, ';
		$sql .= ' planform.date_end, ';
		$sql .= ' planform.fk_user_modification, ';
		$sql .= ' planform.fk_user_creation, ';
		$sql .= ' planform.entity, ';
		$sql .= ' planform.fk_type_financement,';
		$sql .= ' planform.fk_opca,';
		$sql .= ' soc.nom AS opca,';
		$sql .= ' planform.budget_previsionnel, ';
		$sql .= ' planform.budget_finance_accepte, ';
		$sql .= ' planform.budget_finance_reel, ';
		$sql .= ' planform.budget_consomme, ';
		$sql .= ' dict.code as type_fin_code, ';
		$sql .= ' dict.label as type_fin_label ';
		$sql .= ' FROM ' . $this->get_table().' as planform';
		$sql .= ' LEFT JOIN ' . $dict->get_table() . ' as dict ON (planform.fk_type_financement=dict.rowid)';
		$sql .= ' LEFT JOIN '. MAIN_DB_PREFIX .'societe as soc ON (soc.rowid = planform.fk_opca)';
		$sql .= ' WHERE planform.entity IN ('.getEntity(get_class($this)).')';

		return $sql;
	}


	public static function getTrans() {
		global $langs;
		$langs->load('planformation@planformation');

		return array (
			'rowid' => $langs->trans('Id')
			, 'ref' => $langs->trans('Ref')
			, 'date_start' => $langs->trans('DateStart')
			, 'date_end' => $langs->trans('DateEnd')
			, 'title' => $langs->trans('Title')
			, 'opca' => $langs->trans('OPCA')
			, 'budget_previsionnel' => $langs->trans('PFProjectedBudget')
			, 'budget_finance_accepte' => $langs->trans('PFApprovedFundedBudget')
			, 'budget_finance_reel' => $langs->trans('PFActualFundedBudget')
			, 'budget_consomme' => $langs->trans('PFUsedBudget')
			, 'type_fin_label' => $langs->trans('PFTypeFin')
			, 'statut' => $langs->trans('Status')
		);
	}


	/**
	 * Returns the reference to the following non used plan formation used depending on the active numbering module
	 * defined into LEAD_ADDON
	 *
	 * @param int $fk_user Id
	 * @param Societe $objsoc Object
	 * @return string Reference libre pour la lead
	 */
	function getNextNumRef($fk_user = null, Societe $objsoc = null) {
		global $conf, $langs;
		$langs->load("planformation@planformation");

		$dirmodels = array_merge(array (
				'/'
		), ( array ) $conf->modules_parts['models']);

		if (! empty($conf->global->PF_ADDON)) {
			foreach ( $dirmodels as $reldir ) {
				$dir = dol_buildpath($reldir . "core/modules/planformation/");
				if (is_dir($dir)) {
					$handle = opendir($dir);
					if (is_resource($handle)) {
						$var = true;

						while ( ($file = readdir($handle)) !== false ) {
							if ($file == $conf->global->PF_ADDON . '.php') {
								$file = substr($file, 0, dol_strlen($file) - 4);
								require_once $dir . $file . '.php';

								$module = new $file();

								// Chargement de la classe de numerotation
								$classname = $conf->global->PF_ADDON;

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


	public function propose(&$PDOdb) {
		if($this->statut == 0) {
			$this->date_demande = dol_now();
			$this->statut = 1;
			$this->save($PDOdb);

			return true;
		}

		return false;
	}
	
	public function rework(&$PDOdb) {
		if($this->statut == 1) {
			$this->statut = 0;
			$this->save($PDOdb);
			
			return true;
		}

		return false;
	}
	
	public function validate(&$PDOdb) {
		if($this->statut == 1) {
			$this->statut = 2;
			$this->save($PDOdb);
			
			return true;
		}

		return false;
	}
	
	public function abandon(&$PDOdb) {
		if($this->statut != 2) {
			$this->statut = 3;
			$this->save($PDOdb);
			
			return true;
		}

		return false;
	}
	
	public function reopen(&$PDOdb) {
		if($this->statut >= 2) {
			$this->statut = 0;
			$this->save($PDOdb);

			return true;
		}

		return false;
	}
	
	public function getRemainingBudget(&$PDOdb) {

		$spf = new TSectionPlanFormation;
		$TSectionsFilles = array();
		$spf->getSectionsFilles($PDOdb, $TSectionsFilles, $this->id, 0, false);

		$budgetFilles = 0;

		foreach($TSectionsFilles as $sectionFille) {
			$budgetFilles += $sectionFille['budget'];
		}

		return($this->budget_previsionnel - $budgetFilles);
	}

	public function getAvailableSessions(&$PDOdb) {
		$TRes = array();

		$sql = "SELECT s.rowid, s.ref, f.title, s.date_debut, s.date_fin";
		$sql.= " FROM " . MAIN_DB_PREFIX . "planform_session AS s";
		$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "planform_formation AS f ON (f.rowid = s.fk_formation)";
		$sql.= " WHERE s.rowid NOT IN (";
		$sql.= "	SELECT fk_session AS rowid";
		$sql.= "	FROM " . MAIN_DB_PREFIX . "planform_assoc_session";
		$sql.= " )";

		$res = $PDOdb->Execute($sql);
		if($res) {
			for($i = 0; $i < $res->rowCount(); $i++) {
				$TRes[] = $PDOdb->Get_line();
			}
		}

		return $TRes;
	}

	public function getSessions($PDOdb) {
		$TRes = array();

		if($this->rowid <= 0) {
			return $TRes;
		}

		$sql = "SELECT pas.rowid, s.rowid as sessionid, s.ref, f.title, pas.fk_section_parente, s.budget, s.date_debut, s.date_fin";
		$sql.= " FROM " . MAIN_DB_PREFIX . "planform_session AS s";
		$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "planform_formation AS f ON (f.rowid = s.fk_formation)";
		$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "planform_assoc_session AS pas ON (s.rowid = pas.fk_session)";
		$sql.= " WHERE pas.fk_planform = " . $this->rowid;
		
		$res = $PDOdb->Execute($sql);
		if($res) {
			for($i = 0; $i < $res->rowCount(); $i++) {
				$TRes[] = $PDOdb->Get_line();
			}
		}

		return $TRes;
	}


	public function getAllSections(&$PDOdb) {
		$TRes = array();
		
		$sql = "SELECT ps.rowid, title, fk_usergroup, fk_section_parente, budget, nom";
		$sql.= " FROM " . MAIN_DB_PREFIX. "planform_section as ps";
		$sql.= " INNER JOIN " . MAIN_DB_PREFIX . "usergroup as ug ON ps.fk_usergroup=ug.rowid";
		$sql.= " WHERE fk_planform = " . $this->rowid;

		
		$res = $PDOdb->Execute($sql);

		if($res !== false) {
			while ( $PDOdb->Get_line() ) {
				$TRes[] = array(
					'rowid' => $PDOdb->Get_field('rowid')
					, 'title' => $PDOdb->Get_field('title')
					, 'fk_planform' => $fkPlanform
					, 'fk_usergroup' => $PDOdb->Get_field('fk_usergroup')
					, 'groupe' => $PDOdb->Get_field('nom')
					, 'budget' => $PDOdb->Get_field('budget')
					, 'fk_section_parente' => $PDOdb->Get_field('fk_section_parente')
				);
			}
		}

		return $TRes;
	}
}



/**
 * Class TSectionUserGroup
 */
class TSectionPlanFormation extends TObjetStd
{
	function __construct() {
		global $langs;

		parent::set_table(MAIN_DB_PREFIX . 'planform_section');

		parent::add_champs('title', array('type'=>'string','index'=>true));
		parent::add_champs('fk_planform,fk_section_parente', array('type'=>'integer','index'=>true));
		parent::add_champs('fk_usergroup', array('type'=>'integer'));
		parent::add_champs('fk_user_modification,fk_user_creation,entity', array('type'=>'integer','index'=>true));
		parent::add_champs('budget', array('type'=>'float'));

		parent::_init_vars();

		parent::start();

		$this->setChild('TPlanFormationSession', 'fk_section_parente');
	}


	public function delete(&$PDOdb) {
            
		$sql = 'UPDATE '.$this->get_table().'
				SET fk_section_parente=0
				WHERE fk_section_parente='.$this->rowid;
            
		$PDOdb->Execute($sql);
		parent::delete($PDOdb);
	}


	public function getRemainingBudget(&$PDOdb) {

		$TSectionsFilles = array();
		$this->getSectionsFilles($PDOdb, $TSectionsFilles, $this->fk_planform, $this->id, false);
        	
		$budgetFilles = 0;
        	
		foreach($TSectionsFilles as $sectionFille) {
			$budgetFilles += $sectionFille['budget'];
		}

		return ($this->budget - $budgetFilles);
	}


	public function getSectionsFilles(&$PDOdb, &$TSectionEnfantes, $fkPlanform, $fkSectionPF, $recur = true) {
            
		$sql = 'SELECT ps.rowid, title, fk_section_parente, fk_usergroup, budget, nom
				FROM '.$this->get_table().' as ps
				INNER JOIN '.MAIN_DB_PREFIX.'usergroup as ug ON ps.fk_usergroup=ug.rowid
				WHERE fk_section_parente='.$fkSectionPF.'
				AND fk_planform='.$fkPlanform;

		$result = $PDOdb->Execute($sql);

		if ($result !== false) {
			while ( $PDOdb->Get_line() ) {
				$fkSectionPFFille = $PDOdb->Get_field('rowid');
                    
				$TSectionEnfantes[] = array(
					'rowid' => $PDOdb->Get_field('rowid')
					, 'title' => $PDOdb->Get_field('title')
					, 'fk_planform' => $fkPlanform
					, 'fk_usergroup' => $PDOdb->Get_field('fk_usergroup')
					, 'groupe' => $PDOdb->Get_field('nom')
					, 'budget' => $PDOdb->Get_field('budget')
					, 'fk_section_parente' => $fkSectionPF
				);

				if($recur) {
					$this->getSectionsFilles($PDOdb, $TSectionEnfantes, $fkPlanform, $fkSectionPFFille);
				}
			}
		}
	}
}
