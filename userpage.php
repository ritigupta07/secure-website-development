<?php
require_once 'login.php';
echo <<<_END
<html>
<head>
<title>PHP Form Upload</title>
</head>
<body>
<pre>
_END;

// give the option to select from training and testing options.
function displayUserAction() {
echo <<<_END
<form action="userpage.php" method="post" enctype="multipart/form-data">
<p>Please select action</p>
<input type="radio" name="action" value="train"> Training<br>
<input type="radio" name="action" value="test"> Testing<br>
<input type="submit" value="Submit">
</form>
<a href="logout.php">Logout</a>
_END;
}

// redirect to the appropriate option as per the option specified.
if(isset($_POST['action'])) {
    $action = $_POST['action'];
    if($action == "train") {
echo <<<_END
<script>
location.href = "trainPage.php";
</script>
<noscript>
    Your browser doesn't support or has disabled JavaScript
</noscript>
<a href="trainPage.php">trainPage</a> 
_END;
    } else {
echo <<<_END
<script>
location.href = "testModel.php";
</script>
<noscript>
    Your browser doesn't support or has disabled JavaScript
</noscript>
<a href="testModel.php">testModel</a> 
_END;
    }
}

function destroy_session_and_data() {
	$_SESSION = array();
	$prev = 2592000;
	setcookie(session_name(), '', time() - $prev, '/');
	session_destroy();
}

function different_user() {
	destroy_session_and_data();
	echo "Please <a href='main.php'>click here</a> to log in.";
}

session_start();
if (!isset($_SESSION['initiated3'])) {
	session_regenerate_id();
	$id = 1;
	$_SESSION['initiated3'] = $id;
}
if ($_SESSION['check'] != hash('ripemd128', $_SERVER['REMOTE_ADDR'] .$_SERVER['HTTP_USER_AGENT'])) {
	different_user();
}

if (isset($_SESSION['username'])) {
    displayUserAction();
} else {
	echo "Please <a href='main.php'>click here</a> to log in.";
}

echo "</pre></body></html>";
?>
