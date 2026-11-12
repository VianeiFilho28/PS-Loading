<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: text/html; charset=UTF-8');
date_default_timezone_set('America/Fortaleza');

require_once __DIR__ . '/conn/conn.php';

$acao_formulario = 'criar';
$resp = $_GET['resp'] ?? '';
$tp   = $_GET['tp'] ?? '';

$e = $_GET['e'] ?? '';
if (!empty($e)) {
    define('CODIGO_HEX_LEN', 32);
    if (!is_string($e) || !preg_match('/^[A-Fa-f0-9]{' . CODIGO_HEX_LEN . '}$/', $e)) {
        http_response_code(400);
        exit('Código inválido.');
    }

    $sql_e = "SELECT * FROM solicitacoes WHERE cod = ?";
    $stmt_e = mysqli_prepare($conn, $sql_e);
    mysqli_stmt_bind_param($stmt_e, 's', $e);
    mysqli_stmt_execute($stmt_e);
    $result_e = mysqli_stmt_get_result($stmt_e);

    if ($result_e && mysqli_num_rows($result_e) > 0) {
        $qe = mysqli_fetch_assoc($result_e);

        $id_e         = $qe['id'] ?? '';
        $titulo_e     = $qe['titulo'] ?? '';
        $descricao_e  = $qe['descricao'] ?? '';
        $categoria_e  = $qe['categoria'] ?? '';
        $prioridade_e = $qe['prioridade'] ?? '';
        $status_e     = $qe['status'] ?? '';
    }

    mysqli_stmt_close($stmt_e);
}

// Define a classe do alerta conforme tp (s = verde, f = vermelho, padrão = roxo)
$classe_alerta = match ($tp) {
    's'     => 'alerta-sucesso',
    'f'     => 'alerta-erro',
    default => 'alerta-roxo',
};
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Solicitações</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        :root, [data-bs-theme="light"] {
            --roxo-principal: #6f42c1;
            --roxo-escuro: #4a258c;
            --roxo-medio: #8257e5;
            --roxo-suave: #f3ebff;
            --roxo-borda: #d8c2ff;
            --bs-body-bg: #ebe6f5;
            --bs-body-color: #212529;
            --bs-table-bg: #ffffff;
            --bs-table-color: #212529;
            --bs-table-hover-bg: #faf7ff;
            --bs-border-color: #e9e4f2;
        }

        body {
            background-color: #ebe6f5 !important;
            color: #212529 !important;
            margin: 0;
            padding: 0;
        }

        /* Cabeçalho Superior (inspirado na referência) */
        .topo-sistema {
            background: linear-gradient(180deg, #8558d6 0%, #5b32a8 65%, #3d1e78 100%) !important;
            color: #ffffff !important;
            padding: 2rem 1rem 1.75rem 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(45, 20, 95, 0.25);
        }

        .topo-sistema h1 {
            color: #ffffff !important;
            font-weight: 700;
            font-size: 2rem;
            margin: 0;
            text-shadow: 1px 2px 4px rgba(0, 0, 0, 0.28);
            letter-spacing: 0.4px;
        }

        .topo-sistema p {
            color: #e9dfff !important;
            font-size: 0.98rem;
            margin: 0.45rem 0 0 0;
        }

        /* Corpo Centralizado estilo Folha de Documento (encaixado no topo) */
        .corpo-principal {
            max-width: 1120px;
            min-height: calc(100vh - 140px);
            margin: 0 auto 2.5rem auto;
            background-color: #ffffff;
            padding: 2rem 2.25rem 2.75rem 2.25rem;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            box-shadow: 0 0 18px rgba(58, 30, 112, 0.16);
        }

        @media (max-width: 768px) {
            .corpo-principal {
                padding: 1.25rem 1rem 2rem 1rem;
                margin-bottom: 1rem;
                border-radius: 0;
            }
        }

        /* Faixa de título de seção (estilo degradê lateral da referência) */
        .titulo-secao {
            background: linear-gradient(90deg, #efe7ff 0%, #f8f5ff 55%, rgba(255, 255, 255, 0) 100%);
            border-left: 5px solid var(--roxo-principal);
            padding: 0.55rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .titulo-secao h2 {
            color: var(--roxo-escuro) !important;
            font-size: 1.22rem;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .text-roxo {
            color: var(--roxo-principal) !important;
        }

        .badge-roxo-suave {
            background-color: var(--roxo-suave) !important;
            color: var(--roxo-principal) !important;
            border: 1px solid var(--roxo-borda) !important;
        }

        /* Destaque para o contador de registros */
        .badge-contador {
            background: linear-gradient(135deg, var(--roxo-principal), var(--roxo-escuro)) !important;
            color: #ffffff !important;
            font-size: 0.88rem !important;
            font-weight: 600 !important;
            padding: 0.42rem 1.05rem !important;
            border-radius: 50rem !important;
            box-shadow: 0 2px 6px rgba(111, 66, 193, 0.28);
            letter-spacing: 0.2px;
        }

        /* Badges de Prioridade e Status */
        .badge-alta {
            background-color: #dc3545 !important;
            color: #ffffff !important;
        }

        .badge-media {
            background-color: #fd7e14 !important;
            color: #ffffff !important;
        }

        .badge-baixa {
            background-color: #0dcaf0 !important;
            color: #212529 !important;
        }

        .badge-aberta {
            background-color: #6c757d !important;
            color: #ffffff !important;
        }

        .badge-andamento {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .badge-concluida {
            background-color: #198754 !important;
            color: #ffffff !important;
        }

        .btn-roxo {
            background-color: var(--roxo-principal) !important;
            border-color: var(--roxo-principal) !important;
            color: #ffffff !important;
            border-radius: 0.375rem;
        }

        .btn-roxo:hover,
        .btn-roxo:focus {
            background-color: var(--roxo-escuro) !important;
            border-color: var(--roxo-escuro) !important;
            color: #ffffff !important;
        }

        .btn-roxo:disabled {
            background-color: #b79fe3 !important;
            border-color: #b79fe3 !important;
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-outline-roxo {
            background-color: #ffffff !important;
            border: 1px solid var(--roxo-principal) !important;
            color: var(--roxo-principal) !important;
            border-radius: 0.375rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .btn-outline-roxo:hover {
            background-color: var(--roxo-suave) !important;
            color: var(--roxo-escuro) !important;
        }

        /* Estilos do Alerta Personalizado */
        .alerta-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.9rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .alerta-sucesso {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
            border-left: 5px solid #198754;
        }

        .alerta-erro {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            border-left: 5px solid #dc3545;
        }

        .alerta-roxo {
            background-color: var(--roxo-suave);
            color: var(--roxo-escuro);
            border: 1px solid var(--roxo-borda);
            border-left: 5px solid var(--roxo-principal);
        }

        .btn-fechar-alerta {
            background: transparent;
            border: none;
            font-size: 1.35rem;
            line-height: 1;
            color: inherit;
            opacity: 0.7;
            cursor: pointer;
            padding: 0 0.25rem;
            margin-left: 1rem;
        }

        .btn-fechar-alerta:hover {
            opacity: 1;
        }

        /* Botões de Ação com cores explícitas */
        .btn-acao {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            padding: 0;
            border-radius: 6px;
            border: none;
            text-decoration: none;
            transition: transform 0.15s ease, filter 0.15s ease;
        }

        .btn-acao:hover {
            transform: translateY(-1px);
            filter: brightness(0.93);
        }

        .btn-editar {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .btn-excluir {
            background-color: #dc3545 !important;
            color: #ffffff !important;
        }

        /* Bloco interno da tabela e do formulário dentro da folha */
        .bloco-interno {
            border: 1px solid #e7dff5;
            border-radius: 8px;
            overflow: hidden;
            background-color: #ffffff;
        }

        /* Tabela clara */
        .table, .table > :not(caption) > * > * {
            background-color: #ffffff !important;
            color: #212529 !important;
            border-color: #ede8f5 !important;
        }

        .table thead th {
            background-color: #f4effc !important;
            color: #4a258c !important;
        }

        .table-hover > tbody > tr:hover > * {
            background-color: #faf7ff !important;
        }

        /* Padronização completa de Inputs e Select */
        .campo-form {
            display: block;
            width: 100%;
            height: 40px;
            padding: 0.45rem 0.75rem;
            font-size: 0.95rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529 !important;
            background-color: #ffffff !important;
            border: 1px solid #d5cce6 !important;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        textarea.campo-form {
            height: auto;
            min-height: 95px;
        }

        select.campo-form {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 2.25rem;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2359339d' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 14px 10px;
            cursor: pointer;
        }

        /* Select compacto para quantidade por página */
        .select-qtd {
            width: 78px !important;
            text-align: center;
            padding-left: 0.5rem !important;
            padding-right: 1.75rem !important;
            background-position: right 0.5rem center !important;
        }

        .campo-form::placeholder {
            color: #8c829e !important;
        }

        .campo-form:focus {
            outline: 0;
            border-color: var(--roxo-principal) !important;
            box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.2) !important;
        }

        .form-label {
            display: block;
            margin-bottom: 0.4rem;
            color: #4a3f5c !important;
        }

        /* Barra de controles da tabela */
        .barra-filtros {
            background-color: #faf8fe;
            border-bottom: 1px solid #e7dff5;
        }

        .rodape-paginacao {
            background-color: #faf8fe;
            border-top: 1px solid #e7dff5;
        }
    </style>
</head>
<body>

    <!-- Cabeçalho Superior em Degradê Roxo -->
    <header class="topo-sistema">
        <a href="index.php" class="text-decoration-none">
            <h1 class="d-inline-flex align-items-center justify-content-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                    <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/>
                    <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z"/>
                </svg>
                <span>Painel de Solicitações</span>
            </h1>
        </a>
        <p>Controle, acompanhamento e registro de chamados em um único lugar</p>
    </header>

    <!-- Corpo Principal em formato Folha Centralizada -->
    <main class="corpo-principal">

        <!-- Alerta de Feedback (tp=s -> Verde | tp=f -> Vermelho) -->
        <?php if ($resp !== ''): ?>
            <div id="alerta-feedback" class="alerta-box <?php echo $classe_alerta; ?> shadow-sm mb-4" role="alert">
                <span><?php echo htmlspecialchars($resp, ENT_QUOTES, 'UTF-8'); ?></span>
                <button type="button" class="btn-fechar-alerta" onclick="fecharAlerta()" aria-label="Fechar">&times;</button>
            </div>
        <?php endif; ?>

        <!-- SEÇÃO 1: TABELA DE SOLICITAÇÕES -->
        <section class="mb-5">
            <div class="titulo-secao">
                <h2>Solicitações Cadastradas</h2>
                <span id="contadorRegistros" class="badge-contador">
                    0 registros
                </span>
            </div>

            <div class="bloco-interno">
                <!-- Barra de Pesquisa em Tempo Real, Filtros e Seletor na Mesma Linha -->
                <div class="barra-filtros p-3">
                    <div class="d-flex flex-wrap flex-lg-nowrap align-items-end gap-2">
                        <div style="flex: 0 0 auto;">
                            <label for="rowsPerPageSelector" class="form-label fw-semibold small text-uppercase mb-1">Exibir:</label>
                            <select id="rowsPerPageSelector" class="campo-form select-qtd">
                                <option value="10" selected>10</option>
                                <option value="20">20</option>
                                <option value="30">30</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div style="flex: 0 0 155px;">
                            <label for="filterPrioridade" class="form-label fw-semibold small text-uppercase mb-1">Prioridade:</label>
                            <select id="filterPrioridade" class="campo-form">
                                <option value="">Todas</option>
                                <option value="1">Alta</option>
                                <option value="2">Média</option>
                                <option value="3">Baixa</option>
                            </select>
                        </div>

                        <div style="flex: 0 0 175px;">
                            <label for="filterStatus" class="form-label fw-semibold small text-uppercase mb-1">Status:</label>
                            <select id="filterStatus" class="campo-form">
                                <option value="">Todos</option>
                                <option value="1">Aberta</option>
                                <option value="2">Em Andamento</option>
                                <option value="3">Concluída</option>
                            </select>
                        </div>

                        <div style="flex: 1 1 auto; min-width: 200px;">
                            <label for="searchInput" class="form-label fw-semibold small text-uppercase mb-1">Busca Rápida:</label>
                            <input type="text" id="searchInput" class="campo-form" placeholder="Digite aqui para pesquisar...">
                        </div>

                        <div style="flex: 0 0 auto;">
                            <button type="button" id="btnLimparFiltros" class="btn btn-outline-roxo px-3" style="height: 40px;">
                                Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tabelaSolicitacoes">
                        <thead class="small text-uppercase">
                            <tr>
                                <th scope="col" class="ps-4 py-3">Solicitação</th>
                                <th scope="col" class="py-3">Categoria</th>
                                <th scope="col" class="py-3">Prioridade</th>
                                <th scope="col" class="py-3">Status</th>
                                <th scope="col" class="py-3">Data</th>
                                <th scope="col" class="text-end pe-4 py-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="corpoTabela">
                            <?php
                            $sql = "SELECT * FROM solicitacoes ORDER BY data_registro DESC";
                            $stmt = mysqli_prepare($conn, $sql);

                            if (!$stmt) {
                                die('Erro ao preparar: ' . mysqli_error($conn));
                            }

                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);

                            while ($a = mysqli_fetch_assoc($result)) {
                                $id = (int)($a['id'] ?? 0);
                                $cod = $a['cod'] ?? '';

                                $titulo = htmlspecialchars($a['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
                                $descricao = htmlspecialchars($a['descricao'] ?? '', ENT_QUOTES, 'UTF-8');
                                $categoria = htmlspecialchars($a['categoria'] ?? '', ENT_QUOTES, 'UTF-8');
                                $prioridade = (int)($a['prioridade'] ?? 0);
                                $status = (int)($a['status'] ?? 0);

                                $data_registro_bd = $a['data_registro'] ?? '';
                                $data_registro = (!empty($data_registro_bd) && $data_registro_bd !== '0000-00-00')
                                    ? date('d/m/Y', strtotime($data_registro_bd))
                                    : '';
                            ?>
                            <tr class="linha-dado" data-prioridade="<?php echo $prioridade; ?>" data-status="<?php echo $status; ?>">
                                <td class="ps-4 py-3">
                                    <div class="fw-semibold text-dark"><?php echo $titulo; ?></div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 320px;">
                                        <?php echo $descricao; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-roxo-suave"><?php echo $categoria; ?></span>
                                </td>
                                <td> 
                                    <?php if ($prioridade == 1): ?>
                                        <span class="badge badge-alta">Alta</span>
                                    <?php elseif ($prioridade == 2): ?>
                                        <span class="badge badge-media">Média</span>
                                    <?php elseif ($prioridade == 3): ?>
                                        <span class="badge badge-baixa">Baixa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status == 1): ?>
                                        <span class="badge badge-aberta">Aberta</span>
                                    <?php elseif ($status == 2): ?>
                                        <span class="badge badge-andamento">Em Andamento</span>
                                    <?php elseif ($status == 3): ?>
                                        <span class="badge badge-concluida">Concluída</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo $data_registro; ?></small>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <!-- Botão Editar (Lápis Amarelo) -->
                                        <a href="?e=<?php echo urlencode($cod); ?>" class="btn-acao btn-editar shadow-sm" title="Editar" aria-label="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                                            </svg>
                                        </a>
                                        <!-- Botão Excluir (Lixeira Vermelha) -->
                                        <a href="#" class="btn-acao btn-excluir shadow-sm" title="Excluir" aria-label="Excluir">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php } mysqli_stmt_close($stmt); ?>

                            <!-- Mensagem quando nenhum registro corresponder à busca/filtro -->
                            <tr id="linhaSemResultados" style="display: none;">
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Nenhuma solicitação encontrada para os filtros informados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Controles de Paginação -->
                <div class="rodape-paginacao px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <button type="button" id="prevPage" class="btn btn-roxo px-3 py-1 fw-medium">
                        &laquo; Anterior
                    </button>
                    <span class="text-secondary small fw-semibold">
                        Página <span id="currentPage" class="text-roxo">1</span> de <span id="totalPages" class="text-roxo">1</span>
                    </span>
                    <button type="button" id="nextPage" class="btn btn-roxo px-3 py-1 fw-medium">
                        Próximo &raquo;
                    </button>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 2: FORMULÁRIO DE CADASTRO -->
        <section>
            <div class="titulo-secao">
                <h2>Nova Solicitação</h2>
            </div>

            <div class="bloco-interno p-4">
                <form id="form_solicitacoes" action="crud_solicitacoes.php?a=<?php echo htmlspecialchars($acao_formulario, ENT_QUOTES, 'UTF-8'); ?>" method="post" enctype="multipart/form-data">
                    
                    <input type="hidden" name="status" value="1">

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="titulo" class="form-label fw-semibold small text-uppercase">Título</label>
                            <input type="text" class="campo-form" name="titulo" id="titulo" placeholder="Ex: Erro no acesso ao sistema" required>
                        </div>

                        <div class="col-md-4">
                            <label for="categoria" class="form-label fw-semibold small text-uppercase">Categoria</label>
                            <input type="text" class="campo-form" name="categoria" id="categoria" placeholder="Ex: Suporte, Redes, Financeiro" required>
                        </div>

                        <div class="col-md-3">
                            <label for="prioridade" class="form-label fw-semibold small text-uppercase">Prioridade</label>
                            <select class="campo-form" name="prioridade" id="prioridade">
                                <option value="3" selected>Baixa</option>
                                <option value="2">Média</option>
                                <option value="1">Alta</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="descricao" class="form-label fw-semibold small text-uppercase">Descrição</label>
                            <textarea class="campo-form" name="descricao" id="descricao" rows="3" placeholder="Descreva os detalhes da solicitação..." required></textarea>
                        </div>

                        <div class="col-12 text-end mt-3">
                            <button form="form_solicitacoes" type="submit" class="btn btn-roxo px-4 py-2 fw-medium">
                                Registrar Solicitação
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </section>

    </main>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function fecharAlerta() {
            const alerta = document.getElementById('alerta-feedback');
            if (alerta) {
                alerta.remove();
            }
            if (window.history.replaceState) {
                const urlLimpa = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({ path: urlLimpa }, '', urlLimpa);
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const rowsPerPageSelector = document.getElementById("rowsPerPageSelector");
            const searchInput         = document.getElementById("searchInput");
            const filterPrioridade    = document.getElementById("filterPrioridade");
            const filterStatus        = document.getElementById("filterStatus");
            const btnLimparFiltros    = document.getElementById("btnLimparFiltros");
            const prevPageBtn         = document.getElementById("prevPage");
            const nextPageBtn         = document.getElementById("nextPage");
            const linhaSemResultados  = document.getElementById("linhaSemResultados");
            const contadorRegistros   = document.getElementById("contadorRegistros");

            const allRows = Array.from(document.querySelectorAll("#corpoTabela tr.linha-dado"));
            let rowsPerPage  = parseInt(rowsPerPageSelector.value, 10);
            let currentPage  = 1;
            let totalPages   = 1;
            let filteredRows = [...allRows];

            function updatePagination() {
                totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                document.getElementById("totalPages").innerText = totalPages;
                contadorRegistros.innerText = filteredRows.length + (filteredRows.length === 1 ? " registro" : " registros");
                displayTablePage(currentPage);
            }

            function displayTablePage(page) {
                allRows.forEach(row => row.style.display = "none");

                if (filteredRows.length === 0) {
                    linhaSemResultados.style.display = "";
                } else {
                    linhaSemResultados.style.display = "none";
                    const startIndex = (page - 1) * rowsPerPage;
                    const endIndex   = page * rowsPerPage;
                    filteredRows.slice(startIndex, endIndex).forEach(row => row.style.display = "");
                }

                document.getElementById("currentPage").innerText = page;
                prevPageBtn.disabled = (page <= 1);
                nextPageBtn.disabled = (page >= totalPages);
            }

            function changePage(delta) {
                const targetPage = currentPage + delta;
                if (targetPage >= 1 && targetPage <= totalPages) {
                    currentPage = targetPage;
                    displayTablePage(currentPage);
                }
            }

            function applyFilters() {
                const termo      = searchInput.value.trim().toLowerCase();
                const prioridade = filterPrioridade.value;
                const status     = filterStatus.value;

                filteredRows = allRows.filter(row => {
                    const matchPrioridade = (prioridade === "" || row.dataset.prioridade === prioridade);
                    const matchStatus     = (status === "" || row.dataset.status === status);

                    const cellsText = Array.from(row.cells)
                        .slice(0, 5)
                        .map(cell => cell.textContent.toLowerCase())
                        .join(" ");
                    const matchTexto = (termo === "" || cellsText.includes(termo));

                    return matchPrioridade && matchStatus && matchTexto;
                });

                currentPage = 1;
                updatePagination();
            }

            rowsPerPageSelector.addEventListener("change", function() {
                rowsPerPage = parseInt(this.value, 10);
                currentPage = 1;
                updatePagination();
            });

            searchInput.addEventListener("input", applyFilters);
            filterPrioridade.addEventListener("change", applyFilters);
            filterStatus.addEventListener("change", applyFilters);

            btnLimparFiltros.addEventListener("click", function() {
                searchInput.value      = "";
                filterPrioridade.value = "";
                filterStatus.value     = "";
                applyFilters();
            });

            prevPageBtn.addEventListener("click", function() {
                changePage(-1);
            });

            nextPageBtn.addEventListener("click", function() {
                changePage(1);
            });

            updatePagination();
        });
    </script>
</body>
</html>