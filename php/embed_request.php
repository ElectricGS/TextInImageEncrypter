<?php
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    require_once ("input.php");

    $file = Input::xssClean($_FILES['upfile']['tmp_name']);
    if (file_exists($file))
    {
        $imagesizedata = getimagesize($file);
        if ($imagesizedata === FALSE||!testExtension($_FILES['upfile']['name'])||(($_FILES['upfile']['size'])>500000))
        {
            throw new Exception("The file is not an image Or to big");
        }
        else
        {
            $extension = substr(strrchr($_FILES['upfile']['name'], '.'), 1);
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $img = imagecreatefromjpeg($file);
                    break;
                case 'gif':
                    $img = imagecreatefromgif($file);
                    break;
                case 'png':
                    $img = imagecreatefrompng($file);
                    break;
            }

            $data = Input::xssClean(inputValidation($_POST['data'])); //example
            $selection = Input::xssClean(inputValidation($_POST['submit']));
            if($selection=='encrypt'){ //image
                if($q=strlen($data)%3!=0){
                    $data.= ($q == 1) ? "XX" : "X";
                }
                if($img!=false){
                    $j = 0;
                    $dataSizeHolder = imagecolorallocate($img,strlen($data)/3,0 ,0 );
                    imagesetpixel($img, 100,100, $dataSizeHolder);
                    for($i = 0;$i<=(strlen($data)-3);$i+=3){
                        $part = substr ($data , $i,3  );
                        $color = getEncryptedColor($img,$part);
                        imagesetpixel($img, $j++,0, $color);

                    }
                }
                //display image//+*+*+*+*+*+*+*
                $strName = explode(".",$_FILES['upfile']['name']);
                header('Content-Disposition: Attachment;filename=DE_'.$strName[0].'.png');
                imagepng($img);
                imagedestroy($img);
            }
            else if($selection=='decrypt'){

                //decrypt from image - remove on release
                $pixel = imagecolorat($img, 100, 100);
                $colors = imagecolorsforindex($img, $pixel);
                $pixeldata = $colors["red"];
                $dataDec = decryptAllData($img,$pixeldata);
                header("Content-type: text/plain");
                header("Content-Disposition: attachment; filename=decrypted.txt");

                // do your Db stuff here to get the content into $content
                print "Thank you for using DataEncrypt tool!"."\r\n";
                print "Your decrypted massage is:"."\r\n";
                print "".$dataDec;
            }
        }
    }
    else
    {
        throw new Exception("The upload is not a file or the file doesn't exist anymore.");
    }
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
function inputValidation($data){
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
function decryptAllData($img,$datasize){
    $decrypted="";
    for($i=0;$i<$datasize;$i++){
        $decrypted .= decryptDataFromPixel($img,$i,0);
    }
    return $decrypted;
}
function getEncryptedColor($img,$string){
    return imagecolorallocate($img, ord($string[0]), ord($string[1]), ord($string[2]));
}
function printImageValues($image,$num){
    for($x = 0;$x<$num;$x++){
        echo "\n".imagecolorat($image, $x, $x);
    }
    echo "--END--";
}
function decryptDataFromPixel($img,$x,$y){
    $currpixel = imagecolorat($img, $x, $y);
    $colors = imagecolorsforindex($img, $currpixel);
    $str = "".chr($colors["red"]).chr($colors["green"]).chr($colors["blue"]);
    return $str;
}
function testExtension($current_image){
    $extension = substr(strrchr($current_image, '.'), 1);
    if (($extension!= "jpg") && ($extension != "jpeg") && ($extension != "gif") && ($extension != "png") && ($extension != "bmp"))
    {
        return false;
    }
    return true;
}
?>