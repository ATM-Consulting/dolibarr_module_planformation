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

class TParticipantSession extends TObjetStd
{
	
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	
	/**
	 * __construct
	 */
	function __construct() {
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX . 'planform_session_participant');
		
		//parent::add_champs('ref', array('type'=>'string','index'=>true));
		parent::add_champs('fk_user,fk_session', array('type'=>'integer','index'=>true));
		//parent::add_champs('fk_user_modification,fk_user_creation,entity', array('type'=>'integer','index'=>true));
		//parent::add_champs('fk_opca,is_interne', array('type'=>'integer'));
		//parent::add_champs('budget,budget_consomme,prise_en_charge_estimee,prise_en_charge_acceptee,prise_en_charge_reelle', array('type'=>'float'));
		//parent::add_champs('date_debut,date_fin', array('type'=>'date'));
		
		parent::_init_vars();
		parent::start();
		
	}


	function getAllBySession(&$PDOdb, $sessionId = 0) {
		$TRes = array();

		$sql = "SELECT p.rowid, p.fk_user, firstname, lastname";
		$sql.= " FROM " . $this->get_table() . " AS p";
		$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "user AS u ON (u.rowid=p.fk_user)";
		$sql.= " WHERE u.statut = 1";

		if($sessionId > 0) {
			$sql.= " AND fk_session = ".$sessionId;
		}

		$sql.= " ORDER BY lastname ASC, firstname ASC";

		$res = $PDOdb->Execute($sql);

		if($res) {
			for($i = 0; $i < $res->rowCount(); $i++) {
				$TRes[] = $PDOdb->Get_line();
			}
		}

		return $TRes;
	}

	function getUsersNotInSession(&$PDOdb, $sessionId) {
		$TRes = array();

		if($sessionId <= 0) {
			return false;
		}

		$sql = "SELECT rowid, firstname, lastname";
		$sql.= " FROM " . MAIN_DB_PREFIX . "user";
		$sql.= " WHERE statut = 1";
		$sql.= " AND rowid NOT IN (SELECT fk_user AS rowid FROM " . $this->get_table() . " WHERE fk_session = " . $sessionId . ")";

		$res = $PDOdb->Execute($sql);

		if($res) {
			for($i = 0; $i < $res->rowCount(); $i++) {
				$TRes[] = $PDOdb->Get_line();
			}
		}

		return $TRes;
	}
}
