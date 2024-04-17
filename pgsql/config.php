<?php
$host = 'localhost';
$db   = 'postgres';
$user = 'postgres';
$pass = 'password';
$schema = 'public'; // schéma par défaut

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET search_path TO $schema");
} catch (PDOException $e) {
    echo "Échec de la connexion : " . $e->getMessage();
}