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
$localValid = false;
$localIsRu = false;
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
    else if($name=="Local" && $value=="RU" || $name=="Local" && $value=="ENG"){
      $localValid = true;
      if($value=="RU"){
         $localIsRu = true;
      }
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

   if($localValid !==true){
   http_response_code(417);
      $row = array("sucess"=> false, "errorMsg"=> 'Local Invalid');
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
         //get Profile
         //default data 
         $success = true;
         $userType = "client";
         $userId = 0;   
         $userName = "";
         $userEmail = "";
         $isVerified = false;
         $userProfessionId = 0;
         $userSubProfessionId = 0;
         $userKnowledgeAreasList = "";
         $profession = null;
         $subProfession = null;
         $knowledgeAreas = null;
      $userRequestByToken ="SELECT * FROM `users` WHERE (`user_token` = '$userToken')";
      $resultProfile = mysqli_query($conn, $userRequestByToken) or die("Error " . mysqli_error($conn)); 
      if($resultProfile){  
      while ($profile = mysqli_fetch_assoc($resultProfile)) {
      //init
      $userType = $profile['user_type'];
      $userId = $profile['user_id'];  
      $userName = $profile['user_name'];  
      $userEmail = $profile['user_login'];  
      $verifiedStatus = $profile['is_verified'];
      $userProfessionId = $profile['user_profession'];
      $userSubProfessionId = $profile['user_sub_profession'];
      $userKnowledgeAreasList = $profile['user_knowledge_area'];
      if($verifiedStatus==1){
         $isVerified = true;
      }
      else{
         $isVerified = false;
      }
      //get profession
      $getProfession ="SELECT * FROM `professions` WHERE (`profession_id` = '$userProfessionId')";
      $resultProfession = mysqli_query($conn, $getProfession) or die("Error " . mysqli_error($conn)); 
      if($resultProfession){  
      while ($professionResponse = mysqli_fetch_assoc($resultProfession)) {
      //init
      $professionName = $professionResponse['profession_name'];
      $professionIcon = $professionResponse['icon'];
      if(!(empty($professionName))){
         $profession = array("professionId"=> $userProfessionId, "professionName"=> $professionName, "icon"=>$professionIcon);   
             }
            }
          }
     //get sub profession
      $getSubProfessions ="SELECT * FROM `sub_professions` WHERE (`sub_profession_id` = '$userSubProfessionId')";
      $resultSubProfession = mysqli_query($conn, $getSubProfessions) or die("Error " . mysqli_error($conn)); 
      if($resultSubProfession){  
      while ($subProfessionResponse = mysqli_fetch_assoc($resultSubProfession)) {
      //init
      $subProfessionName = $subProfessionResponse['sub_profession_name'];
      $subProfessionIcon = $subProfessionResponse['icon'];
      if(!(empty($subProfessionName))){
         $subProfession = array("subProfessionId"=> $userSubProfessionId, "subProfessionName"=> $subProfessionName, "icon"=>$subProfessionIcon);   
             }
            }
          }
     //get knowledge areas
          $knowledgeAreas = array();
          $knowledgeAreasArray = explode(", ", $userKnowledgeAreasList);
          foreach ($knowledgeAreasArray as &$id) {
            $getProfessionsAreas ="SELECT * FROM `knowledge_areas` WHERE (`area_id` = '$id')";
            $resultAreas = mysqli_query($conn, $getProfessionsAreas) or die("Error " . mysqli_error($conn)); 
            if($resultAreas){  
            while ($area = mysqli_fetch_assoc($resultAreas)) {
      //init
            $areaName = $area['area_name'];
            if(!(empty($areaName))){
               $area = array("areaId"=> $id, "areaName"=> $areaName);
               array_push($knowledgeAreas, $area);
             }
            }
          }
            }
      }
   }
         $row = array("success"=> true, "userType"=> $userType, "userId"=> $userId, "userName"=> $userName, "userEmail"=> $userEmail, "isVerified"=> $isVerified, "userProfession"=> $profession, "userSubProfession"=> $subProfession, "userKnowledgeAreas"=> $knowledgeAreas, "errorMsg"=> "");
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