<?php

/**
 * AtomMVC: Auth Class
 * atom/app/lib/Auth.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class Auth {

    public static function login($email, $password, &$user = null) {

        $authenticated = false;
        $credentials = static::getCredentials($email);

        if($credentials) {
            $authenticated = Hash::verify($password, $credentials[User::Field_Password]);

            if($authenticated) {
                $user = User::getByEmail($email);
                Session::value('user', $user->getEmail());
                Session::regen();
            }
        }

        return $authenticated;
    }

    public static function logout() {
        Session::clearAll();
        Session::regen();
        $redirect = Config::get('authentication', 'logoutRedirect');
        Response::redirect($redirect);
    }

    public static function accessGranted() {

        $accessGranted = false;
        $controller = Request::segment(0);
        $method = Request::segment(1);
        $permissions = static::permissions($controller, $method);

        if($permissions) {

            $permissionLevel = static::userPermissionLevel();
            $allow = $permissions['allow'];
            $deny = $permissions['deny'];
            $failedRedirect = $permissions['failedRedirect'];
            $authRedirect = $permissions['authRedirect'];

            // The user is authenticated if they meet one of the following criteria:
            //   a] They are in the "allow" list and they are not in the "deny" list.
            //   b] The "allow" list is empyt and they are not in the "deny" list.
            if((empty($allow) || in_array($permissionLevel, $allow)) && !in_array($permissionLevel, $deny)) {
                $accessGranted = true;
            }

            if($accessGranted === true && $authRedirect) {
                Response::redirect($authRedirect);
            } else if ($accessGranted !== true) {
                $redirect = $failedRedirect ?: 403;
                Response::redirect($redirect);
            }

        } else {
            $accessGranted = true;
        }

        return $accessGranted;
    }

    public static function permissions($controller, $method) {

        if(!$controller) {
            $controller = 'home';
        }

        $permissions = Config::get('permissions',$controller);
        $permissionDefaults = [
            'allow' => [],
            'deny' => [],
            'failedRedirect' => null,
            'authRedirect' => null
        ];
        $permissionSettings = array();

        if(!$method) {
            $method = isset($permissions['index']) ? 'index' : 'ALL';
        }

        if($permissions && isset($permissions[$method])) {

            foreach($permissionDefaults as $key => $value) {

                if(isset($permissions[$method][$key])) {
                    $permissionSettings[$key] = $permissions[$method][$key];
                } else {
                    $permissionSettings[$key] = $permissionDefaults[$key];
                }
            }
        }

        return $permissionSettings;
    }

    protected static function extractPermissions($key) {

    }

    public static function userPermissionLevel() {

        $userType = 0;

        if(Session::hasValue('user')) {
            $email = Session::value('user');
            $user = User::getByEmail($email);
            $userType = $user->get('user_type_id');
        }

        return $userType;
    }

    protected static function getCredentials($email) {
        return (new QueryBuilder(User::TableName))
            ->select([User::Field_Email, User::Field_Password])
            ->where(User::Field_Email, $email)
            ->first();
    }
}