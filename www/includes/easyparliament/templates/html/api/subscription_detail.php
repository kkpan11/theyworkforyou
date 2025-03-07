<?php

$quota_status = $subscription->quota_status();
$balance = $subscription->stripe->customer->balance;
if ($subscription->upcoming) {
    if ($subscription->upcoming->total < 0) {
        # Going to be credited
        $balance += $subscription->upcoming->total;
    }
}

?>

    <section class="account-form">
        <h2>Subscription</h2>

        <?php if ($quota_status['blocked']) { ?>
          <?php if ($quota_status['quota'] > 0 && $quota_status['count'] > $quota_status['quota']) { ?>
            <p class="attention-box warning">
                You have used up your quota for the <?= $subscription->stripe->plan->interval ?>.
                Please <a href="/api/update-plan">upgrade</a>
                or <a href="/contact/">contact us</a>.
            </p>
          <?php } else { ?>
            <p class="attention-box warning">
                Your account is currently suspended. Please
                <a href="/contact/">contact us</a>.
            </p>
          <?php } ?>
        <?php } ?>

        <?php if ($subscription->stripe->plan) { ?>

            <p>Your current plan is <strong><?= $subscription->stripe->plan->nickname ?></strong>.</p>

            <p>It costs you £<?= $subscription->actual_paid ?>/<?= $subscription->stripe->plan->interval ?>.
            <?php if ($subscription->stripe->discount) { ?>
                (£<?= $subscription->stripe->plan->amount ?>/<?= $subscription->stripe->plan->interval ?> with
                <?= $subscription->stripe->discount->coupon->percent_off ?>% discount applied.)
            <?php } ?>
            </p>

            <?php if ($subscription->stripe->schedule && count($subscription->stripe->schedule->phases) > 1 && $subscription->stripe->schedule->phases[1]->items[0]->plan->nickname != $subscription->stripe->plan->nickname) { ?>
                 <p>You are switching to <strong><?= $subscription->stripe->schedule->phases[1]->items[0]->plan->nickname ?></strong> at the end of your current period.</p>
            <?php } ?>

            <?php if ($subscription->stripe->discount && $subscription->stripe->discount->end) { ?>
                <p>Your discount will expire on <?= $subscription->stripe->discount->end ?>.</p>
            <?php } ?>

            <p>Subscription created on <?= date('d/m/Y', $subscription->stripe->created) ?>;
            <?php if ($subscription->stripe->cancel_at_period_end) { ?>
                <strong>it expires on <?= date('d/m/Y', $subscription->stripe->current_period_end) ?>.</strong>
            <?php } elseif ($subscription->actual_paid > 0) { ?>
                your next payment
                <?php
                if ($subscription->upcoming) {
                    echo 'of £' . number_format($subscription->upcoming->amount_due / 100, 2);
                }
                ?> will be taken on <?= date('d/m/Y', $subscription->stripe->current_period_end) ?>.
            <?php } else { ?>
                your next invoice date is <?= date('d/m/Y', $subscription->stripe->current_period_end) ?>.
            <?php } ?>

            <?php if ($balance) { ?>
                <br>Your account has a balance of £<?= number_format(-$balance / 100, 2); ?>.
            <?php } ?>
            </p>

            <ul class="unstyled-list inline-list">
                <li><a href="/api/update-plan" class="btn btn--small btn--primary">Change plan</a></li>
              <?php if (!$subscription->stripe->cancel_at_period_end) { ?>
                <li><a href="/api/cancel-plan" class="btn btn--small btn--danger">Cancel subscription</a></li>
              <?php } ?>
            </ul>

            <hr>

            <h3>Your usage</h3>

            <p>
                This <?= $subscription->stripe->plan->interval ?>:
                <?= number_format($quota_status['count']) ?>
              <?php if ($quota_status['quota'] > 0) { ?>
                out of <?= number_format($quota_status['quota']) ?>
              <?php } ?>
                API calls
            </p>

            <?php if ($quota_status['quota'] > 0) { ?>
                <meter class="subscription-quota-meter"
                    value="<?= $quota_status['count'] ?>"
                    min="0"
                    max="<?= $quota_status['quota'] ?>">
                    <?= number_format($quota_status['count']) ?> out of
                    <?= number_format($quota_status['quota']) ?> API calls
                </meter>
            <?php } ?>

            <hr>

            <h3>Your payment details</h3>

            <?php if ($subscription->card_info) { ?>
                <p>Payment details we hold: <?= $subscription->card_info->brand ?>,
                last four digits <code><?= $subscription->card_info->last4 ?></code>.</p>
            <?php } else { ?>
                <p>We do not currently hold any payment details for you.</p>
            <?php } ?>

            <noscript>
                <p class="account-form__field--error"> Unfortunately, our payment
                processor requires JavaScript to update details.<br>Please
                <a href="/contact/">contact us</a> about your requirements and
                we’ll be happy to help.</p>
            </noscript>

            <p>Fill in the below to update your card details:</p>
            <form action="/api/update-card" method="POST" id="update_card_form">
                <?= \Volnix\CSRF\CSRF::getHiddenInputString() ?>
                <div class="row">
                    <label for="id_card_name">Name on card:</label>
                    <div class="account-form__input"><input type="text" name="card_name" id="id_card_name"></div>
                </div>

                <div class="row">
                    <label for="card-element">Credit or debit card details:</label>
                    <div id="card-element"><!-- A Stripe Element will be inserted here. --></div>
                    <div id="card-errors" role="alert"></div>
                </div>

                <input type="hidden" name="payment_method" value="">

                <div class="row row--submit">
                   <button id="customButton"
                            class="button">Update card details</button>
                    <div id="spinner" class="mysoc-spinner mysoc-spinner--small" role="status">
                        <span class="sr-only">Processing…</span>
                    </div>
                </div>
                <script src="https://js.stripe.com/v3"></script>
                <script id="js-payment" data-key="<?= STRIPE_PUBLIC_KEY ?>" data-api-version="<?= STRIPE_API_VERSION ?>" src="<?= cache_version('js/payment.js') ?>"></script>
            </form>

        <?php } else { ?>
            <p>You are not currently subscribed to a plan.</p>
            <p>
                <a href="/api/update-plan" class="button">Subscribe to a plan</a></p>
            </p>

        <?php } ?>

    </section>
