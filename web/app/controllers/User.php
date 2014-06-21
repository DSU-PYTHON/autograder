<?php

namespace controllers;

class User extends \Controller {
	
	protected $User = null;
	
	function __construct() {
		$this->User = \models\User::instance();
	}
	
	function signIn($base) {
		try {
			$User = $this->User;
			$userId = $base->get("POST.userid");
			$password = $base->get("POST.password");
			
			$userInfo = $User->findByIdAndPassword($userId, $password);
			if ($userInfo == null)
				throw new UserException("user_not_found", "The user/password pair was not found.");
			
			$this->setUserStatus($userInfo);
			
			if ($base->exists("SESSION.forgot_password"))
				$base->clear("SESSION.forgot_password");
			
			if ($base->exists("POST.redirect_hash")) {
				$redirect_uri = $base->get("POST.redirect_hash");
				if (strpos($redirect_uri, '#') === false) $redirect_uri = "";
			} else $redirect_uri = "";
			
			$base->reroute("/" . $redirect_uri);
			
		} catch (UserException $e) {
			$this->json_echo($e->toArray(), True);
		}
	}
	
	function logOut($base) {
		$this->voidUserStatus();
		$base->reroute('/');
	}
	
}
