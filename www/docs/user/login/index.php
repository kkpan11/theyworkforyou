<?php

// The login form page.

/*
    If the form hasn't been submitted, display_page() is called and the form shown.
    If the form has been submitted we check the input.
    If the input is OK, the user is logged in and taken to wherever they were before.
    If the input is not OK, the form is displayed again with error messages.
*/

include_once '../../../includes/easyparliament/init.php';

# we need this for the facebook login code to work as it stores
# some CSFR tokens in the session
MySociety\TheyWorkForYou\Utility\Session::start();
$this_page = "userlogin";

if (get_http_var("submitted") == "true") {
    // Form has been submitted, so check input.

    $email 		= get_http_var("email");
    $password 	= get_http_var("password");
    $remember 	= get_http_var("remember");

    // The user may have tried to do something that requires being logged in.
    // In which case we should arrive here with that page's URL in 'ret'.
    // We can then send the user there after log in.
    $returnurl 	= get_http_var("ret");

    $errors = [];

    if ($email == "") {
        $errors["email"] = gettext("Please enter your email address");
    } elseif (!validate_email($email)) {
        $errors["email"] = gettext("Please enter a valid email address");
    }
    if ($password == "") {
        $errors["password"] = gettext("Please enter your password");
    }

    if (sizeof($errors) > 0) {
        // Validation errors. Print form again.
        display_page($errors);

    } else {
        // No errors so far, so try to log in.

        $valid = $THEUSER->isvalid($email, $password);

        if ($valid && !is_array($valid)) {
            // No validation errors.
            if ($remember == "true") {
                $expire = "never";
            } else {
                $expire = "session";
            }

            // $returnurl is the url of where we'll send the user after login.
            $THEUSER->login($returnurl, $expire);

        } else {

            // Merge the validation errors with any we already have.
            $errors = array_merge($errors, $valid);

            display_page($errors);
        }

    }

} elseif ($resend = get_http_var('resend')) {
    $USER = new USER();
    $USER->init($resend);
    if (!$USER->confirmed()) {
        $details = [
            'email' => $USER->email(),
            'firstname' => $USER->firstname(),
            'lastname' => $USER->lastname(),
        ];
        $USER->send_confirmation_email($details);
        $this_page = 'userwelcome';
        $PAGE->page_start();
        $PAGE->stripe_start();
        $message = [
            'title' => gettext("Confirmation email resent"),
            'text' => gettext("You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address before you can log in. Thanks."),
        ];
        $PAGE->message($message);
        $PAGE->stripe_end();
        $PAGE->page_end();
    }
} else {
    // First time to the page...
    display_page();
}


function display_page($errors = []) {
    global $PAGE, $this_page, $THEUSER;

    $PAGE->page_start();

    $PAGE->stripe_start();

    if ($THEUSER->isloggedin()) {
        // Shouldn't really get here, but you never know.
        $URL = new \MySociety\TheyWorkForYou\Url('userlogout');
        ?>
    <p><strong>You are already signed in. <a href="<?php echo $URL->generate(); ?>">Sign out?</a></strong></p>
    <?php
        $PAGE->stripe_end();
        $PAGE->page_end();
        return;
    }

    $login = new \MySociety\TheyWorkForYou\FacebookLogin();

    ?>

    <p><a href="<?php echo htmlspecialchars($login->getloginURL()); ?>" class="button button--facebook">Sign in with Facebook</a></p>

    <p><?= gettext("Or sign in by email:")?></p>

    <?php $PAGE->login_form($errors); ?>

    <?php

        $PAGE->stripe_end([
            [
                'type' => 'include',
                'content' => 'userlogin',
            ],
        ]);

    $PAGE->page_end();

} // End display_page()

?>
