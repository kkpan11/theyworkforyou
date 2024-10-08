<?php

$URL = new \MySociety\TheyWorkForYou\Url('help');
$helpurl = $URL->generate();
$this->block_start(['id' => 'help', 'title' => "What is the Northern Ireland Assembly?"]);
?>

<p>The current <strong>Northern Ireland Assembly</strong> was established in
1998 as part of the Belfast Agreement. It was suspended on the 14th of October
2002, and remained suspended until 8th May 2007.</p>

<p>Between 8 May 2006 and 22 November 2006 the <strong>Assembly established
under the Northern Ireland Act 2006</strong>, and between November 2006 and
January 2007 the <strong>Transitional Assembly</strong> created by the Northern
Ireland (St Andrews Agreement) Act 2006, met in order to prepare for the
restoration of devolved government.</p>

<?php
$this->block_end();
?>
