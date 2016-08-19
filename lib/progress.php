<?php
class progress {
	public $status = - 1;
	
	/**
	 * *******************************************************************************
	 * Function: __construct()
	 * Description: Upon instantiation of class, checks for document progression and
	 * returns the value to session.
	 * *******************************************************************************
	 */
	public function __construct($header) {
		// Set the current status based on checks from database
		$this->setStatus ( $header->db );
	}
	private function setStatus($db) {
		// check for document id. If so then allow the requirements tab
		if (! isset ( $_SESSION ['docID'] )) {
			$this->status = 1;
			return;
		}
		
		// Check for any requirements saved for current doc if so then allow actions tab
		$string = "SELECT id FROM tblDocRequirements WHERE di = '" . $_SESSION ['docID'] . "';";
		// var_dump($string);
		$result = $db->query ( $string );
		if ($result->num_rows == 0) {
			$this->status = 2;
			return;
		} // User should be directed to requirements tab at most
		
		var_dump ( $result );
		
		// Check for any requirements that don't have any corresponding actions
		$answer = implode ( ",", $result );
		$string = "SELECT id FROM tblDocActions WHERE ri IN(" . $answer . ");";
		var_dump ( $string );
		// $result = $db->query($string);
		// if (!$result) {$this->status = 3; exit();}//User should be directed to the actions tab at most
		
		// $this->status = -1;
	}
	public function moveTo() {
		switch ($this->status) {
			case 1 :
				header ( "Location: type.php" );
				break;
			case 2 :
				header ( "Location: requirements.php" );
				break;
			case 3 :
				header ( "Location: actions.php" );
				break;
			case 4 :
				header ( "Location: construct.php" );
				break;
			case 5 :
				header ( "Location: finalize.php" );
				break;
			// case -1:
			// header("Location: type.php");
			// break;
		}
	}
}
?>
