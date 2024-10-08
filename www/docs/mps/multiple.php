<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/member.php";

$db = new ParlDB();

$PAGE->page_start();
$PAGE->stripe_start();

$q = $db->query("select m1.person_id, m1.house as house1, m2.house as house2
    from member as m1, member as m2
    where m1.person_id = m2.person_id and m1.house != m2.house and m1.house < m2.house
        and m1.left_house='9999-12-31' and m2.left_house='9999-12-31'");
foreach ($q as $row) {
    $pid = $row['person_id'];
    $member = new MEMBER(['person_id' => $pid]);
    $h1 = $row['house1'];
    $h2 = $row['house2'];
    $out[$h1][$h2][] = $member;
}

out(1, 3, 'MP and MLA');
out(1, 4, 'MP and MSP');
out(2, 3, 'Lord and MLA');
out(2, 4, 'Lord and MSP');
out(3, 4, 'MLA and MSP');

function out($h1, $h2, $title) {
    global $out;
    if (!isset($out[$h1][$h2])) {
        return;
    }
    print "<h3>$title</h3>\n<ul>\n";
    foreach ($out[$h1][$h2] as $m) {
        print '<li><a href="' . $m->url() . '">' . $m->full_name() . "</a></li>\n";
    }
    print '</ul>';
}

$PAGE->stripe_end([
    ['type' => 'include', 'content' => 'donate'],
]);
$PAGE->page_end();
