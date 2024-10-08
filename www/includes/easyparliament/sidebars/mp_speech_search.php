<?php
// This sidebar is on the pages that show information about single MPs.

global $MEMBER;

$SEARCHURL = new \MySociety\TheyWorkForYou\Url("search");

if ($MEMBER->person_id()) {
    $pid = $MEMBER->person_id();
    $this->block_start(['id' => 'mpsearch', 'title' => "Search this person's speeches"]);
    ?>

                <div class="mpsearchbox">
                    <form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
                    <p>
                    <input name="q" size="24" maxlength="200">
                    <input type="hidden" name="pid" value="<?php echo $pid; ?>">
                    <input type="submit" class="submit" value="GO"></p>
                    </form>
                </div>

<?php
    $this->block_end();
}
?>
