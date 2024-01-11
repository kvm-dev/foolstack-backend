<?php
ini_set('post_max_size', '256M');
ini_set('memory_limit', '-1');
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
     //get professions
            $professionsList = array();
            $getProfessions ="SELECT * FROM `professions` WHERE (`type` = '0') ORDER BY priority DESC";
            $resultProfessions = mysqli_query($conn, $getProfessions) or die("Error " . mysqli_error($conn));
            if($resultProfessions){  
            while ($profession = mysqli_fetch_assoc($resultProfessions)) {
      //init
            $professionId = $profession['profession_id'];
            $professionName = $profession['profession_name'];
            $professionIcon = $profession['icon'];
            $professionParent = $profession['parent'];
            $professionType = $profession['type'];
            $professionPriority = $profession['priority'];
            $subProfessionsList = array();
    //get subs
            $subProfessionsList = array();
            $getSubProfessions ="SELECT * FROM `professions` WHERE (`parent` = '$professionId') ORDER BY priority DESC";
            $resultSubProfessions = mysqli_query($conn, $getSubProfessions) or die("Error " . mysqli_error($conn));
            if($resultSubProfessions){  
            while ($subProfession = mysqli_fetch_assoc($resultSubProfessions)) {
      //init
            $subProfessionId = $subProfession['profession_id'];
            $subProfessionName = $subProfession['profession_name'];
            $subProfessionIcon = $subProfession['icon'];
            $subProfessionParent = $subProfession['parent'];
            $subProfessionType = $subProfession['type'];
            $subProfessionPriority = $subProfession['priority'];
    //one more level subs
            $subProfessionsList2 = array();
            $getSubProfessions2 ="SELECT * FROM `professions` WHERE (`parent` = '$subProfessionId') ORDER BY priority DESC";
            $resultSubProfessions2 = mysqli_query($conn, $getSubProfessions2) or die("Error " . mysqli_error($conn));
            if($resultSubProfessions2){  
            while ($subProfession2 = mysqli_fetch_assoc($resultSubProfessions2)) {
      //init
            $subProfessionId2 = $subProfession2['profession_id'];
            $subProfessionName2 = $subProfession2['profession_name'];
            $subProfessionIcon2 = $subProfession2['icon'];
            $subProfessionParent2 = $subProfession2['parent'];
            $subProfessionType2 = $subProfession2['type'];
            $subProfessionPriority2 = $subProfession2['priority'];
            $subInnerProfessionsList2 = array();


    //one more level subs
            $subProfessionsList3 = array();
            $getSubProfessions3 ="SELECT * FROM `professions` WHERE (`parent` = '$subProfessionId2') ORDER BY priority DESC";
            $resultSubProfessions3 = mysqli_query($conn, $getSubProfessions3) or die("Error " . mysqli_error($conn));
            if($resultSubProfessions3){  
            while ($subProfession3 = mysqli_fetch_assoc($resultSubProfessions3)) {
      //init
            $subProfessionId3 = $subProfession3['profession_id'];
            $subProfessionName3 = $subProfession3['profession_name'];
            $subProfessionIcon3 = $subProfession3['icon'];
            $subProfessionParent3 = $subProfession3['parent'];
            $subProfessionType3 = $subProfession3['type'];
            $subProfessionPriority3 = $subProfession3['priority'];
            $subInnerProfessionsList3 = array();
              $subProfessionsList4 = array(); //empty now
            if(!(empty($subProfessionName3))){
               $subProfession3 = array("professionId"=> intval($subProfessionId3), "professionName"=> $subProfessionName3, "icon"=>$subProfessionIcon3, "type"=>intval($subProfessionType3), "parent"=>intval($subProfessionParent3), "priority"=>intval($subProfessionPriority3), "subProfessions"=>$subProfessionsList4);
               array_push($subProfessionsList3, $subProfession3);
             }
            }
          }        

            if(!(empty($subProfessionName2))){
               $subProfession2 = array("professionId"=> intval($subProfessionId2), "professionName"=> $subProfessionName2, "icon"=>$subProfessionIcon2, "type"=>intval($subProfessionType2), "parent"=>intval($subProfessionParent2), "priority"=>intval($subProfessionPriority2), "subProfessions"=>$subProfessionsList3);
               array_push($subProfessionsList2, $subProfession2);
             }
            }
          }
            if(!(empty($subProfessionName))){
               $subProfession = array("professionId"=> intval($subProfessionId), "professionName"=> $subProfessionName, "icon"=>$subProfessionIcon, "type"=>intval($subProfessionType), "parent"=>intval($subProfessionParent), "priority"=>intval($professionPriority), "subProfessions"=>$subProfessionsList2);
               array_push($subProfessionsList, $subProfession);
             }
            }
          }
            if(!(empty($professionName))){
               $profession = array("professionId"=> intval($professionId), "professionName"=> $professionName, "icon"=>$professionIcon, "type"=>intval($professionType), "parent"=>intval($professionParent), "priority"=>intval($professionPriority), "subProfessions"=>$subProfessionsList);
               array_push($professionsList, $profession);
             }
            }
          }
        
            $row = array("success"=> true, "professions"=> $professionsList, "errorMsg"=> "");
            $result = json_encode($row, JSON_PRETTY_PRINT);
            echo $result;

   mysqli_close($conn);
?>