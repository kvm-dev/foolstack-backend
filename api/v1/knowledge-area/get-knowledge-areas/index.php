<?php
ini_set('post_max_size', '256M');
ini_set('memory_limit', '-1');
include('../../secret/secrets.php');


$subProfessionsIds = array();
$idsIsValid = false;
// Retrieve the raw POST data
$jsonData = file_get_contents('php://input');
// Decode the JSON data into a PHP associative array
$data = json_decode($jsonData, true);
// Check if decoding was successful
if ($data !== null) {
   if(empty($data['subProfessionsIds'])){
   // JSON decoding failed
   http_response_code(400); // Bad Request
   $row = array("sucess"=> false, "errorMsg"=> 'Invalid JSON Data');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
}
else{
   if(count($data['subProfessionsIds'])>0){
      $subProfessionsIds = $data['subProfessionsIds'];
   $idsIsValid = true;
   }
   else{
      http_response_code(400); // Bad Request
   $row = array("sucess"=> false, "errorMsg"=> 'Sub Profession Ids Is Empty');
      $result = json_encode($row, JSON_PRETTY_PRINT);
      echo $result;
      exit();
   }
}
}
else{
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

   //result
   $knowledgeAreas = array();
    foreach ($subProfessionsIds as &$subId) {
          $getAreas ="SELECT * FROM `knowledge_areas` WHERE (`sub_profession_parent` = '$subId')";
          $resultAreas = mysqli_query($conn, $getAreas) or die("Error " . mysqli_error($conn)); 
          if($resultAreas){  
            while ($areaItem = mysqli_fetch_assoc($resultAreas)) {
      //init
               $areaId = $areaItem['area_id'];
               $areaName = $areaItem['area_name'];
               $complexity = $areaItem['complexity'];
               $priority = $areaItem['priority'];
            if(!(empty($areaName))){
               $itemCollector = array("areaId"=> intval($areaId), "areaName"=> $areaName, "complexity"=>intval($complexity), "priority"=>intval($priority));
               array_push($knowledgeAreas, $itemCollector);
             }
            }
          }
       }
         $row = array("success"=> true, "knowledgeAreas"=> $knowledgeAreas, "errorMsg"=> "");
         $result = json_encode($row, JSON_PRETTY_PRINT);
         echo $result;
         exit(); 

   mysqli_close($conn);
?>