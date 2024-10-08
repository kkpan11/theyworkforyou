<?php

include_once '../../includes/easyparliament/init.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, STRIPE_ENDPOINT_SECRET);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch (\Stripe\Error\SignatureVerification $e) {
    http_response_code(400);
    exit();
}

$obj = $event->data->object;
if ($event->type == 'customer.subscription.deleted') {
    $db = new ParlDB();
    $sub = new \MySociety\TheyWorkForYou\Subscription($obj->id);
    if ($sub->stripe) {
        $sub->delete_from_redis();
        $db->query('DELETE FROM api_subscription WHERE stripe_id = :stripe_id', [':stripe_id' => $obj->id]);
    }
} elseif ($event->type == 'customer.subscription.updated') {
    $sub = new \MySociety\TheyWorkForYou\Subscription($obj->id);
    if ($sub->stripe) {
        $sub->redis_update_max($obj->plan->id);
    }
} elseif ($event->type == 'invoice.payment_failed' && $obj->billing_reason == 'subscription_cycle' && stripe_twfy_sub($obj)) {
    $customer = \Stripe\Customer::retrieve($obj->customer);
    $email = $customer->email;
    if ($obj->next_payment_attempt) {
        send_template_email(['template' => 'api_payment_failed', 'to' => $email], []);
    } else {
        send_template_email(['template' => 'api_cancelled', 'to' => $email], []);
    }
} elseif ($event->type == 'invoice.payment_succeeded' && stripe_twfy_sub($obj)) {
    # If this isn't a manual invoice (so it's the monthly one), reset the quota
    $sub = null;
    if ($obj->billing_reason != 'manual') {
        $sub = stripe_reset_quota($obj->subscription);
    }
    # The plan might have changed too, so update the maximum
    if (!$sub) {
        $sub = new \MySociety\TheyWorkForYou\Subscription($obj->subscription);
    }
    if ($sub->stripe) {
        $sub->redis_update_max($sub->stripe->plan->id);
    }
    try {
        # Update the invoice's PaymentIntent and Charge to say it came from TWFY (for CSV export)
        # Both are shown in the Stripe admin, annoyingly
        if ($obj->payment_intent) {
            \Stripe\PaymentIntent::update($obj->payment_intent, [ 'description' => 'TheyWorkForYou' ]);
        }
        if ($obj->charge) {
            \Stripe\Charge::update($obj->charge, [ 'description' => 'TheyWorkForYou' ]);
        }
    } catch (\Stripe\Error\Base $e) {
    }
} elseif ($event->type == 'invoice.updated' && stripe_twfy_sub($obj)) {
    if ($obj->forgiven && property_exists($event->data, 'previous_attributes')) {
        $previous = $event->data->previous_attributes;
        if (array_key_exists('forgiven', $previous) && !$previous['forgiven']) {
            stripe_reset_quota($obj->subscription);
        }
    }
}

http_response_code(200);

# ---

function stripe_twfy_sub($invoice) {
    # If the invoice doesn't have a subscription, ignore it
    if (!$invoice->subscription) {
        return false;
    }
    $stripe_sub = \Stripe\Subscription::retrieve($invoice->subscription);
    return substr($stripe_sub->plan->id, 0, 4) == 'twfy';
}

function stripe_reset_quota($subscription) {
    $sub = new \MySociety\TheyWorkForYou\Subscription($subscription);
    if ($sub->stripe) {
        $sub->redis_reset_quota();
    } else {
        $subject = "Someone's subscription was not renewed properly";
        $message = "TheyWorkForYou tried to reset the quota for subscription $subscription but couldn't find it";
        send_email(CONTACTEMAIL, $subject, $message);
    }
    return $sub;
}
