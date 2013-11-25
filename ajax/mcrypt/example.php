<?php
include("Mcrypt.php");

$mcrypt = new Anti_Mcrypt("my_secret_password");
echo $encryptedText = $mcrypt->encrypt("Fischers Fritz fischt frische Fische");
echo "<hr />";
echo $mcrypt->decrypt($encryptedText); // Fischers Fritz ..
?>
