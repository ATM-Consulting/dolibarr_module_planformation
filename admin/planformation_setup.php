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
 * \file admin/planformation.php
 * \ingroup planformation
 * \brief This file is an example module setup page
 * Put some comments here
 */
// Dolibarr environment
require_once ('../config.php');

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../class/planformation.class.php';
require_once '../lib/planformation.lib.php';

// Translations
$langs->load("planformation@planformation");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

if ($action == 'updateMask') {
	$maskconstlead = GETPOST('maskconstpf', 'alpha');
	$masklead = GETPOST('maskpf', 'alpha');
	if ($maskconstlead)
		$res = dolibarr_set_const($db, $maskconstlead, $masklead, 'chaine', 0, '', $conf->entity);

	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'updateMaskSection') {
	$maskconstlead = GETPOST('maskconstpfs', 'alpha');
	$masklead = GETPOST('maskpfs', 'alpha');
	if ($maskconstlead)
		$res = dolibarr_set_const($db, $maskconstlead, $masklead, 'chaine', 0, '', $conf->entity);

	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	} else {
		setEventMessages($langs->trans("Error") . ' ' . $db->lasterror, null, 'errors');
	}
}
elseif ($action=='setmod') {
	$code = 'PF_ADDON';
	if (dolibarr_set_const($db, $code, GETPOST('value','alpha'), 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	} else {
		setEventMessages($langs->trans("Error") . ' ' . $db->lasterror, null, 'errors');
	}
}elseif ($action=='setmodsec') {
	$code = 'PF_SECTION_ADDON';
	if (dolibarr_set_const($db, $code, GETPOST('value','alpha'), 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	} else {
		setEventMessages($langs->trans("Error") . ' ' . $db->lasterror, null, 'errors');
	}
}elseif ($action=='setmodsess') {
	$code = 'PF_SESSION_ADDON';
	if (dolibarr_set_const($db, $code, GETPOST('value','alpha'), 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	} else {
		setEventMessages($langs->trans("Error") . ' ' . $db->lasterror, null, 'errors');
	}
}

if (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0) {
		Header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	} else {
		setEventMessages($langs->trans("Error") . ' ' . $db->lasterror, null, 'errors');
	}
}

/*
 * View
 */
$page_name = "planformationSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = planformationAdminPrepareHead();
dol_fiche_head($head, 'settings', $langs->trans("Module104951Name"), 0, "planformation@planformation");

// Setup page goes here
$form = new Form($db);
$var = false;

$dirmodels = array_merge(array (
		'/'
), ( array ) $conf->modules_parts['models']);

echo load_fiche_titre($langs->trans('PFPlanFormation'));
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . "</td>\n";
print '<td>' . $langs->trans("Description") . "</td>\n";
print '<td class="nowrap">' . $langs->trans("Example") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
print '</tr>' . "\n";

clearstatcache();

$form = new Form($db);

foreach ( $dirmodels as $reldir ) {
	$dir = dol_buildpath($reldir . "core/modules/planformation/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;
			while ( ($file = readdir($handle)) !== false ) {
				if ((substr($file, 0, 18) == 'mod_planformation_')
						&& (substr($file, dol_strlen($file) - 3, 3) == 'php')
						&& (substr($file, 0, 26) != 'mod_planformation_section_')
						&& (substr($file, 0, 26) != 'mod_planformation_session_')) {
					$file = substr($file, 0, dol_strlen($file) - 4);
					require_once $dir . $file . '.php';

					/**
					 *
					 * @var ModeleNumRefPlanFormation $module
					 */
					$module = new $file();

					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
						continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
						continue;

					if ($module->isEnabled()) {
						$var = ! $var;
						print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp))
							print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured')
							print $langs->trans($tmp);
						else
							print $tmp;
						print '</td>' . "\n";

						print '<td align="center">';
						if ($conf->global->PF_ADDON == "$file") {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmod&amp;value=' . $file . '">';
							print img_picto($langs->trans("Disabled"), 'switch_off');
							print '</a>';
						}
						print '</td>';

						$module_dest = new TPlanFormation();
						$module_dest->_init_vars();

						// Info
						$htmltooltip = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval = $module->getNextValue($user->id, $mysoc, $module_number);
						if ("$nextval" != $langs->trans("NotAvailable")) // Keep " on nextval
{
							$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
							if ($nextval) {
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td align="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						print '</td>';

						print "</tr>\n";
					}
				}
			}
			closedir($handle);
		}
	}
}
print "</table><br>\n";

echo load_fiche_titre($langs->trans('PFSection'));
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . "</td>\n";
print '<td>' . $langs->trans("Description") . "</td>\n";
print '<td class="nowrap">' . $langs->trans("Example") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
print '</tr>' . "\n";

clearstatcache();

$form = new Form($db);

foreach ( $dirmodels as $reldir ) {
	$dir = dol_buildpath($reldir . "core/modules/planformation/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;
			while ( ($file = readdir($handle)) !== false ) {
				if ((substr($file, 0, 18) == 'mod_planformation_')
						&& (substr($file, dol_strlen($file) - 3, 3) == 'php')
						&& (substr($file, 0, 26) == 'mod_planformation_section_')) {
							$file = substr($file, 0, dol_strlen($file) - 4);
							require_once $dir . $file . '.php';

							/**
							 *
							 * @var ModeleNumRefPlanFormation $module
							 */
							$module = new $file();

							// Show modules according to features level
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
								continue;
								if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
									continue;

									if ($module->isEnabled()) {
										$var = ! $var;
										print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
										print $module->info();
										print '</td>';

										// Show example of numbering module
										print '<td class="nowrap">';
										$tmp = $module->getExample();
										if (preg_match('/^Error/', $tmp))
											print '<div class="error">' . $langs->trans($tmp) . '</div>';
											elseif ($tmp == 'NotConfigured')
											print $langs->trans($tmp);
											else
												print $tmp;
												print '</td>' . "\n";

												print '<td align="center">';
												if ($conf->global->PF_SECTION_ADDON == "$file") {
													print img_picto($langs->trans("Activated"), 'switch_on');
												} else {
													print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmodsec&amp;value=' . $file . '">';
													print img_picto($langs->trans("Disabled"), 'switch_off');
													print '</a>';
												}
												print '</td>';

												$module_dest = new TSection();
												$module_dest->_init_vars();

												// Info
												$htmltooltip = '';
												$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
												$nextval = $module->getNextValue($user->id, $mysoc, $module_number);
												if ("$nextval" != $langs->trans("NotAvailable")) // Keep " on nextval
												{
													$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
													if ($nextval) {
														$htmltooltip .= $nextval . '<br>';
													} else {
														$htmltooltip .= $langs->trans($module->error) . '<br>';
													}
												}

												print '<td align="center">';
												print $form->textwithpicto('', $htmltooltip, 1, 0);
												print '</td>';

												print "</tr>\n";
									}
						}
			}
			closedir($handle);
		}
	}
}
print "</table><br>\n";

// Session de formation

echo load_fiche_titre($langs->trans('PFFormationSession'));
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . "</td>\n";
print '<td>' . $langs->trans("Description") . "</td>\n";
print '<td class="nowrap">' . $langs->trans("Example") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
print '</tr>' . "\n";

clearstatcache();

$form = new Form($db);

foreach ( $dirmodels as $reldir ) {
	$dir = dol_buildpath($reldir . "core/modules/planformation/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;
			while ( ($file = readdir($handle)) !== false ) {
				if ((substr($file, 0, 26) == 'mod_planformation_session_')
						&& (substr($file, dol_strlen($file) - 3, 3) == 'php')) {
							$file = substr($file, 0, dol_strlen($file) - 4);
							require_once $dir . $file . '.php';

							/**
							 *
							 * @var ModeleNumRefPlanFormation $module
							 */
							$module = new $file();

							// Show modules according to features level
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
								continue;
								if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
									continue;

									if ($module->isEnabled()) {
										$var = ! $var;
										print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
										print $module->info();
										print '</td>';

										// Show example of numbering module
										print '<td class="nowrap">';
										$tmp = $module->getExample();
										if (preg_match('/^Error/', $tmp))
											print '<div class="error">' . $langs->trans($tmp) . '</div>';
											elseif ($tmp == 'NotConfigured')
											print $langs->trans($tmp);
											else
												print $tmp;
												print '</td>' . "\n";

												print '<td align="center">';
												if ($conf->global->PF_SESSION_ADDON == "$file") {
													print img_picto($langs->trans("Activated"), 'switch_on');
												} else {
													print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmodsess&amp;value=' . $file . '">';
													print img_picto($langs->trans("Disabled"), 'switch_off');
													print '</a>';
												}
												print '</td>';

												$module_dest = new TSectionPlanFormation();
												$module_dest->_init_vars();

												// Info
												$htmltooltip = '';
												$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
												$nextval = $module->getNextValue($user->id, $mysoc, $module_number);
												if ("$nextval" != $langs->trans("NotAvailable")) // Keep " on nextval
												{
													$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
													if ($nextval) {
														$htmltooltip .= $nextval . '<br>';
													} else {
														$htmltooltip .= $langs->trans($module->error) . '<br>';
													}
												}

												print '<td align="center">';
												print $form->textwithpicto('', $htmltooltip, 1, 0);
												print '</td>';

												print "</tr>\n";
									}
						}
			}
			closedir($handle);
		}
	}
}
print "</table><br>\n";

/*print '<table class="noborder" width="100%">';
 print '<tr class="liste_titre">';
 print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
 print '<td align="center" width="20">&nbsp;</td>';
 print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

 // Example with a yes / no select
 $var = ! $var;
 print '<tr ' . $bc[$var] . '>';
 print '<td>' . $langs->trans("ParamLabel") . '</td>';
 print '<td align="center" width="20">&nbsp;</td>';
 print '<td align="right" width="300">';
 print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
 print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
 print '<input type="hidden" name="action" value="set_CONSTNAME">';
 print $form->selectyesno("CONSTNAME", $conf->global->CONSTNAME, 1);
 print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
 print '</form>'
 print '</td></tr>';

 print '</table>';
 ;*/

llxFooter();

$db->close();