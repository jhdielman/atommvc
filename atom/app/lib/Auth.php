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
	
	public static function login($username, $password) {
		
		$authenticated = false;
		$credentials = static::getCredentials($username);
		
		if($credentials) {
			
			$authenticated = Hash::verify($password, $credentials['password']);
			
			if($authenticated) {
				$user = User::findByUsername($username);
				Session::value('user', $user->value('email'));
				Session::regen();
			}
		}
		
		return $authenticated;
	}
	
	public static function logout() {
		Session::clear('user');
		Session::regen();
		$redirect = Config::get('authentication', 'logoutRedirect');
		Request::redirect($redirect);
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
			
			if(in_array($permissionLevel, $allow) && !in_array($permissionLevel, $deny)) {
				$accessGranted = true;
			}
			
			if($accessGranted === true && $authRedirect) {
				Request::redirect($authRedirect);
			} else if ($accessGranted !== true) {
				$redirect = $failedRedirect ?: 403;
				Request::redirect($redirect);
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
			$username = Session::value('user');
			$user = User::findByUsername($username);
			$userType = $user->value('user_type_id');
		}
		
		return $userType;
	}
	
	protected static function getCredentials($username) {
		return (new QueryBuilder('users'))
			->select(['email','password'])
			->where('email', '=', $username)
			->first();
	}
}