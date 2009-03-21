<?php
/***************************************************************************
 *   copyright				: (C) 2008 WeBid
 *   site					: http://www.webidsupport.com/
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

include "includes/config.inc.php";
include $include_path . "countries.inc.php";

if ($system->SETTINGS['https'] == 'y' && $_SERVER['HTTPS'] != 'on') {
	$sslurl = str_replace('http://', 'https://', $system->SETTINGS['siteurl']);
    header('Location: ' . $sslurl . 'user_login.php');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == "login") {
    $query = "SELECT id, email, name, accounttype, suspended FROM " . $DBPrefix . "users WHERE nick = '" . $system->cleanvars($_POST['username']) . "' AND password = '" . md5($MD5_PREFIX . $_POST['password']) . "' AND (suspended = 0 OR suspended = 2)";
    $res = mysql_query($query);
    $system->check_mysql($res, $query, __LINE__, __FILE__);

    if (mysql_num_rows($res) == 0) {
        $errmsg = $ERR_038;
    } elseif (($suspended = mysql_result($res, 0, 'suspended')) != 0) {
        switch ($suspended) {
            case 10:
                $errmsg = $ERR_713;
                break;
            case 8:
                $errmsg = $ERR_714;
                break;
        }
    } else {
        $user_id = mysql_result($res, 0, 'id');
        $_SESSION['WEBID_LOGGED_IN'] = $user_id;
        $_SESSION['WEBID_LOGGED_IN_USERNAME'] = $_POST['username'];
        $_SESSION['WEBID_LOGGED_EMAIL'] = mysql_result($res, 0, "email");
        $_SESSION['WEBID_LOGGED_ACCOUNT'] = mysql_result($res, 0, "accounttype");
        $_SESSION['WEBID_LOGGED_NAME'] = mysql_result($res, 0, "name");

        $query = "UPDATE " . $DBPrefix . "users SET lastlogin = '" . gmdate('Y-m-d H:i:s') . "', reg_date = reg_date
				WHERE id = " . $_SESSION['WEBID_LOGGED_IN'];
        $system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);

        if (isset($_POST['rememberme'])) {
            $remember_key = md5(time());
            $query = "INSERT INTO " . $DBPrefix . "rememberme VALUES(" . $user_id . ",'" . $remember_key . "')";
            $system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
            setcookie("WEBID_RM_ID", $remember_key, time() + (3600 * 24 * 365));
        }

        $query = "SELECT id FROM " . $DBPrefix . "usersips WHERE USER='" . $user_id . "' AND ip='" . $_SERVER['REMOTE_ADDR'] . "'";
        $res = mysql_query($query);
        $system->check_mysql($res, $query, __LINE__, __FILE__);
        if (mysql_num_rows($res) == 0) {
            $query = "INSERT INTO " . $DBPrefix . "usersips VALUES
					(NULL, '" . $user_id . "', '" . $_SERVER['REMOTE_ADDR'] . "', 'after','accept')";
            $system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
        } else {
            $query = "UPDATE " . $DBPrefix . "usersips SET ip = '" . $_SERVER['REMOTE_ADDR'] . "' WHERE id = " . mysql_result($res, 0, "id");
            $system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
        }

        if (isset($_SESSION['REDIRECT_AFTER_LOGIN'])) {
            $URL = str_replace('\r', '', str_replace('\n', '', $_SESSION['REDIRECT_AFTER_LOGIN']));
            unset($_SESSION['REDIRECT_AFTER_LOGIN']);
        } else {
            $URL = 'user_menu.php';
        }

        header("Location: $URL");
        exit;
    }
}

$template->assign_vars(array(
        'L_ERROR' => (isset($errmsg)) ? $errmsg : '',
        'USER' => (isset($_POST['username'])) ? $_POST['username'] : ''
        ));

include "header.php";
$template->set_filenames(array(
        'body' => 'user_login.html'
        ));
$template->display('body');
include "footer.php";

?>