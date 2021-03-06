<?php
/***************************************************************************
 *   copyright				: (C) 2008 - 2013 WeBid
 *   site					: http://www.webidsupport.com/
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

include 'common.php';

unset($ERR);
if ($system->SETTINGS['boards'] == 'n')
{
	header('location: index.php');
}

// Is the seller logged in?
if (!$user->is_logged_in())
{
	$_SESSION['REDIRECT_AFTER_LOGIN'] = 'boards.php';
	header('location: user_login.php');
	exit;
}	
if (isset($_POST['board_id']))
{
	$board_id = intval($_POST['board_id']);
}
elseif (isset($_GET['board_id']))
{
	$board_id = intval($_GET['board_id']);
}

if (!isset($board_id) || is_array($board_id) || empty($board_id) || $board_id == 0)
{
	header('location: boards.php');
	exit;
}

$NOW = time();

$query = "SELECT id FROM " . $DBPrefix . "comm_messages WHERE boardid = " . $board_id;
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

if (isset($_POST['action']) && empty($_POST['newmessage']))
{
	$ERR = $ERR_624;
}

$TOTALMSGS = mysql_num_rows($res);

//Update exclude user ADDON BY MAIKELB
	function checkstate($board_id)
	{
		global $system, $user;
		$_SESSION['checkstate'] = 'falser';
		$queryser = "SELECT excludeuser FROM webid_groups WHERE id = '" . $board_id . "'"; 
		$resser = mysql_query($queryser);
		$system->check_mysql($resser, $queryser, __LINE__, __FILE__);
		while ($evalser = mysql_fetch_assoc($resser))
		{
			$excludeuser = implode($evalser);
			$excludeuser = explode(",", $excludeuser);
		}
		if(!empty($excludeuser))
		{
			if (in_array($user->user_data['id'], $excludeuser)) 
			{
				$_SESSION['checkstate'] = 'falser';
			}
			else
			{
				$_SESSION['checkstate'] = 'truer';
			}
		}
		else
		{			
			$_SESSION['checkstate'] = 'truer';
		}
	}
//Check if it's the firstload so it can fetch the checkbox
if (!isset($_SESSION['checkstate']) || !isset($_SESSION['firstload']))
{
	$_SESSION['checkstate'] = array();
	$_SESSION['firstload'] = array();
	checkstate($board_id);
}
if (isset($_POST['emailcheckbox']))
{	
	$queryser = "SELECT excludeuser FROM " . $DBPrefix . "groups WHERE id = '" . $board_id . "'"; 
	global $resser;
	$resser = mysql_query($queryser);
	$system->check_mysql($resser, $queryser, __LINE__, __FILE__);
	
		if($_POST['emailcheckbox'] == 1)
		{
			while ($evalser = mysql_fetch_row($resser))
			{
				$excludeuser = implode($evalser);
				$excludeuser = explode(",", $excludeuser);
				if(!empty($excludeuser))
				{
					if(!in_array($user->user_data['id'], $excludeuser))
					{
						array_push($excludeuser, $user->user_data['id']);
						$evalinput = implode(",", $excludeuser);					
					}
				}
				else
				{
					array_push($excludeuser, $user->user_data['id']);
					$evalinput = implode(",", $excludeuser);
				}
				$query = "UPDATE " . $DBPrefix . "groups SET excludeuser = '" . $evalinput . "' WHERE " . $DBPrefix . "groups.id = '" . $board_id . "'";
				$resser = mysql_query($query);
				$system->check_mysql($resser, $query, __LINE__, __FILE__);
			}
		checkstate($board_id);
		}
		else if($_POST['emailcheckbox'] == 0)
		{
			if(!isset($_SESSION['checkstate']) || !isset($_SESSION['firstload']))
			{
				$_SESSION['checkstate'] = array();
				$_SESSION['firstload'] = array();
			}
			while ($evalser = mysql_fetch_row($resser))
			{
				$excludeuser = implode($evalser);
				$excludeuser = explode(",", $excludeuser);
				if(!empty($excludeuser))
				{
					if(in_array($user->user_data['id'], $excludeuser))
					{
						$inhoud = array_diff($excludeuser, array($user->user_data['id']));
						$evalinput = implode(",", $inhoud);
						$queryser = "UPDATE " . $DBPrefix . "groups SET excludeuser = '" . $evalinput . "' WHERE " . $DBPrefix . "groups.id = '" . $board_id . "'";
						$resser = mysql_query($queryser);							
					}
				}
			}
			checkstate($board_id);
		}
}
// Retrieve excluded state from user (by checking the uid in the database)


// retrieve message board title
$query = "SELECT name, active, msgstoshow FROM " . $DBPrefix . "community WHERE id = " . $board_id;
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$BOARD_TITLE = mysql_result($res, 0, 'name');
$BOARD_ACTIVE = mysql_result($res, 0, 'active');
$BOARD_LIMIT = mysql_result($res, 0, 'msgstoshow');

// Insert new message in the database
if (isset($_POST['action']) && $_POST['action'] == 'insertmessage' && !empty($_POST['newmessage'])) {
	
	if ($system->SETTINGS['wordsfilter'] == 'y')
	{
		$message = strip_tags($system->filter($_POST['newmessage']));
	}
	else
	{
		$message = strip_tags($_POST['newmessage']);
	}
	$query = "INSERT INTO " . $DBPrefix . "comm_messages VALUES
			(NULL, " . intval($_POST['board_id']) . ", '$NOW', " . $user->user_data['id'] . ",
			'" . $user->user_data['nick'] . "', '" . $system->cleanvars($message) . "')";
	$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
	header('location: ' . $_SERVER['HTTP_REFERER']);
	
	// Send email to all users within the desired group ADDON BY MAIKELB
	$queryser = "SELECT excludeuser FROM " . $DBPrefix . "groups WHERE id = '" . $board_id . "'"; 
	$resser = mysql_query($queryser);
	$system->check_mysql($resser, $queryser, __LINE__, __FILE__);
	
	$evalser = mysql_fetch_row($resser);
	if(!empty($excludeuser))
	{
		$excludeuser = implode($evalser);
		$excludeuser = explode(",", $excludeuser);
	}
	else 
	{
		$excludeuser = array();
	}
	
	$query = "SELECT id,email  FROM " . $DBPrefix . "users WHERE groups LIKE '%" . $board_id . "%'";
	$res = mysql_query($query);	
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	// Prepare array for all the emails
	$emailholder = array();
			while($eval = mysql_fetch_row($res))
			{
				if (!in_array($eval[0], $excludeuser)) 
				{	
					array_push($emailholder, $eval[1]);
				}
			}
			if(!empty($emailholder))
			{
				$emailer = new email_handler();
				$emailer->assign_vars(array(
				'SITENAME' => $system->SETTINGS['sitename'],
				'SITEURL' => $system->SETTINGS['siteurl'],
				'ADMINMAIL' => $system->SETTINGS['adminmail'],
				'GROUP' => $BOARD_TITLE,
				'MESSAGE' => $message,
				'GROUPLINK' => $system->SETTINGS['siteurl'] . 'msgboard.php?board_id=' . $board_id,
				'USER' => $user['name']
				));
				$emailer->email_sender($emailholder, 'groupmail_custom_message.php', $_MSG['CM_2026_0004'] . $BOARD_TITLE);
			}
		}
		



	// Update messages counter and lastmessage date
	$query = "UPDATE " . $DBPrefix . "community
			SET messages = messages + 1, lastmessage = '$NOW' WHERE id = " . $board_id;
	$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
	
if (!isset($_GET['PAGE']))
{
	$OFFSET = 0;
	$PAGE = 1;
}
else
{
	$PAGE = $_GET['PAGE'];
	$OFFSET = ($PAGE - 1) * $BOARD_LIMIT;
}
$PAGES = ceil($TOTALMSGS / $BOARD_LIMIT);
if (!$PAGES) $PAGES = 1;

if ($BOARD_ACTIVE == 2)
{
	header('location: boards.php');
	exit;
}

if (isset($_GET['show']) && $_GET['show'] == 'all')
{
	$SQL_LIMIT = '';
}
else
{
	$SQL_LIMIT = " LIMIT $OFFSET, $BOARD_LIMIT";
}
// Retrieve messages for this message board
$query = "SELECT * FROM " . $DBPrefix . "comm_messages WHERE boardid = " . $board_id . " ORDER BY msgdate DESC $SQL_LIMIT";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

if (mysql_num_rows($res) > 0)
{
	$k = 0;
	while ($messages = mysql_fetch_array($res))
	{
		$template->assign_block_vars('msgs', array(
				'MSG' => nl2br(stripslashes($messages['message'])),
				'USERNAME' => $messages['username'],
				'POSTED' => FormatDate($messages['msgdate']),
				'BGCOLOUR' => (!($k % 2)) ? '' : 'class="alt-row"',
				));
		$k++;
	}
}

$PREV = intval($PAGE - 1);
$NEXT = intval($PAGE + 1);
if ($PAGES > 1)
{
	$LOW = $PAGE - 5;
	if ($LOW <= 0) $LOW = 1;
	$COUNTER = $LOW;
	while ($COUNTER <= $PAGES && $COUNTER < ($PAGE + 6))
	{
		$template->assign_block_vars('pages', array(
				'PAGE' => ($PAGE == $COUNTER) ? '<b>' . $COUNTER . '</b>' : '<a href="' . $system->SETTINGS['siteurl'] . 'msgboard.php?PAGE=' . $COUNTER . '&board_id=' . $board_id . '"><u>' . $COUNTER . '</u></a>'
				));
		$COUNTER++;
	}
}
// Count message
$query = "SELECT id FROM " . $DBPrefix . "comm_messages";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$COUNT = mysql_num_rows($res);

$template->assign_vars(array(
		'ERROR' => (isset($ERR)) ? $ERR : '',
		'BOARD_NAME' => $BOARD_TITLE,
		'BOARD_ID' => $board_id,
		'PREV' => ($PAGES > 1 && $PAGE > 1) ? '<a href="' . $system->SETTINGS['siteurl'] . 'msgboard.php?PAGE=' . $PREV . '&board_id=' . $board_id . '"><u>' . $MSG['5119'] . '</u></a>&nbsp;' : '',
		'NEXT' => ($PAGE < $PAGES) ? '<a href="' . $system->SETTINGS['siteurl'] . 'msgboard.php?PAGE=' . $NEXT . '&board_id=' . $board_id . '"><u>' . $MSG['5120'] . '</u></a>' : '',
		'PAGE' => $PAGE,
		'PAGES' => $PAGES,
		'CHECKSTATE' => $_SESSION['checkstate']
		));
// Build the bottom navigation line for the template
if ($COUNT > $BOARD_LIMIT && (!isset($_GET['show']) || $_GET['show'] != 'all'))
{
	$NAVIGATION = '<a href="' . $system->SETTINGS['siteurl'] . 'msgboard.php?show=all&offset=' . $_REQUEST['offset'] . '&board_id=' . $_REQUEST['board_id'] . '">' . $MSG['5062'] . '</a> (' . $COUNT . ')';
}
elseif ($_GET['show'] == 'all')
{
	$NAVIGATION = '<a href="' . $system->SETTINGS['siteurl'] . 'msgboard.php?board_id=' . $_REQUEST['board_id'] . '&offset=' . $_REQUEST['offset'] . '">&lt;&lt; ' . $MSG['270'] . '</a> ';
}
else
{
	$NAVIGATION = '';
}

include 'header.php';
$template->set_filenames(array(
		'body' => 'msgboard.tpl'
		));
$template->display('body');
include 'footer.php';
?>
