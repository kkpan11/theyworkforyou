<?php

$new_style_template = true;

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . 'easyparliament/member.php';

$q = get_http_var('q');
$q = preg_replace('#[^a-z0-9]#i', '', $q);
if (validate_postcode($q)) {
    header("Location: /postcode/?pc=" . urlencode($q));
    exit;
}

if (!DEVSITE) {
    header('Cache-Control: max-age=900');
}

if (get_http_var('pid') == 16407) {
    header('Location: /search/?pid=10133');
    exit;
}


$search = new MySociety\TheyWorkForYou\Search();
$data = $search->display();

MySociety\TheyWorkForYou\Renderer::output($data['template'], $data);
