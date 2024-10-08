<?php

include_once '../../includes/easyparliament/init.php';

$dates = file('alldates');
shuffle($dates);
$date = trim($dates[0]);

$db = new ParlDB();
$gid = $db->query("select gid from hansard where htype in (10,11) and major=1 and hdate='$date' order by rand() limit 1")->first()['gid'];
$gid = fix_gid_from_db($gid);

$URL = new \MySociety\TheyWorkForYou\Url('debates');
$URL->insert([ 'id' => $gid ]);
header('Location: ' . $URL->generate());
