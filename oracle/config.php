<?php
// config.php

$tns = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=myhost)(PORT=1521))(CONNECT_DATA=(SERVER=DEDICATED)(SERVICE_NAME=myoracle)))";
$username = "myusername";
$password = "mypassword";

try {
    $conn = oci_connect($username, $password, $tns);
} catch (Exception $e) {
    echo "Échec de la connexion : " . $e->getMessage();
}
