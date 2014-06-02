<?php

namespace models;

class Assignment extends \Model {
	
	protected $assignments;
	
	function __construct() {
		parent::__construct();
		$this->assignments = json_decode(file_get_contents("data/assignments.json"), true);
	}
	
	function getAllAssignments() {
		return $this->assignments;
	}
	
}

