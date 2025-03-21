<?php

include_once '../../includes/easyparliament/init.php';
include_once './api_functions.php';
include_once INCLUDESPATH . '../../commonlib/phplib/auth.php';

MySociety\TheyWorkForYou\Utility\Session::start();

$this_page = 'api_key';
$PAGE->page_start();
$PAGE->stripe_start();

$subscription = null;
if ($THEUSER->loggedin()) {
    if (get_http_var('create_key')) {
        if (!Volnix\CSRF\CSRF::validate($_POST)) {
            print 'CSRF validation failure!';
            exit;
        }
        create_key($THEUSER);
    }

    if (get_http_var('cancelled')) {
        print '<p class="alert-box">Your subscription has been cancelled.</p>';
    }

    $subscription = new MySociety\TheyWorkForYou\Subscription($THEUSER);
    $errors = [];

    if ($subscription->stripe) {
        $sub = $subscription->stripe;
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !get_http_var('create_key')) {
            if (!Volnix\CSRF\CSRF::validate($_POST)) {
                print 'CSRF validation failure!';
                exit;
            }

            $payment_method = get_http_var('payment_method');
            $subscription->update_payment_method($payment_method);

            try {
                $invoice = $sub->latest_invoice;
                $invoice->pay([ 'expand' => [ 'payment_intent' ] ]);
            } catch (\Stripe\Exception\CardException $e) {
                $invoice = $subscription->api->client->invoices->retrieve($sub->latest_invoice, ['expand' => [ 'payment_intent'] ]);
            }
        } else {
            $invoice = $sub->latest_invoice;
        }
        $needs_processing = null;
        if ($invoice) {
            $needs_processing = check_payment_intent($invoice);
            if ($needs_processing) {
                include_once INCLUDESPATH . 'easyparliament/templates/html/api/check.php';
            } else {
                if (get_http_var('updated')) {
                    print '<p class="alert-box">Thanks very much!</p>';
                    if (has_no_keys($THEUSER)) {
                        create_key($THEUSER);
                    }
                }
                include_once INCLUDESPATH . 'easyparliament/templates/html/api/subscription_detail.php';
            }
        } else {
            include_once INCLUDESPATH . 'easyparliament/templates/html/api/subscription_detail.php';
        }
        if ($needs_processing) {
            $PAGE->stripe_end();
            $PAGE->page_end();
            exit;
        }
    } else {
        include_once INCLUDESPATH . 'easyparliament/templates/html/api/update.php';
    }

    print '<hr>';
    $keys = get_keys($THEUSER);
    if ($keys) {
        list_keys($keys);
    }
    if ($subscription->stripe) {
        include_once INCLUDESPATH . 'easyparliament/templates/html/api/key-form.php';
    }
} else {
    logged_out();
}

$sidebar = api_sidebar($subscription);
$PAGE->stripe_end([$sidebar]);
$PAGE->page_end();

# ---

function logged_out() {
    echo 'Your plan and key are tied to your TheyWorkForYou account,
so if you don’t yet have one, please <a href="/user/?pg=join&amp;ret=/api/key">sign up</a>, then
return here to get a key.</p>';
    echo '<p style="font-size:200%"><strong><a href="/user/login/?ret=/api/key">Sign in</a></strong> (or <a href="/user/?pg=join&amp;ret=/api/key">sign up</a>) to get an API key.</p>';
}

function has_no_keys($user) {
    $db = new ParlDB();
    $q = $db->query('SELECT COUNT(*) as count FROM api_key WHERE user_id=' . $user->user_id())->first()['count'];
    return $q ? false : true;
}

function get_keys($user) {
    $db = new ParlDB();
    $q = $db->query('SELECT api_key, created, reason FROM api_key WHERE user_id=' . $user->user_id());
    $keys = [];
    foreach ($q as $row) {
        $keys[] = [$row['api_key'], $row['created'], $row['reason']];
    }
    return $keys;
}

function list_keys($keys) {
    $db = new ParlDB();
    echo '<h2>Your keys</h2> <ul>';
    foreach ($keys as $keyarr) {
        [$key, $created, $reason] = $keyarr;
        echo '<li><span style="font-size:200%">' . $key . '</span><br><span style="color: #666666;">';
        echo 'Key created ', $created, '; ', $reason;
        echo '</span><br><em>Usage statistics</em>: ';
        $c = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 day')->first()['count'];
        echo "last 24 hours: $c, ";
        $c = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 week')->first()['count'];
        echo "last week: $c, ";
        $c = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 month')->first()['count'];
        echo "last month: $c";
        echo '</p>';
    }
    echo '</ul>';
}

function create_key($user) {
    $key = auth_ab64_encode(urandom_bytes(16));
    $db = new ParlDB();
    $db->query('INSERT INTO api_key (user_id, api_key, commercial, created, reason, estimated_usage) VALUES
        (:user_id, :key, -1, NOW(), :reason, -1)', [
        ':user_id' => $user->user_id(),
        ':key' => $key,
        ':reason' => '',
    ]);
    $r = new \MySociety\TheyWorkForYou\Redis();
    $r->set("key:$key:api:" . REDIS_API_NAME, $user->user_id());
}

function check_payment_intent($invoice) {
    # The subscription has been created, but it is possible that the
    # payment failed (card error), or we need to do 3DS or similar
    $pi = $invoice->payment_intent;
    if (!$pi) { # Free plan
        return;
    }
    if ($pi->status == 'requires_payment_method' || $pi->status == 'requires_source') {
        return [
            'requires_payment_method' => true,
            'requires_action' => false,
        ];
    } elseif ($pi->status == 'requires_action' || $pi->status == 'requires_source_action') {
        return [
            'requires_payment_method' => false,
            'requires_action' => true,
            'payment_intent_client_secret' => $pi->client_secret,
        ];
    }
}
