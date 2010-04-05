<?php
// userconfigvars.inc.php - List of user configuration variables
//                      and functions to manage them
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas, <ivanlucas[at]users.sourceforge.net

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

$CFGTAB['application'] = array('themeprefs', 'displayprefs', 'localeprefs');
$TABI18n['application'] = $strApplication;

$CFGCAT['themeprefs'] = array('theme','iconset',
                               'show_emoticons'
                               );


$CFGCAT['displayprefs'] = array('incident_refresh',
                               'incident_log_order',
                               'updates_per_page',
                               'show_table_legends',
                               'incident_popup_onewindow',
                               'show_confirmation_caution',
                               'show_confirmation_delete',
                               'show_inactive_data'
                               );

$CFGCAT['localeprefs'] = array('language','utc_offset');


// i18n keys for categories
$CATI18N['themeprefs'] = $strTheme;
$CATI18N['displayprefs'] = $strDisplay;
$CATI18N['localeprefs'] = $strLocale;

// Descriptions of all the config variables
// each config var may have these elements:
//      title   - A title/short description of the configuration variable
//      help    - A line of instructions/help to assist the user configuring
//      helplink - A help context label for help/en-GB/help.txt type help
//      type - A datatype, see cfgVarInput() for list
//      unit - A unit string to print after the input
//      options - A pipe seperated list of optios for a 'select' type


$CFGVAR['iconset']['title'] = 'Icon set'; // FIXME i18n
$CFGVAR['iconset']['type'] = 'select';
$CFGVAR['iconset']['options'] = 'sit|oxygen|crystalclear';
// TODO our included 'kdeclassic' icon theme doesn't appear to be in the 'sit' filename format

$CFGVAR['language']['title'] = $strLanguage;
$CFGVAR['language']['type'] = 'userlanguageselect';

$CFGVAR['incident_log_order']['title'] = $strIncidentLogOrder;
$CFGVAR['incident_log_order']['help'] = "Choose 'asc' for Ascending (oldest first), or 'desc' for Descending (newest first)";
$CFGVAR['incident_log_order']['type'] = 'select';
$CFGVAR['incident_log_order']['options'] = 'asc|desc';

$CFGVAR['incident_refresh']['title'] = $strIncidentRefresh;
$CFGVAR['incident_refresh']['type'] = 'number';
$CFGVAR['incident_refresh']['unit'] = $strSeconds;

$CFGVAR['incident_popup_onewindow']['title'] = 'Use a single window for incident details'; // FIXME i18n
$CFGVAR['incident_popup_onewindow']['type'] = 'checkbox';

$CFGVAR['theme']['title'] = $strInterfaceStyle;
$CFGVAR['theme']['type'] = 'interfacestyleselect';

// TODO
$CFGVAR['show_confirmation_caution']['title'] = 'Show general confirmation ("Are you sure?") messages';
$CFGVAR['show_confirmation_caution']['type'] = 'checkbox';

// TODO
$CFGVAR['show_confirmation_delete']['title'] = 'Show confirmation messages ("Are you sure?") on delete';
$CFGVAR['show_confirmation_delete']['type'] = 'checkbox';

$CFGVAR['show_emoticons']['title'] = $strShowEmoticons;
$CFGVAR['show_emoticons']['type'] = 'checkbox';

$CFGVAR['show_table_legends']['title'] = $strShowTableLegends;
$CFGVAR['show_table_legends']['type'] = 'checkbox';

$CFGVAR['show_inactive_data']['title'] = 'Show Inactive Data';
$CFGVAR['show_inactive_data']['type'] = 'checkbox';

// TODO

$CFGVAR['updates_per_page']['title'] = $strIncidentUpdatesPerPage;
$CFGVAR['updates_per_page']['type'] = 'number';

$CFGVAR['utc_offset']['title'] = $strUTCOffset;
$CFGVAR['utc_offset']['type'] = 'timezoneselect';


if (function_exists('plugin_do'))
{
    // Plugin_do won't always be available in this file, because we use this
    // file for setup as well, no plugins before sit is installed.
    plugin_do('usercfgvar');
}
?>