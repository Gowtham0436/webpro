<?php
session_start();

function updatePlayerMoney($username, $money) {
    $file = "../data/data.txt";
    $lines = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $updatedLines = [];
    $found = false;
    foreach ($lines as $line) {
        $values = explode(',', trim($line));
        if ($values[0] === $username) {
            $oldScore = isset($values[1]) ? (int)$values[1] : 0;
            $money = max($money, $oldScore);
            $updatedLines[] = $username . "," . $money;
            $found = true;
        } else {
            $updatedLines[] = $line;
        }
    }
    if (!$found) {
        $updatedLines[] = $username . "," . $money;
    }
    file_put_contents($file, implode("\n", $updatedLines) . "\n");
}

// Always use the session value for the final amount
if (!isset($_SESSION['current_money'])) {
    header("Location: ./start.php");
    exit();
}
$money = $_SESSION['current_money'];
if (isset($_COOKIE['player'])) {
    updatePlayerMoney($_COOKIE['player'], $money);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Finale</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
 <div class="confetti-container">
  <div class="confetti"></div>
  <div class="confetti"></div>
  <div class="confetti"></div>
  <div class="confetti"></div>
  <div class="confetti"></div>
  <div class="confetti"></div>
  <div class="confetti"></div>
  <div class="confetti"></div>
  <div class="confetti"></div>
  <div class="confetti"></div>
</div>

    <audio src="../data/theme.mp3" autoplay loop></audio>
    <div class="login-box">
        <form action="../action.php" method="post">
            <img class="final-img" src="../data/logo.png" alt="Logo" class="center">
            <?php
            $formatted_money = number_format($money);
            echo "<h2>Congratulations!</h2>";
            echo "<h2>You've made it with $" . $formatted_money . "</h2>";
            ?>
        </form>
        <div class="button-group">
        <?php if ($money < 1000000): ?>
            <a class="button" title="Sign up" href="./start.php">TRY AGAIN</a>
        <?php endif; ?>
        <a class="button" title="Leaderboard" href="../leaderboard.php">LEADERBOARD</a>
        </div>
    </div>
</body>
</html>
