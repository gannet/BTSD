<?php
session_name ( "CCHMC_SOP" );
session_start ();
error_reporting ( E_ALL );
ini_set ( 'display_errors', true );
date_default_timezone_set ( 'America/New_York' );
require 'dbConnect.php';
class template {
	public $db;
	public $login_fail = false;
	public $message = "Central Login";
	public $user_name = null;
	public $user_mode = null;
	public $user_status = false;
	public $title = 'Bidirectional Traceable Document Developer';
	
	// private $jquery_vers = '2.0.0';
	private $jquery_vers = '1.7.1'; // HAD AN ISSUE WITH THIS VERSION ON requirements.php PAGE.
	private $jq_ui_vers = '1.8.16';
	
	/**
	 * *******************************************************************************
	 * Function: __construct()
	 * Description: Upon instantiation of class, sets up application db connection
	 * *******************************************************************************
	 */
	public function __construct() {
		
		// pull all documents and their content from the database
		
		// If page is any other than type.php and no document loaded then move to type.php
		if (basename ( $_SERVER ['PHP_SELF'] ) != 'type.php' && ! isset ( $_SESSION ['docID'] )) {
			
			header ( 'Location: type.php' );
			exit ();
		}
		
		// Open application database
		$db = new dbConnect ();
		$this->db = $db->connect ();
		
		// f logging out then destroy session and reset variables
		if (isset ( $_GET ['logout'] ))
			$this->revoke_clearance ();
		
		if (isset ( $_POST ['login_user'], $_POST ['login_pass'] )) {
			$this->login_fail = false;
			$this->auth_clearance ();
		}
		
		// if they were able to login, they're at least a CCHMC employee
		if (isset ( $_SESSION ['login_user'] )) {
			
			$this->user_name = $_SESSION ['login_user'];
			$this->app_access ();
			
			// if user found in database and not disabled then allow them to see page
			if (! $this->user_status) {
				$this->message = "Login not permitted: user_status=" . $this->user_status;
				$this->login_fail = true;
			}
			
			// echo "User authenticated as ".$this->user_name." with access level:".$this->user_mode;
		}
	} // END __construct()
	
	/**
	 * *******************************************************************************
	 * Function: auth_clearance()
	 * Description: Takes POST variables 'login_user' and 'login_pass' to authenticate
	 * the user.
	 * *******************************************************************************
	 */
	private function auth_clearance() {
		// ensure login as password is sanitized before sending to db
		$this->user_name = $this->db->escape_string ( $_POST ['login_user'] );
		$tmp_pass = $this->db->escape_string ( $_POST ['login_pass'] );
		
		// check LDAP for user. If succeeded then at least a cchmc user
		// if ($this->ldap_auth($this->user_name,$tmp_pass)) {
		
		$_SESSION ['login_user'] = $this->user_name;
		session_regenerate_id ();
		header ( 'Location: ' . $_SERVER ['PHP_SELF'] );
		exit ();
		// }
		// else {
		// $this->login_fail = true;
		
		// }
	} // END auth_clearance()
	
	/*
	 * Attempt LDAP bind. @return - value will be of type bool with value of 1 if username and password combo found
	 */
	private function ldap_auth($user, $pass) {
		if (! function_exists ( "ldap_connect" ))
			die ( 'Missing php5-ldap package' );
			
			// if (!$pass)
			// {
			// $this->message = "Central Login<BR><I><font size=2 color='Red'>Please provide password</font></I>";
			// return false;
			// }
			
		// Connect to ldap
		$ldap = ldap_connect ( $ldap_host ) or die ( 'Authentication system missing.' );
		
		// Bind with bmisop login
		$msg = ldap_bind ( $ldap, $ldap_rdn, $ldap_pass );
		
		// set search filter
		$filter = "(SamAccountName=" . $user . ")";
		
		// search ldap for user and return dn
		$usrdn = ldap_search ( $ldap, $ldap_rdn, $filter ) or die ( "Unable to perform search" );
		$entry = ldap_first_entry ( $ldap, $usrdn );
		
		$error = "Central Login<BR><I><font size=2 color='Red'>Username and password combination not found</font></I>";
		
		if (! $entry or ! $pass) {
			$this->message = $error;
			return false;
		}
		
		$dn = ldap_get_dn ( $ldap, $entry );
		
		// Now that we have the dn, use the dn to bind
		$msg = @ldap_bind ( $ldap, $dn, $pass );
		
		if (! $msg) {
			$this->message = $error;
			return false;
		}
		
		ldap_close ( $ldap );
		
		return $msg;
	} // END ldap_auth()
	
	/*
	 * resets auth-related members, resets session cookies
	 */
	private function revoke_clearance() {
		$this->user_name = null;
		
		// TODO: Setup user skills in mysql db
		// this->user_skills = array();
		$this->user_mode = null;
		
		unset ( $_SESSION ['login_user'] );
		$params = session_get_cookie_params ();
		setcookie ( session_name (), '', time () - 42000, $params ['path'], $params ['domain'], $params ['secure'], $params ['httponly'] );
		session_destroy ();
		session_regenerate_id ();
		header ( 'Location: ' . $_SERVER ['PHP_SELF'] );
		exit ();
	} // END revoke_clearance()
	
	/*
	 * Get user's application access. @return - if no user found then
	 */
	private function app_access() {
		// search db for user and return account type and user status;
		$usrQry = "SELECT acctType,status FROM tblUsers WHERE username = '" . $this->user_name . "';";
		
		$result = $this->db->query ( $usrQry );
		
		// Pull from assoc array data returned and set values
		$row = $result->fetch_object ();
		$this->user_mode = $row->acctType;
		$this->user_status = ($row->status == 'Enabled') ? true : false;
	} // END app_access()
	public function is_authorized($mode) {
		// FROM PAGE: check that user is enabled, has user_mode
		return ($this->user_status && $mode == $this->user_mode);
	} // END is_authorized()
	
	/*
	 * if page is part of admin section, return true
	 */
	private function admin_page() {
		if ('admin.php' == $_SERVER ['PHP_SELF']) {
			return true;
		}
		return false;
	}
	
	/*
	 * Displays the header of each page.
	 */
	public function display() {
		// All html below is called by display()
		?>
<!DOCTYPE html>
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title><?php echo $this->title ?></title>


<script src="js/jquery-<?php echo $this->jquery_vers ?>.min.js"></script>
<script src="js/jquery-ui-<?php echo $this->jq_ui_vers ?>.custom.min.js"></script>
<script src="js/jquery.ui.nestedSortable.js" type="text/javascript"></script>
<link rel="stylesheet"
	href="css/ui-lightness/jquery-ui-<?php echo $this->jq_ui_vers ?>.custom.css">
<style>
#workflow {
	width: 50em;
	margin: 0 auto
}
</style>

</head>
<body>



	<div id="login" class="ui-helper-hidden">
		<form action=<?php echo $_SERVER['PHP_SELF'];?> method="Post">
			<label for="login_user">Username:</label><input type='text'
				name='login_user'><br> <label for="login_pass">Password:</label><input
				type='password' name='login_pass'><br> <input type="submit"
				VALUE="Login" name='Login'>
		</form>
	</div>
<?php
		
		if (! isset ( $_SESSION ['login_user'] ))
			echo '<script type="text/javascript">$(document).ready(function(){$("#login").dialog({title:"' . $this->message . '",modal:true,minWidth:315,width:315,closeOnEscape: false});})</script>';
		
		if ($this->admin_page ()) {
			echo 'this is an admin page';
		} else {
			
			$active = ' ui-tabs-selected ui-state-active';
			$tabs = array (
					array (
							'/type.php',
							'Type' 
					),
					array (
							'/requirements.php',
							'Requirements' 
					),
					array (
							'/actions.php',
							'Actions' 
					),
					array (
							'/construct.php',
							'Construct' 
					),
					array (
							'/review.php',
							'Review' 
					),
					array (
							'/finalize.php',
							'Finalize' 
					) 
			);
			
			echo '		<div id="workflow" class="ui-tabs ui-widget ui-widget-content ui-corner-all">', "\n", '		 <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">', "\n";
			
			foreach ( $tabs as $tab ) {
				echo '<li class="ui-state-default ui-corner-top';
				if ($_SERVER ['PHP_SELF'] == $tab [0])
					echo $active;
				echo '"><a href="', $tab [0], '">', $tab [1], '</a></li>', "\n";
			}
			
			echo '</ul>', "\n";
			if (isset ( $_SESSION ['docTitle'] )) {
				echo "<div style='margin-top: 5px; padding: 0pt 0.7em;' class='ui-state-highlight ui-corner-all'>";
				echo "<span style='float: left; margin-right: 0.3em;' class='ui-icon ui-icon-info'></span>";
				echo "<strong>Active document: </strong>" . $_SESSION ['docTitle'] . "</div>";
			}
			echo '<a href="type.php?logout=true">Log Out</a>';
		}
	} // END display()
	public function footer() {
		$email = (isset ( $_SERVER ['SERVER_ADMIN'] ) && $_SERVER ['SERVER_ADMIN'] != '[no address given]') ? $_SERVER ['SERVER_ADMIN'] : 'michael.kuhlmann@cchmc.org';
		echo '</div><p style="text-align:center;"><i>If you\'re having any difficulty with this program, please contact <a href="mailto:', $email, '">system administrator</a>.<i></p>	 	 	
	 	</body>
	 	</html>';
	} // END footer()
} // END template

?>