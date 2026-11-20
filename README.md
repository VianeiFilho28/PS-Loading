# Painel de Solicitações

Aplicação web desenvolvida em **PHP 8+**, **MySQLi** e **Bootstrap 5** para controle, acompanhamento, histórico de movimentações e exportação de relatórios de chamados/solicitações internas.

---

## Funcionalidades

* **CRUD Completo de Solicitações (`crud_solicitacoes.php`):**
  * Cadastro de novas solicitações com os campos: título, descrição, categoria e prioridade (baixa, média ou alta).
  * Edição de solicitações com validação rigorosa de fluxo de estados:
    * `1` — **Aberta**
    * `2` — **Em Andamento**
    * `3` — **Concluída**
    * *(Não é permitido pular diretamente de Aberta para Concluída nem retroceder um status).*
  * Exclusão de registros.
* **Histórico Automático de Movimentações (`historico_solicitacoes`):**
  * Registra automaticamente cada abertura (`1`), mudança para Em Andamento (`2`), conclusão (`3`) e exclusão (`4`), mantendo a rastreabilidade completa mesmo após a exclusão do chamado original.
* **Listagem Interativa (`index.php`):**
  * Busca rápida em tempo real por texto.
  * Filtros dinâmicos por **Prioridade** (`1 = Alta`, `2 = Média`, `3 = Baixa`) e **Status**.
  * Paginação no lado do cliente com seletor de registros por página (`10`, `20`, `30` ou `50`) e contador dinâmico.
* **Exportação de Relatórios (`exportar_historico.php`):**
  * Suporte para exportação tanto de **Solicitações Cadastradas** quanto do **Histórico de Movimentações**.
  * Formatos disponíveis:
    * **PDF (`.pdf`)** — Renderizado via biblioteca **mPDF** (`mpdf/mpdf`) com layout estilizado na identidade visual do sistema.
    * **Planilha Excel (`.xls`)** — Tabela formatada com codificação UTF-8 BOM.
    * **CSV (`.csv`)** — Separado por ponto e vírgula (`;`) compatível com Excel em PT-BR.
    * **Texto Plano (`.txt`)** — Relatório tabular alinhado.

---

## Estrutura de Diretórios

```text
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css     # Framework Bootstrap 5
│   │   ├── estilo.css            # Folha de estilos principal do painel web
│   │   └── relatorio_pdf.css     # Folha de estilos dedicada ao mPDF
│   └── js/
│       └── bootstrap.bundle.min.js
├── conn/
│   └── conn.php                  # Conexão MySQLi com o banco de dados 'solicitacao'
├── vendor/
│   └── autoload.php              # Dependências do Composer (mPDF)
├── crud_solicitacoes.php         # Controlador de criação, edição, exclusão e histórico
├── exportar_historico.php        # Gerador de relatórios (PDF, XLS, CSV e TXT)
├── index.php                     # Interface principal do sistema
└── /Banco de Dados para importar # Pasta com o Banco de Dados
    └── solicitacao.sql           # Script SQL de criação das tabelas