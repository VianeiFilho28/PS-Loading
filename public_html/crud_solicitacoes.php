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

    if(!mysqli_stmt_execute($stmt)){
        mysqli_stmt_close($stmt);
        $msg = urlencode("Erro ao cadastrar a Solicitação $titulo!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }
    mysqli_stmt_close($stmt);

    

    $sql_his = "INSERT INTO historico_solicitacoes
            (
                cod_solicitacao,
                status,
                descricao,
                data_registro
            )
            VALUES (?, 1, 'A Solicitação foi Aberta', NOW())";

    $stmt_his = mysqli_prepare($conn, $sql_his);

    mysqli_stmt_bind_param(
        $stmt_his,
        's',
        $codigo_unico,
    );

    if (mysqli_stmt_execute($stmt_his)) {
        mysqli_stmt_close($stmt_his);
        $msg = urlencode("A Solicitação $titulo foi cadastrada com sucesso!");
        header("Location: index.php?resp=$msg&tp=s");
        exit;
    }
        mysqli_stmt_close($stmt);
        $msg = urlencode("Solicitação cadastrada,mas deu Erro ao registrar o Histórico!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;

}


if ($a === 'excluir' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $cod     = trim((string)($_GET['cod'] ?? ''));

    if (!preg_match('/^[A-Fa-f0-9]{32}$/', $cod)) {
        $msg = urlencode("Código Inválido, processo de Excluir cancelado!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }

    $sql_excluir = "DELETE FROM solicitacoes
               WHERE cod = ?
               LIMIT 1";

    $st_excluir = mysqli_prepare($conn, $sql_excluir);
    mysqli_stmt_bind_param($st_excluir, 's', $cod);

    if (mysqli_stmt_execute($st_excluir)) {
        mysqli_stmt_close($st_excluir);

        $sql_his = "INSERT INTO historico_solicitacoes
            (
                cod_solicitacao,
                status,
                descricao,
                data_registro
            )
            VALUES (?, 4, 'A Solicitação foi Excluída', NOW())";

        $stmt_his = mysqli_prepare($conn, $sql_his);

        mysqli_stmt_bind_param(
            $stmt_his,
            's',
            $cod,
        );

        if (mysqli_stmt_execute($stmt_his)) {
            mysqli_stmt_close($stmt_his);
            $msg = urlencode("A Solicitação foi excluída com sucesso!");
            header("Location: index.php?resp=$msg&tp=s");
            exit;
        }
        mysqli_stmt_close($st_excluir);
        $msg = urlencode("Solicitação excluída mas falha ao registrar no histórico!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }
    mysqli_stmt_close($st_excluir);
    $msg = urlencode("Falha ao excluir Solicitação!");
    header("Location: index.php?resp=$msg&tp=f");
    exit;
}
?>