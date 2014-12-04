<?php

/**
 * AtomMVC: Master config
 * atom/config/master.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 *
 */


return [
    'charset' => 'UTF-8',
    'doctype' => 'HTML 4.01 Transitional',
    'allowGet' => true,

    /*
    |--------------------------------------------------------------------------
    | Cookie Related Variables
    |--------------------------------------------------------------------------
    |
    | 'cookiePrefix' = Set a prefix if you need to avoid collisions
    | 'cookieDomain' = Set to .your-domain.com for site-wide cookies
    | 'cookiePath'   =  Typically will be a forward slash
    | 'cookieSecure' =  Cookies will only be set if a secure HTTPS connection exists.
    |
     */
    'cookiePrefix' => '',
    'cookieDomain' => '',
    'cookiePath' => '/',
    'cookieSecure' => true,

    /*
    |--------------------------------------------------------------------------
    | Cross Site Request Forgery
    |--------------------------------------------------------------------------
    | Enables a CSRF cookie token to be set. When set to TRUE, token will be
    | checked on a submitted form. If you are accepting user data, it is strongly
    | recommended CSRF protection be enabled.
    |
    | 'antiCsrfTokenName' = The token name
    | 'antiCsrfCookieName' = The cookie name
    | 'antiCsrfExpire' = The number in seconds the token should expire.
     */
    'csrfProtection' =>  true,
    'antiCsrfTokenName' =>  'atomCsrfToken',
    'antiCsrfCookieName' =>  'atomCsrfCookie',
    'antiCsrfExpire' =>  60
];
