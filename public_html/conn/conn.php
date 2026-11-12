<?php 
$host = 'localhost';
$name = 'solicitacao';
$user = 'root';
$senha = '';

try {
    $conn = new mysqli($host, $user, $senha, $name);
    $conn->set_charset("utf8mb4");
} catch (Throwable $e) {
    error_log('Erro ao conectar ao banco: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao conectar ao banco de dados.');
}
?>