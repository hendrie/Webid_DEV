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

if(!defined('InWeBid')) exit();

// Retrieve user's prefered language
$USERLANG = @mysql_result(@mysql_query("SELECT language FROM " . $DBPrefix . "userslanguage WHERE user='".$Winner['id']."'"),0,"language");
if(!isset($USERLANG)) $USERLANG = $language;

$buffer = file($main_path.'language/'.$USERLANG.'/mail_endauction_youwin.inc.php');
$i = 0;
$j = 0;

while($i < count($buffer)) {
	if(!ereg("^#(.)*$",$buffer[$i])){
		$skipped_buffer[$j] = $buffer[$i];
		$j++;
	}
	$i++;
}

//--Retrieve message

$message = implode($skipped_buffer,'');

//--Change TAGS with variables content
$message = ereg_replace("<#s_name#>",$Seller['name'],$message);
$message = ereg_replace("<#s_nick#>",$Seller['nick'],$message);
$message = ereg_replace("<#s_email#>",$Seller['email'],$message);
$message = ereg_replace("<#s_payment#>",$Seller['payment_details'],$message);
$message = ereg_replace("<#s_address#>",$Seller['address'],$message);
$message = ereg_replace("<#s_city#>",$Seller['city'],$message);
$message = ereg_replace("<#s_prov#>",$Seller['prov'],$message);
$message = ereg_replace("<#s_country#>",$Seller['country'],$message);
$message = ereg_replace("<#s_zip#>",$Seller['zip'],$message);
$message = ereg_replace("<#s_phone#>",$Seller['phone'],$message);
$message = ereg_replace("<#w_name#>",$Winner['name'],$message);
$message = ereg_replace("<#w_nick#>",$Winner['nick'],$message);
$message = ereg_replace("<#i_title#>",$Auction['title'],$message);
$message = ereg_replace("<#i_qty#>",$qty,$message);
$message = ereg_replace("<#i_currentbid#>",$system->print_money($WINNERS_BID[$Winner['current_bid']]),$message);
$message = ereg_replace("<#i_winningbid#>",$system->print_money($WINNING_BID),$message);
$message = ereg_replace("<#i_description#>",substr(strip_tags($Auction['description']),0,50)."...",$message);
$auction_url = $SITE_URL."item.php?id=".$Auction['id'];
$message = ereg_replace("<#i_url#>",$auction_url,$message);
$message = ereg_replace("<#i_ends#>",$ends_string,$message);
$message = ereg_replace("<#i_wanted>",$Winner['wanted'],$message);
$message = str_replace("<#i_got>",$Winner['quantity'],$message);
$message = ereg_replace("<#c_sitename#>",$system->SETTINGS['sitename'],$message);
$message = ereg_replace("<#c_siteurl#>",$system->SETTINGS['siteurl'],$message);
$message = ereg_replace("<#c_adminemail#>",$system->SETTINGS['adminmail'],$message);

mail($Winner['email'], $MSG['909'], stripslashes($message), 'From:'.$system->SETTINGS['sitename'].' <'.$system->SETTINGS['adminmail'].'>'."\n".'Content-Type: text/html; charset='.$CHARSET);
?>