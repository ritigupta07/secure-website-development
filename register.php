<?php
// This page will be users to register their username and passwords
require_once 'login.php';
echo <<<_END
<html>
<head>
<title>PHP Form Upload</title>

<script type="text/javascript">
function validateUsername(field) {
    maxLen = 5;
    if (field == "") return "No Username was entered.\n"
    else if (field.length < maxLen)
		return "Usernames must be at least 5 characters.\n"
    else if (/[^a-zA-Z0-9_-]/.test(field))
        return "Only a-z, A-Z, 0-9, - and _ allowed in Usernames.\n"
    return ""
}

function validatePassword(field) {
    maxLen = 6;
    if (field == "") return "No Password was entered.\n"
    else if (field.length < maxLen)
        return "Passwords must be at least 6 characters.\n"
    return ""	
}

function validateEmail(email) {
if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email))
  {
    return "";
  }
    return "Invalid email.\n";
}

function validate(form) {
	fail=""
    fail += validateUsername(form.user_name.value);
	fail += validatePassword(form.password.value);
    fail += validateEmail(form.email.value);
    if (fail == "") {
		return true;
	}
    else { alert(fail); return false }
}
</script>
<noscript>
    Your browser doesn't support or has disabled JavaScript
</noscript>
</head>

<body>
<pre>

<form action="register.php" method="post" enctype="multipart/form-data" onsubmit="return validate(this)">
Username <input type="text" name="user_name">
Password <input type="password" name="password">
E-mail <input type="email" name="email">
<input type="submit" value="Submit">
</form>
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

function userExists($username, $conn) {
		$query = "SELECT * FROM user_credentials WHERE username='$username'";
		$result = $conn->query($query);
		if (!$result) die(mysql_fatal_error("OOPS !! ", $conn));
		else if ($result->num_rows) {
			$result->close();
			return true;
		}
		$result->close();
		return false;
}

// adds users to users database.
function add_user($conn) {
   $stmt = $conn->prepare("INSERT INTO user_credentials(username, password, email, salt1, salt2) VALUES(?,?,?,?,?)");
   $stmt->bind_param('sssss',$username, $token, $email, $salt1, $salt2);
   $username = get_post($conn, 'user_name');
   $password = get_post($conn, 'password');
   $email = get_post($conn, 'email');
   if(userExists($username, $conn)) {
		die(mysql_fatal_error("OOPS. User exists !! ", $conn));
   }
   $salt1 = uniqid(mt_rand(), true);
   $salt2 = uniqid(mt_rand(), true);
   $token = hash('ripemd128', "$salt1$password$salt2");
   $stmt->execute();
   $min = 0;
   if($stmt->affected_rows == $min) {
     // insert failed.
	  die(mysql_fatal_error("OOPS. Check credentials !! ", $conn));
   }
   $stmt->close();
}

function isValidUser($username) {
    $maxLen = 5;
    if(strlen($username) < $maxLen) {
        echo "Usernames must be atleast 5 characters.\n";
        return false;
    }
    for($i=0; $i<strlen($username); $i++) {
        if(!ctype_digit($username[$i]) && !ctype_alpha($username[$i]) && 
             $username[$i]!='-' && $username[$i] !='_') {
			echo "The username can contain English letters (capitalized or not), digits, and the characters '_' (underscore) and '-' (dash). Nothing else.";
            return false;
        }
    }
    return true;
}

function isValidEmail($email) {
   if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      echo "Invalid email address";
      return false;
   }
   return true; 
}

function isValidPassword($pwd) {
    $minLenPwd = 6;
    if(strlen($pwd)<$minLenPwd) {
        echo "The length of password should be atleast 10 characters.";
        return false;
    }
    return true;
}

if(isset($_POST['user_name']) && isset($_POST['password']) && isset($_POST['email'])) {
    if(isValidUser($_POST['user_name']) && isValidEmail($_POST['email'])) {
        $conn = new mysqli($hn, $un, $pw, $db);
        if ($conn->connect_error) die(mysql_fatal_error("OOPS !! ", $conn));
            add_user($conn);
        $conn->close();
    }
}
echo "</pre></body></html>";
?>
