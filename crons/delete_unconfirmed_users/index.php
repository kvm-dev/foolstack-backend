<?php
include('../../api/v1/secret/secrets.php');

//database
// Create connection
$conn = mysqli_connect($servername, $userbdname, $userbdpassword, $database);
//date and time
$userdate = date("Y.m.d");
$usertime = date("H:i:s");

// Check connection

if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn,"utf8");

$currentDateTime = strtotime("now");
      
$users ="SELECT * FROM `users` WHERE ( is_verified = '0' AND email_confirm_code_exp < '$currentDateTime')";
$unconfirmedusers = mysqli_query($conn, $users) or die("error:  " . mysqli_error($conn)); 
if($unconfirmedusers)
{
  while ($unconfirmeduser = mysqli_fetch_assoc($unconfirmedusers)) {
$userId = $unconfirmeduser['user_id'];
$deleteUser = mysqli_query( $conn,  "DELETE FROM users WHERE (user_id ='$userId')");
}
}
mysqli_close($conn);
?>