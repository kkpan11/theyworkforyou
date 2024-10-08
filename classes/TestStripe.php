<?php

namespace MySociety\TheyWorkForYou;

class TestStripe extends Stripe {
    public function getSubscription($args) {
        if ($args['id'] == 'sub_123') {
            return \Stripe\Util\Util::convertToStripeObject([
                'id' => 'sub_123',
                'discount' => [
                    'coupon' => ['percent_off' => 100],
                    'end' => null,
                ],
                'plan' => [
                    'amount' => '2000',
                    'id' => 'twfy-1k',
                    'nickname' => 'Some calls per month',
                    'interval' => 'month',
                ],
                'cancel_at_period_end' => false,
                'created' => time(),
                'current_period_end' => time(),
                'latest_invoice' => [],
                'customer' => [
                    'id' => 'cus_123',
                    'account_balance' => 0,
                    'default_source' => [],
                    'invoice_settings' => [
                        'default_payment_method' => [],
                    ],
                ],
            ], null);
        }
        return \Stripe\Util\Util::convertToStripeObject([], null);
    }

    public function getUpcomingInvoice($args) {
        return \Stripe\Util\Util::convertToStripeObject([], null);
    }

    public function createCustomer($args) {
        return \Stripe\Util\Util::convertToStripeObject([
            'id' => 'cus_123',
            'email' => 'test@example.org',
        ], null);
    }

    public function updateCustomer($id, $args) {
        return \Stripe\Util\Util::convertToStripeObject([], null);
    }

    public function createSubscription($args) {
        return \Stripe\Util\Util::convertToStripeObject([
            'id' => 'sub_123',
        ], null);
    }
}
