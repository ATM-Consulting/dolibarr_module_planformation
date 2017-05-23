<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */

if(!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);

	require('../config.php');

}

dol_include_once('/planformation/class/dictionnaire.class.php');
dol_include_once('/planformation/class/planformation.class.php');
dol_include_once('/planformation/class/formation.class.php');
dol_include_once('/planformation/class/participant.class.php');

$ATMdb=new TPDOdb;
$ATMdb->db->debug=true;

$o=new TTypeFinancement;
$o->init_db_by_vars($ATMdb);

$o=new TPlanFormation;
$o->init_db_by_vars($ATMdb);

$sql = 'ALTER TABLE '.$o->table.' ADD UNIQUE INDEX uk_'.str_replace(MAIN_DB_PREFIX, '', $o->table).'_ref(ref)';
$result=$ATMdb->Execute($sql);

/*
$o=new TSection;
$o->init_db_by_vars($ATMdb);
*/

$o=new TFormation;
$o->init_db_by_vars($ATMdb);


$o=new TSectionPlanFormation;
$o->init_db_by_vars($ATMdb);


$o=new TSessionFormation;
$o->init_db_by_vars($ATMdb);

$sql = 'ALTER TABLE '.$o->table.' ADD UNIQUE INDEX uk_'.str_replace(MAIN_DB_PREFIX, '', $o->table).'_ref(ref)';
$result=$ATMdb->Execute($sql);

$o=new TParticipantSession;
$o->init_db_by_vars($ATMdb);

$sql = 'ALTER TABLE '.$o->table.' ADD UNIQUE INDEX uk_'.str_replace(MAIN_DB_PREFIX, '', $o->table).'(fk_session, fk_user)';
$result=$ATMdb->Execute($sql);

/*
$sql = 'ALTER TABLE '.$o->table.' ADD UNIQUE INDEX uk_'.str_replace(MAIN_DB_PREFIX, '', $o->table).'_fk_section_fk_planform(fk_planform,fk_section)';
$result=$ATMdb->Execute($sql);
*/

