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

class TFormation extends TObjetStd
{

	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * __construct
	 */
	function __construct() {
		global $langs,$conf;

		dol_include_once('/planformation/class/sessionformation.class.php');

		parent::set_table(MAIN_DB_PREFIX . 'planform_formation');

		parent::add_champs('title', array('type'=>'string'));
		parent::add_champs('duree', array('type'=>'float'));
		parent::add_champs('fk_user_modification,fk_user_creation,entity', array('type'=>'integer','index'=>true));

		parent::_init_vars();
		parent::start();

		$this->setChild('TSessionFormation', 'fk_formation');

		$this->entity = $conf->entity;
	}

	function getAllWithCondition(&$PDOdb, $filter = '1') {
		$sql = "SELECT rowid, title, duree";
		$sql.= " FROM " . $this->get_table();
		$sql.= " WHERE ".$filter;

		$res = $PDOdb->Execute($sql);

		$TReturn = array();

		if($res) {
			while($PDOdb->Get_line()) {
				$TReturn[] = array(
					'rowid' => $PDOdb->Get_field('rowid')
					, 'title' => $PDOdb->Get_field('title')
					, 'duree' => $PDOdb->Get_field('duree')
				);
			}
		}

		return $TReturn;
	}

	function getNextId(&$PDOdb) {

		$sql = "SELECT MAX(rowid) AS maxid FROM ".$this->get_table();

		$PDOdb->Execute($sql);
		$res = $PDOdb->Get_line();

		return $res->maxid + 1;
	}
}
