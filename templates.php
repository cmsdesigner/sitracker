<?php
// templates.php - Manage email and notice templates
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_TEMPLATE_EDIT; // Edit Template
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$id = cleanvar($_REQUEST['id']); // can be alpha (a name) as well as numeric (id)
$action = clean_fixed_list($_REQUEST['action'], array('showform', 'list', 'edit', 'update', 'delete', 'new'));
$templategenre = clean_fixed_list($_REQUEST['template'], array('', 'email', 'notice'));

$templatetypes = array('incident', 'user', 'system'); // 'contact','site','incident','kb'

if (empty($action) OR $action == 'showform' OR $action == 'list')
{
    // Show select email type form
    $title = $strTemplates;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('templates', 32)." ";
    echo "{$strTemplates}</h2>";
    plugin_do('templates');
    echo "<p align='center'><a href='template_new.php'>{$strNewTemplate}</a></p>";

    $sql = "SELECT * FROM `{$dbEmailTemplates}` ORDER BY id";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    while ($email = mysqli_fetch_object($result))
    {
        $templates[$email->id] = array('id' => $email->id, 'template' => 'email', 'name'=> $email->name,'type' => $email->type, 'desc' => $email->description);
    }
    $sql = "SELECT * FROM `{$dbNoticeTemplates}` ORDER BY id";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    while ($notice = mysqli_fetch_object($result))
    {
        $templates[$notice->name] = array('id' => $notice->id, 'template' => 'notice', 'name'=> $notice->name, 'type' => $notice->type, 'desc' => $notice->description);
    }
    ksort($templates);
    $shade = 'shade1';
    echo "<table class='maintable'>";
    echo "<tr><th>{$strTemplate}</th><th>{$strType}</th><th>{$strUsed}</th><th>{$strTemplate}</th><th>{$strActions}</th></tr>";
    foreach ($templates AS $template)
    {
        $system = FALSE;
        $tsql = "SELECT COUNT(id) FROM `{$dbTriggers}` WHERE template='{$template['name']}'";
        $tresult = mysqli_query($db, $tsql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        list($numtriggers) = mysqli_fetch_row($tresult);

        $editurl = "{$_SERVER['PHP_SELF']}?id={$template['id']}&amp;action=edit&amp;template={$template['template']}";
        if ($numtriggers < 1 AND ($template['template'] == 'email' AND $template['type'] != 'incident')) $shade = 'expired';
        echo "<tr class='{$shade}'>";
        echo "<td>";
        if ($template['template'] == 'notice')
        {
            echo icon('info', 16).' '.$strNotice;
        }
        elseif ($template['template'] == 'email')
        {
            echo icon('email', 16).' '.$strEmail;
        }
        else
        {
            echo $strOther;
        }
        echo "</td>";
        echo "<td>{$template['type']} {$template['template']}</td>";
        echo "<td>";
        if ($template['template'] == 'email' AND $template['type'] == 'incident')
        {
            echo icon('support',16, $strIncident);
        }
        if ($numtriggers > 0) echo icon('trigger', 16, $strTrigger);
        if ($numtriggers > 1) echo " (&#215;{$numtriggers})";
        echo "</td>";
        echo "<td><a href='{$editurl}'>{$template['name']}</a>";
        if (!empty($template['desc']))
        {
            if (substr_compare($template['desc'], 'str', 0, 3) === 0)
            {
                echo "<br />{$GLOBALS[$template['desc']]}";
                $system = TRUE;
            }
            else
            {
                echo "<br />{$template['desc']}";
            }
        }
        echo "</td>";
        if (!$system)
        {
            echo "<td><a href='{$editurl}'>{$strEdit}</a></td>";
        }
        else
        {
            echo "<td></td>";
        }
        echo "</tr>\n";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }
    echo "</table>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "edit" OR $action == "new")
{
    // Retrieve the template from the database, whether it's email or notice
    switch ($templategenre)
    {
        case 'email':
            if (!is_numeric($id)) $sql = "SELECT * FROM `{$dbEmailTemplates}` WHERE name='{$id}' LIMIT 1";
            else $sql = "SELECT * FROM `{$dbEmailTemplates}` WHERE id='{$id}'";
            $title = "{$strEdit}: {$strEmailTemplate}";
            $templateaction = 'ACTION_EMAIL';
            break;
        case 'notice':
        default:
            if (!is_numeric($id)) $sql = "SELECT * FROM `{$dbNoticeTemplates}` WHERE name='{$id}' LIMIT 1";
            else $sql = "SELECT * FROM `{$dbNoticeTemplates}` WHERE id='{$id}' LIMIT 1";
            $title = "{$strEdit}: {$strNoticeTemplate}";
            $templateaction = 'ACTION_NOTICE';
    }
    
    if ($action != "new")
    {
        // This is a edit template so exists in the DB
        $result = mysqli_query($db, $sql);
        $template = mysqli_fetch_object($result);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    }

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    if (mysqli_num_rows($result) > 0 OR $action == "new")
    {
        echo "<h2>{$title}</h2>";
        plugin_do('templates');
        echo "<div style='width: 48%; float: left;'>";
        echo "<form name='edittemplate' action='{$_SERVER['PHP_SELF']}?action=update' method='post' onsubmit=\"return confirm_action('{$strAreYouSureMakeTheseChanges}')\">";
        echo "<table class='vertical' width='100%'>";

        $tsql = "SELECT * FROM `{$dbTriggers}` WHERE action = '{$templateaction}' AND template = '{$id}' LIMIT 1";
        $tresult = mysqli_query($db, $tsql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        if (mysqli_num_rows($tresult) >= 1)
        {
            $trigaction = mysqli_fetch_object($tresult);
            echo "<tr><th>{$strTrigger}</th><td>".trigger_description($triggerarray[$trigaction->triggerid])."<br /><br />";
            echo triggeraction_description($trigaction)."</td></tr>";
        }
        else
        {
            echo "<tr><th>{$strTrigger}</th><td>{$strNone}</td></tr>\n";
        }

        // Set template type to the trigger type if no type is already specified
        if (empty($template->type)) $template->type = $triggerarray[$trigaction->triggerid]['type'];


        echo "<tr><th>{$strID}:</th><td>";
        echo "<input maxlength='50' name='name' size='5' value='{$template->id} 'readonly='readonly' disabled='disabled' /> <span class='required'>{$strRequired}</span></td></tr>\n";
        echo "<tr><th>{$strTemplate}:</th><td>";
        if ($templategenre == 'notice')
        {
            echo icon('info', 32).' '.$strNotice;
        }
        elseif ($templategenre == 'email')
        {
            echo icon('email', 32).' '.$strEmail;
        }
        else
        {
            echo $strOther;
        }

        // Set up required params, each template type needs an entry here TODO add the rest
        if ($template->type == 'user') 
        {
            $required = array('incidentid', 'userid');
        }
        elseif ($template->type == 'incident') 
        {
            $required = array('incidentid', 'triggeruserid');
        }
        else 
        {
            $required = $triggerarray[$trigaction->triggerid]['required'];
        }

//         echo " ({$template->type})";

        if (!empty($required) AND $CONFIG['debug'])
        {
            debug_log("Variables required by email template {$template->id}: ".print_r($required, TRUE));
        }
        echo "</td><tr>";

        
        $templatename = $template->name;
        if ($action == "new")
        {
            $templatename = cleanvar($_REQUEST['name']);
        }
        
        echo "<tr><th>{$strName}:</th><td><input class='required' maxlength='100' name='name' size='40' value=\"{$templatename}\" /> <span class='required'>{$strRequired}</span></td></tr>\n";
        echo "<tr><th>{$strType}:</th><td>". array_drop_down($templatetypes, 'type', $template->type) . " <span class='required'>{$strRequired}</span></td></tr>\n";
        echo "<tr><th>{$strDescription}:</th>";
        echo "<td><textarea class='required' name='description' cols='50' rows='5' onfocus=\"clearFocusElement(this);\"";
        if (mb_strlen($template->description) > 3 AND substr_compare($template->description, 'str', 0, 3) === 0)
        {
             echo " readonly='readonly' ";
             $template->description = ${$template->description};
        }
        echo ">{$template->description}</textarea> <span class='required'>{$strRequired}</span></td></tr>\n";
        switch ($templategenre)
        {
            case 'email':
                echo "<tr><th colspan='2'>{$strEmail}</th></tr>";
                echo "<tr><td colspan='2'>{$strTemplatesShouldNotBeginWith}</td></tr>";
                echo "<tr><th>{$strTo}</th>";
                echo "<td><input class='required' id='tofield' maxlength='100' name='tofield' size='40' value=\"{$template->tofield}\" onfocus=\"recordFocusElement(this);\" /> <span class='required'>{$strRequired}</span></td></tr>\n";
                echo "<tr><th>{$strFrom}</th>";
                echo "<td><input class='required' id='fromfield' maxlength='100' name='fromfield' size='40' value=\"{$template->fromfield}\" onfocus=\"recordFocusElement(this);\" /> <span class='required'>{$strRequired}</span></td></tr>\n";
                echo "<tr><th>{$strReplyTo}</th>";
                echo "<td><input class='required' id='replytofield' maxlength='100' name='replytofield' size='40' value=\"{$template->replytofield}\" onfocus=\"recordFocusElement(this);\" /> <span class='required'>{$strRequired}</span></td></tr>\n";
                echo "<tr><th>{$strCC}</th>";
                echo "<td><input id='ccfield' maxlength='100' name='ccfield' size='40' value=\"{$template->ccfield}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strBCC}</th>";
                echo "<td><input id='bccfield' maxlength='100' name='bccfield' size='40' value=\"{$template->bccfield}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strSubject}</th>";
                echo "<td><input id='subject' maxlength='255' name='subjectfield' size='60' value=\"{$template->subjectfield}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                break;
            case 'notice':
                echo "<tr><th>{$strLinkText}</th>";
                echo "<td><input id='linktext' maxlength='50' name='linktext' size='50' ";
                if (mb_strlen($template->linktext) > 3 AND substr_compare($template->linktext, 'str', 0, 3) === 0)
                {
                    echo " readonly='readonly' ";
                    $template->linktext = $SYSLANG[$template->linktext];
                }
                echo "value=\"{$template->linktext}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strLink}</th>";
                echo "<td><input id='link' maxlength='100' name='link' size='50' value=\"{$template->link}\"  onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strDurability}</th>";
                echo "<td><select id='durability' onfocus=\"recordFocusElement(this);\">";
                echo "<option";
                if ($template->durability == 'sticky')
                {
                    echo " checked='checked' ";
                }
                echo ">sticky</option>";
                echo "<option";
                if ($template->durability == 'session')
                {
                    echo " checked='checked' ";
                }
                echo ">session</option>";
                echo "</option></select>";
        }

        //if ($trigaction AND $template->type != $triggerarray[$trigaction->triggerid]['type']) echo "<p class='warning'>Trigger type mismatch</p>";
        echo "</td></tr>\n";


        if ($templategenre == 'email') $body = $template->body;
        else $body = $template->text;
        echo "<tr><th>{$strText}</th>";
        echo "<td>";
        if ($templategenre == 'notice') echo bbcode_toolbar('bodytext');

        echo "<textarea id='bodytext' name='bodytext' rows='20' cols='50' onfocus=\"recordFocusElement(this);\"";
        if (mb_strlen($body) > 3 AND substr_compare($body, 'str', 0, 3) === 0)
        {
            echo " readonly='readonly' ";
            $body = $SYSLANG[$body];
        }
        echo ">{$body}</textarea></td>";

        if ($template->type == 'incident')
        {
            echo "<tr><th></th><td><label><input type='checkbox' name='storeinlog' value='Yes' ";
            if ($template->storeinlog == 'Yes')
            {
                echo "checked='checked'";
            }
            echo " /> {$strStoreInLog}</label>";
            echo " &nbsp; (<input type='checkbox' name='cust_vis' value='yes' ";
            if ($template->customervisibility == 'show')
            {
                echo "checked='checked'";
            }
            echo " /> {$strVisibleToCustomer})";
            echo "</td></tr>\n";
        }
        plugin_do('templates_form');
        echo "</table>\n";

        echo "<p class='formbuttoms'>";
        echo "<input name='savenew' type='hidden' value='";
        if ($action == "new") echo "yes";
        else echo "no";
        echo "' />";
        echo "<input name='type' type='hidden' value='{$template->type}' />";
        echo "<input name='template' type='hidden' value='{$templategenre}' />";
        echo "<input name='focuselement' id='focuselement' type='hidden' value='' />";
        echo "<input name='id' type='hidden' value='{$id}' />";
        echo "<input name='reset' type='reset' value='{$strReset}' /> ";
        echo "<input name='submit' type='submit' value=\"{$strSave}\" />";
        echo "</p>\n";

        // Don't allow deletion when template is being used
        $sql = "SELECT * FROM `{$dbTriggers}` WHERE template = '{$template->name}'";
        $resultUsed = mysqli_query($db, $sql);

        // TODO We should check whether a template is in use perhaps before allowing deletion? Mantis 1885
        if ($template->type == 'user' AND mysqli_num_rows($resultUsed) == 0)
        {
            echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?action=delete&amp;id={$id}'>{$strDelete}</a></p>";
        }
        echo "</form>";
        echo "</div>";

        // Show a list of available template variables.  Only variables that have 'requires' matching the 'required'
        // that the trigger provides is shown
        echo "<div id='templatevariables' style='display:none;'>";
        echo "<h4>{$strTemplateVariables}</h4>";
        echo "<p align='center'>{$strFollowingSpecialIdentifiers}</p>";
        if (!is_array($required)) echo "<p class='info'>{$strSomeOfTheseIdentifiers}</p>";

        echo "<dl>";

        foreach ($ttvararray AS $identifier => $ttvar)
        {
            $showtvar = FALSE;

            // if we're a multiply-defined variable, get the actual data
            if (!isset($ttvar['name']) AND !isset($ttvar['description']))
            {
                $ttvar = $ttvar[0];
            }

            if (empty($ttvar['requires']) AND $ttvar['show'] !== FALSE)
            {
                $showtvar = TRUE;
            }
            elseif ($ttvar['show'] === FALSE)
            {
                $showtvar = FALSE;
            }
            else
            {
                if (!is_array($ttvar['requires'])) $ttvar['requires'] = array($ttvar['requires']);
                foreach ($ttvar['requires'] as $needle)
                {
                    if (!is_array($required) OR in_array($needle, $required)) $showtvar = TRUE;
                }
            }

            if ($showtvar)
            {
                echo "<dt><code><a href=\"javascript:insertTemplateVar('{$identifier}');\">{$identifier}</a></code></dt>";
                if (!empty($ttvar['description'])) echo "<dd>{$ttvar['description']}";
                {
                    if (!empty($ttvar[0]['description'])) echo "<dd>{$ttvar[0]['description']}";
                }
                echo "<br />";
            }
        }

        echo "</dl>";
        plugin_do('templates_variables_content');
        echo "</table>\n";
        echo "</div>";

        echo "<p style='clear:both; margin-top: 2em;' class='return'><a href='{$_SERVER['PHP_SELF']}'>{$strBackToList}</a></p>";

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    else
    {
        echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strEmailTemplate}'"), E_USER_ERROR);
    }
}
elseif ($action == "delete")
{
    if (empty($id) OR is_numeric($id) == FALSE)
    {
        // id must be filled and be a number
        header("Location: {$_SERVER['PHP_SELF']}?action=showform");
        exit;
    }
    
    // Don't allow deletion when template is being used
    $sql = "SELECT * FROM `{$dbTriggers}` AS t, `{$dbEmailTemplates}` AS et WHERE t.template = et.name AND et.id = {$id}";
    $resultUsed = mysqli_query($db, $sql);
    
    if (mysqli_num_rows($resultUsed) > 0)
    {
        // Only try and delete if not used
        // We only allow user templates to be deleted
        $sql = "DELETE FROM `{$dbEmailTemplates}` WHERE id='{$id}' AND type='user' LIMIT 1";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);
    }
    header("Location: {$_SERVER['PHP_SELF']}?action=showform");
    exit;
}
elseif ($action == "update")
{
    // External variables
    $template = cleanvar($_POST['template']);
    $name = cleanvar($_POST['name']);
    $description = cleanvar($_POST['description']);
    $templatetype = clean_fixed_list($_POST['type'], $templatetypes);
    
    $tofield = cleanvar($_POST['tofield']);
    $fromfield = cleanvar($_POST['fromfield']);
    $replytofield = cleanvar($_POST['replytofield']);
    $ccfield = cleanvar($_POST['ccfield']);
    $bccfield = cleanvar($_POST['bccfield']);
    $subjectfield = cleanvar($_POST['subjectfield']);
    $bodytext = cleanvar($_POST['bodytext']);

    $link = cleanvar($_POST['link']);
    $linktext = cleanvar($_POST['linktext']);
    $durability = cleanvar($_POST['durability']);

    $cust_vis = cleanvar($_POST['cust_vis']);
    $storeinlog = cleanvar($_POST['storeinlog']);
    $id = cleanvar($_POST['id']);
    $type = cleanvar($_POST['type']);
    
    $savenew = clean_fixed_list($_REQUEST['savenew'], array('yes', 'no'));

    // echo "<pre>".print_r($_POST,true)."</pre>";

    // User templates may not have _ (underscore) in their names, we replace with spaces
    // in contrast system templates must have _ (underscore) instead of spaces, so we do a replace
    // the other way around for those
    // We do this to help prevent user templates having names that clash with system templates
    if ($type == 'user') $name = str_replace('_', ' ', $name);
    else $name = str_replace(' ', '_', strtoupper(trim($name)));

    if ($cust_vis == 'yes') $cust_vis = 'show';
    else $cust_vis = 'hide';

    if ($storeinlog == 'Yes') $storeinlog = 'Yes';
    else $storeinlog = 'No';

    plugin_do('templates_submitted');

    switch ($template)
    {
        case 'email':
            if ($savenew == "yes")
            {
                // First check the template does not already exist
                $sql = "SELECT id FROM `{$dbEmailTemplates}` WHERE name = '{$name}' LIMIT 1";
                $result = mysqli_query($db, $sql);
                if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
                if (mysqli_num_rows($result) < 1)
                {
                    $sql = "INSERT INTO `{$dbEmailTemplates}` (name, type) VALUES('{$name}', '{$templatetype}')";
                    mysqli_query($db, $sql);
                    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);
                    $id = mysqli_insert_id($id);
                }
                else
                {
                    html_redirect($_SERVER['PHP_SELF'], FALSE, $strADuplicateAlreadyExists);
                    exit;
                }
            }
            $sql  = "UPDATE `{$dbEmailTemplates}` SET name='{$name}', type='{$templatetype}', description='{$description}', tofield='{$tofield}', fromfield='{$fromfield}', ";
            $sql .= "replytofield='{$replytofield}', ccfield='{$ccfield}', bccfield='{$bccfield}', subjectfield='{$subjectfield}', ";
            $sql .= "body='{$bodytext}', customervisibility='{$cust_vis}', storeinlog='{$storeinlog}' ";
            $sql .= "WHERE id='{$id}' LIMIT 1";
            break;
        case 'notice':
            if ($savenew == "yes")
            {
                // First check the template does not already exist
                $sql = "SELECT id FROM `{$dbNoticeTemplates}` WHERE name = '{$name}' LIMIT 1";
                $result = mysqli_query($db, $sql);
                if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
                if (mysqli_num_rows($result) < 1)
                {
                    $sql = "INSERT INTO `{$dbNoticeTemplates}`(name) VALUES('{$name}')";
                    mysqli_query($db, $sql);
                    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);
                    $id = mysqli_insert_id($id);
                }
                else
                {
                    html_redirect($_SERVER['PHP_SELF'], FALSE, $strADuplicateAlreadyExists);
                    exit;
                }
            }
            $sql  = "UPDATE `{$dbNoticeTemplates}` SET name='{$name}', description='{$description}', type='".USER_DEFINED_NOTICE_TYPE."', ";
            $sql .= "linktext='{$linktext}', link='{$link}', durability='{$durability}', ";
            $sql .= "text='{$bodytext}' ";
            $sql .= "WHERE id='{$id}' LIMIT 1";
            break;
        default:
            trigger_error('Error: Invalid template type', E_USER_WARNING);
            html_redirect($_SERVER['PHP_SELF'], FALSE);
    }

    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
    if ($result)
    {
        plugin_do('templates_saved');
        journal(CFG_LOGGING_NORMAL, 'Email Template Updated', "Email Template {$type} was modified", CFG_JOURNAL_ADMIN, $type);
        html_redirect($_SERVER['PHP_SELF']);
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
?>
