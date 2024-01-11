<?php
//jwt
namespace jwt\JWT;
ini_set('post_max_size', '256M');
ini_set('memory_limit', '-1');
require '../../jwt/JWT.php';
include('../../secret/secrets.php');


$email = "";
$password = "";

// Retrieve the raw POST data
$jsonData = file_get_contents('php://input');
// Decode the JSON data into a PHP associative array
$data = json_decode($jsonData, true);
// Check if decoding was successful
if ($data !== null) {
   if(empty($data['email']) || empty($data['password'])){
   // JSON decoding failed
   http_response_code(400); // Bad Request
   $row = array("sucess"=> false, "errorMsg"=> 'Invalid JSON Data');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
}
else{
   // Access the data and perform operations
   $email = $data['email'];
   $password = $data['password'];
}
} else {
   // JSON decoding failed
   http_response_code(400); // Bad Request
   $row = array("sucess"=> false, "errorMsg"=> 'Invalid JSON Data');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
}

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

//headers validation
$platformValid = false;
$userTypeValid = false;
$versionValid = false;

foreach (getallheaders() as $name => $value) {
   // echo "$name: $value\n";
    if($name=="Platform" && $value=="ios" || $value=="android" || $value=="web"){
      $platformValid = true;
    }
    else if($name=="Version" && strlen($value)>1){
      $versionValid = true;
    }
    else if($name=="Usertype" && $value=="client"){
      $userTypeValid = true;
    }
}

//auth pair validation
$emailIsValid = false;
$passwordIsValid = false;
   if(!empty($email) && preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $email)){
      $emailIsValid = true;
    }
   if(!empty($password) && strlen($password)==4){
      $passwordIsValid = true;
   }

//checkErrors
   if($platformValid !==true){
   http_response_code(417);
      $row = array("sucess"=> false, "errorMsg"=> 'Expectation Platform Failed');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($versionValid !==true){
   http_response_code(417);
      $row = array("sucess"=> false, "errorMsg"=> 'Expectation Version Failed');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($userTypeValid !==true){
   http_response_code(417);
      $row = array("sucess"=> false, "errorMsg"=> 'Expectation Usertype Failed');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($emailIsValid !==true){
      $row = array("sucess"=> false, "errorMsg"=> 'Email Is Empty Or Incorrect');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($passwordIsValid !==true){
      $row = array("userToken"=> "", "userRefreshToken"=> "", "errorMsg"=> 'Password Is Empty Or Incorrect');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($passwordIsValid !==true){
      $row = array("userToken"=> "", "userRefreshToken"=> "", "errorMsg"=> 'Password Is Empty Or Incorrect');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   //check user in database and get confirm and encoded password
   $userIsExist = false;
   $userEncodedPassword = "";
   $userIsConfirmed = false;
   $passwordComparison = false;

   $userRequest ="SELECT * FROM `users` WHERE (`user_login` = '$email')" ;
   $resultCode = mysqli_query($conn, $userRequest) or die("Error " . mysqli_error($conn)); 
   if($resultCode)
   {  
   while ($userData = mysqli_fetch_assoc($resultCode)) {
   $userLogin = $userData['user_login'];
   $userVerificationStatus = $userData['is_verified'];
   $userEncodedPassword = $userData['user_password'];
   $userToken = $userData['user_token'];
   $userRefreshToken = $userData['user_refresh_token'];
   if(!empty($userLogin)){
      $userIsExist = true;
   }
   if($userVerificationStatus==1){
      $userIsConfirmed = true;
   }
   if($passwordIsValid==true){
      $tokenedPasswordPayload = array('password' => $password);
      $encodedPassword = JWT::encode($tokenedPasswordPayload, $serviceTokenPrivate, 'RS256');
      if($userEncodedPassword==$encodedPassword){
         $passwordComparison = true;
      }
   }
   }
   }

   if($userIsExist==false){
      http_response_code(404);
      $row = array("userToken"=> "", "userRefreshToken"=> "", "errorMsg"=> 'User Is Not Found');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($userIsConfirmed==false){
      $row = array("userToken"=> "", "userRefreshToken"=> "", "errorMsg"=> 'User Is Unconfirmed');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($passwordComparison==false){
      $row = array("userToken"=> "", "userRefreshToken"=> "", "errorMsg"=> 'Password Is Invalid');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   //response tokens
   if($platformValid==true && $userTypeValid==true && $versionValid==true && $emailIsValid==true && $passwordIsValid==true && $userIsExist==true && $userIsConfirmed==true && $passwordComparison==true){
      $row = array("userToken"=> $userToken, "userRefreshToken"=> $userRefreshToken, "errorMsg"=> '');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
   }

   mysqli_close($conn);
?>