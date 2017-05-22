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
 *	\file		lib/planformation.lib.php
 *	\ingroup	planformation
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function planformationAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("planformation@planformation");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/planformation/admin/planformation_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/planformation/admin/planformation_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@planformation:/planformation/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@planformation:/planformation/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'planformation');

    return $head;
}


function session_prepare_head(TSessionFormation &$session)
{
	global $langs, $conf;
	
	$langs->load("planformation@planformation");
	
	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath("/planformation/session.php?id=".$session->id, 1);
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'session';
	$h++;
	
	$head[$h][0] = dol_buildpath("/planformation/session.php?id=".$session->id.'&action=info', 1);
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;
	
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'session');
	
	return $head;
}

function formation_prepare_head(TFormation &$formation)
{
	global $langs, $conf;

	$langs->load("planformation@planformation");
	
	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath("/planformation/formation.php?id=".$formation->id, 1);
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'formation';
	$h++;
	
	$head[$h][0] = dol_buildpath("/planformation/formation.php?id=".$formation->id.'&action=info', 1);
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;
	
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'formation');
	
	return $head;
}


function planformation_prepare_head(TPlanFormation &$pf)
{
	global $langs, $conf;

	$langs->load("planformation@planformation");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/planformation/planformation.php?id=".$pf->getId(), 1);
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'planformation';
	$h++;

	$head[$h][0] = dol_buildpath("/planformation/planformation.php?id=".$pf->getId().'&action=info', 1);
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'planformation');

	return $head;

	return $head;
}

function planformation_section_prepare_head(TSection &$pfs)
{
	global $langs, $conf;

	$langs->load("planformation@planformation");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/planformation/section.php?id=".$pfs->getId(), 1);
        if(!empty($_REQUEST['plan_id']))
            $head[$h][0] = dol_buildpath("/planformation/section.php?id=".$pfs->getId()."&plan_id=".$_REQUEST['plan_id'], 1);
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'section';
	$h++;

	$head[$h][0] = dol_buildpath("/planformation/section.php?id=".$pfs->getId().'&action=info', 1);
        if(!empty($_REQUEST['plan_id']))
            $head[$h][0] = dol_buildpath("/planformation/section.php?id=".$pfs->getId().'&plan_id='.$_REQUEST['plan_id'].'&action=info', 1);
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'planformation_section');
        
	return $head;
}
