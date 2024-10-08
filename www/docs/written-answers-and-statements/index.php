<?php

#
# Index page for written answers/statements.

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/member.php";
include_once INCLUDESPATH . "easyparliament/recess.php";

$this_page = 'wranswmsfront';
$view = new MySociety\TheyWorkForYou\SectionView\WransView();
$data = $view->display();
if ($data) {
    if ($data['template']) {
        $template = $data['template'];
    } else {
        $template = 'section/section';
    }
    MySociety\TheyWorkForYou\Renderer::output($template, $data);
}
