<?php
//jwt
namespace jwt\JWT;
ini_set('post_max_size', '256M');
ini_set('memory_limit', '-1');
require '../../jwt/JWT.php';
include('../../secret/secrets.php');


$email = "";
$password = "";
$verificationCode = "";

// Retrieve the raw POST data
$jsonData = file_get_contents('php://input');
// Decode the JSON data into a PHP associative array
$data = json_decode($jsonData, true);
// Check if decoding was successful
if ($data !== null) {
   if(empty($data['email']) || empty($data['password']) || empty($data['verification_code'])){
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
   $verificationCode = $data['verification_code'];
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

//email and verification code validation
$emailIsValid = false;
$passwordIsValid = false;
$codeIsValid = false;
$confirmationCode = 0;
$isConfirmationCodeValid = false;
$isConfirmationCodeNotExpired = false;

$codeString = (string)$verificationCode;
   if(!empty($email) && preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $email)){
      $emailIsValid = true;
    }
   if(!empty($password) && strlen($password)>5){
      $passwordIsValid = true;
   }
       if(!empty($verificationCode) && strlen($codeString)==4){
      $codeIsValid = true;
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
      $row = array("sucess"=> false, "errorMsg"=> 'Password Is Empty Or Incorrect');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($codeIsValid !==true){
      $row = array("sucess"=> false, "errorMsg"=> 'Verification Code Is Empty Or Incorrect');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   //check userdata
   $unconfirmedUserError = true;
   $userResponse ="SELECT * FROM `users` WHERE (`user_login` = '$email')" ;
   $resultData = mysqli_query($conn, $userResponse) or die("Error " . mysqli_error($conn)); 
   if($resultData)
   {  
   while ($data = mysqli_fetch_assoc($resultData)) {
   $confirmationCode = $data['user_forgot_password_code'];
   if($confirmationCode==$verificationCode){
      $isConfirmationCodeValid = true;
   }
    if($data['user_forgot_password_code_exp']>strtotime("now")){
         $isConfirmationCodeNotExpired = true;
      }
      if($data['is_verified']==1){
       $unconfirmedUserError = false;  
      }
   }
   }
   else{
      http_response_code(404);
      $row = array("sucess"=> false, "errorMsg"=> 'User Is Not Found');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($unconfirmedUserError==true){
      $row = array("sucess"=> false, "errorMsg"=> 'User Is Unconfirmed');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($isConfirmationCodeValid==false){
      $row = array("sucess"=> false, "errorMsg"=> 'Confirm Code Is Incorrect');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($isConfirmationCodeNotExpired==false){
      $row = array("sucess"=> false, "errorMsg"=> 'Verification Code Is Expired');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   //changePassword
   if($platformValid==true && $userTypeValid==true && $versionValid==true && $emailIsValid==true && $codeIsValid==true && $unconfirmedUserError==false && $isConfirmationCodeValid==true && $isConfirmationCodeNotExpired==true){
      //update change password
      $newPasswordPayload = array('password' => $password);
      $newPassword = JWT::encode($newPasswordPayload, $serviceTokenPrivate, 'RS256');
      $updateChangePassword = mysqli_query( $conn,  "UPDATE users SET user_password='$newPassword' WHERE (user_login='$email')");
      //response
      $row = array("sucess"=> true, "errorMsg"=> '');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
   }


   mysqli_close($conn);
?>