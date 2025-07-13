<!DOCTYPE html>
<html>
  <?php
  if (isset($_GET['error']) && $_GET['error'] === 'empty') {
    echo '<div style="color: red; text-align: center; margin-top: 10px;">User name cannot be empty or consist of only whitespace.</div>';
  }
  if (isset($_GET['error']) && $_GET['error'] === 'exists') {
    echo '<div style="color: red; text-align: center; margin-top: 10px;">User name already exists please login.</div>';
  }
  ?>
	<head>
		<title>Register</title>
		
		<meta charset="utf-8" />
		<!-- Link Style sheet -->
		<link rel="stylesheet" href="../css/style.css">
		
	</head>
	<body>
  <div class="login-box">
  <h2>Register</h2>
  <form action="./startreg.php" method="post">
    <div class="user-box">
    <input type="text" name="player" size="15" placeholder="USERNAME" autocomplete="off" required maxlength="10"><br><br>
    </div>
    <input class="submit-box" type="submit" value="Submit">
    <input class="submit-box" type="button" value=" Already registerd? Login" onclick="location.href='login.php'">
      </form>   
  </div>
 
  </body>
  
</html>
 
