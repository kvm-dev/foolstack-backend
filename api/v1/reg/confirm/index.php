<?php
//jwt
namespace jwt\JWT;
ini_set('post_max_size', '256M');
ini_set('memory_limit', '-1');
require '../../jwt/JWT.php';
include('../../secret/secrets.php');


$email = "";
$verificationCode = "";

// Retrieve the raw POST data
$jsonData = file_get_contents('php://input');
// Decode the JSON data into a PHP associative array
$data = json_decode($jsonData, true);
// Check if decoding was successful
if ($data !== null) {
   if(empty($data['email']) || empty($data['verification_code'])){
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
$codeIsValid = false;
$codeString = (string)$verificationCode;
   if(!empty($email) && preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $email)){
      $emailIsValid = true;
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

   if($codeIsValid !==true){
      $row = array("sucess"=> false, "errorMsg"=> 'Verification Code Is Empty Or Incorrect');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   //check unconfirmed user in database
   $unconfirmedUserError = true;
   $confirmCode ="SELECT * FROM `users` WHERE (`user_login` = '$email' AND `is_verified` = '0')" ;
   $resultCode = mysqli_query($conn, $confirmCode) or die("Error " . mysqli_error($conn)); 
   if($resultCode)
   {  
   while ($codeInBd = mysqli_fetch_assoc($resultCode)) {
   $email_confirm_code = $codeInBd['email_confirm_code'];
   if($email_confirm_code==$verificationCode){
      $unconfirmedUserError = false;
   }
   }
   }

   if($unconfirmedUserError==true){
      $row = array("sucess"=> false, "errorMsg"=> 'Email Is Not Found, Invalid Verification Code Or User Already Confirmed');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   //registration
   if($platformValid==true && $userTypeValid==true && $versionValid==true && $emailIsValid==true && $codeIsValid==true && $unconfirmedUserError==false){
      //update confirm status
      $updateConfirmData = mysqli_query( $conn,  "UPDATE users SET is_verified=1 WHERE (user_login='$email')");
      //response
      $row = array("sucess"=> true, "errorMsg"=> '');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
   }

   mysqli_close($conn);
?>