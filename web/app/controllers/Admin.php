<?php

namespace controllers;

class Admin extends \Controller {
	
	private function verifyAdminPermission($show_json_error = true) {
		$user_info = $this->getUserStatus();
		if ($user_info == null || !$user_info["role"]["permissions"]["manage"])
			if ($show_json_error)
				$this->json_echo(array("error" => "permission_denied", "error_description" => "You cannot access admin panel."), true);
			else $this->base->reroute("/");
		
		return $user_info;
	}
	
	function showAdminHomepage($base) {
		$user_info = $this->verifyAdminPermission(false);
		$base->set('me', $user_info);
		$this->setView('admincp.html');
	}
	
	function showAnnouncementsPage($base) {
		$user_info = $this->verifyAdminPermission();
		$Rss = new \models\Rss("data/feed.xml");
		$base->set("announcements", $Rss->get_items());
		$base->set('me', $user_info);
		$this->setView('admin/ajax_announcements.html');
	}
	
	function showAssignmentPage($base) {
		$user_info = $this->verifyAdminPermission();
		$base->set('me', $user_info);
		$this->setView('admin/ajax_assignments.html');
	}
	
	function showServerPage($base) {
		$user_info = $this->verifyAdminPermission();
		$base->set('me', $user_info);
		$this->setView('admin/ajax_server.html');
	}
	
	function showStatusPage($base) {
		$user_info = $this->verifyAdminPermission();
		$base->set('me', $user_info);
		$this->setView('admin/ajax_status.html');
	}
	
	function showUsersPage($base) {
		$user_info = $this->verifyAdminPermission();
		$base->set('me', $user_info);
		$this->setView('admin/ajax_users.html');
	}
	
}
