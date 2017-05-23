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
 * Class TCreneauSession
 */

class TCreneauSession extends TObjetStd
{
	
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	
	/**
	 * __construct
	 */
	function __construct() {
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX . 'planform_session_creneau');
		
		//parent::add_champs('ref', array('type'=>'string','index'=>true));
		parent::add_champs('fk_session', array('type'=>'integer','index'=>true));
		//parent::add_champs('fk_user_modification,fk_user_creation,entity', array('type'=>'integer','index'=>true));
		//parent::add_champs('fk_opca,is_interne', array('type'=>'integer'));
		//parent::add_champs('budget,budget_consomme,prise_en_charge_estimee,prise_en_charge_acceptee,prise_en_charge_reelle', array('type'=>'float'));
		parent::add_champs('debut,fin', array('type'=>'date','index'=>true));

		parent::_init_vars();
		parent::start();
		
	}
}
