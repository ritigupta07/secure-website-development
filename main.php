<?php
require_once 'login.php';
echo <<<_END
<html>
<head>
<title>PHP Form Upload</title>
</head>
<body>
<pre>

<form action="main.php" method="post" enctype="multipart/form-data">
  Username <input type="text" name="user_name">
  Password <input type="password" name="password">
<input type="submit" value="Login">
</form>

<a href="logout.php">Logout</a>

<br>

<button onclick="redirect()">Register</button>

<script>
function redirect() {
  location.href = "register.php";
}
</script>

<noscript>
	Your browser doesn't support or has disabled JavaScript
</noscript>

<a href="register.php">Register</a> 
_END;

function mysql_fatal_error($msg, $conn) {
echo <<<_END
    <p> $msg </p>
_END;
}

function sanitizeString($var) {
		$var = stripslashes($var);
		$var = strip_tags($var);
		$var = htmlentities($var);
		return $var;
}

function sanitizeMySQL($conn, $var){
		$var = $conn->real_escape_string($var);
        $var = sanitizeString($var);
        return $var;
}

function get_post($conn, $var){
		return sanitizeMySQL($conn, $_POST[$var]);
}

function validateUser($username, $password, $conn) {
		$un_temp = sanitizeMySQL($conn, $username);
		$pw_temp = sanitizeMySQL($conn, $password);
		$query = "SELECT * FROM user_credentials WHERE username='$un_temp'";
		$result = $conn->query($query);
		if (!$result) die(mysql_fatal_error("OOPS !! ", $conn));
		else if ($result->num_rows) {
			$row = $result->fetch_array(MYSQLI_NUM);
			$result->close();
            $indx = 3;
			$salt1 = $row[$indx];
            $indx = 4;
			$salt2 = $row[$indx];
			$token = hash('ripemd128', "$salt1$pw_temp$salt2");
            $indx = 1;
			if ($token == $row[$indx]) {
				echo "$row[0] : Hi $row[0], you are now logged in\n";
				return true;
			} else {
					die(mysql_fatal_error("Invalid username/password combination", $conn));
			}
		} else {
				$result->close();
				die(mysql_fatal_error("Invalid username/password combination", $conn));
		}
		return false;
}

if(isset($_POST['user_name']) && isset($_POST['password'])) {
    $conn = new mysqli($hn, $un, $pw, $db);
    if ($conn->connect_error) die(mysql_fatal_error("OOPS !! ", $conn));
    if(validateUser($_POST['user_name'], $_POST['password'], $conn)) {
        session_start();
		if (!isset($_SESSION['initiated0'])) {
           session_regenerate_id();
           $id = 1;
           $_SESSION['initiated0'] = $id;
        }
        $limit = 60*60*24;
        ini_set('session.gc_maxlifetime', $limit);
        $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'] .$_SERVER['HTTP_USER_AGENT']);
        $_SESSION['username'] = get_post($conn, 'user_name');
echo <<<_END
<script>
location.href = "userpage.php";
</script>
<noscript>
    Your browser doesn't support or has disabled JavaScript
</noscript>
<a href="userpage.php">UserPage</a> 
_END;
    }
    $conn->close();
}

echo "</pre></body></html>";
?>
