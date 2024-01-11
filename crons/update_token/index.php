<?php
//jwt
namespace jwt\JWT;
ini_set('post_max_size', '256M');
ini_set('memory_limit', '-1');
require '../../api/v1/jwt/JWT.php';
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
$expDate = strtotime("+13 day");
$users ="SELECT * FROM `users` WHERE (user_token_exp < '$currentDateTime')";
$usersData = mysqli_query($conn, $users) or die("error:  " . mysqli_error($conn)); 
if($usersData)
{
  while ($userInfo = mysqli_fetch_assoc($usersData)) {
$userId = $userInfo['user_id'];
$userPassword = $userInfo['user_password'];
$decodedPass = JWT::decode($userPassword, $serviceTokenPublic, array('RS256'));
$decoded_array = (array) $decodedPass;
foreach ($decoded_array as $name => $value) {
    if($name=="password"){
      $tokenPayload = array('redDate' => $currentDateTime, 'password'=> $value);
      $newToken = JWT::encode($tokenPayload, $serviceTokenPrivate, 'RS256');
       $updateToken = mysqli_query( $conn,  "UPDATE users SET user_token='$newToken' WHERE (user_id='$userId')");
       $updateLastUpdate = mysqli_query( $conn,  "UPDATE users SET user_token_last_update='$currentDateTime' WHERE (user_id='$userId')");
       $updateExp = mysqli_query( $conn,  "UPDATE users SET user_token_exp='$expDate' WHERE (user_id='$userId')");
    }
}
}
}
mysqli_close($conn);
?>