<?php

// This the page users come to when they click the link in their
// confirmation email after joining the site.

// What happens? They will come here with t=23-adsf7897fd78d9sfsd
// where the value of 't' is a form of their registration token.
// We check this exists in the database and if so we log the user in.

// This redirects them to this page with welcome=t in the URL.
// We then print a nice welcome message.

include_once '../../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/member.php";

if (get_http_var('welcome') == 't') {
    // The user has successfully clicked the link, been logged in, and
    // then been redirected here.
    // So welcome them!

    $this_page = 'userconfirmed';

    $PAGE->page_start();

    $PAGE->stripe_start();

    if ($THEUSER->isloggedin()) {
        ?>

    <p>Welcome to TheyWorkForYou! You are now logged in.</p>

    <p><strong>TheyWorkForYou</strong> helps make sense of the goings on in Parliament: debates, divisions, committees and questions.</p>

    <p>You can use it to keep an eye on your MP, Peers, or any of the other representatives we cover: see what speeches they make, how they vote, and check how well you’re being represented.
        And if you have opinions about any of that? You can look for the ‘Send a message’ button at the top of your MP’s page.</p>

    <p>If you are looking to sign up for our API, please begin by <a href="/api/key">getting a key here</a>.</p>

<?php
    } else {
        // Oops, something must have gone wrong when the user was logged in.
        // It shouldn't do, but...
        $PAGE->error_message("Sorry, we couldn't log you in - TheyWorkForYou requires cookies to keep
you logged in, so please make sure they're enabled for this site.");
    }

    $PAGE->page_end();


} elseif (get_http_var('email') == 't') {
    $this_page = 'emailconfirmed';

    $PAGE->page_start();

    $PAGE->stripe_start();

    if ($THEUSER->isloggedin()) {
        ?>
    <p>Hi, Your email address has now been updated.</p>

    <p>Do <a href="mailto:<?php echo str_replace('@', '&#64;', CONTACTEMAIL); ?>">let us know</a> if you find a bug, or have a suggestion.</p>

<?php
    } else {
        // Oops, something must have gone wrong when the user was logged in.
        // It shouldn't do, but...
        $PAGE->error_message("Sorry, we couldn't log you in - TheyWorkForYou requires cookies to keep
you logged in, so please make sure they're enabled for this site.");
    }

    $PAGE->stripe_end([
        [
            'type'		=> 'include',
            'content'	=> 'userconfirmed',
        ],
    ]);

    $PAGE->page_end();

} elseif (get_http_var('t') != '') {
    // The user's first visit to this page, and they have a registration token.
    // So let's confirm them and hope they get logged in...

    if (get_http_var('c') == 'email') {
        $success = $THEUSER->confirm_email(get_http_var('t'));
    } else {
        $success = $THEUSER->confirm(get_http_var('t'));
    }

    if (!$success) {
        confirm_error();
    }
} else {
    // We have no registration token, and no notification of welcome...

    confirm_error();

}

function confirm_error() {
    // Friendly error, not a normal one!
    global $PAGE, $this_page;

    $this_page = 'userconfirmfailed';

    if (!$PAGE->page_started()) {
        $PAGE->page_start();

        $PAGE->stripe_start();

        ?>

    <p>The link you followed to reach this page appears to be incomplete.</p>

<?php
    } else {
        $PAGE->stripe_start();
    }
    ?>

    <p>If you clicked a link in your confirmation email you may need to manually copy and paste the entire link to the 'Location' bar of the web browser and try again.</p>

    <p>If you still get this message, please do <a href="mailto:<?php echo str_replace('@', '&#64;', CONTACTEMAIL); ?>">email us</a> and let us know, and we'll help out!</p>

<?php

        $PAGE->stripe_end();

    $PAGE->page_end();
}

?>
