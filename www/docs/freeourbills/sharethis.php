<?php

require_once '../../../commonlib/phplib/sharethis.php';

function freeourbills_share_details() {
    $name = '';
    $email = '';

    return [
        'name' => $name,
        'email' => $email,
        'url' => "http://www.theyworkforyou.com/freeourbills",
        'title' => 'Free Our Bills! - TheyWorkForYou',
        'email_url' => '/freeourbills',
    ];
}

function freeourbills_share_form() {
    $data = freeourbills_share_details();
    share_form(
        $data['url'],
        $data['title'],
        $data['email_url'],
        $data['name'],
        $data['email']
    );
}

function freeourbills_email_friends_link() {
    $email = "Hi,

I just signed up to an important campaign to help make all the laws we
have to obey better. I thought you might want to take action and
join up too.

http://www.theyworkforyou.com/freeourbills/

This is being organised by the charity who help us keep an eye on our
Members of Parliament with the website www.theyworkforyou.com . They'd
like information about proposed new laws to be published in a better
form, so we can all hear about them before it is too late.

Thank you so much for your help - forward this email to friends!
";
    $subject = "Help make the laws we have to obey better! - TheyWorkForYou.com";

    print '<a href="mailto:?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($email) . '">Click here to open a new email and invite your friends, family and colleagues to get involved.</a>';
}

function freeourbills_share_page() {
    $data = freeourbills_share_details();
    echo '<div id="col1">';
    echo '<p>';
    freeourbills_email_friends_link();
    echo '</p>';
    echo '<p>Share with friends on a social networking site that you use, click below:</p>';
    share_form_social($data['url'], $data['title']);
    /*echo '<h3 style="margin-top: 1em">' . _('Email') . '</h3>';
    share_form_email($data['email_url'], $data['name'], $data['email']); */
    echo '</div>';
}

function freeourbills_sharethis_link() {
    print '<p><a href="/freeourbills/doshare"';
    #print 'onclick="share(this); return false;"';
    print ' title="' .
        _('E-mail this, post to del.icio.us, etc.') . '" class="share_link" rel="nofollow">' .
        _('Share this') . '</a></p>';
    #freeourbills_share_form();
}
