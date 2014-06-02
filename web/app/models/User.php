<?php

namespace models;

class User extends \Model {
	
	protected $users;
	protected $roles;
	
	function __construct() {
		parent::__construct();
		$this->users = json_decode(file_get_contents("data/users.json"), true);
		$this->roles = json_decode(file_get_contents("data/roles.json"), true);
	}
	
	function findById($id) {
		foreach ($this->users as $rolename => $members) {
			if (array_key_exists($id, $members))
				return array(
					"userid" => $id,
					"role" => array("name" => $rolename, "permissions" => $this->roles[$rolename]),
					"password" => $members[$id]
				);
		}
		return null;
	}
	
	function findByIdAndPassword($id, $password) {
		$userinfo = $this->findById($id);
		
		if ($userinfo != null && $password == $userinfo["password"])
			return $userinfo;
		return null;
	}
	
}

