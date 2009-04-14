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

if (!defined('InWeBid')) exit();

// Check if the e-mail has to be sent or not
$query = "SELECT endemailmode FROM " . $DBPrefix . "users WHERE id = " . $Seller['id'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$emailmode = mysql_result($res, 0, 'endemailmode');

if ($emailmode == 'one') {
	$emailer = new email_class();
	$emailer->assign_vars(array(
			'S_NAME' => $Seller['name'],
			
			'A_URL' => $system->SETTINGS['siteurl'] . 'item.php?id=' . $Auction['id'],
			'A_PICURL' => ($Auction['pict_url'] != '') ? $uploaded_path . $Auction['id'] . '/' . $Auction['pict_url'] : 'images/email_alerts/default_item_img.jpg',
			'A_TITLE' => $Auction['title'],
			'A_CURRENTBID' => $system->print_money($Auction['current_bid']),
			'A_QTY' => $Auction['quantity'],
			'A_ENDS' => $ends_string,
			
			'B_REPORT' => $report_text,
			
			'SITE_URL' => $system->SETTINGS['siteurl'],
			'SITENAME' => $system->SETTINGS['sitename']
			));
	$emailer->email_uid = $Seller['id'];
	$subject = $system->SETTINGS['sitename'] . ' ' . $MSG['079'] . ' ' . $MSG['907'] . ' ' . $Auction['title'];
	$emailer->email_sender($Seller['email'], 'endauction_winner.inc.php', $subject);
}
?>
