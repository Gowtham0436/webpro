<?php
// wrong.php
session_start();
$file = "../data/data.txt";
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$updated = false;
$username = isset($_COOKIE['player']) ? $_COOKIE['player'] : '';
$money = isset($_SESSION['current_money']) ? $_SESSION['current_money'] : 0;

foreach ($lines as $line) {
    $values = explode(',', trim($line));
    if ($values[0] === $username) {
        $updatedLines[] = $username . "," . $money;  // Step 2: Update match
        $found = true;
    } else {
        $updatedLines[] = $line; // Keep other lines
}
}

file_put_contents($file, implode("\n", $updatedLines) . "\n");


// Clear session data on wrong answer
?>
<!DOCTYPE html>
<html>

<head>
  <title>EndGame</title>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="../css/style.css">
</head>

<body>
  <audio src="../data/wrong.mp3"></audio>
  <div class="login-box">
      <legend>
            <img src="../data/logo.png" alt="Millionaire?" width="200" height="200">
        </legend>
        <br>
    <h2>Uh Oh, You got it wrong!</h2>
    <h2>You won $<?php echo number_format($money); ?></h2>
    <div class="button-group">
      <a class="button" title="Sign up" href="./start.php">TRY AGAIN</a>
      <a class="button" title="Leaderboard" href="../leaderboard.php">LEADERBOARD</a>
    </div>
  </div>
</body>

</html>