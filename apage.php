<?php
require_once('class.php');
$sa = new steamAuth();
$sa->sessionStart();
if (!isset($_SESSION['steamid'])) {//Not logged in
    $sa->headerExit('index.php');//Go to login page (index.php for now)
} else {//Logged in
    //Page content....
    $sa->examplePlayerInfo();//Example data output
}