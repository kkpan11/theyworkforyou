<?php

$this_page = "parliaments";

include_once '../../includes/easyparliament/init.php';

$PAGE->page_start();
$PAGE->stripe_start();
echo '<dl>';
foreach (['hansard', 'sp_home', 'ni_home', 'wales_home', 'london_home'] as $page) {
    $menu = $DATA->page_metadata($page, 'menu');
    $title = $menu['text'];
    $text = $menu['title'];
    $URL = new \MySociety\TheyWorkForYou\Url($page);
    $url = $URL->generate();
    echo "<dt><a href='$url'>$title</a></dt><dd>$text</dd>\n";
}
echo '</ul>';

$PAGE->stripe_end();
$PAGE->page_end();
