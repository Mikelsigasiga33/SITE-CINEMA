<?php
// config/db.php

$host         = 'localhost';
$port         = '1521';
$service_name = 'XE';        // Confirma se no teu Oracle é XE ou ORCL
$username     = 'SYSTEM';    // O teu utilizador
$password     = 'db123'; // <--- ATENÇÃO: Coloca aqui a tua senha real do Oracle

$tns = "//$host:$port/$service_name";

try {
    // Criação da conexão
    $pdo = new PDO("oci:dbname=" . $tns . ";charset=AL32UTF8", $username, $password);
    
    // Configurações de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Se quiseres testar, descomenta a linha abaixo. Se aparecer no ecrã, é porque ligou.
    // echo "Conexão OK!"; 

} catch (PDOException $e) {
    // Se der erro, mostra no ecrã e para tudo
    die("Erro Crítico na Conexão (db.php): " . $e->getMessage());
}
?>