<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: text/html; charset=UTF-8');
date_default_timezone_set('America/Fortaleza');

require_once __DIR__ .'/conn/conn.php';

$acao_formulario = 'criar';

?>

<form id="form_solicitacoes" action="crud_solicitacoes.php?a=<?php echo htmlspecialchars($acao_formulario, ENT_QUOTES, 'UTF-8'); ?>" method="post" enctype="multipart/form-data">
    <input type="text" name="titulo" id="titulo">
    <textarea name="descricao" id="descricao"></textarea>
    <input type="text" name="categoria" id="categoria">
    <select name="prioridade" id="prioridade">
        <option value="3">Baixa</option>
        <option value="2">Média</option>
        <option value="1">Alta</option>
    </select>
    <button form="form_solicitacoes" type="submit"> Enviar</button>

</form>