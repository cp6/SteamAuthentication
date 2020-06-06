<?php
require_once('class.php');
$sa = new steamAuth();
$sa->sessionStart();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Steam Auth</title>
</head>
<body>
<?php
if (!isset($_SESSION['steamid'])) {//Not logged in
    if (isset($_GET['login'])) {//Login button was pressed
        $sa->doAuth();//OpenId auth
    }
    echo "Welcome please login<br>";
    $sa->loginButton();//Display login button
} elseif (isset($_GET['logout'])) {//Logout request
    $sa->logout();//Destroy session
} else {//Logged in
    $sa->examplePlayerInfo();//Example data output
    $sa->logoutButton();//Display logout button
}
?>
</body>
</html>