
<?php
require_once "../config/conexao.php";

// Busca todos os produtos
$stmt = $pdo->query("
    SELECT *
    FROM produtos
    ORDER BY nome ASC
");

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula os indicadores do estoque
$totalProdutos = count($produtos);
$totalUnidades = 0;
$estoqueBaixo = 0;
$semEstoque = 0;

foreach ($produtos as $produto) {
    $estoque = (int)$produto["estoque"];

    $totalUnidades += $estoque;

    if ($estoque <= 0) {
        $semEstoque++;
    } elseif ($estoque <= 5) {
        $estoqueBaixo++;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque | Doce Controle</title>

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

        .mensagem {
            background: #192D23;
            border: 1px solid #286044;
            color: #8DE0AC;
            padding: 14px 18px;
            border-radius: 9px;
            margin-bottom: 20px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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

        .tabela-container {
            background: #191620;
            border: 1px solid #292333;
            border-radius: 14px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
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

        .status {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .estoque-sem {
            background: #3B2028;
            color: #FF929F;
        }

        .estoque-baixo {
            background: #3B3020;
            color: #F5C875;
        }

        .estoque-ok {
            background: #1E3529;
            color: #83D9A1;
        }

        .acoes-tabela {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .acoes-tabela a {
            color: #C4A7FF;
            text-decoration: none;
            font-size: 13px;
        }

        .acoes-tabela a:hover {
            color: white;
            text-decoration: underline;
        }

        .vazio {
            text-align: center;
            color: #A99BB8;
            padding: 30px;
        }

        .aviso {
            color: #93869F;
            font-size: 13px;
            margin-top: 18px;
        }

        @media (max-width: 1000px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
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

        <a href="index.php" class="ativo">Estoque</a>
        <a href="historico.php">Histórico</a>
        <a href="../relatorios/index.php">Relatórios</a>
    </nav>
</aside>

<main>

    <section class="topo">
        <h1>Controle de Estoque</h1>
        <p>Acompanhe os produtos e suas quantidades disponíveis.</p>
    </section>

    <?php if (isset($_GET["ajustado"])): ?>
        <div class="mensagem">
            Estoque atualizado com sucesso!
        </div>
    <?php endif; ?>

    <div class="acoes">
        <a href="../produtos/cadastrar.php" class="botao">
            + Cadastrar produto
        </a>

        <a href="historico.php" class="botao botao-secundario">
            Histórico de movimentações
        </a>
    </div>

    <section class="cards">

        <div class="card">
            <h3>Produtos cadastrados</h3>
            <p><?= $totalProdutos ?></p>
        </div>

        <div class="card">
            <h3>Total de unidades</h3>
            <p><?= $totalUnidades ?></p>
        </div>

        <div class="card">
            <h3>Estoque baixo</h3>
            <p><?= $estoqueBaixo ?></p>
        </div>

        <div class="card">
            <h3>Sem estoque</h3>
            <p><?= $semEstoque ?></p>
        </div>

    </section>

    <h2 class="secao-titulo">Produtos em estoque</h2>

    <div class="tabela-container">

        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Quantidade</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($produtos) > 0): ?>

                    <?php foreach ($produtos as $produto): ?>

                        <?php
                        $estoque = (int)$produto["estoque"];

                        if ($estoque <= 0) {
                            $status = "Sem estoque";
                            $classe = "estoque-sem";
                        } elseif ($estoque <= 5) {
                            $status = "Estoque baixo";
                            $classe = "estoque-baixo";
                        } else {
                            $status = "Disponível";
                            $classe = "estoque-ok";
                        }
                        ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars($produto["nome"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($produto["categoria"] ?? "") ?>
                            </td>

                            <td>
                                <?= $estoque ?> unidades
                            </td>

                            <td>
                                <span class="status <?= $classe ?>">
                                    <?= $status ?>
                                </span>
                            </td>

                            <td>
                                <div class="acoes-tabela">
                                    <a href="ajustar.php?id=<?= (int)$produto["id"] ?>">
                                        Ajustar estoque
                                    </a>

                                    <a href="../produtos/editar.php?id=<?= (int)$produto["id"] ?>">
                                        Editar produto
                                    </a>
                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="vazio">
                            Nenhum produto cadastrado.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

    <p class="aviso">
        Produtos com 5 unidades ou menos são considerados com estoque baixo.
    </p>

</main>

</body>
</html>
