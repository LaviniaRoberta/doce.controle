
<?php
require_once "../config/conexao.php";

// Recebe os filtros
$produto_id = filter_input(INPUT_GET, "produto_id", FILTER_VALIDATE_INT);
$tipo = $_GET["tipo"] ?? "";
$data_inicio = $_GET["data_inicio"] ?? "";
$data_fim = $_GET["data_fim"] ?? "";

// Busca os produtos para o filtro
$produtos = $pdo->query("
    SELECT id, nome
    FROM produtos
    ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

// Monta os filtros da consulta
$where = " WHERE 1 = 1";
$parametros = [];

if ($produto_id) {
    $where .= " AND movimentacoes_estoque.produto_id = ?";
    $parametros[] = $produto_id;
}

if (in_array($tipo, ["Entrada", "Saída"], true)) {
    $where .= " AND movimentacoes_estoque.tipo = ?";
    $parametros[] = $tipo;
}

if ($data_inicio !== "") {
    $where .= " AND date(movimentacoes_estoque.data_movimentacao) >= date(?)";
    $parametros[] = $data_inicio;
}

if ($data_fim !== "") {
    $where .= " AND date(movimentacoes_estoque.data_movimentacao) <= date(?)";
    $parametros[] = $data_fim;
}

// Busca as movimentações
$sql = "
    SELECT
        movimentacoes_estoque.id,
        COALESCE(produtos.nome, 'Compra de ingrediente/material') AS produto,
        movimentacoes_estoque.produto_id,
        movimentacoes_estoque.tipo,
        movimentacoes_estoque.quantidade,
        movimentacoes_estoque.descricao,
        movimentacoes_estoque.data_movimentacao
    FROM movimentacoes_estoque
    LEFT JOIN produtos
        ON movimentacoes_estoque.produto_id = produtos.id
    $where
    ORDER BY movimentacoes_estoque.data_movimentacao DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula os totais usando os mesmos filtros
$sqlTotais = "
    SELECT
        COALESCE(SUM(
            CASE
                WHEN movimentacoes_estoque.tipo = 'Entrada'
                THEN movimentacoes_estoque.quantidade
                ELSE 0
            END
        ), 0) AS entradas,

        COALESCE(SUM(
            CASE
                WHEN movimentacoes_estoque.tipo = 'Saída'
                THEN movimentacoes_estoque.quantidade
                ELSE 0
            END
        ), 0) AS saidas

    FROM movimentacoes_estoque
    $where
";

$stmtTotais = $pdo->prepare($sqlTotais);
$stmtTotais->execute($parametros);
$totais = $stmtTotais->fetch(PDO::FETCH_ASSOC);

$entradas = $totais["entradas"];
$saidas = $totais["saidas"];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Estoque | Doce Controle</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #0D0B12;
            color: #F5F0FA;
        }

        .sidebar {
            width: 255px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #14111B;
            border-right: 1px solid #292333;
            padding: 30px 20px;
        }

        .logo {
            color: #C4A7FF;
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 40px;
        }

        .menu-titulo {
            color: #81758F;
            font-size: 11px;
            text-transform: uppercase;
            margin: 25px 0 12px;
            letter-spacing: 1px;
        }

        .menu a {
            display: block;
            color: #B9B0C5;
            text-decoration: none;
            padding: 13px 15px;
            border-radius: 9px;
            margin-bottom: 6px;
            font-size: 14px;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.ativo {
            background: #292036;
            color: #C4A7FF;
        }

        main {
            margin-left: 255px;
            padding: 40px;
        }

        .topo {
            background: linear-gradient(120deg, #302047, #1C1726);
            border: 1px solid #49336C;
            border-radius: 18px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .topo h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .topo p {
            color: #C5B8D6;
            font-size: 14px;
        }

        .acoes {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .botao {
            display: inline-block;
            background: #8B5CF6;
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }

        .botao:hover {
            background: #A78BFA;
        }

        .botao-secundario {
            background: #211B2C;
            border: 1px solid #49336C;
            color: #C4A7FF;
        }

        .botao-secundario:hover {
            background: #302441;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 35px;
        }

        .card {
            background: #191620;
            border: 1px solid #292333;
            border-radius: 14px;
            padding: 23px;
        }

        .card h3 {
            color: #A99BB8;
            font-size: 13px;
            font-weight: normal;
            margin-bottom: 15px;
        }

        .card p {
            color: #C4A7FF;
            font-size: 29px;
            font-weight: bold;
        }

        .secao-titulo {
            font-size: 20px;
            margin-bottom: 18px;
        }

        .filtros {
            background: #191620;
            border: 1px solid #292333;
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 35px;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .campo label {
            display: block;
            color: #C4A7FF;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 9px;
        }

        .campo input,
        .campo select {
            width: 100%;
            padding: 13px;
            background: #0D0B12;
            border: 1px solid #393044;
            border-radius: 9px;
            color: #F5F0FA;
            font-size: 14px;
            outline: none;
        }

        .campo input:focus,
        .campo select:focus {
            border-color: #A78BFA;
        }

        .botoes-filtro {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .tabela-container {
            background: #191620;
            border: 1px solid #292333;
            border-radius: 14px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th {
            color: #C4A7FF;
            font-size: 12px;
            text-transform: uppercase;
            text-align: left;
            padding: 18px;
            background: #211B2C;
        }

        td {
            padding: 17px 18px;
            border-top: 1px solid #292333;
            color: #D8D0E0;
            font-size: 14px;
        }

        tr:hover td {
            background: #201B29;
        }

        .tipo {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .entrada {
            background: #1E3529;
            color: #83D9A1;
        }

        .saida {
            background: #3B2028;
            color: #FF929F;
        }

        .vazio {
            text-align: center;
            color: #A99BB8;
            padding: 30px;
        }

        @media (max-width: 1000px) {
            .filtros-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            main {
                margin-left: 0;
                padding: 20px;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="logo">DOCE CONTROLE.</div>

    <nav class="menu">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../produtos/listar.php">Produtos</a>
        <a href="../compras/listar.php">Compras</a>
        <a href="../vendas/listar.php">Vendas</a>
        <a href="../despesas/listar.php">Despesas</a>

        <p class="menu-titulo">Controle</p>

        <a href="index.php">Estoque</a>
        <a href="historico.php" class="ativo">Histórico</a>
        <a href="../relatorios/index.php">Relatórios</a>
    </nav>
</aside>

<main>

    <section class="topo">
        <h1>Histórico de Estoque</h1>
        <p>Acompanhe todas as entradas e saídas dos produtos e materiais.</p>
    </section>

    <div class="acoes">
        <a href="index.php" class="botao">
            Voltar ao estoque
        </a>

        <a href="../dashboard.php" class="botao botao-secundario">
            Voltar ao início
        </a>
    </div>

    <section class="cards">

        <div class="card">
            <h3>Total de Entradas</h3>
            <p>
                <?= number_format((float)$entradas, 2, ',', '.') ?>
            </p>
        </div>

        <div class="card">
            <h3>Total de Saídas</h3>
            <p>
                <?= number_format((float)$saidas, 2, ',', '.') ?>
            </p>
        </div>

    </section>

    <h2 class="secao-titulo">Filtrar movimentações</h2>

    <form method="GET" class="filtros">

        <div class="filtros-grid">

            <div class="campo">
                <label for="produto_id">Produto</label>

                <select name="produto_id" id="produto_id">
                    <option value="">Todos os produtos</option>

                    <?php foreach ($produtos as $produto): ?>
                        <option
                            value="<?= (int)$produto["id"] ?>"
                            <?= (string)$produto_id === (string)$produto["id"] ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars($produto["nome"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="tipo">Tipo de movimentação</label>

                <select name="tipo" id="tipo">
                    <option value="">Todas</option>

                    <option value="Entrada" <?= $tipo === "Entrada" ? "selected" : "" ?>>
                        Entrada
                    </option>

                    <option value="Saída" <?= $tipo === "Saída" ? "selected" : "" ?>>
                        Saída
                    </option>
                </select>
            </div>

            <div class="campo">
                <label for="data_inicio">Data inicial</label>

                <input
                    type="date"
                    name="data_inicio"
                    id="data_inicio"
                    value="<?= htmlspecialchars($data_inicio) ?>"
                >
            </div>

            <div class="campo">
                <label for="data_fim">Data final</label>

                <input
                    type="date"
                    name="data_fim"
                    id="data_fim"
                    value="<?= htmlspecialchars($data_fim) ?>"
                >
            </div>

        </div>

        <div class="botoes-filtro">
            <button type="submit" class="botao">
                Filtrar movimentações
            </button>

            <a href="historico.php" class="botao botao-secundario">
                Limpar filtros
            </a>
        </div>

    </form>

    <h2 class="secao-titulo">Movimentações registradas</h2>

    <div class="tabela-container">

        <table>

            <thead>
                <tr>
                    <th>Produto ou material</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Descrição</th>
                    <th>Data</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($movimentacoes) > 0): ?>

                    <?php foreach ($movimentacoes as $movimentacao): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($movimentacao["produto"]) ?>
                            </td>

                            <td>
                                <?php
                                $classeTipo = $movimentacao["tipo"] === "Entrada"
                                    ? "entrada"
                                    : "saida";
                                ?>

                                <span class="tipo <?= $classeTipo ?>">
                                    <?= htmlspecialchars($movimentacao["tipo"]) ?>
                                </span>
                            </td>

                            <td>
                                <?= number_format(
                                    (float)$movimentacao["quantidade"],
                                    2,
                                    ",",
                                    "."
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($movimentacao["descricao"] ?? "") ?>
                            </td>

                            <td>
                                <?php
                                $data = $movimentacao["data_movimentacao"];

                                echo $data
                                    ? date("d/m/Y H:i", strtotime($data))
                                    : "-";
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="vazio">
                            Nenhuma movimentação encontrada.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>
</html>
