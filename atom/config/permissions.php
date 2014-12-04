<?php

use Atom\UserType;
/**
 * AtomMVC: Permissions Config
 * atom/config/permissions.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 *
 */

return [

    /**
     * Format:
     *
     * 'url-controller-segment' => [
     *         'url-method-segment' => [
     *
     *             // allow|deny is an array of user_type_ids
     *             // use consts in UserType
     *             // 0 indicates anonymous users
     *             // To allow all non-anonymous users, simply "deny" => [UserType::Anonymous]
     *            'allow' => [5,6],
     *            'deny' => [0],
     *            'failedRedirect' => '/login',
     *            'authRedirect' => '/'
     *     ]
     * ]
     */

    //"dashboard" => [
    //    "activity" => [
    //        "deny" => [UserType::Anonymous]],
    //    "billing" => [
    //        "deny" => [UserType::Anonymous]],
    //    "bookmarks" => [
    //        "deny" => [UserType::Anonymous]],
    //    "create-aircraft" => [
    //        "deny" => [UserType::Anonymous]],
    //    "edit-aircraft" => [
    //        "deny" => [UserType::Anonymous]],
    //    "inventory" => [
    //        "deny" => [UserType::Anonymous]],
    //    "messages" => [
    //        "deny" => [UserType::Anonymous]],
    //    "preferences" => [
    //        "deny" => [UserType::Anonymous]],
    //    "profile" => [
    //        "deny" => [UserType::Anonymous]]]

    // *** Testing ***
    //'home' => [
    //    'ALL' => [
    //        'allow' => [1],
    //        'failedRedirect' => '/login'
    //    ]
    //],
    //'about' => [
    //    'ALL' => [
    //        'allow' => [1],
    //        'failedRedirect' => '/login'
    //    ]
    //],
    //'contact' => [
    //    'ALL' => [
    //        'allow' => [1],
    //        'failedRedirect' => '/account/login'
    //    ]
    //]
];