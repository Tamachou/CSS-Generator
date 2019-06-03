<?php
$array = array(); // contain list all pictures
$path = NULL;
$nameFile =  NULL;
$spriteName = "sprite.png";
$styleName = "style.css";

function optionName($argv, $argc)
{
    global $spriteName;
    global $styleName;

    for ($i = 0; $i < ($argc - 1); $i++) {
        $arrayOption = explode("=", $argv[$i]);
        if (isset($arrayOption[1]) && $arrayOption[1] == "") //gestion d'erreur
        {
            unset($arrayOption[1]); // destroy variable cause empty
            echo "invalid argument\n";
        }
        switch ($arrayOption[0]) { //return value depending on the case
            case "-i":
                $spriteName = $arrayOption[1].".png";
                break;
            case "-output-image":
                $spriteName = $arrayOption[1].".png";
                break;
            case "-s":
                $styleName = $arrayOption[1].".css";
                break;
            case "-output-style":
                $styleName = $arrayOption[1].".css";
                break;
        }
    }
}

//get path of folder
function get_path($argv)
{
    global $path;
    foreach ($argv as $path) {
        if (is_dir($path))
            return $path;
    }

}

//get pictures without recursive
function my_readdir($path){
    global $array;

    $handle = opendir($path);


    while(($var = readdir($handle)) !== false)
    {
        if($var !== ".." && $var !== ".")
        {
            $var = $path . "/" . $var;

            if(!is_dir($var))
            {
                if (exif_imagetype($var) === IMAGETYPE_PNG) {
                    array_push($array, $var);
                }
            }
        }
    }
    return ($array);
}

//git pictures with recursive
function my_recursive_readdir($path){
    global $array;

    $handle = opendir($path);


    while(($var = readdir($handle)) !== false)
    {
        if($var !== ".." && $var !== "."){
            $path2 = $path . "/" . $var;
            $var = $path . "/" . $var;

            if(is_dir($path2)) {
                my_recursive_readdir($path2);
            }
            elseif(is_file($path2))
            {
                if(exif_imagetype($var) === IMAGETYPE_PNG) {
                    array_push($array, $var);
                }
            }
        }
    }
    return ($array);
}

//merge pics
function merge_image($array)
{
    global $spriteName;
    $width = 0;
    $height = 0;
    foreach ($array as $file) {
        $checkHeight = getimagesize($file);
        if ($checkHeight[1] > $height) {
            $height = $checkHeight[1];
        }
        $calculateWidth = getimagesize($file);
        $width = $calculateWidth[0] + $width;
    }
    $finalPic = imagecreatetruecolor($width, $height);

    $sourcePoint = 0;
    foreach ($array as $file)
    {
        $dataImage = getimagesize($file);
        $pic = imagecreatefrompng($file);
        $imageHeight = imagesx($pic);
        imagecopy($finalPic, $pic, $sourcePoint, 0, 0, 0, $dataImage[0], $dataImage[1]);
        $sourcePoint += $imageHeight;
    }
    imagepng($finalPic, $spriteName);
}
// used in my_generate_css, get name of pictures
function get_name ()
{
    global $array;
    static $nom = 0;
    $firstExplode = explode("/", $array[$nom]);
    $spliceOne = array_splice($firstExplode, -1);
    $firstImplode = implode($spliceOne);
    $title = explode(".", $firstImplode);
    $nom++;
    return $title[0];
}

// generate css
function my_generate_css($array)
{
    global $styleName;
    global $array;
    $handle = fopen($styleName,"w+");

    fwrite($handle, ".sprite {
    background-image: url($styleName);
    background-repeat: no-repeat;
    display: block;
    } \n\n");

    foreach ($array as $file) {
        $dataImage = getimagesize($file);
        $backPosWidth = 0 + $dataImage[0];
        fwrite($handle, ".sprite-".get_name()." {
        width: $dataImage[0]"."px;
        height: $dataImage[1]"."px;
        background-position: -"."$backPosWidth"."px -0px;
        }\n\n");
    }
}

//activate all functions
function main($argv)
{
    global $argc;
    if (!pathinfo($argv[0], PATHINFO_EXTENSION) == "php"){
        die("incorrect file\n");
    }
    array_shift($argv);

    if (count($argv) == 0)
    {
        die("Too few arguments\n");
    }

    if(is_dir($argv[0]) && count($argv) == 1)
    {
        $tableau = my_readdir($argv[0]);
        merge_image($tableau);
        my_generate_css($tableau);
    }

    if (count($argv) > 1)
    {
        $string = implode(" ", $argv);
        $withoutEqual = str_replace("=", " ", $string);
        $countI = substr_count($withoutEqual, "-i");
        $countImage = substr_count($withoutEqual, "-output-image");
        $countS = substr_count($withoutEqual, "-s");
        $countSprite = substr_count($withoutEqual, "-output-style");
        if (($countI + $countImage) <= 1 && ($countS + $countSprite) <= 1)
        {
            if (in_array("-r", $argv) || in_array("-recursive", $argv)) {
                if ((count(array_keys($argv, "-r")) + count(array_keys($argv, "-recursive"))) == 1) {
                    optionName($argv, $argc);
                    $path = get_path($argv);
                    $tableauRecursive = my_recursive_readdir($path);
                    merge_image($tableauRecursive);
                    my_generate_css($tableauRecursive);
                } else {
                    echo "Too much arguments \n";
                }

            } else {
                optionName($argv, $argc);
                $path = get_path($argv);
                $tableau = my_readdir($path);
                merge_image($tableau);
                my_generate_css($tableau);
            }
        } else
        {
            echo  "invalid arguments\n";
        }
    }
}
main($argv);
?>