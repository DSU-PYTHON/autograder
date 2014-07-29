<?php

namespace models;

class Assignment extends \Model {
	
	protected $assignments;
	protected $baseDir;
	
	function __construct() {
		parent::__construct();
		$this->assignments = json_decode(file_get_contents($this->Base->get("DATA_PATH") . "/assignments.json"), true);
	}
	
	function getAllAssignments() {
		return $this->assignments;
	}
	
	function getDefaultAssignmentData() {
		return array(
			'display' => '',
			'start' => '',
			'close' => '',
			'quota_strategy' => 'daily',
			'quota_amount' => 15,
			'submit_filetype' => 'c',
			'submit_filesize' => 10240,
			'submit_notes' => '',
			'max_score' => 100,
			'grader_script' => '/',
			'grader_tar' => ''
		);
	}
	
	function findById($key) {
		if (array_key_exists($key, $this->assignments)) {
			$data = $this->assignments[$key];
			$data["id"] = $key;
			return $data;
		}
		return null;
	}
	
	function addAssignment($id, $data) {
		if (array_key_exists('id', $data)) unset($data['id']);
		$this->assignments[$id] = $data;
		return count($this->assignments);
	}
	
	function editAssignment($id, $data) {
		if (array_key_exists('id', $data)) unset($data['id']);
		$this->assignments[$id] = $data;
	}
	
	function deleteAssignment($id) {
		if (array_key_exists($id, $this->assignments)) {
			unset($this->assignments[$id]);
			return true;
		}
		return false;
	}
	
	function saveAssignments($table = null) {
		if ($table == null) $table = $this->assignments;
		return @file_put_contents($this->Base->get("DATA_PATH") . "assignments.json", json_encode($table), LOCK_EX);
	}
	
	function setBaseDir($d) {
		$this->baseDir = d;
	}
	
	/**
	 * Fetch all the submissions of a user to an assignment.
	 * 
	 * 
	 */
	function getAllSubmissionsOf($userId, $assignmentId) {
		$result = $this->query("SELECT * FROM submissions WHERE user_id=:uid AND assignment_id=:aid ORDER BY date_created DESC", array(
			':uid' => $userId,
			':aid' => $assignmentId
		));
		return $result;
	}
	
	/**
	 * Fetch the info of a submission record given its ID.
	 *
	 * @param	$sid: the ID of a submission record, corresponding to the 'id' field
	 * 		      of the 'submissions' table in the database.
	 * 
	 * @return	a row in the 'submissions' table whose id equals $sid; otherwise, null.
	 */
	function findSubmissionById($sid) {
		$result = $this->query("SELECT * FROM submissions WHERE id=?", $sid);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function findSubmissionByPath($p) {
		$result = $this->query("SELECT * FROM submissions WHERE file_path=?", $p);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	/**
	 * Update a submission record in database given the submission data array.
	 * 
	 * @param	$s: an array previously returned by a findSubmission* function.
	 */
	function updateSubmission(&$s) {
		$this->query("UPDATE submissions SET " . 
				"user_id=:user_id, " . 
				"assignment_id=:assignment_id, " .
				"file_path=:file_path, " . 
				"status=:status, " . 
				"date_updated=NOW(), " . 
				"grade=:grade, " .
				"grade_adjustment=:grade_adjustment, " .
				"grade_detail=:grade_detail, " .
				"grader_formal_log=:grader_formal_log, " . 
				"grader_internal_log=:grader_internal_log, " . 
				"web_internal_log=:web_internal_log " . 
				"WHERE id=:id LIMIT 1;", 
			array(
				":id" => $s["id"],
				":user_id" => $s["user_id"],
				":assignment_id" => $s["assignment_id"],
				":file_path" => $s["file_path"],
				":status" => $s["status"],
				":grade" => $s["grade"],
				":grade_adjustment" => $s["grade_adjustment"],
				":grade_detail" => $s["grade_detail"],
				":grader_formal_log" => $s["grader_formal_log"],
				":grader_internal_log" => $s["grader_internal_log"],
				":web_internal_log" => $s["web_internal_log"]
			)
		);
	}
	
	function addLog(&$s, $log_str) {
		$s["web_internal_log"] = $s["web_internal_log"] . "[" . date("c") . "] " . $log_str . "\n";
	}
	
	/**
	 * Given a list of submissions (queried from db), count them by date, week, etc.
	 *
	 * @param	$submissions: a query result from submission history. 
	 * 		              It must contain a key named "date_created" for evaluating.
	 * @param	$assignment_info: the assignment information array. If not null,
	 * 		                  function will calculate the remaining submission chances.
	 * 		$assignment_info["quota_strategy"]: one of 'daily', 'weekly', 'total'.
	 * 		$assignment_info["quota_amount"]: the quota amount.
	 * 
	 * @return	An array with keys 'today', 'this_week", 'total', whose values are the
	 * 		counts within their time frames.
	 * 		If $strategy is not null, the key named 'remaining' will be given.
	 */
	function countSubmissions($submissions, $assignment_info = null) {
		$count = array(
			"today" => 0,
			"this_week" => 0,
			"total" => 0
		);
		foreach ($submissions as $item) {
			$t = strtotime($item["date_created"]);
			if ($t > strtotime("yesterday")) {
				$count["today"]++;
			}
			if ($t > strtotime("Monday this week")) {
				$count["this_week"]++;
			}
			$count["total"]++;
		}
		$strategy = $assignment_info["quota_strategy"];
		if ($strategy != null) {
			if ($strategy == "daily") {
				$count["remaining"] = $assignment_info["quota_amount"] - $count["today"];
			} else if ($strategy == "weekly") {
				$count["remaining"] = $assignment_info["quota_amount"] - $count["this_week"];
			} else if ($strategy == "total") {
				$count["remaining"] = $assignment_info["quota_amount"] - $count["total"];
			}
		}
		return $count;
	}
	
	/**
	 * Save the submitted file to disk.
	 * 
	 * @param	$user_info: the array of the submitter (returned by User::findById).
	 * @param	$assignment_info: the data array of the assignment (returned by findById).
	 * 
	 * @return	an int value if the file is successfully saved to submission pool;
	 * 		otherwise return one string of 'file_too_large', 'empty_ext_name', 'invalid_ext_name', and 'upload_error'
	 * 		to indicate the reason.
	 */
	function saveSubmission($user_info, $assignment_info) {
		
		$Web = \Web::instance();
		$overwrite = true;
		$slug = true;
		$count = 0;
		$error_description = "unknown_error";
		
		$files = $Web->receive(
			function($file) use ($user_info, $assignment_info, &$count, &$error_description) {
				/* $file looks like:
					array(5) {
						["name"] =>     string(19) "csshat_quittung.png"
						["type"] =>     string(9) "image/png"
						["tmp_name"] => string(14) "/tmp/php2YS85Q"
						["error"] =>    int(0)
						["size"] =>     int(172245)
						}
					and $file['name'] already contains the slugged name
				*/
				
				if ($file["error"] > 0) {
					$error_description = "upload_error";
					return false;
				}
				
				// accept at most ONE file per submission request
				if ($count > 0) return false;
				
				// if there is file size limit, check it
				if (array_key_exists("submit_filesize", $assignment_info) && $file["size"] > $assignment_info["submit_filesize"]) {
					$error_description = "file_too_large";
					return false;
				}
				
				$file_ext = pathinfo($file["name"], PATHINFO_EXTENSION);
				// files are required to have an extension name, and thus names like 'hello' will be refused
				if ($file_ext == "") {
					$error_description = "empty_ext_name";
					return false;
				}
				
				// there is file extension name limit, check it
				if (array_key_exists("submit_filetype", $assignment_info)) {
					$accepted_ext = "," . str_replace(" ", "", $assignment_info["submit_filetype"]) . ",";
					$accepted_ext = str_replace(",,", ",", $accepted_ext);
					if (strpos($accepted_ext, "," . $file_ext . ",") === false) {
						$error_description = "invalid_ext_name";
						return false;
					}
				}
				
				// move the file from php tmp to upload tmp
				++$count;
				return true;
			},
			$overwrite,
			$slug
		);
		
		// $files is an array of filename-status pairs
		$record = null;
		foreach ($files as $name => $status) {
			if ($status) {
				$path = dirname($name) . "/history/";
				$file_ext = pathinfo($name, PATHINFO_EXTENSION);
				$file_name_new = "archive." . date('c') . "." . $file_ext;
				// 'history' is safe because $name must have a '.' in the file name
				if (!file_exists($path)) mkdir($path, 0777);
				rename($name, $path . $file_name_new);
				copy($path . $file_name_new, $path . "../latest.archive");
				
				// add to database
				$this->query(
					"INSERT INTO submissions (user_id, assignment_id, file_path, status, date_created, date_updated) " .
					"VALUES (:user_id, :assignment_id, :file_path, 'submitted', NOW(), NOW()); ",
					array(
						':user_id' => $user_info["user_id"],
						':assignment_id' => $assignment_info["id"],
						':file_path' => $path . $file_name_new
					)
				);
				$record = $this->findSubmissionByPath($path . $file_name_new);
			}
		}
		
		if ($count == 1) return $record;
		return $error_description;
	}
	
	/**
	 * Fetch all submission records that satisfy the $cond.
	 * 
	 */
	function findSubmissions($cond) {
		$sql_cond = array();
		
		if (array_key_exists(':user_id_pattern', $cond)) {
			$sql_cond[] = "user_id LIKE :user_id_pattern";
			$cond[':user_id_pattern'] = $this->toSqlWildcard($cond[':user_id_pattern']);
		}
		
		if (array_key_exists(':date_created_start', $cond))
			$sql_cond[] = "date_created >= :date_created_start";
		
		if (array_key_exists(':date_updated_start', $cond))
			$sql_cond[] = "date_updated >= :date_updated_start";
		
		if (array_key_exists(':date_created_end', $cond))
			$sql_cond[] = "date_created <= :date_created_end";
		
		if (array_key_exists(':date_updated_end', $cond))
			$sql_cond[] = "date_updated <= :date_updated_end";
		
		if (array_key_exists(':grade_max', $cond))
			$sql_cond[] = "grade <= :grade_max";
		
		if (array_key_exists(':grade_min', $cond))
			$sql_cond[] = "grade >= :grade_min";
		
		if (array_key_exists(':assignment_id_set', $cond)) {
			$sql_cond[] = "assignment_id IN (" . '"' . implode('","', $cond[':assignment_id_set']) . '"' . ")";
			unset($cond[':assignment_id_set']);
		}
		
		if (array_key_exists(':status_set', $cond)) {
			$sql_cond[] = "status IN (" . '"' . implode('","', $cond[':status_set']) . '"' . ")";
			unset($cond[':status_set']);
		}
		
		if (count($sql_cond) > 0) $where = ' WHERE ' . implode(' AND ', $sql_cond);
		else $where = '';
		
		$sql = "SELECT * FROM submissions" . $where;
		
		return $this->query($sql, $cond);
	}
	
	/**
	 * Remove the submission record from database, but do not remove the files.
	 */
	function deleteSubmission($id) {
		$this->query("DELETE FROM submissions WHERE id=? LIMIT 1;", $id);
	}
	
	function toSqlWildcard($str) {
		$str = str_replace('*', '%', $str);
		$str = str_replace('?', '_', $str);
		$str = str_replace('"', '\"', $str);
		return $str;
	}
	
	function isValidIdentifier($str) {
		return preg_match("/\\s/", $str) == 0;
	}
	
	function isValidFilePath($str) {
		return is_file($str);
	}
}
