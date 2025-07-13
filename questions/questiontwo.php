<?php
session_start();

$correct = 'A';
$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$selected = $submitted ? ($_POST['answer'] ?? '') : '';

$redirect = false;
$redirectUrl = '';
$redirectDelay = 0;

if ($submitted) {
    if ($selected === 'X') {
        $_SESSION['current_money'] = 1000; // Quit value for Q2
        header("Location: final.php");
        exit();
    }
    if ($selected === $correct) {
        $_SESSION['current_money'] = 10000;
        $redirect = true;
        $redirectUrl = "questionthree.php";
        $redirectDelay = 1;
    } else {
        $_SESSION['current_money'] = 0;
        $redirect = true;
        $redirectUrl = "wrong.php";
        $redirectDelay = 1.5;
    }
}
function getGlowClass($submitted, $selected, $correct, $option) {
    if (!$submitted) return '';
    if ($option === $correct) return ' correct-glow';
    if ($option === $selected) return ' wrong-glow';
    return '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Question 2</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="questionstyle.css">
    <?php if ($redirect): ?>
    <meta http-equiv="refresh" content="<?= $redirectDelay ?>;url=<?= $redirectUrl ?>">
    <?php endif; ?>
</head>
<body>
    <audio src="../data/play.mp3"></audio>
    <div class="main-content">
        <form action="questiontwo.php" method="post">
            <fieldset <?php if($submitted) echo 'class="disabled"'; ?>>
                <div class="question-number">Question 2</div>
                <div class="question-text">What does the Internet prefix "www" stand for?</div>
                <div class="options-row">
                    <div class="options<?= getGlowClass($submitted, $selected, $correct, 'A') ?>">
                        <input type="radio" id="WorldWideWeb" name="answer" value="A"
                            <?php if($selected == 'A') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                        <label for="WorldWideWeb">World Wide Web</label>
                    </div>
                    <div class="options<?= getGlowClass($submitted, $selected, $correct, 'B') ?>">
                        <input type="radio" id="WideWorldWeb" name="answer" value="B"
                            <?php if($selected == 'B') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                        <label for="WideWorldWeb">Wide World Web</label>
                    </div>
                </div>
                <div class="options-row">
                    <div class="bottom-options<?= getGlowClass($submitted, $selected, $correct, 'C') ?>">
                        <input type="radio" id="WebWorldWindow" name="answer" value="C"
                            <?php if($selected == 'C') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                        <label for="WebWorldWindow">Web World Window</label>
                    </div>
                    <div class="bottom-options<?= getGlowClass($submitted, $selected, $correct, 'D') ?>">
                        <input type="radio" id="WebWideWindow" name="answer" value="D"
                            <?php if($selected == 'D') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                        <label for="WebWideWindow">Web Wide Window</label>
                    </div>
                </div>
                <div class="bottom-options" id="quit-section">
                    <input type="radio" id="Quit" name="answer" value="X"
                        <?php if($selected == 'X') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                    <label id="Quit-Label" for="Quit">I'll quit at $1,000</label>
                </div>
                <div class="submit-section">
                    <?php if(!$submitted): ?>
                        <input class="submit-box" type="submit" value="Submit">
                    <?php else: ?>
                        <?php if($selected === $correct): ?>
                            <div style="color: #28a745; font-weight: bold; margin-top: 10px;">
                                Correct! Next question...
                            </div>
                        <?php elseif($selected === 'X'): ?>
                            <div style="color: #e67e22; font-weight: bold; margin-top: 10px;">
                                You quit at $1,000. <a href="final.php">See Results</a>
                            </div>
                        <?php else: ?>
                            <div style="color: #c0392b; font-weight: bold; margin-top: 10px;">
                                Wrong! The correct answer is World Wide Web.<br>
                                Redirecting...
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </fieldset>
        </form>
    </div>
</body>
</html>
