<?php
session_start();
if (!isset($_SESSION['initiated'])) {
	session_regenerate_id();
    $id = 1;
	$_SESSION['initiated'] = $id;
}

function destroy_session_and_data() {
	$_SESSION = array();
	$prev = 2592000;
	setcookie(session_name(), '', time() - $prev, '/');
	session_destroy();
}
destroy_session_and_data();
header('location:main.php');
echo "LOGGED OUT !!";
?>
