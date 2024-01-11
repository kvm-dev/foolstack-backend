<?php
//jwt
namespace jwt\JWT;
ini_set('post_max_size', '256M');
ini_set('memory_limit', '-1');
require '../../jwt/JWT.php';
include('../../secret/secrets.php');


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
$bearerContain = false;
$userToken = "";
$tokenIsVerified = false;

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

    else if($name=="Authorization" && str_contains($value, 'Bearer')){
      $bearerContain = true;
      $splitPart = explode("Bearer ", $value);
      $userToken = $splitPart[1];
    }
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


   if($bearerContain !==true){
      http_response_code(401);
      $row = array("success"=> false, "errorMsg"=> 'Unauthorized');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }

   if($platformValid==true && $userTypeValid==true && $versionValid==true && $bearerContain==true && strlen($userToken)>12){
      $bdToken = "";
      $currentServerTime = strtotime("now");
   //check token is exist in user bd and it not expired
      $tokenRequest ="SELECT * FROM `users` WHERE (`user_token` = '$userToken')";
      $resultCode = mysqli_query($conn, $tokenRequest) or die("Error " . mysqli_error($conn)); 
   if($resultCode){  
      while ($userData = mysqli_fetch_assoc($resultCode)) {
      $bdToken = $userData['user_token'];
      $tokenExpiredTime = $userData['user_token_exp'];
      if($tokenExpiredTime<$currentServerTime){
      $row = array("success"=> false, "errorMsg"=> 'User Token Is Expired');
      $result = json_encode($row, JSON_PRETTY_PRINT);
         echo $result;
         exit(); 
      }
      else{
         //update last login
          $updateLastLogin = mysqli_query( $conn,  "UPDATE users SET user_last_login=$currentServerTime WHERE (user_token='$userToken')");
         $row = array("success"=> true, "errorMsg"=> "");
         $result = json_encode($row, JSON_PRETTY_PRINT);
         echo $result;
         exit(); 
   }
}
}
   if(empty($bdToken)){
      http_response_code(401);
      $row = array("success"=> false, "errorMsg"=> 'Unauthorized');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
}

}
else{
      http_response_code(401);
      $row = array("success"=> false, "errorMsg"=> 'Unauthorized');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
 }
   mysqli_close($conn);
?>