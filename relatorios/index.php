
<?php
require_once "../config/conexao.php";

// TOTAL DE VENDAS
$stmt = $pdo->query("
    SELECT COALESCE(SUM(valor_total), 0)
    FROM vendas
");
$totalVendas = (float)$stmt->fetchColumn();

// TOTAL DE COMPRAS
$stmt = $pdo->query("
    SELECT COALESCE(SUM(valor_total), 0)
    FROM compras
");
$totalCompras = (float)$stmt->fetchColumn();

// TOTAL DE DESPESAS
$stmt = $pdo->query("
    SELECT COALESCE(SUM(valor), 0)
    FROM despesas
");
$totalDespesas = (float)$stmt->fetchColumn();

// RESULTADO FINANCEIRO
$totalSaidas = $totalCompras + $totalDespesas;
$resultado = $totalVendas - $totalSaidas;

// QUANTIDADE DE VENDAS
$stmt = $pdo->query("SELECT COUNT(*) FROM vendas");
$quantidadeVendas = (int)$stmt->fetchColumn();

// QUANTIDADE DE COMPRAS
$stmt = $pdo->query("SELECT COUNT(*) FROM compras");
$quantidadeCompras = (int)$stmt->fetchColumn();

// QUANTIDADE DE DESPESAS
$stmt = $pdo->query("SELECT COUNT(*) FROM despesas");
$quantidadeDespesas = (int)$stmt->fetchColumn();

// RELATÓRIO MENSAL
$sql = "
    SELECT
        mes,
        SUM(vendas) AS vendas,
        SUM(compras) AS compras,
        SUM(despesas) AS despesas
    FROM (

        SELECT
            strftime('%Y-%m', data_venda) AS mes,
            SUM(valor_total) AS vendas,
            0 AS compras,
            0 AS despesas
        FROM vendas
        GROUP BY strftime('%Y-%m', data_venda)

        UNION ALL

        SELECT
            strftime('%Y-%m', data_compra) AS mes,
            0 AS vendas,
            SUM(valor_total) AS compras,
            0 AS despesas
        FROM compras
        GROUP BY strftime('%Y-%m', data_compra)

        UNION ALL

        SELECT
            strftime('%Y-%m', data_despesa) AS mes,
            0 AS vendas,
            0 AS compras,
            SUM(valor) AS despesas
        FROM despesas
        GROUP BY strftime('%Y-%m', data_despesa)

    )
    WHERE mes IS NOT NULL
    GROUP BY mes
    ORDER BY mes DESC
";

$stmt = $pdo->query($sql);
$relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FORMATA VALORES EM REAIS
function dinheiro($valor) {
    return "R$ " . number_format((float)$valor, 2, ",", ".");
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Relatórios Financeiros | Doce Controle</title>

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
            margin-bottom: 30px;
        }

        .botao:hover {
            background: #A78BFA;
        }

        .secao {
            margin-bottom: 35px;
        }

        .secao h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .descricao {
            color: #A99BB8;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .cards-resumo {
            grid-template-columns: repeat(5, 1fr);
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
            font-size: 23px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .card small {
            display: block;
            color: #93869F;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 12px;
        }

        .tabela-container {
            background: #191620;
            border: 1px solid #292333;
            border-radius: 14px;
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
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

        .resultado-positivo {
            color: #83D9A1;
        }

        .resultado-negativo {
            color: #FF929F;
        }

        .resultado-neutro {
            color: #C4A7FF;
        }

        .aviso {
            background: #211B2C;
            border: 1px solid #49336C;
            border-radius: 12px;
            padding: 18px;
            color: #B9B0C5;
            font-size: 13px;
            line-height: 1.6;
            margin-top: 25px;
        }

        .vazio {
            text-align: center;
            color: #A99BB8;
            padding: 30px;
        }

        @media (max-width: 1200px) {
            .cards-resumo {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 900px) {
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

            .cards,
            .cards-resumo {
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

        <a href="../estoque/index.php">Estoque</a>
        <a href="../estoque/historico.php">Histórico</a>
        <a href="index.php" class="ativo">Relatórios</a>
    </nav>

</aside>

<main>

    <section class="topo">
        <h1>Relatórios Financeiros</h1>
        <p>Acompanhe os resultados financeiros da confeitaria.</p>
    </section>

    <a href="../dashboard.php" class="botao">
        ← Voltar ao painel
    </a>

    <section class="secao">

        <h2>Resumo financeiro geral</h2>
        <p class="descricao">
            Confira os valores totais registrados no sistema.
        </p>

        <div class="cards cards-resumo">

            <div class="card">
                <h3>Total de vendas</h3>
                <p><?= dinheiro($totalVendas) ?></p>
            </div>

            <div class="card">
                <h3>Total de compras</h3>
                <p><?= dinheiro($totalCompras) ?></p>
            </div>

            <div class="card">
                <h3>Total de despesas</h3>
                <p><?= dinheiro($totalDespesas) ?></p>
            </div>

            <div class="card">
                <h3>Total de saídas</h3>
                <p><?= dinheiro($totalSaidas) ?></p>
            </div>

            <div class="card">
                <h3>Resultado estimado</h3>
                <p class="<?= $resultado >= 0 ? 'resultado-positivo' : 'resultado-negativo' ?>">
                    <?= dinheiro($resultado) ?>
                </p>
            </div>

        </div>

    </section>

    <section class="secao">

        <h2>Informações gerais</h2>
        <p class="descricao">
            Quantidade de registros cadastrados em cada módulo.
        </p>

        <div class="cards">

            <div class="card">
                <h3>Vendas realizadas</h3>
                <p><?= $quantidadeVendas ?></p>
            </div>

            <div class="card">
                <h3>Compras realizadas</h3>
                <p><?= $quantidadeCompras ?></p>
            </div>

            <div class="card">
                <h3>Despesas cadastradas</h3>
                <p><?= $quantidadeDespesas ?></p>
            </div>

        </div>

    </section>

    <section class="secao">

        <h2>Relatório mensal</h2>

        <p class="descricao">
            Compare o faturamento, as compras e as despesas de cada mês.
        </p>

        <div class="tabela-container">

            <table>

                <thead>
                    <tr>
                        <th>Mês</th>
                        <th>Vendas</th>
                        <th>Compras</th>
                        <th>Despesas</th>
                        <th>Total de saídas</th>
                        <th>Resultado</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($relatorios) > 0): ?>

                        <?php foreach ($relatorios as $relatorio): ?>

                            <?php
                            $vendasMes = (float)$relatorio["vendas"];
                            $comprasMes = (float)$relatorio["compras"];
                            $despesasMes = (float)$relatorio["despesas"];

                            $saidasMes = $comprasMes + $despesasMes;
                            $resultadoMes = $vendasMes - $saidasMes;

                            $mesFormatado = date(
                                "m/Y",
                                strtotime($relatorio["mes"] . "-01")
                            );
                            ?>

                            <tr>

                                <td><?= $mesFormatado ?></td>

                                <td><?= dinheiro($vendasMes) ?></td>

                                <td><?= dinheiro($comprasMes) ?></td>

                                <td><?= dinheiro($despesasMes) ?></td>

                                <td><?= dinheiro($saidasMes) ?></td>

                                <td>
                                    <strong class="<?= $resultadoMes >= 0 ? 'resultado-positivo' : 'resultado-negativo' ?>">
                                        <?= dinheiro($resultadoMes) ?>
                                    </strong>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="vazio">
                                Ainda não existem dados para os relatórios.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <section class="secao">

        <h2>Comparação financeira</h2>

        <p class="descricao">
            Veja a diferença entre as receitas e os gastos registrados.
        </p>

        <div class="cards">

            <div class="card">
                <h3>Receitas</h3>
                <p><?= dinheiro($totalVendas) ?></p>
                <small>Total recebido pelas vendas.</small>
            </div>

            <div class="card">
                <h3>Gastos</h3>
                <p><?= dinheiro($totalSaidas) ?></p>
                <small>Compras somadas às despesas.</small>
            </div>

            <div class="card">
                <h3>Diferença</h3>

                <p class="<?= $resultado > 0 ? 'resultado-positivo' : ($resultado < 0 ? 'resultado-negativo' : 'resultado-neutro') ?>">
                    <?= dinheiro($resultado) ?>
                </p>

                <?php if ($resultado > 0): ?>
                    <small>As receitas superam os gastos.</small>
                <?php elseif ($resultado < 0): ?>
                    <small>Os gastos superam as receitas.</small>
                <?php else: ?>
                    <small>Receitas e gastos estão iguais.</small>
                <?php endif; ?>

            </div>

        </div>

    </section>

    <div class="aviso">
        <strong>Observação:</strong> o resultado apresentado é uma estimativa,
        calculada pela diferença entre vendas, compras e despesas cadastradas.
    </div>

</main>

</body>
</html>
