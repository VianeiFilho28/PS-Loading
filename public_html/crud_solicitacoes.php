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
    header('Location: index.php?resp=' . urlencode('Formulário Inválido') . '&tp=f');
    exit;
}

if ($a === 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo     = trim((string)($_POST['titulo'] ?? ''));
    $descricao  = trim((string)($_POST['descricao'] ?? ''));
    $categoria  = trim((string)($_POST['categoria'] ?? ''));
    $prioridade = (int)($_POST['prioridade'] ?? 0);

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

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        $msg = urlencode("Erro ao cadastrar a Solicitação $titulo!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }

    $id_solicitacao = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $desc_historico = "A Solicitação #$id_solicitacao ($titulo) foi Aberta";

    $sql_his = "INSERT INTO historico_solicitacoes
            (
                cod_solicitacao,
                status,
                descricao,
                data_registro
            )
            VALUES (?, 1, ?, NOW())";

    $stmt_his = mysqli_prepare($conn, $sql_his);

    mysqli_stmt_bind_param(
        $stmt_his,
        'ss',
        $codigo_unico,
        $desc_historico
    );

    if (mysqli_stmt_execute($stmt_his)) {
        mysqli_stmt_close($stmt_his);
        $msg = urlencode("A Solicitação $titulo foi cadastrada com sucesso!");
        header("Location: index.php?resp=$msg&tp=s");
        exit;
    }

    mysqli_stmt_close($stmt_his);
    $msg = urlencode("Solicitação cadastrada, mas deu erro ao registrar o histórico!");
    header("Location: index.php?resp=$msg&tp=f");
    exit;
}

if ($a === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $cod        = trim((string)($_POST['cod'] ?? ''));
    $titulo     = trim((string)($_POST['titulo'] ?? ''));
    $descricao  = trim((string)($_POST['descricao'] ?? ''));
    $categoria  = trim((string)($_POST['categoria'] ?? ''));
    $prioridade = (int)($_POST['prioridade'] ?? 0);
    $status     = (int)($_POST['status'] ?? 0);

    if (!preg_match('/^[A-Fa-f0-9]{32}$/', $cod)) {
        $msg = urlencode("Código inválido. A ação de Editar foi cancelada!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }

    // Buscar o ID e o status atual no banco para validar o salto
    $sql_val = "SELECT id, status FROM solicitacoes WHERE cod = ? LIMIT 1";
    $st_val = mysqli_prepare($conn, $sql_val);
    mysqli_stmt_bind_param($st_val, 's', $cod);
    mysqli_stmt_execute($st_val);
    $res_val = mysqli_stmt_get_result($st_val);
    $id_solicitacao = 0;
    $status_atual = 1;
    if ($row_val = mysqli_fetch_assoc($res_val)) {
        $id_solicitacao = (int)$row_val['id'];
        $status_atual   = (int)$row_val['status'];
    }
    mysqli_stmt_close($st_val);

    // Regra: Aberta(1) -> Em andamento(2) -> Concluída(3)
    if ($status === 3 && $status_atual === 1) {
        $msg = urlencode("Transição inválida: Não é permitido pular de Aberta direto para Concluída.");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }
    if ($status < $status_atual) {
        $msg = urlencode("Transição inválida: Não é permitido retroceder o status de uma solicitação.");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }

    $sql = "UPDATE solicitacoes
            SET titulo = ?,
                descricao = ?,
                categoria = ?,
                prioridade = ?,
                status = ?
            WHERE cod = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'sssiis',
        $titulo,
        $descricao,
        $categoria,
        $prioridade,
        $status,
        $cod
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        $msg = urlencode("Erro ao atualizar a Solicitação $titulo!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }
    mysqli_stmt_close($stmt);

    // Registra no histórico apenas quando o status for 2 ou 3
    if ($status === 2 || $status === 3) {
        $desc_historico = ($status === 2)
            ? "A Solicitação #$id_solicitacao ($titulo) está em Andamento"
            : "A Solicitação #$id_solicitacao ($titulo) foi Concluída";

        $sql_his = "INSERT INTO historico_solicitacoes
                (
                    cod_solicitacao,
                    status,
                    descricao,
                    data_registro
                )
                VALUES (?, ?, ?, NOW())";

        $stmt_his = mysqli_prepare($conn, $sql_his);

        mysqli_stmt_bind_param(
            $stmt_his,
            'sis',
            $cod,
            $status,
            $desc_historico
        );

        if (!mysqli_stmt_execute($stmt_his)) {
            mysqli_stmt_close($stmt_his);
            $msg = urlencode("Solicitação atualizada, mas deu erro ao registrar o histórico!");
            header("Location: index.php?resp=$msg&tp=f");
            exit;
        }
        mysqli_stmt_close($stmt_his);
    }

    $msg = urlencode("A Solicitação $titulo foi atualizada com sucesso!");
    header("Location: index.php?resp=$msg&tp=s");
    exit;
}

if ($a === 'excluir' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $cod = trim((string)($_GET['cod'] ?? ''));

    if (!preg_match('/^[A-Fa-f0-9]{32}$/', $cod)) {
        $msg = urlencode("Código inválido, processo de Excluir cancelado!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }

    // Busca o ID e o título da solicitação antes de excluí-la do banco
    $sql_busca = "SELECT id, titulo FROM solicitacoes WHERE cod = ? LIMIT 1";
    $st_busca = mysqli_prepare($conn, $sql_busca);
    mysqli_stmt_bind_param($st_busca, 's', $cod);
    mysqli_stmt_execute($st_busca);
    $res_busca = mysqli_stmt_get_result($st_busca);

    if (!$row_busca = mysqli_fetch_assoc($res_busca)) {
        mysqli_stmt_close($st_busca);
        $msg = urlencode("Solicitação não encontrada ou já excluída!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }

    $id_excluido     = (int)$row_busca['id'];
    $titulo_excluido = $row_busca['titulo'];
    mysqli_stmt_close($st_busca);

    $sql_excluir = "DELETE FROM solicitacoes
                    WHERE cod = ?
                    LIMIT 1";

    $st_excluir = mysqli_prepare($conn, $sql_excluir);
    mysqli_stmt_bind_param($st_excluir, 's', $cod);

    if (mysqli_stmt_execute($st_excluir)) {
        mysqli_stmt_close($st_excluir);

        $desc_historico = "A Solicitação #$id_excluido ($titulo_excluido) foi Excluída";

        $sql_his = "INSERT INTO historico_solicitacoes
            (
                cod_solicitacao,
                status,
                descricao,
                data_registro
            )
            VALUES (?, 4, ?, NOW())";

        $stmt_his = mysqli_prepare($conn, $sql_his);

        mysqli_stmt_bind_param(
            $stmt_his,
            'ss',
            $cod,
            $desc_historico
        );

        if (mysqli_stmt_execute($stmt_his)) {
            mysqli_stmt_close($stmt_his);
            $msg = urlencode("A Solicitação #$id_excluido ($titulo_excluido) foi excluída com sucesso!");
            header("Location: index.php?resp=$msg&tp=s");
            exit;
        }

        mysqli_stmt_close($stmt_his);
        $msg = urlencode("Solicitação excluída, mas falha ao registrar no histórico!");
        header("Location: index.php?resp=$msg&tp=f");
        exit;
    }

    mysqli_stmt_close($st_excluir);
    $msg = urlencode("Falha ao excluir Solicitação!");
    header("Location: index.php?resp=$msg&tp=f");
    exit;
}
?>