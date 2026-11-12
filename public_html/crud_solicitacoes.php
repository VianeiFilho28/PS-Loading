<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/conn/conn.php';
date_default_timezone_set('America/Fortaleza');

$a = $_GET['a'] ?? '';

if ($a === '') {
    header('Location: agentes.php?resp=' . urlencode('Formulário Inválido') . '&tp=f');
    exit;
}

if ($a === 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo     = trim((string)($_POST['titulo'] ?? ''));
    $descricao  = trim((string)($_POST['descricao'] ?? ''));
    $categoria  = trim((string)($_POST['categoria'] ?? ''));
    $prioridade = (int)($_POST['prioridade'] ?? 0);
    $status     = (int)($_POST['status'] ?? 0);

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
            VALUES (?, ?, ?, ?, 1, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'sssis',
        $titulo,
        $descricao,
        $categoria,
        $prioridade,
        $codigo_unico
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        $msg = urlencode("A Solicitação $titulo foi cadastrada com sucesso!");
        header("Location: index.php?resp=$msg&tp=s");
        exit;
    }

    mysqli_stmt_close($stmt);
    $msg = urlencode("Erro ao cadastrar a Solicitação $titulo!");
    header("Location: index.php?resp=$msg&tp=f");
    exit;
}
?>