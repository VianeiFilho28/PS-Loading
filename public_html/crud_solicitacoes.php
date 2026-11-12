<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/conn/conn.php';
date_default_timezone_set('America/Fortaleza');

$a = $_GET['a'] ?? header('Location: agentes.php?resp=Formulário Inváido');

$titulo = trim((string)($_POST['titulo'] ?? ''));
$descricao = trim((string)($_POST['descricao'] ?? ''));
$categoria = trim((string)($_POST['categoria'] ?? ''));
$prioridade = trim((int)($_POST['prioridade'] ?? ''));
$status = trim((int)($_POST['status'] ?? ''));


if($a == 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST'){

$codigo_unico = bin2hex(random_bytes(16));

    $sql = "INSERT INTO solicitacoes
            (
                titulo,
                descricao,
                categoria,
                prioridade,
                status,
                cod,
                data_registro
            )
            VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'sssiis',
        $titulo,
        $descricao,
        $categoria,
        $prioridade,
        $status,
        $codigo_unico
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: index.php?resp=A Solicitação $titulo foi cadastrada com sucesso!");
        exit;
    }

    $erro = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    header("Location: index.php?resp=Erro ao cadastrar a Solicitação $titulo!");
        exit;


    

}


?>
