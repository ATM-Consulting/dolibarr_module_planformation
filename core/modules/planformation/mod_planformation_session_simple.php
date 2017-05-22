<?php
/*
 * Copyright (C) 2014 Florian HENRY <florian.henry@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file planformation/core/modules/planformation/mod_planformation_simple.php
 * \ingroup planformation
 * \brief File with class to manage the numbering module Simple for planformation references
 */
dol_include_once('/planformation/core/modules/planformation/modules_planformation.php');

/**
 * Class to manage the numbering module Simple for planformation references
 */
class mod_planformation_session_simple extends ModeleNumRefPlanFormation
{

	var $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'
	var $prefix = 'SF';

	var $error = '';

	var $nom = "Simple";

	/**
	 * Return description of numbering module
	 *
	 * @return string Text with description
	 */
	function info()
	{
		global $langs;
		return $langs->trans("PFSimpleSessionNumRefModelDesc", $this->prefix);
	}

	/**
	 * Return an example of numbering module values
	 *
	 * @return string Example
	 */
	function getExample()
	{
		return $this->prefix . "1603-0001";
	}

	/**
	 * Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 * de conflits qui empechera cette numerotation de fonctionner.
	 *
	 * @return boolean false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $conf, $langs;

		$coyymm = '';
		$max = '';

		$posindice = 6;
		$sql = "SELECT MAX(SUBSTRING(ref FROM " . $posindice . ")) as max";
		$sql .= " FROM " . MAIN_DB_PREFIX . "planform_session";
		$sql .= " WHERE ref LIKE '" . $this->prefix . "____-%'";
		// $sql.= " AND entity = ".$conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$coyymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if (! $coyymm || preg_match('/' . $this->prefix . '[0-9][0-9][0-9][0-9]/i', $coyymm)) {
			return true;
		} else {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}
	}

	/**
	 * Return next value
	 *
	 * @param int $fk_user User creating
	 * @param Societe $objsoc Party
	 * @param TSessionFormation $session Session de formation
	 * @return string Valeur
	 */
	function getNextValue($fk_user, $objsoc, $session)
	{
		global $db, $conf;

		// D'abord on recupere la valeur max
		$posindice = 10;
		$sql = "SELECT MAX(SUBSTRING(ref FROM " . $posindice . ")) as max";
		$sql .= " FROM " . MAIN_DB_PREFIX . "planform_session";
		$sql .= " WHERE ref like '" . $this->prefix . "____-%'";

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj)
				$max = intval($obj->max);
			else
				$max = 0;
		} else {
			dol_syslog(get_class($this).'::'.__METHOD__. 'Error : '.$db->lasterror, LOG_ERR);
			return - 1;
		}

		$date = empty($session->date_cre) ? dol_now() : $session->date_cre;

		// $yymm = strftime("%y%m",time());
		$yymm = strftime("%y%m", $date);
		$num = sprintf("%04s", $max + 1);

		dol_syslog(get_class($this).'::'.__METHOD__. ' return ' . $this->prefix . $yymm . "-" . $num, LOG_DEBUG);
		return $this->prefix . $yymm . "-" . $num;
	}
}