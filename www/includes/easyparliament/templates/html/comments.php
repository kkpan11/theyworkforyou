<?php
/*	Remember, we are currently within the COMMENTLIST class,
    in the render() function.

    We cycle through the $data array and output the comment(s).
    It should be something like this:

    $data = array (
        'info'	=> array (
            'user_id' => '34'		//  Might be used to highlight the user's comments.
        ),
        'comments' => array (
            0 => array (
                'comment_id'	=> '1',
                'user_id'		=> '4',
                'epobject_id'	=> '304',
                'body'			=> 'My comment goes here...',
                'posted'		=> '2003-12-31 23:00:00',
                'firstname'		=> 'Guy',
                'lastname'		=> 'Fawkes',
                'url'			=> '/permalink/to/this/comment/?id=304#c1',
                'hbody'			=> 'To answer the honourable gentleman...',
                'major'			=> 1,
            ),
            1 => array (
                etc....
            )
        )
    );


*/
global $PAGE, $DATA, $this_page, $THEUSER;


// Something's telling me these subheadings shouldn't be in this template...!

if (isset($data['comments'][0]['preview']) && $data['comments'][0]['preview'] == true) {
    // If we're just previewing a comment, we passed in 'preview' => true.
    $subheading = 'Your annotation would look like this:';

} elseif ($this_page == 'addcomment') {
    $subheading = 'Previous annotations';

} elseif ($this_page == 'commentreport' || $this_page == 'admin_commentreport') {
    $subheading = "";

} else {
    $subheading = 'Annotations';
}

if (isset($data['comments']) && count($data['comments']) > 0) {
    ?>
                <a name="comments"></a>
<?php
if ($subheading != '') {
    echo "\t\t\t\t<h3>$subheading</h3>\n";
}




    foreach ($data['comments'] as $n => $comment) {
        $style = $n % 2 != 0 ? '1' : '2';

        if (isset($data['info']['user_id']) &&
            $comment['user_id'] == $data['info']['user_id']) {
            $style .= '-on';
        }

        if (isset($comment['comment_id'])) {
            $id = 'c' . $comment['comment_id'];
        } else {
            $id = '';
        }

        ?>
                <div class="comment" id="<?php echo $id; ?>">
                    <a name="<?php echo $id; ?>"></a>
<?php

        // COMMENT REPORTING LINK.

        if (!$comment['visible']) {
            $reporthtml = '';
        } elseif (($this_page != 'commentreport' &&
            $this_page != 'addcomment'  &&
            $this_page != 'admin_commentreport')
            && $THEUSER->is_able_to('reportcomment')
            && $THEUSER->user_id() != $comment['user_id']
            && !$comment['modflagged']
        ) {

            // The comment hasn't been reported and we're on a page where we want to
            // display this link.

            $URL = new \MySociety\TheyWorkForYou\Url('commentreport');
            $URL->insert([
                'id'	=> $comment['comment_id'],
                'ret' 	=> $comment['url'],
            ]);

            $reporthtml = '(<a href="' . $URL->generate() . '" title="Notify moderators that this annotation should be deleted">Report this annotation</a>)';

        } elseif ($comment['modflagged']) {
            $reporthtml = '(This annotation has been reported to moderators)';
        } else {
            // When previewing a comment...
            $reporthtml = '';
        }


        // USERNAME AND DATE AND TIME.

        $USERURL = new \MySociety\TheyWorkForYou\Url('userview');
        $USERURL->insert(['u' => $comment['user_id']]);

        [$date, $time] = explode(' ', $comment['posted']);
        $date = format_date($date, SHORTDATEFORMAT);
        $time = format_time($time, TIMEFORMAT);
        ?>
                    <p class="credit"><a href="<?php echo $USERURL->generate(); ?>" title="See information about this user"><strong><?php echo _htmlentities($comment['firstname']) . ' ' . _htmlentities($comment['lastname']); ?></strong></a><br>
                    <small>Posted on <?php echo $date;

        if (isset($comment['url'])) {
            print ' <a href="' . $comment['url'] . '" title="Link to this annotation">' . $time . '</a>';

        } else {
            // There won't be a URL when we're just previewing a comment.
            print ' ' . $time;
        }
        ?> <?php echo $reporthtml; ?></small></p>

<?php

        // THE COMMENT BODY.
        if ($comment['visible']) {
            // Make URLs into links and do <br>s.
            $body = prepare_comment_for_display($comment['body']); // In utility.php
            echo "<p class=\"comment\">$body</p>\n";
        } else {
            print '<p class="comment"><em>This annotation has been removed</em></p>' . "\n";
        }

        ?>
                </div> <!-- end .comment -->
<?php
    }

}
?>
