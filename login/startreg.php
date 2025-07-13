<?php
if (!isset($_POST["player"]) || trim($_POST["player"]) === "") {
    header("Location: ../login/register.php?error=empty");
    exit();
}
$player = trim($_POST["player"]);
$file = "../data/data.txt";
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode(",", $line);
        if (strcasecmp($parts[0], $player) === 0) {
            header("Location: ../login/register.php?error=exists");
            exit();
        }
    }
}
$data = $player. ",0" .PHP_EOL;
file_put_contents($file, $data, FILE_APPEND);

setcookie("player", $player, time() + (86400 * 30), "/"); 

header("Location: ../questions/start.php");
exit();
?>