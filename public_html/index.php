<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: text/html; charset=UTF-8');
date_default_timezone_set('America/Fortaleza');

require_once __DIR__ . '/conn/conn.php';

$acao_formulario = 'criar';
$titulo_formulario = "NOVA SOLICITAÇÃO";
$botão_formulario = "Registrar Solicitação";
$resp = $_GET['resp'] ?? '';
$tp   = $_GET['tp'] ?? '';

$e = $_GET['e'] ?? '';
if (!empty($e)) {
    define('CODIGO_HEX_LEN', 32);
    if (!is_string($e) || !preg_match('/^[A-Fa-f0-9]{' . CODIGO_HEX_LEN . '}$/', $e)) {
        http_response_code(400);
        exit('Código inválido.');
    }

    $acao_formulario = 'editar';
    $titulo_formulario = "EDITAR SOLICITAÇÃO";
    $botão_formulario = "Editar Solicitação";

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
        $cod_e        = $qe['cod'] ?? '';
    }

    mysqli_stmt_close($stmt_e);
}

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
    <link rel="stylesheet" href="assets/css/estilo.css">
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

    <!-- Corpo Principal -->
    <main class="corpo-principal">

        <!-- Alerta de Feedback -->
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
                                        <!-- Botão Editar -->
                                        <a href="?e=<?php echo urlencode($cod); ?>" class="btn-acao btn-editar shadow-sm" title="Editar" aria-label="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                                            </svg>
                                        </a>
                                        <!-- Botão Excluir -->
                                        <a href="crud_solicitacoes.php?a=excluir&cod=<?php echo urlencode($cod);?>" class="btn-acao btn-excluir shadow-sm" title="Excluir" aria-label="Excluir">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php } mysqli_stmt_close($stmt); ?>

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

        <!-- SEÇÃO 2: EXPORTAR RELATÓRIO -->
        <section class="mb-5">
            <div class="titulo-secao">
                <h2>Exportar Relatório</h2>
            </div>

            <div class="bloco-interno p-3">
                <form action="exportar_historico.php" method="get" target="_blank" id="formExportacao">
                    <div class="d-flex flex-wrap flex-md-nowrap align-items-end gap-3">
                        <div style="flex: 1 1 250px;">
                            <label for="exportTabela" class="form-label fw-semibold small text-uppercase mb-1">Dados para Exportação:</label>
                            <select name="tabela" id="exportTabela" class="campo-form">
                                <option value="solicitacoes">Solicitações Cadastradas</option>
                                <option value="historico">Histórico de Movimentações</option>
                            </select>
                        </div>

                        <div style="flex: 1 1 200px;">
                            <label for="exportFormato" class="form-label fw-semibold small text-uppercase mb-1">Formato do Arquivo:</label>
                            <select name="formato" id="exportFormato" class="campo-form">
                                <option value="pdf">Exibir PDF</option>
                                <option value="excel">Planilha (.xls)</option>
                                <option value="csv">Arquivo .CSV</option>
                                <option value="txt">Arquivo .TXT</option>
                            </select>
                        </div>

                        <div style="flex: 0 0 auto;">
                            <button type="submit" class="btn btn-roxo px-4 d-inline-flex align-items-center gap-2 fw-medium shadow-sm" style="height: 40px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
                                </svg>
                                <span>Exportar</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- SEÇÃO 3: FORMULÁRIO DE CADASTRO -->
        <section>
            <div class="titulo-secao">
                <h2><?php echo $titulo_formulario;?></h2>
            </div>

            <div class="bloco-interno p-4">
                <form id="form_solicitacoes" action="crud_solicitacoes.php?a=<?php echo htmlspecialchars($acao_formulario, ENT_QUOTES, 'UTF-8'); ?>" method="post" enctype="multipart/form-data">
                    <?php if(!empty($e)):?>
                    <input type="hidden" name="cod" value="<?php echo $cod_e;?>">
                    <?php endif;?>

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="titulo" class="form-label fw-semibold small text-uppercase">Título</label>
                            <input value="<?php echo htmlspecialchars($titulo_e ??'', ENT_QUOTES, 'UTF-8');?>" type="text" class="campo-form" name="titulo" id="titulo" placeholder="Ex: Erro no acesso ao sistema" required>
                        </div>

                        <div class="col-md-4">
                            <label for="categoria" class="form-label fw-semibold small text-uppercase">Categoria</label>
                            <input value="<?php echo htmlspecialchars($categoria_e ??'', ENT_QUOTES, 'UTF-8');?>" type="text" class="campo-form" name="categoria" id="categoria" placeholder="Ex: Suporte, Redes, Financeiro" required>
                        </div>

                        <div class="col-md-3">
                            <label for="prioridade" class="form-label fw-semibold small text-uppercase">Prioridade</label>
                            <select class="campo-form" name="prioridade" id="prioridade">
                                <option value="3" <?= ($prioridade_e ?? '') == 3 ? 'selected' : '' ?>>Baixa</option>
                                <option value="2" <?= ($prioridade_e ?? '') == 2 ? 'selected' : '' ?>>Média</option>
                                <option value="1" <?= ($prioridade_e ?? '') == 1 ? 'selected' : '' ?>>Alta</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="status" class="form-label fw-semibold small text-uppercase">Situação</label>
                            <select class="campo-form" name="status" id="status">
                                <?php 
                                $ultimo_status = '';
                                $sql_status = "SELECT * FROM historico_solicitacoes WHERE cod_solicitacao = ? ORDER BY data_registro DESC LIMIT 1";
                                $stmt_status = mysqli_prepare($conn, $sql_status);
                                mysqli_stmt_bind_param($stmt_status, 's', $e);
                                mysqli_stmt_execute($stmt_status);
                                $result_e = mysqli_stmt_get_result($stmt_status);

                                if ($result_e && mysqli_num_rows($result_e) > 0) {
                                    $status = mysqli_fetch_assoc($result_e);
                                    $ultimo_status = $status['status'] ?? '';
                                }
                                mysqli_stmt_close($stmt_status);
                                ?>
                                <?php if($ultimo_status === 3 || $ultimo_status === 2):?>
                                <option value="3" <?= ($status_e ?? '') == 3 ? 'selected' : '' ?>>Concluída</option>
                                <?php endif;?>

                                <?php if($ultimo_status === 2 || $ultimo_status === 1):?>
                                <option value="2" <?= ($status_e ?? '') == 2 ? 'selected' : '' ?>>Em Andamento</option>
                                <?php endif;?>

                                <?php if($ultimo_status === 1 || $ultimo_status === ''):?>
                                <option value="1" <?= ($status_e ?? '') == 1 ? 'selected' : '' ?>>Aberta</option>
                                <?php endif;?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="descricao" class="form-label fw-semibold small text-uppercase">Descrição</label>
                            <textarea class="campo-form" name="descricao" id="descricao" rows="3" placeholder="Descreva os detalhes da solicitação..." required><?php echo htmlspecialchars($descricao_e ??'', ENT_QUOTES, 'UTF-8');?></textarea>
                        </div>

                        <div class="col-12 text-end mt-3">
                            <button form="form_solicitacoes" type="submit" class="btn btn-roxo px-4 py-2 fw-medium">
                                <?php echo $botão_formulario; ?>
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
            const formExportacao      = document.getElementById("formExportacao");
            const exportFormato       = document.getElementById("exportFormato");

            // Abre PDF em nova aba e faz download direto nos demais formatos
            formExportacao.addEventListener("submit", function() {
                formExportacao.target = (exportFormato.value === "pdf") ? "_blank" : "_self";
            });

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