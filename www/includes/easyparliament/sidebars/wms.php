<?php

$URL = new \MySociety\TheyWorkForYou\Url('help');
$helpurl = $URL->generate();

$this->block_start(['id' => 'help', 'title' => "What are Written Ministerial Statements?"]);
?>

<p>Written Ministerial Statements were introducted in late 2002
to stop the practice of having "planted" or "inspired" questions
designed to elicit Government statements.</p>
<p>They are just that &#8212; statements on a particular topic by a Government Minister.</p>
<?php
$this->block_end();
?>
