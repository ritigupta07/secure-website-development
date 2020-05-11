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

// user specifies the model name and the csv training file.
function displayTrainForm() {
echo <<<_END
<form action="trainPage.php" method="post" enctype="multipart/form-data">
Model Name <input type="text" name="Name">
Select File<input type="file" name="filename" size="10">
<input type="submit" value="Submit">
</form>
<a href="logout.php">Logout</a>
_END;
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
if (!isset($_SESSION['initiated2'])) {
	session_regenerate_id();
	$id = 1;
	$_SESSION['initiated2'] = $id;
}
if ($_SESSION['check'] != hash('ripemd128', $_SERVER['REMOTE_ADDR'] .$_SERVER['HTTP_USER_AGENT'])) {
	different_user();
}

if(isset($_SESSION['username'])) {
	displayTrainForm();
} else {
	echo "Please <a href='main.php'>click here</a> to log in.";
}

if($_FILES) {
        $conn = new mysqli($hn, $un, $pw, $db);
        if ($conn->connect_error) die(mysql_fatal_error("OOPS !! ", $conn));
		$name = $_FILES["filename"]["name"];
		move_uploaded_file($_FILES["filename"]["tmp_name"], $name);
		if($_FILES["filename"]["type"] == "text/csv") {
    			$argument = './train.py '.$name; 
                $command = escapeshellcmd($argument);
                // lock temp param files to which the model training writes.
				$fh1 = fopen("./theta1_file_tmp.csv", 'w+') or die("Failed to open file");
				$fh2 = fopen("./theta2_file_tmp.csv", 'w+') or die("Failed to open file");
				if(flock($fh1, LOCK_EX) && flock($fh2, LOCK_EX)) {
					// call the python script to execute training with user specified csv training file.
                	$output = shell_exec($command);
	                echo $output;
					$mname = get_post($conn, 'Name');
                	$uname =sanitizeMySQL($conn, $_SESSION['username']);
	                $newName = $uname.".".$mname.".model.csv";
					$theta1File = "./theta1.".$newName;
					$theta2File = "./theta2.".$newName;
            	    rename('./theta1_file_tmp.csv', $theta1File);
                	rename('./theta2_file_tmp.csv', $theta2File);
	                //unclock temp param files
					flock($fh1, LOCK_UN);
					flock($fh2, LOCK_UN);
				
					// insert the model name and file paths in the database.
					$stmt = $conn->prepare("INSERT INTO model_information(username, modelname, modelfile1,modelfile2) VALUES(?,?,?,?)");
					$pfile1 = sanitizeMySQL($conn,$theta1File);
					$pfile2 = sanitizeMySQL($conn,$theta2File);
					$stmt->bind_param('ssss',$uname, $mname,$pfile1,$pfile2);
					$stmt->execute();
					if($stmt->affected_rows == 0) {
						$stmt->close();
						die(mysql_fatal_error("OOPS..", $conn));
					}
					$stmt->close();
                }
		} else {
				echo "Invalid File Type. Only csv files allowed.\n";
		}
        $conn->close();
}

echo "</pre></body></html>";
?>
