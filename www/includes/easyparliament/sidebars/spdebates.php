<?php

$URL = new \MySociety\TheyWorkForYou\Url('help');
$helpurl = $URL->generate();
$this->block_start(['id' => 'help', 'title' => "What is the Scottish Parliament?"]);
?>

<p>The Scottish Parliament is the national legislature of Scotland, located in
Edinburgh. The Parliament consists of 129 members known as <a
href="/msps/">MSPs</a>, elected for four-year terms; 73 represent
individual geographical constituencies, and 56 are regional representatives.</p>

<?php
$this->block_end();
?>
