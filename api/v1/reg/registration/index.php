<?php
//jwt
namespace jwt\JWT;
ini_set('post_max_size', '256M');
ini_set('memory_limit', '-1');
require '../../jwt/JWT.php';
include('../../secret/secrets.php');


$login = "";
$password = rand(1, 9).rand(0, 9).rand(0, 9).rand(0, 9);

// Retrieve the raw POST data
$jsonData = file_get_contents('php://input');
// Decode the JSON data into a PHP associative array
$data = json_decode($jsonData, true);
// Check if decoding was successful
if ($data !== null) {
   if(empty($data['login'])){
   // JSON decoding failed
   http_response_code(400); // Bad Request
   $row = array("sucess"=> false, "errorMsg"=> 'Invalid JSON Data');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
}
else{
   // Access the data and perform operations
   $login = $data['login'];
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
$localValid = false;
$localIsRu = false;

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

    else if($name=="Local" && $value=="RU" || $name=="Local" && $value=="ENG"){
      $localValid = true;
      if($value == "RU"){
       $localIsRu = true;  
      }
      
    }
}

//reg pair validation
$loginIsValid = false;
$passwordIsValid = false;
   if(!empty($login) && preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $login)){
      $loginIsValid = true;
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

   if($localValid !==true){
      http_response_code(417);
      $row = array("sucess"=> false, "errorMsg"=> 'Local Invalid');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($loginIsValid !==true){
      $row = array("sucess"=> false, "errorMsg"=> 'Login Is Empty Or Incorrect');
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


   //check user in database
   $userAlreadyExist = true;

   $row = mysqli_fetch_array(mysqli_query( $conn, "SELECT * FROM `users` WHERE ( `user_login` = '$login' ) LIMIT 1" ));
    if (empty($row['user_login'])){
    $userAlreadyExist = false;    
    }

   if($userAlreadyExist==true){
      $row = array("sucess"=> false, "errorMsg"=> 'User Is Already Exist');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }
   //registration
   else{
      $userRegDate = strtotime("now");
      $tokenedPasswordPayload = array('password' => $password);
      $encryptedPassword = JWT::encode($tokenedPasswordPayload, $serviceTokenPrivate, 'RS256');
      $isVerified = false;
      $confirmCode = rand(1, 9).rand(0, 9).rand(0, 9).rand(0, 9);
      $confirmCodeExp = strtotime("+1 day");
   //generate token
      $tokenPayload = array('redDate' => $userRegDate, 'password'=> $password);
      $refreshPayload = array('login' => $login, 'password'=> $password);
      $token = JWT::encode($tokenPayload, $serviceTokenPrivate, 'RS256');
      $refreshToken = JWT::encode($refreshPayload, $serviceTokenPrivate, 'RS256');
      $tokenExp = strtotime("+13 day");
      $tokenRefreshExp = strtotime("+89 day");

      //add to database new user
      $reg = "INSERT INTO users (user_type, user_login, user_password, user_token, user_token_exp, user_refresh_token, user_refresh_token_exp, user_reg_date, is_verified, email_confirm_code, email_confirm_code_exp, user_token_last_update) VALUES ('client', '$login', '$encryptedPassword', '$token', '$tokenExp', '$refreshToken', '$tokenRefreshExp', '$userRegDate', '$isVerified', '$confirmCode', '$confirmCodeExp', '$userRegDate')";$result = mysqli_query($conn, $reg) or die("DataBaseError: " . mysqli_error($conn));    

   //sending email for confirm user
      $local = "EN";
      if($localIsRu == true){
      $local = "RU";
   }
      $ch = curl_init ();
      $access = 29385; //for example - antispam bot
      $scriptaddress= "https://foolstack.ru/email-templates/confirm-registration.php?useremail=".$login."&access=".$access."&code=".$confirmCode."&locale=".$local;
      curl_setopt ($ch, CURLOPT_URL, $scriptaddress);
      curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
      echo curl_exec ($ch);
      $row = array("sucess"=> true, "errorMsg"=> '');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
   }

   mysqli_close($conn);
?>