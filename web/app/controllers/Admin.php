<?php

namespace controllers;

class Admin extends \Controller {
	
	const PASSWORD_LEN = 12;
	
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
	
	function addAnnouncement($base) {
		$user_info = $this->verifyAdminPermission();
	}
	
	function editAnnouncement($base) {
		
	}
	
	function deleteAnnouncement($base) {
		
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
		$Assignment = \models\Assignment::instance();
		
		$base->set('me', $user_info);
		$base->set('assignment_list', $Assignment->getAllAssignments());
		$this->setView('admin/ajax_assignments.html');
	}
	
	function addAssignment($base) {
	}
	
	function editAssignment($base) {
	}
	
	function deleteAssignment($base) {
	}
	
	function regradeSubmission($base) {
	}
	
	function querySubmission($base) {
	}
	
	function showServerPage($base) {
		$user_info = $this->verifyAdminPermission();
		$base->set('me', $user_info);
		$this->setView('admin/ajax_server.html');
	}
	
	function addServer($base) {
	}
	
	function editServer($base) {
	}
	
	function deleteServer($base) {
	}
	
	function checkServer($base) {
	}
	
	function showStatusPage($base) {
		$user_info = $this->verifyAdminPermission();
		$base->set('me', $user_info);
		$this->setView('admin/ajax_status.html');
	}
	
	function showUsersPage($base) {
		$User = \models\User::instance();
		
		$user_info = $this->verifyAdminPermission();
		$roles_info = $User->getRoleTable();
		$base->set('roles_info', $roles_info);
		$base->set('me', $user_info);
		$this->setView('admin/ajax_users.html');
	}
	
	function updateUser($base) {
		$User = \models\User::instance();
		
		$user_info = $this->verifyAdminPermission();
		
		$action = $base->get('POST.action');
		if ($action == 'query') {
			$id_pattern = $base->get('POST.name_pattern');
			$role_pattern = $base->get('POST.role_pattern');
			$result = $User->matchByPatterns($id_pattern, $role_pattern);
			if (count($result) == 0)
				$this->json_echo($this->getError('empty_result', 'There is no user matching the given patterns.'));
			else {
				$base->set('user_list', $result);
				$data = \View::instance()->render('admin/ajax_user_rows.html');
				$this->json_echo($this->getSuccess($data));
			}
		} else if ($action == 'add') {
			$role_name = $base->get('POST.role');
			if ($User->findRoleByName($role_name) == null)
				$this->json_echo($this->getError('invalid_data', 'The role "' . $role_name . '" is not defined.'));
			$user_list = str_replace("\r", "", $base->get('POST.user_list'));
			$users = explode("\n", $user_list);
			
			//TODO: here we are assuming the length is large enough, which is bad.
			$password_pool = $User->getPasswordPool(count($users), static::PASSWORD_LEN);
			
			$c = 0;
			$skip_list = array();
			
			foreach ($users as $i => $name) {
				if (!empty($name)) {
					// skip existing users
					if ($User->findById($name) != null) {
						$skip_list[] = $name;
					} else {
						$User->addUser($name, $role_name, $password_pool[$i]);
						++$c;
					}
				}
			}
			
			if (count($skip_list) > 0) $skip_str = ' Skipped existing users: ' . implode(', ', $skip_list) . '.';
			else $skip_str = '';
			
			if ($User->saveUserTable() === false) {
				$this->json_echo($this->getError('write_failure', "Failed to write data to \"" . realpath($base->get("DATA_PATH") . "users.json") . "\"."));
			}
			
			$this->json_echo($this->getSuccess('Added ' . $c . ' user(s) to role "' . $role_name . '".' . $skip_str));
		
		} else if ($action == 'delete') {
			$users = $base->get('POST.users');
			foreach ($users as $name => $item)
				if (array_key_exists('selected', $item)) $User->deleteUserById($name);
			
			if ($User->saveUserTable() === false) {
				$this->json_echo($this->getError('write_failure', "Failed to write data to \"" . realpath($base->get("DATA_PATH") . "users.json") . "\"."));
			}
			
			$this->json_echo($this->getSuccess('Successfully deleted the selected user(s).'));
		} else if ($action == 'change_role') {
			$role_name = $base->get('POST.role');
			if ($User->findRoleByName($role_name) == null)
				$this->json_echo($this->getError('invalid_data', 'The role "' . $role_name . '" is not defined.'));
			
			$users = $base->get('POST.users');
			foreach ($users as $name => $item) {
				if (array_key_exists('selected', $item)) {
					$user_info = $User->findById($name);
					if ($user_info == null || $user_info['role']['name'] == $role_name) continue;
					$User->editUser($user_info, null, $role_name, null);
				}
			}
			
			if ($User->saveUserTable() === false) {
				$this->json_echo($this->getError('write_failure', "Failed to write data to \"" . realpath($base->get("DATA_PATH") . "users.json") . "\"."));
			}
			
			$this->json_echo($this->getSuccess('Successfully updated the role of the selected user(s).'));
				
		} else if ($action == 'reset_password') {
			$users = $base->get('POST.users');
			$password_pool = $User->getPasswordPool(count($users), static::PASSWORD_LEN);
			$i = 0;
			foreach ($users as $name => $item) {
				if (array_key_exists('selected', $item)) {
					$user_info = $User->findById($name);
					if ($user_info != null)
						$User->editUser($user_info, null, null, $password_pool[$i++]);
				}
			}
			
			if ($User->saveUserTable() === false) {
				$this->json_echo($this->getError('write_failure', "Failed to write data to \"" . realpath($base->get("DATA_PATH") . "users.json") . "\"."));
			}
			
			$this->json_echo($this->getSuccess('Successfully generated new password for the selected user(s).'));
		} else if ($action == 'update') {
			$users = $base->get('POST.users');
			$skip_list = array();
			$i = 0;
			
			foreach ($users as $name => $item) {
				if (array_key_exists('selected', $item) && array_key_exists('new_name', $item) && !empty($item['new_name']) && $item['new_name'] != $name) {
					$user_info = $User->findById($name);
					if ($user_info != null) {
						$new_user_info = $User->findById($item['new_name']);
						if ($new_user_info != null) $skip_list[] = $item['new_name'];
						else {
							$User->editUser($user_info, $item['new_name'], null, null);
							++$i;
						}
					}
				}
			}
			
			if (count($skip_list) > 0) $skip_str = ' The following ids already exist and cannot be renamed to: ' . implode(', ', $skip_list) . '.';
			else $skip_str = '';
			
			if ($User->saveUserTable() === false) {
				$this->json_echo($this->getError('write_failure', "Failed to write data to \"" . realpath($base->get("DATA_PATH") . "users.json") . "\"."));
			}
			
			$this->json_echo($this->getSuccess('Successfully renamed ' . $i . ' users.' . $skip_str));
			
		} else if ($action == 'send_email') {
			$subject = $base->get('POST.subject');
			$body = $base->get('POST.body');
			$users = $base->get('POST.users');
			$i = 0;
			
			if (empty($subject) || empty($body))
				$this->json_echo($this->getError('empty_fields', "Subject and body should not be empty."));
			
			foreach ($users as $name => $item) {
				if (array_key_exists('selected', $item)) {
					$user_info = $User->findById($name);
					if ($user_info == null) continue;
					$mail_subject = $User->replaceTokens($user_info, $subject);
					$mail_body = $User->replaceTokens($user_info, $body);
					
					$mail = new \models\Mail();
					$mail->addTo($name . $base->get('USER_EMAIL_DOMAIN'), $name);
					$mail->setFrom($base->get('COURSE_ADMIN_EMAIL'), $base->get('COURSE_ID_DISPLAY') . ' No-Reply');
					$mail->setSubject($mail_subject);
					$mail->setMessage($mail_body);
					$mail->send();
					++$i;
				}
			}
			
			$this->json_echo($this->getSuccess('Successfully sent email to ' . $i . ' user(s).'));
			
		} else 
			$this->json_echo($this->getError('undefined_action', 'The action you are performing is not defined.'));
	}
	
	function updateRole($base) {
		$user_info = $this->verifyAdminPermission();
		
		$User = \models\User::instance();
		$role_data = array();
		$current_roles = $base->get('POST.current');
		$new_role = $base->get('POST.new');
		
		foreach ($current_roles as $i => $role) {
			
			if (array_key_exists('delete', $role)) continue;
			
			if (!array_key_exists('key', $role) || !array_key_exists('display', $role))
				$this->json_echo($this->getError('invalid_data', 'ID and name are required fields.'));
			
			if (!array_key_exists('submit_priority', $role) || !is_numeric($role['submit_priority']))
				$this->json_echo($this->getError('invalid_data', 'Priority should be an integer value.'));
			
			$role_data[$role['key']] = $User->sanitizeRoleEntry($role);
		}
		
		if (array_key_exists('key', $new_role) && !empty($new_role['key'])) {
			if (!array_key_exists('display', $new_role) || empty($new_role['display']) || array_key_exists($new_role['key'], $role_data))
				$this->json_echo($this->getError('invalid_data', 'To add a new role, please provide an unused ID and a non-empty name.'));
			
			$role_data[$new_role['key']] = $User->sanitizeRoleEntry($new_role);		
		}
		
		if ($User->saveRoleTable($role_data) === false) {
			$this->json_echo($this->getError('write_failure', "Failed to write data to \"" . realpath($base->get("DATA_PATH") . "roles.json") . "\"."));
		}
		
		$this->json_echo($this->getSuccess('Successfully saved role data.'));
	}
	
}
