<?php
session_start();

$username = isset($_COOKIE['player']) ? $_COOKIE['player'] : null;
$score = isset($_SESSION['current_money']) ? $_SESSION['current_money'] : 0;

if ($username) {
   $file = "./data/data.txt";
   $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   $updated = false;

   foreach ($lines as $line) {
      $parts = explode(':', $line, 2);
      if ($parts[0] === $username) {
         $line = $parts[0] . ':' . $score;
         $updated = true;
         break;
      }
   }
   unset($line);

   if (!$updated) {
      $lines[] = $username . ':' . $score;
   }

   file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
}