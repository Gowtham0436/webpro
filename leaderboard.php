<?php
session_start();

class LeaderboardManager
{
    private $dataFile;

    public function __construct($dataFile)
    {
        $this->dataFile = $dataFile;
    }

    public function getLeaderboardData()
    {
        if (!file_exists($this->dataFile)) {
            return array();
        }

        $textFile = file_get_contents($this->dataFile);
        if ($textFile === false) {
            throw new Exception("Error reading the data file.");
        }

        $lines = array_filter(explode("\n", $textFile));
        $users = array();

        foreach ($lines as $line) {
            $userData = explode(',', trim($line));
            if (count($userData) >= 2) {
                $users[] = array(
                    'name' => htmlspecialchars(trim($userData[0])),
                    'score' => (int)trim($userData[1])
                );
            }
        }

        // Sort by score in descending order
        usort($users, function ($a, $b) {
            return $b['score'] - $a['score'];
        });

        return $users;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Leaderboard</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <h1>LEADERBOARD</h1>
    <div class="login-box">
        <img class="final-img" src="./data/logo.png" alt="Logo" width="80" height="90">
        <?php
        try {
            $leaderboard = new LeaderboardManager('./data/data.txt');
            $users = $leaderboard->getLeaderboardData();

            if (!empty($users)) {
        ?>
                <div>
                    <form action="leaderboard.php" method="GET">
                        <fieldset>
                            <table style="width:100%" id="player">
                                <tr style="text-align:center">
                                    <th>Rank</th>
                                    <th>PLAYER</th>
                                    <th>SCORE</th>
                                </tr>
                                <?php
                                // Display up to the top 10 players
                                for ($i = 0; $i < min(10, count($users)); $i++) {
                                    echo '<tr>';
                                    echo '<td>' . ($i + 1) . '</td>';
                                    echo '<td>' . $users[$i]['name'] . '</td>';
                                    echo '<td>$' . number_format($users[$i]['score']) . '</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </table>
                            <br>
                        </fieldset>
                    </form>
                </div>
        <?php
            } else {
                echo "<p>No scores recorded yet!</p>";
            }
        } catch (Exception $e) {
            echo "<p>Error loading leaderboard: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        <div style="display: flex; justify-content: center; margin-top: 30px;">
            <a href="index.html" class="button" style="max-width: 250px; width: 100%;">Back to Home</a>
        </div>
    </div>
</body>

</html>