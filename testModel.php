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

// adds the option values of user specidied models from the database.
// Also added the option to select model.
function displayTestForm($conn) {
    $username = sanitizeString($_SESSION['username']);
    $un_temp = sanitizeMySQL($conn, $username);
    $query = "SELECT modelname FROM model_information WHERE username='$un_temp'";
    $result = $conn->query($query);
echo <<<_END
<form action="testModel.php" method="post" enctype="multipart/form-data">
<select name="Model_Name">
<option value="default">default</option>
_END;
    if (!$result) die(mysql_fatal_error("OOPS !!", $conn));
    else if ($result->num_rows) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $modelname = $row['modelname'];
echo <<<_END
<option value=$modelname>$modelname</option>
_END;
        }
        $result->close();
// user specifies the test csv file along with the model option.
echo <<<_END
</select>
Select File<input type="file" name="filename" size="10">
<input type="submit" value="Submit">
</form>
_END;
    } else {
        $result->close();
        die(mysql_fatal_error("Missing Models",$conn));
    }
echo <<<_END
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
if (!isset($_SESSION['initiated1'])) {
	session_regenerate_id();
	$id = 1;
	$_SESSION['initiated1'] = $id;
}

if ($_SESSION['check'] != hash('ripemd128', $_SERVER['REMOTE_ADDR'] .$_SERVER['HTTP_USER_AGENT'])) {
	different_user();
}

$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) die(mysql_fatal_error("OOPS !! ", $conn));

if(isset($_SESSION['username'])) {
	displayTestForm($conn);
} else {
	echo "Please <a href='main.php'>click here</a> to log in.";
}

if($_FILES) {
		$name = $_FILES["filename"]["name"];
		move_uploaded_file($_FILES["filename"]["tmp_name"], $name);
		if($_FILES["filename"]["type"] == "text/csv") {
            if(isset($_POST['Model_Name'])) {
                //default parameter files.
				$paramFile1 = './theta1_gold.csv';
				$paramFile2 = './theta2_gold.csv';

                // if some other model specified, 
				// select the file paths from the models database.
				if(strcmp($_POST['Model_Name'],"default")!=0) {

					$un_temp = sanitizeMySQL($conn, $_SESSION['username']);
					$modelname_temp = sanitizeMySQL($conn,$_POST['Model_Name']);
					$query = "SELECT * FROM model_information WHERE username='$un_temp' and modelname='$modelname_temp'";
					$result = $conn->query($query);
					if (!$result) die(mysql_fatal_error("OOPS !! ", $conn));
					else if ($result->num_rows) {
						$row = $result->fetch_array(MYSQLI_NUM);
						$result->close();
                        $indx = 3;
						$paramFile1 = $row[$indx];
                        $indx = 4;
						$paramFile2 = $row[$indx];
					} else {
						$result->close();
						die(mysql_fatal_error("OOPS...",$conn));
					}
				}

				// python code to execute the predicting using the specified model.
				$argument = './predict.py '.$name.' '.$paramFile1.' '.$paramFile2;
                $command = escapeshellcmd($argument);
                $output = shell_exec($command);
                echo $output; 
            }
		} else {
				echo "Invalid File Type. Only csv files allowed.\n";
		}
}

$conn->close();
echo "</pre></body></html>";
?>
