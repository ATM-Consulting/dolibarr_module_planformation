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
		parent::add_champs('date_start, date_end', array('type'=>'date'));
		parent::add_champs('ref,title', array('type'=>'string'));
		// Ici
		parent::add_champs('budget', array('type'=>'float'));
		// Jusque là
		parent::add_champs('fk_user_modification,fk_user_creation,entity', array('type'=>'integer','index'=>true));
		parent::add_champs('statut', array('type' => 'integer'));

		parent::_init_vars();
		parent::start();

		$dt = new DateTime();
		$dt->setDate($dt->format('Y'), 1, 1);
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
		// Ici
		$sql .= ' planform.budget, ';
		// Jusque là
		$sql .= ' dict.code as type_fin_code, ';
		$sql .= ' dict.label as type_fin_label ';
		$sql .= ' FROM ' . $this->get_table().' as planform';
		$sql .= ' LEFT JOIN ' . $dict->get_table() . ' as dict ON (planform.fk_type_financement=dict.rowid)';
		$sql .= ' WHERE planform.entity IN ('.getEntity(get_class($this)).')';

		return $sql;
	}

	/**
	 *
	 * @param string $mode
	 */
	public function getTrans($mode = 'std') {
		global $langs;
		$langs->load('planformation@planformation');

		$transarray = array (
				'rowid' => $langs->trans('Id'),
				'ref' => $langs->trans('Ref'),
				'date_start' => $langs->trans('DateStart'),
				'date_end' => $langs->trans('DateEnd'),
				'title' => $langs->trans('Title'),
				// Ici
				'budget' => $langs->trans('Budget'),
				// Jusque là
				'type_fin_label' => $langs->trans('PFTypeFin'),
				'statut' => $langs->trans('Status')
		);
		if ($mode == 'title') {
			foreach ( $transarray as $key => $val ) {
				$trans_array_title[$key . '_title'] = $val;
			}

			$transarray = $trans_array_title;
		}

		return $transarray;
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

}

/**
 * Class TSection
 */
class TSection extends TObjetStd
{
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	function __construct() {
		global $langs;

		parent::set_table(MAIN_DB_PREFIX . 'planform_section');
		parent::add_champs('title,ref', array('type'=>'string','index'=>true));
		parent::add_champs('fk_usergroup', array('type'=>'integer','index'=>true));
		parent::add_champs('fk_user_modification,fk_user_creation,entity', array('type'=>'integer','index'=>true));
		parent::_init_vars();
		parent::start();

	}
        
        public function isDeletable(&$PDOdb) {
            $sql = 'SELECT rowid
                    FROM '.MAIN_DB_PREFIX.'planform_planform_section
                    WHERE fk_section='.$this->rowid;
            $result = $PDOdb->Execute($sql);
            if ($result !== false) {
		return !$PDOdb->Get_line();
                /*while ( $PDOdb->Get_line() ) {
			return false;
		}
                return true;*/
            }
            
        }
	
	function save(&$PDOdb, $budget = '', $fk_section_parente='', $plan_id=0) {
		if(!empty($plan_id)) {
			$planSection = new TSectionPlanFormation();
                        $planSection->loadByCustom($PDOdb, array('fk_planform' => $plan_id, 'fk_section' => $_REQUEST['id']));
                        
                        $deltaBudget = $planSection->checkBudget($PDOdb, $budget);
                        
                        if($deltaBudget < 0)
                            $planSection->budget = $budget + $deltaBudget;
                        else
                            $planSection->budget = $budget;
                        
                        $planSection->fk_section_parente = $fk_section_parente;
                        $planSection->save($PDOdb);
		}
		parent::save($PDOdb);
	}

	/**
	 *
	 * @return string
	 */
	public function getSQLFetchAll() {
		global $conf, $langs,$user;

		$sql = 'SELECT s.rowid as ID,';
		$sql .= ' s.ref, ';
		$sql .= ' s.title, ';
		$sql .= ' s.fk_usergroup, ';
		$sql .= ' g.nom as group_name, ';
		$sql .= ' s.fk_user_modification, ';
		$sql .= ' s.fk_user_creation, ';
		$sql .= ' s.entity ';
		$sql .= ' FROM ' . $this->get_table().' as s';
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX.'usergroup as g ON (s.fk_usergroup=g.rowid AND g.entity IN ('.getEntity('usergroup').'))';
		$sql .= ' WHERE s.entity IN ('.getEntity(get_class($this)).')';

		return $sql;
	}

	/**
	 *
	 * @param string $mode
	 */
	public function getTrans($mode = 'std') {
		global $langs;
		$langs->load('planformation@planformation');
		$langs->load("users");

		$transarray = array (
				'rowid' => $langs->trans('Id'),
				'ref' => $langs->trans('Ref'),
				'title' => $langs->trans('Title'),
				'group_name' => $langs->trans('Group'),
				'fk_usergroup' => $langs->trans('Group'),
                                'nom_section_parente' => $langs->trans('Parent'),
		);
		if ($mode == 'title') {
			foreach ( $transarray as $key => $val ) {
				$trans_array_title[$key . '_title'] = $val;
			}

			$transarray = $trans_array_title;
		}

		return $transarray;
	}

	/**
	 * Returns the reference to the following non used section used depending on the active numbering module
	 * defined into PF_SECTION_ADDON
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

		if (! empty($conf->global->PF_SECTION_ADDON)) {
			foreach ( $dirmodels as $reldir ) {
				$dir = dol_buildpath($reldir . "core/modules/planformation/");
				if (is_dir($dir)) {
					$handle = opendir($dir);
					if (is_resource($handle)) {
						$var = true;

						while ( ($file = readdir($handle)) !== false ) {
							if ($file == $conf->global->PF_SECTION_ADDON . '.php') {
								$file = substr($file, 0, dol_strlen($file) - 4);
								require_once $dir . $file . '.php';

								$module = new $file();

								// Chargement de la classe de numerotation
								$classname = $conf->global->PF_SECTION_ADDON;

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


	/**
	 *
	 * @param TPDOdb $PDOdb
	 * @param number $planform_id
	 * @return array
	 */
	public function getAvailableSection(TPDOdb &$PDOdb, $planform_id=0) {
		$pfs_link = new TSectionPlanFormation();
		$sec = new TSection();

		//Find already linked section to avoid them into comobox
		$alreadylinked=array();
		$sql = $pfs_link->getSQLFetchAll(array (
				'p.rowid' => $planform_id
		));
		$result = $PDOdb->Execute($sql);
		if ($result !== false) {
			while ( $PDOdb->Get_line() ) {
				$alreadylinked[$PDOdb->Get_field('section_id')] = $PDOdb->Get_field('section_id');
			}
		} else {
			setEventMessage($PDOdb->db->errorInfo()[2],'errors');
		}

		//Build Combo box array
		$sql = $sec->getSQLFetchAll();
		$result = $PDOdb->Execute($sql);
		if ($result !== false) {
			while ( $PDOdb->Get_line() ) {
				if (!array_key_exists($PDOdb->Get_field('ID'), $alreadylinked)) {
					$availableSection[$PDOdb->Get_field('ID')] = $PDOdb->Get_field('ref') .' - ' . dol_trunc($PDOdb->Get_field('title'),20);
				}
			}
		} else {
			setEventMessage($PDOdb->db->errorInfo()[2],'errors');
		}

		return $availableSection;

	}
        
        /*private static function getSectionNameByIdbis(TPDOdb &$PDOdb, $TParam) {
            
            $TName = array();
            foreach($TParam as $id) {
                
                $pfs = new TSection();
                $pfs->load($PDOdb, $id);
                $TName[] = $pfs->title;
            }
            return $TName;
        }*/
        
        
}

/**
 * Class TSectionUserGroup
 */
class TSectionPlanFormation extends TObjetStd
{
	function __construct() {
		global $langs;

		parent::set_table(MAIN_DB_PREFIX . 'planform_planform_section');
		parent::add_champs('fk_planform,fk_section,fk_section_parente', array('type'=>'integer','index'=>true));
		parent::add_champs('budget', array('type'=>'float','index'=>true));

		parent::_init_vars();
		parent::start();
	}
        
        public function delete(&$PDOdb) {
            
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'planform_planform_section
                    SET fk_section_parente=0
                    WHERE fk_section_parente='.$this->fk_section;
            
            $PDOdb->Execute($sql);
            parent::delete($PDOdb);
        }


        public static function getSectionNameById(TPDOdb &$PDOdb, $id) {
            
            $pfs = new TSection();
            $pfs->load($PDOdb, $id);
            return $pfs->title;
        }
        
        public function checkBudget(TPDOdb &$PDOdb, $newBudget) {
            $pfsLink = new TSectionPlanFormation();
            $pfsLink->loadByCustom($PDOdb, array('fk_planform' => $this->fk_planform, 'fk_section' => $this->fk_section));
            
            $TsectionSoeurs = array(); 
            $this->getSectionsSoeurs($TsectionSoeurs, $this->fk_section);
            
            $sommeBudget = 0;
            foreach ($TsectionSoeurs as $id) {
                $sec = new TSectionPlanFormation();
                $sec->loadByCustom($PDOdb, array('fk_planform' => $this->fk_planform, 'fk_section' => $id));
                
                $sommeBudget += $sec->budget;
            }
            
            // Récupérer le budget du plan de formation ou de la section parente
            var_dump($this->fk_section_parente);
            if(empty($pfsLink->fk_section_parente)) {
                $pf = new TPlanFormation();
                $pf->load($PDOdb, $this->fk_planform);
                return $pf->budget - ($sommeBudget + $newBudget);
            }
            $pfsLinkParente = new TSectionPlanFormation();
            $pfsLinkParente->loadByCustom($PDOdb, array('fk_planform' => $this->fk_planform, 'fk_section' => $pfsLink->fk_section_parente));
            return $pfsLinkParente->budget - ($sommeBudget + $newBudget);
        }
        
        
        public function getAvailableParentSection(TPDOdb &$PDOdb) {
            // Récupération de toutes les sections du plan de formation
            $sql = 'SELECT fk_section 
                    FROM '.MAIN_DB_PREFIX.'planform_planform_section
                    WHERE fk_planform='.$this->fk_planform;
            $result = $PDOdb->Execute($sql);
            $TSections = array();
            if ($result !== false) {
                while ( $PDOdb->Get_line() ) {
                    $TSections[] = $PDOdb->Get_field('fk_section');
                }
            }
            
            
            //Identifier celles qui ne sont pas disponibles (enfantes)
            $TSectionEnfantes = array();            
            $this->getSectionsFilles($TSectionEnfantes, $this->fk_planform, $this->fk_section);
            $TSectionEnfantes[] = $this->fk_section;
                        
            // Différence entre les deux tableaux (TSections et TSectionEnfantes)
            $TAvailableParentSectionsId = array_diff($TSections, $TSectionEnfantes);
            
            $TAvailableParentSections = array();
            $TAvailableParentSections[0] = '';
            //return self::getSectionNameById($PDOdb, $TAvailableParentSections);
            foreach($TAvailableParentSectionsId as $id) {
                $TAvailableParentSections[$id] = TSectionPlanFormation::getSectionNameById($PDOdb, $id);
            }
            
            return $TAvailableParentSections;
        }
        
        /*
         * Retourne toutes les sections soeurs d'une section.
         * Ne renvoie pas la section qui a pour id, l'id passé en paramètre.
         */
        public function getSectionsSoeurs(&$TSectionSoeurs, $fkSection) {
            
            $PDOdb = new TPDOdb;
            $fkSectionParente = $this->fk_section_parente;
            
            $sql = 'SELECT fk_section 
                    FROM '.MAIN_DB_PREFIX.'planform_planform_section
                    WHERE fk_section_parente='.$fkSectionParente
                    .' AND fk_planform='.$this->fk_planform;
            
            $result = $PDOdb->Execute($sql);
            if ($result !== false) {
                while ( $PDOdb->Get_line() ) {
                    $fkSectionSoeur = (int) $PDOdb->Get_field('fk_section');
                    if($fkSectionSoeur !== $fkSection)
                        $TSectionSoeurs[] = $fkSectionSoeur;
                }
            }
        }
        
        public function getSectionsFilles(&$TSectionEnfantes, $fkPlanform, $fkSectionPF) {
            
            $PDOdb = new TPDOdb;
            $sql = 'SELECT pps.rowid, fk_section, fk_section_parente , pps.budget, ref, nom
                    FROM '.MAIN_DB_PREFIX.'planform_planform_section as pps
                    INNER JOIN '.MAIN_DB_PREFIX.'planform_section as ps ON pps.fk_section=ps.rowid
                    INNER JOIN '.MAIN_DB_PREFIX.'usergroup as ug ON ps.fk_usergroup=ug.rowid
                    WHERE fk_section_parente='.$fkSectionPF
                    .' AND fk_planform='.$fkPlanform;
            
            $result = $PDOdb->Execute($sql);
            if ($result !== false) {
                while ( $PDOdb->Get_line() ) {
                    $fkSectionPFFille = $PDOdb->Get_field('rowid');
                    
                    $TSectionEnfantes[] = array(
						'rowid' => $PDOdb->Get_field('fk_section')
						,'fk_planform' => $fkPlanform
						,'fk_section' => $PDOdb->Get_field('fk_section')
						,'ref' => $PDOdb->Get_field('ref')
						,'groupe' => $PDOdb->Get_field('nom')
						,'budget' => $PDOdb->Get_field('budget')
						,'fk_section_parente' => $fkSectionPF
                    );

                    $this->getSectionsFilles($TSectionEnfantes, $fkPlanform, $fkSectionPFFille);
                }
            }
        }
        
        /*
         * Retourne toutes les sections d'un plan de formation ordonnées de telle façon que les sections filles
         * apparaissent immédiatement au-dessous de leur section parente.
         */
        public function getAllSection(&$PDOdb, &$TRes, $fkPlanform) {
            
            $sql = 'SELECT pps.rowid, fk_section, fk_section_parente, pps.budget, ref, nom
                    FROM '.MAIN_DB_PREFIX.'planform_planform_section as pps
                    INNER JOIN '.MAIN_DB_PREFIX.'planform_section as ps ON pps.fk_section=ps.rowid
                    INNER JOIN '.MAIN_DB_PREFIX.'usergroup as ug ON ps.fk_usergroup=ug.rowid
                    WHERE fk_planform='.$fkPlanform
                    .' AND fk_section_parente=0';
            
            $result = $PDOdb->Execute($sql);
            if ($result !== false) {
                while ( $PDOdb->Get_line() ) {
                    $fkSectionPF = $PDOdb->Get_field('rowid');

					$TRes[] = array(
						'rowid' => $PDOdb->Get_field('rowid')
						,'fk_planform' => $fkPlanform
						,'fk_section' => $PDOdb->Get_field('fk_section')
						,'ref' => $PDOdb->Get_field('ref')
						,'groupe' => $PDOdb->Get_field('nom')
						,'budget' => $PDOdb->Get_field('budget')
						,'fk_section_parente' => '0'
					);

                    $this->getSectionsFilles($TRes, $fkPlanform, $fkSectionPF);
                }
            }
            
            return $res;
        }
	
	function loadByCustom(&$db, $TParam, $annexe=false) {                
		$sql = "SELECT ".OBJETSTD_MASTERKEY." FROM ".$this->get_table()." WHERE 1=1";
		
		foreach($TParam as $key => $value) {
			$sql .= " AND $key=$value";
		}
		
		$sql .= ' LIMIT 1';
	  	$db->Execute($sql);
		if($db->Get_line()) {
                    return $this->load($db, $db->Get_field(OBJETSTD_MASTERKEY), $annexe);
		}
		else {
			return false;
		}
	}

	/**
	 *
	 * @return string
	 */
	public function getSQLFetchAll($filterAnd=array(),$filterOr=array()) {
		global $conf, $langs,$user, $db;

		$pf = new TPlanFormation();
		$sec = new TSection();

		$sql = 'SELECT ps.rowid as ID ,';
		$sql .= ' s.rowid as section_id, ';
		$sql .= ' s.ref, ';
		$sql .= ' s.title, ';
		$sql .= ' s.fk_usergroup, ';
		$sql .= ' g.nom as group_name, ';
		$sql .= ' s.fk_user_modification, ';
		$sql .= ' s.fk_user_creation, ';
		//$sql .= ' ps.fk_section_parente, ';
                $sql .= ' s2.title as nom_section_parente, ';
		$sql .= ' ps.budget, ';     
		$sql .= ' s.entity, ';
		$sql .= ' p.rowid as planform_id, ';
		$sql .= ' "" as supprimer ';
		$sql .= ' FROM ' . $this->get_table().' as ps';
		$sql .= ' INNER JOIN '.$pf->get_table().' as p ON (p.rowid=ps.fk_planform)';
		$sql .= ' INNER JOIN '.$sec->get_table().' as s ON (s.rowid=ps.fk_section)';
                $sql .= ' LEFT JOIN '.$sec->get_table().' as s2 ON (s2.rowid=ps.fk_section_parente)';
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX.'usergroup as g ON (s.fk_usergroup=g.rowid AND g.entity IN ('.getEntity('usergroup').'))';
		$sql .= ' WHERE s.entity IN ('.getEntity(get_class($sec)).') AND p.entity IN ('.getEntity(get_class($pf)).')';

		// Manage filter
		$sqlwhere = array ();
		if (count($filterAnd) > 0) {
			foreach ( $filterAnd as $key => $value ) {
				if (($key == 's.rowid' || $key == 'p.rowid') && is_numeric($value)) {
					$sqlwhere[] = $key . ' = ' . $db->escape(price2num($value));
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND '. implode(' AND ', $sqlwhere);
		}

		// Manage filter OR
		$sqlwhere = array ();
		if (count($filterOr) > 0) {
			foreach ( $filterOr as $key => $value ) {
				if (($key == 's.rowid' || $key == 'p.rowid') && is_numeric($value)) {
					$sqlwhere[] = $key . ' = ' . $escape(price2num($value));
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' OR ', $sqlwhere).')';
		}

		return $sql;
	}
	
	/**
	 *
	 * @param string $mode
	 */
	public function getTrans($mode = 'std') {
		global $langs;
		$langs->load('planformation@planformation');
		$langs->load("users");

		$transarray = array (
				'rowid' => $langs->trans('Id'),
				'date_cre' => $langs->trans('Date_cre'),
				'date_maj' => $langs->trans('Date_maj'),
				'fk_planform' => $langs->trans('Planform'),
				'fk_section' => $langs->trans('Section'),
				'fk_section_parente' => $langs->trans('Parent'),
				'budget' => $langs->trans('Budget')
		);
		if ($mode == 'title') {
			foreach ( $transarray as $key => $val ) {
				$trans_array_title[$key . '_title'] = $val;
			}

			$transarray = $trans_array_title;
		}

		return $transarray;
	}
}
