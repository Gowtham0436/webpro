<?php
$file = '../data/data.txt';
$textFile = file_get_contents($file);
$singles = explode("\n", $textFile);
$player = $_POST["player"];
$userInfo = array();
foreach ($singles as $users) {
    $userInfo = explode(",", $users);
    if ($userInfo[0] == $player){
        setcookie("player", $player, time() + (86400 * 30), "/"); 

        header("Location: ../questions/start.php");
        exit();
    }
}

    header("Location: ./register.php");
    exit();

?>
