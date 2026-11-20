<?php
date_default_timezone_set('America/Fortaleza');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/conn/conn.php';

$tabela  = $_GET['tabela'] ?? 'solicitacoes';
$formato = $_GET['formato'] ?? 'pdf';
$dataEmissao = date('d/m/Y H:i');

function obterDadosStatus(int $status): array {
    return match ($status) {
        1 => ['texto' => 'Aberta',       'cor' => '#6c757d'],
        2 => ['texto' => 'Em Andamento', 'cor' => '#b37400'],
        3 => ['texto' => 'Concluída',    'cor' => '#198754'],
        4 => ['texto' => 'Excluída',     'cor' => '#dc3545'],
        default => ['texto' => 'Status #' . $status, 'cor' => '#6f42c1'],
    };
}

function obterDadosPrioridade(int $prioridade): array {
    return match ($prioridade) {
        1 => ['texto' => 'Alta',  'cor' => '#dc3545'],
        2 => ['texto' => 'Média', 'cor' => '#fd7e14'],
        3 => ['texto' => 'Baixa', 'cor' => '#0aa2c0'],
        default => ['texto' => '-', 'cor' => '#6c757d'],
    };
}

// =========================================================================
// 1. CONSULTA SQL CONFORME A TABELA ESCOLHIDA NO SELECT
// =========================================================================
if ($tabela === 'historico') {
    $tituloSecao    = 'Histórico de Movimentações';
    $subtituloTopo  = 'Relatório de Histórico e Movimentações';
    $prefixoArquivo = 'historico_solicitacoes_';

    $sql = "SELECT 
                h.id_hist,
                h.cod_solicitacao,
                s.id AS id_solicitacao,
                s.titulo AS titulo_solicitacao,
                s.categoria,
                h.status,
                h.descricao,
                h.data_registro
            FROM historico_solicitacoes h
            LEFT JOIN solicitacoes s ON h.cod_solicitacao = s.cod
            ORDER BY h.data_registro DESC";
} else {
    $tabela = 'solicitacoes';
    $tituloSecao    = 'Solicitações Cadastradas';
    $subtituloTopo  = 'Relatório Geral de Solicitações Cadastradas';
    $prefixoArquivo = 'solicitacoes_cadastradas_';

    $sql = "SELECT 
                id,
                titulo,
                descricao,
                categoria,
                prioridade,
                status,
                data_registro
            FROM solicitacoes
            ORDER BY data_registro DESC";
}

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$registros = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mapeia ID e Título por cod_solicitacao (para recuperar dados mesmo de linhas antigas de solicitações já excluídas)
$mapaSolicitacoes = [];
if ($tabela === 'historico') {
    foreach ($registros as $reg) {
        $cod = $reg['cod_solicitacao'] ?? '';
        if ($cod === '') continue;

        if (!isset($mapaSolicitacoes[$cod])) {
            $mapaSolicitacoes[$cod] = ['id' => null, 'titulo' => null];
        }

        if (!empty($reg['id_solicitacao'])) {
            $mapaSolicitacoes[$cod]['id'] = '#' . $reg['id_solicitacao'];
        } elseif ($mapaSolicitacoes[$cod]['id'] === null && preg_match('/#(\d+)/', $reg['descricao'] ?? '', $mId)) {
            $mapaSolicitacoes[$cod]['id'] = '#' . $mId[1];
        }

        if (!empty($reg['titulo_solicitacao'])) {
            $mapaSolicitacoes[$cod]['titulo'] = $reg['titulo_solicitacao'];
        } elseif ($mapaSolicitacoes[$cod]['titulo'] === null && preg_match('/\(([^)]+)\)/', $reg['descricao'] ?? '', $mTit)) {
            $mapaSolicitacoes[$cod]['titulo'] = $mTit[1];
        }
    }
}

function resolverIdSolicitacao(array $item, array $mapa): string {
    $cod = $item['cod_solicitacao'] ?? '';
    return $mapa[$cod]['id'] ?? '-';
}

function resolverTituloSolicitacao(array $item, array $mapa): string {
    $cod = $item['cod_solicitacao'] ?? '';
    return $mapa[$cod]['titulo'] ?? 'Solicitação removida';
}

$totalRegistros = count($registros);

switch ($formato) {
    // =========================================================================
    // 1. EXPORTAÇÃO / EXIBIÇÃO EM PDF
    // =========================================================================
    case 'pdf':
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 14,
            'margin_bottom' => 18,
        ]);

        $mpdf->SetFooter('<span style="color:#6c757d; font-size:8pt;">Painel de Solicitações &bull; Emitido em ' . $dataEmissao . '</span>||<span style="color:#4a258c; font-weight:bold; font-size:8pt;">Página {PAGENO} de {nbpg}</span>');

        $stylesheet = file_get_contents(__DIR__ . '/assets/css/relatorio_pdf.css');
        $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

        $html = '
        <div class="topo-pdf">
            <h1>Painel de Solicitações</h1>
            <p>' . $subtituloTopo . ' &bull; Emitido em ' . $dataEmissao . '</p>
        </div>

        <div class="faixa-secao">
            <table>
                <tr>
                    <td class="titulo-secao-texto">' . $tituloSecao . '</td>
                    <td align="right" class="contador-texto">
                        Total: ' . $totalRegistros . ($totalRegistros === 1 ? ' registro' : ' registros') . '
                    </td>
                </tr>
            </table>
        </div>';

        if ($tabela === 'historico') {
            $html .= '
            <table class="dados">
                <thead>
                    <tr>
                        <th width="7%">ID</th>
                        <th width="11%">ID Sol.</th>
                        <th width="22%">Solicitação</th>
                        <th width="14%">Status</th>
                        <th width="31%">Descrição da Movimentação</th>
                        <th width="15%">Data/Hora</th>
                    </tr>
                </thead>
                <tbody>';

            if (empty($registros)) {
                $html .= '<tr><td colspan="6" style="text-align:center; padding: 18px; color: #6c757d;">Nenhum registro encontrado no histórico.</td></tr>';
            } else {
                foreach ($registros as $item) {
                    $dataFormatada = date('d/m/Y H:i', strtotime($item['data_registro']));
                    $titulo = resolverTituloSolicitacao($item, $mapaSolicitacoes);
                    $categoria = $item['categoria'] ?? '';
                    $st = obterDadosStatus((int)$item['status']);
                    $idSolicitacao = resolverIdSolicitacao($item, $mapaSolicitacoes);

                    $html .= '
                        <tr>
                            <td style="color:#4a258c; font-weight:bold;">#' . htmlspecialchars((string)$item['id_hist']) . '</td>
                            <td style="color:#6f42c1; font-weight:bold;">' . htmlspecialchars($idSolicitacao) . '</td>
                            <td>
                                <div style="font-weight:bold;">' . htmlspecialchars($titulo) . '</div>';
                    if ($categoria !== '') {
                        $html .= '<div class="texto-categoria">' . htmlspecialchars($categoria) . '</div>';
                    }
                    $html .= '
                            </td>
                            <td class="texto-status" style="color:' . $st['cor'] . ';">' . htmlspecialchars($st['texto']) . '</td>
                            <td>' . nl2br(htmlspecialchars($item['descricao'])) . '</td>
                            <td class="texto-data">' . $dataFormatada . '</td>
                        </tr>';
                }
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '
            <table class="dados">
                <thead>
                    <tr>
                        <th width="7%">ID</th>
                        <th width="38%">Solicitação / Descrição</th>
                        <th width="15%">Categoria</th>
                        <th width="11%">Prioridade</th>
                        <th width="14%">Status</th>
                        <th width="15%">Data</th>
                    </tr>
                </thead>
                <tbody>';

            if (empty($registros)) {
                $html .= '<tr><td colspan="6" style="text-align:center; padding: 18px; color: #6c757d;">Nenhuma solicitação cadastrada.</td></tr>';
            } else {
                foreach ($registros as $item) {
                    $dataFormatada = date('d/m/Y H:i', strtotime($item['data_registro']));
                    $pr = obterDadosPrioridade((int)$item['prioridade']);
                    $st = obterDadosStatus((int)$item['status']);

                    $html .= '
                        <tr>
                            <td style="color:#4a258c; font-weight:bold;">#' . htmlspecialchars((string)$item['id']) . '</td>
                            <td>
                                <div style="font-weight:bold; margin-bottom:2px;">' . htmlspecialchars($item['titulo']) . '</div>
                                <div style="color:#555; font-size:8.5pt;">' . nl2br(htmlspecialchars($item['descricao'])) . '</div>
                            </td>
                            <td class="texto-categoria" style="font-weight:bold;">' . htmlspecialchars($item['categoria']) . '</td>
                            <td class="texto-status" style="color:' . $pr['cor'] . ';">' . htmlspecialchars($pr['texto']) . '</td>
                            <td class="texto-status" style="color:' . $st['cor'] . ';">' . htmlspecialchars($st['texto']) . '</td>
                            <td class="texto-data">' . $dataFormatada . '</td>
                        </tr>';
                }
            }
            $html .= '</tbody></table>';
        }

        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output($prefixoArquivo . date('Ymd_His') . '.pdf', \Mpdf\Output\Destination::INLINE);
        exit;

    // =========================================================================
    // 2. EXPORTAÇÃO PARA PLANILHA EXCEL (.xls)
    // =========================================================================
    case 'excel':
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=" . $prefixoArquivo . date('Ymd_His') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "\xEF\xBB\xBF";
        echo '<table border="1">';

        if ($tabela === 'historico') {
            echo '<thead>
                    <tr style="background-color:#5b32a8; color:#ffffff; font-weight:bold;">
                        <th>ID Histórico</th>
                        <th>ID Solicitação</th>
                        <th>Solicitação</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Descrição</th>
                        <th>Data Registro</th>
                    </tr>
                  </thead><tbody>';

            foreach ($registros as $item) {
                $st = obterDadosStatus((int)$item['status']);
                echo '<tr>
                        <td>' . $item['id_hist'] . '</td>
                        <td>' . htmlspecialchars(resolverIdSolicitacao($item, $mapaSolicitacoes)) . '</td>
                        <td>' . htmlspecialchars(resolverTituloSolicitacao($item, $mapaSolicitacoes)) . '</td>
                        <td>' . htmlspecialchars($item['categoria'] ?? '-') . '</td>
                        <td>' . htmlspecialchars($st['texto']) . '</td>
                        <td>' . htmlspecialchars($item['descricao']) . '</td>
                        <td>' . date('d/m/Y H:i', strtotime($item['data_registro'])) . '</td>
                      </tr>';
            }
        } else {
            echo '<thead>
                    <tr style="background-color:#5b32a8; color:#ffffff; font-weight:bold;">
                        <th>ID</th>
                        <th>Título</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Data Registro</th>
                    </tr>
                  </thead><tbody>';

            foreach ($registros as $item) {
                $pr = obterDadosPrioridade((int)$item['prioridade']);
                $st = obterDadosStatus((int)$item['status']);
                echo '<tr>
                        <td>' . $item['id'] . '</td>
                        <td>' . htmlspecialchars($item['titulo']) . '</td>
                        <td>' . htmlspecialchars($item['descricao']) . '</td>
                        <td>' . htmlspecialchars($item['categoria']) . '</td>
                        <td>' . htmlspecialchars($pr['texto']) . '</td>
                        <td>' . htmlspecialchars($st['texto']) . '</td>
                        <td>' . date('d/m/Y H:i', strtotime($item['data_registro'])) . '</td>
                      </tr>';
            }
        }

        echo '</tbody></table>';
        exit;

    // =========================================================================
    // 3. EXPORTAÇÃO PARA .CSV
    // =========================================================================
    case 'csv':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $prefixoArquivo . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        if ($tabela === 'historico') {
            fputcsv($output, ['ID Histórico', 'ID Solicitação', 'Solicitação', 'Categoria', 'Status', 'Descrição', 'Data Registro'], ';');
            foreach ($registros as $item) {
                $st = obterDadosStatus((int)$item['status']);
                fputcsv($output, [
                    $item['id_hist'],
                    resolverIdSolicitacao($item, $mapaSolicitacoes),
                    resolverTituloSolicitacao($item, $mapaSolicitacoes),
                    $item['categoria'] ?? '-',
                    $st['texto'],
                    $item['descricao'],
                    date('d/m/Y H:i', strtotime($item['data_registro']))
                ], ';');
            }
        } else {
            fputcsv($output, ['ID', 'Título', 'Descrição', 'Categoria', 'Prioridade', 'Status', 'Data Registro'], ';');
            foreach ($registros as $item) {
                $pr = obterDadosPrioridade((int)$item['prioridade']);
                $st = obterDadosStatus((int)$item['status']);
                fputcsv($output, [
                    $item['id'],
                    $item['titulo'],
                    $item['descricao'],
                    $item['categoria'],
                    $pr['texto'],
                    $st['texto'],
                    date('d/m/Y H:i', strtotime($item['data_registro']))
                ], ';');
            }
        }

        fclose($output);
        exit;

    // =========================================================================
    // 4. EXPORTAÇÃO PARA .TXT
    // =========================================================================
    case 'txt':
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $prefixoArquivo . date('Ymd_His') . '.txt');

        echo "PAINEL DE SOLICITAÇÕES - " . mb_strtoupper($tituloSecao) . " (EMITIDO EM: {$dataEmissao})\r\n";
        echo str_repeat("=", 115) . "\r\n";

        if ($tabela === 'historico') {
            echo sprintf("%-6s | %-8s | %-22s | %-14s | %-30s | %-16s\r\n", "ID", "ID SOL.", "SOLICITAÇÃO", "STATUS", "DESCRIÇÃO", "DATA/HORA");
            echo str_repeat("-", 115) . "\r\n";

            foreach ($registros as $item) {
                $st = obterDadosStatus((int)$item['status']);
                echo sprintf(
                    "%-6s | %-8s | %-22s | %-14s | %-30s | %-16s\r\n",
                    '#' . $item['id_hist'],
                    resolverIdSolicitacao($item, $mapaSolicitacoes),
                    mb_strimwidth(resolverTituloSolicitacao($item, $mapaSolicitacoes), 0, 22, "..."),
                    $st['texto'],
                    mb_strimwidth(str_replace(["\r", "\n"], ' ', $item['descricao']), 0, 30, "..."),
                    date('d/m/Y H:i', strtotime($item['data_registro']))
                );
            }
        } else {
            echo sprintf("%-6s | %-24s | %-15s | %-11s | %-14s | %-16s\r\n", "ID", "TÍTULO", "CATEGORIA", "PRIORIDADE", "STATUS", "DATA/HORA");
            echo str_repeat("-", 115) . "\r\n";

            foreach ($registros as $item) {
                $pr = obterDadosPrioridade((int)$item['prioridade']);
                $st = obterDadosStatus((int)$item['status']);
                echo sprintf(
                    "%-6s | %-24s | %-15s | %-11s | %-14s | %-16s\r\n",
                    '#' . $item['id'],
                    mb_strimwidth($item['titulo'], 0, 24, "..."),
                    mb_strimwidth($item['categoria'], 0, 15, "..."),
                    $pr['texto'],
                    $st['texto'],
                    date('d/m/Y H:i', strtotime($item['data_registro']))
                );
            }
        }
        exit;
}