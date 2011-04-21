<?php
// setup.inc.php - functions used during seup of SiT
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
 * Array filter callback to check to see if a config file is a recognised file
 * @author Ivan Lucas
 * @param string $var. Filename to check
 * @retval bool TRUE : recognised
 * @retval bool FALSE : unrecognised
 */
function filterconfigfiles($var)
{
    $poss_config_files = array('config.inc.php', 'sit.conf');
    $recognised = FALSE;
    foreach ($poss_config_files AS $poss)
    {
        if (mb_substr($var, mb_strlen($var) - mb_strlen($poss)) == $poss)
        {
            $recognised = TRUE;
        }
    }
    return $recognised;
}


/**
 * Setup configuration form
 * @author Ivan Lucas
 * @retval string HTML
 */
function setup_configure()
{
    global $SETUP, $CFGVAR, $CONFIG, $configfiles, $config_filename, $cfg_file_exists;
    global $cfg_file_writable, $numconfigfiles;
    $html = '';

    if ($cfg_file_exists AND $_REQUEST['configfile'] != 'new')
    {
        if ($_SESSION['new'])
        {
            if ($numconfigfiles < 2)
            {
                $html .= "<h4>Found an existing config file <var>{$config_filename}</var></h4>";
            }
            else
            {
                $html .= "<p class='error'>Found more than one existing config file</p>";
                if ($cfg_file_writable)
                {
                    $html .= "<ul>";
                    foreach ($configfiles AS $conf_filename)
                    {
                        $html .= "<li><var>{$conf_filename}</var></li>";
                    }
                    $html .= "</ul>";
                }
            }
        }
        //$html .= "<p>Since you already have a config file we assume you are upgrading or reconfiguring, if this is not the case please delete the existing config file.</p>";
        if ($cfg_file_writable)
        {
            $html .= "<p class='error'>Important: The file permissions on the configuration file ";
            $html .= "allow it to be modified, we recommend you make this file read-only once SiT! is configured.";
            $html .= "</p>";
        }
        else
        {
            $html .= "<p><a href='setup.php?action=reconfigure&amp;configfile=new' >Create a new config file</a>.</p>";
        }
    }
    else $html .= "<h2>New Configuration</h2><p>Please complete this form to create a new configuration file for SiT!</p>";

    if ($cfg_file_writable OR $_SESSION['new'] === 1 OR $cfg_file_exists == FALSE OR $_REQUEST['configfile'] == 'new')
    {
        $html .= "\n<form action='setup.php' method='post'>\n";

        if ($_REQUEST['config'] == 'advanced')
        {
            $html .= "<input type='hidden' name='config' value='advanced' />\n";
            foreach ($CFGVAR AS $setupvar => $setupval)
            {
                $SETUP[] = $setupvar;
            }
        }

        $c=1;
        foreach ($SETUP AS $setupvar)
        {
            $html .= "<div class='configvar{$c}'>";
            if ($CFGVAR[$setupvar]['title']!='') $title = $CFGVAR[$setupvar]['title'];
            else $title = $setupvar;
            $html .= "<h4>{$title}</h4>";
            if ($CFGVAR[$setupvar]['help']!='') $html .= "<p class='helptip'>{$CFGVAR[$setupvar]['help']}</p>\n";

            $html .= "<var>\$CONFIG['$setupvar']</var> = ";

            $value = '';
            if (!$cfg_file_exists OR ($cfg_file_exists AND $cfg_file_writable))
            {
                $value = $CONFIG[$setupvar];
                if (is_bool($value))
                {
                    if ($value == TRUE) $value = 'TRUE';
                    else $value = 'FALSE';
                }
                elseif (is_array($value))
                {
                    if (is_assoc($value))
                    {
                        $value = "array(".implode_assoc('=>',',',$value).")";
                    }
                    else
                    {
                        $value="array(".implode(',',$value).")";
                    }
                }
                if ($setupvar == 'db_password' AND $_REQUEST['action'] != 'reconfigure') $value = '';
            }

            if (!$cfg_file_exists OR $_REQUEST['configfile'] == 'new')
            {
                // Dynamic defaults
                    // application_fspath was removed, leaving this code just-in-case
                    // DEPRECATED - remove for >= 3.50
                if ($setupvar == 'application_fspath')
                {
                    $value = str_replace('htdocs' . DIRECTORY_SEPARATOR, '', dirname( __FILE__ ) . DIRECTORY_SEPARATOR);
                }

                if ($setupvar == 'application_webpath')
                {
                    $value = dirname( strip_tags( $_SERVER['PHP_SELF'] ) );
                    if ($value == '/' OR $value == '\\') $value = '/';
                    else $value = $value . '/';
                }
            }

            switch ($CFGVAR[$setupvar]['type'])
            {
                case 'select':
                    $html .= "<select name='$setupvar'>";
                    if (empty($CFGVAR[$setupvar]['options'])) $CFGVAR[$setupvar]['options'] = "TRUE|FALSE";
                    $options = explode('|', $CFGVAR[$setupvar]['options']);
                    foreach ($options AS $option)
                    {
                        $html .= "<option value=\"{$option}\"";
                        if ($option == $value) $html .= " selected='selected'";
                        $html .= ">{$option}</option>\n";
                    }
                    $html .= "</select>";
                    break;
                case 'percent':
                    $html .= "<select name='$setupvar'>";
                    for($i = 0; $i <= 100; $i++)
                    {
                        $html .= "<option value=\"{$i}\"";
                        if ($i == $value) $html .= " selected='selected'";
                        $html .= ">{$i}</option>\n";
                    }
                    $html .= "</select>";
                    break;
                case 'text':
                default:
                    if (mb_strlen($CONFIG[$setupvar]) < 65)
                    {
                        $html .= "<input type='text' name='$setupvar' size='60' value=\"{$value}\" />";
                    }
                    else
                    {
                        $html .= "<textarea name='$setupvar' cols='60' rows='10'>{$value}</textarea>";
                    }
            }
            if ($setupvar == 'db_password' AND $_REQUEST['action'] != 'reconfigure' AND $value != '')
            {
                $html .= "<p class='info'>The current password setting is not shown</p>";
            }
            $html .= "</div>";
            $html .= "<br />\n";
            if ($c == 1) $c = 2;
            else $c = 1;
        }
        $html .= "<input type='hidden' name='action' value='save_config' />";
        $html .= "<br /><input type='submit' name='submit' value='Save Configuration' />";
        $html .= "</form>\n";
    }
    return $html;
}


/**
 * Execute a list of SQL queries
 * @author Ivan Lucas
 * @note Attempts to be clever and print helpful messages in the case
 * of an error
 */
function setup_exec_sql($sqlquerylist)
{
    global $CONFIG, $dbSystem, $installed_schema, $application_version;
    if (!empty($sqlquerylist))
    {
        if (!is_array($sqlquerylist)) $sqlquerylist = array($sqlquerylist);


        // Loop around the queries
        foreach ($sqlquerylist AS $schemaversion => $queryelement)
        {
            if ($schemaversion != '0') $schemaversion = mb_substr($schemaversion, 1);

            if ($schemaversion == 0 OR $installed_schema < $schemaversion)
            {
                $sqlqueries = explode( ';', $queryelement);
                // We don't need the last entry it's blank, as we end with a ;
                array_pop($sqlqueries);
                $errors = 0;
                foreach ($sqlqueries AS $sql)
                {
                    if (!empty($sql))
                    {
                        mysql_query($sql);
                        if (mysql_error())
                        {
                            $errno = mysql_errno();
                            $errstr = '';
                            // See http://dev.mysql.com/doc/refman/5.0/en/error-messages-server.html
                            // For list of mysql error numbers
                            switch ($errno)
                            {
                                case 1022:
                                case 1050:
                                case 1060:
                                case 1061:
                                case 1062:
                                    $severity = 'info';
                                    $errstr = "This could be because this part of the database schema is already up to date.";
                                    break;
                                case 1058:
                                    $severity = 'error';
                                    $errstr = "This looks suspiciously like a bug, if you think this is the case please report it.";
                                    break;

                                case 1051:
                                case 1091:
                                    if (preg_match("/DROP/", $sql) >= 1)
                                    {
                                        $severity = 'info';
                                        $errstr = "We expected to find something in order to remove it but it doesn't exist. This could be because this part of the database schema is already up to date..";
                                    }
                                    break;
                                case 1044:
                                case 1045:
                                case 1142:
                                case 1143:
                                case 1227:
                                    $severity = 'error';
                                    $errstr = "This could be because the MySQL user '{$CONFIG['db_username']}' does not have appropriate permission to modify the database schema.<br />";
                                    $errstr .= "<strong>Check your MySQL permissions allow the schema to be modified</strong>.";
                                default:
                                    $severity = 'error';
                                    $errstr = "You may have found a bug, if you think this is the case please report it.";
                            }
                            $html .= "<p class='{$severity}'>";
                            if ($severity == 'info')
                            {
                                $html .= "<strong>Information:</strong>";
                            }
                            else
                            {
                                $html .= "<strong>A MySQL error occurred:</strong>";
                                $errors ++;
                            }
                            $html .= " [".mysql_errno()."] ".mysql_error()."<br />";
                            if (!empty($errstr)) $html .= $errstr."<br />";
                            $html .= "Raw SQL: <code class='small'>".htmlspecialchars($sql)."</code>";
                        }
                    }
                }
            }
        }
    }
    echo $html;
    return $errors;
}


/**
 * Create a blank SiT database
 * @author Ivan Lucas
 * @retval bool TRUE database created OK
 * @retval bool FALSE database not created, error.
 */
function setup_createdb()
{
    global $CONFIG;

    $res = FALSE;
    $sql = "CREATE DATABASE `{$CONFIG['db_database']}` DEFAULT CHARSET utf8";
    $db = @mysql_connect($CONFIG['db_hostname'], $CONFIG['db_username'], $CONFIG['db_password']);
    if (!@mysql_error())
    {
        // See Mantis 506 for sql_mode discussion
        @mysql_query("SET SESSION sql_mode = '';");

        // Connected to database
        echo "<h2>Creating empty database...</h2>";
        $result = mysql_query($sql);
        if ($result)
        {
            $res = TRUE;
            echo "<p><strong>OK</strong> Database '{$CONFIG['db_database']}' created.</p>";
            echo setup_button('', 'Next');
        }
        else $res = FALSE;
    }
    else
    {
        $res = FALSE;
    }

    if ($res == FALSE)
    {
        echo "<p class='error'>";
        if (mysql_error())
        {
            echo mysql_error()."<br />";
        }
        echo "The database could not be created automatically, ";
        echo "you can create it manually by executing the SQL statement <br /><code>{$sql};</code></p>";
        echo setup_button('', 'Next');
    }
    return $res;
}


/**
 * Check to see whether an admin user exists
 * @author Ivan Lucas
 * @retval bool TRUE : an admin account exists
 * @retval bool FALSE : an admin account doesn't exist
 */
function setup_check_adminuser()
{
    global $dbUsers;
    $sql = "SELECT id FROM `{$dbUsers}` WHERE id=1 OR username='admin' OR roleid='1'";
    $result = @mysql_query($sql);
    if (mysql_num_rows($result) >= 1) return TRUE;
    else FALSE;
}


/**
 * An HTML action button, i.e. a form with a single button
 * @author Ivan Lucas
 * @param string $action.    Value for the hidden 'action' field
 * @param string $label.     Label for the submit button
 * @param string $extrahtml. Extra HTML to display on the form
 * @return A form with a button
 * @retval string HTML form
 */
function setup_button($action, $label, $extrahtml='')
{
    $html = "\n<form action='{$_SERVER['PHP_SELF']}' method='post'>";
    if (!empty($action))
    {
        $html .= "<input type='hidden' name='action' value=\"{$action}\" />";
    }
    $html .= "<input type='submit' value=\"{$label}\" />";
    if (!empty($extrahtml)) $html .= $extrahtml;
    $html .= "</form>\n";

    return $html;
}


/**
 * Runs the install script for all installed dashboards
 * @author Paul Heaney
 * @return int number of errors encountered 
 */
function install_dashboard_components()
{
    global  $dbDashboard;
    $sql = "SELECT * FROM `{$dbDashboard}` WHERE enabled = 'true'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    //echo "<h2>Dashboard</h2>";
    
    $errors = array();
    
    while ($dashboardnames = mysql_fetch_object($result))
    {
        $version = 1;
        include (APPLICATION_PLUGINPATH . "dashboard_{$dashboardnames->name}.php");
        $func = "dashboard_{$dashboardnames->name}_install";

        if (function_exists($func))
        {
            if (!$func()) $errors[] = $dashboardnames->name;
        }
    }
    
    return $errors;
}