<?php
// This sidebar appears on the very front page of the site.

$URL = new \MySociety\TheyWorkForYou\Url('sitenews');
$sitenewsurl = $URL->generate();

$URL = new \MySociety\TheyWorkForYou\Url('sitenews_archive');
$archiveurl = $URL->generate();

$this->block_start(['title' => "Site news", 'url' => $sitenewsurl]);

include BASEDIR . '/news/home_page_include.php';
?>

<p><a href="<?php echo $archiveurl; ?>">Archive</a></p>

<?php
$this->block_end();
?>
