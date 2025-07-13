<?php
session_start();
$_SESSION['current_money'] = 0;

$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$selected = $submitted ? ($_POST['answer'] ?? '') : '';
$correct = 'D';

$redirect = false;
$redirectUrl = '';
$redirectDelay = 0;

if ($submitted) {
    if ($selected === 'X') {
        $_SESSION['current_money'] = 0; // Quit value for Q1
        header("Location: final.php");
        exit();
    }
    if ($selected === $correct) {
        $_SESSION['current_money'] = 1000;
        $redirect = true;
        $redirectUrl = "questiontwo.php";
        $redirectDelay = 1;
    } else {
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
    <title>Question 1</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="questionstyle.css">
    <?php if ($redirect): ?>
    <meta http-equiv="refresh" content="<?= $redirectDelay ?>;url=<?= $redirectUrl ?>">
    <?php endif; ?>
    <style>
    .question-text {
        font-size: 1.45em;
        font-weight: 700;
        color: #e7cf43;
        text-align: center;
        margin-bottom: 32px;
        padding-bottom: 10px;
        border-bottom: 2px solid #ffd70033;
        line-height: 1.4;
        letter-spacing: 0.5px;
        word-break: break-word;
        hyphens: auto;
        background: none;
        width: 100%;
        box-sizing: border-box;
    }
    </style>
</head>
<body>
    <audio src="../data/play.mp3"></audio>
    <div class="main-content">
        <form action="questionone.php" method="post">
            <fieldset <?php if($submitted) echo 'class="disabled"'; ?>>
                <div class="question-number">Question 1</div>
                <div class="question-text">Which of these is not typically classified as a citrus fruit?</div>
                <div class="options-row">
                    <div class="options<?= getGlowClass($submitted, $selected, $correct, 'A') ?>">
                        <input type="radio" id="Lemon" name="answer" value="A"
                            <?php if($selected == 'A') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                        <label for="Lemon">Lemon</label>
                    </div>
                    <div class="options<?= getGlowClass($submitted, $selected, $correct, 'B') ?>">
                        <input type="radio" id="Orange" name="answer" value="B"
                            <?php if($selected == 'B') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                        <label for="Orange">Orange</label>
                    </div>
                </div>
                <div class="options-row">
                    <div class="bottom-options<?= getGlowClass($submitted, $selected, $correct, 'C') ?>">
                        <input type="radio" id="Grapefruit" name="answer" value="C"
                            <?php if($selected == 'C') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                        <label for="Grapefruit">Grapefruit</label>
                    </div>
                    <div class="bottom-options<?= getGlowClass($submitted, $selected, $correct, 'D') ?>">
                        <input type="radio" id="Apple" name="answer" value="D"
                            <?php if($selected == 'D') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                        <label for="Apple">Apple</label>
                    </div>
                </div>
                <div class="bottom-options" id="quit-section">
                    <input type="radio" id="Quit" name="answer" value="X"
                        <?php if($selected == 'X') echo 'checked'; ?> <?php if($submitted) echo 'disabled'; ?>>
                    <label id="Quit-Label" for="Quit">I'll quit at $0</label>
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
                                You quit at $0. <a href="final.php">See Results</a>
                            </div>
                        <?php else: ?>
                            <div style="color: #c0392b; font-weight: bold; margin-top: 10px;">
                                Wrong! The correct answer is Apple.<br>
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
