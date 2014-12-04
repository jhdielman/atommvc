<?php

/**
 * AtomMVC: Stripe Config
 * atom/config/stripe.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 * @see http://www.stripe.com
 *
 */

$keys = [
    'test'  => [
        'secretKey'         => '{STRIPE_TEST_SECRET_KEY}',
        'publishableKey'    => '{STRIPE_TEST_PUBLISHABLE_KEY}'],
    'live'  => [
        'secretKey'         => '{STRIPE_LIVE_SECRET_KEY}',
        'publishableKey'    => '{STRIPE_LIVE_PUBLISHABLE_KEY}']
];

$env = 'test';

return $keys[$env];