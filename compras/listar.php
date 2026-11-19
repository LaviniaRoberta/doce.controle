
<?php
require_once "../config/conexao.php";

$dataInicio = $_GET["inicio"] ?? "";
$dataFim = $_GET["fim"] ?? "";

$sql = "
    SELECT
        compras.id,
        compras.estabelecimento,
        compras.data_compra,
        compras.valor_total,
        compras.observacao,
        GROUP_CONCAT(itens_compra.descricao, ', ') AS produtos
    FROM compras
    LEFT JOIN itens_compra
        ON compras.id = itens_compra.compra_id
    WHERE 1 = 1
";

$parametros = [];

if ($dataInicio !== "") {
    $sql .= " AND date(compras.data_compra) >= date(?)";
    $parametros[] = $dataInicio;
}

if ($dataFim !== "") {
    $sql .= " AND date(compras.data_compra) <= date(?)";
    $parametros[] = $dataFim;
}

$sql .= "
    GROUP BY compras.id
    ORDER BY compras.data_compra DESC, compras.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);

$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCompras = 0;

foreach ($compras as $compra) {
    $totalCompras += $compra["valor_total"];
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Compras | Doce Controle</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #0D0B12;
            color: #F5F3FA;
            min-height: 100vh;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* MENU LATERAL */

        .sidebar {
            width: 255px;
            background: #14111B;
            border-right: 1px solid #292333;
            padding: 28px 18px;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .logo {
            padding: 0 12px 30px;
            border-bottom: 1px solid #292333;
            margin-bottom: 25px;
        }

        .logo h2 {
            color: #B99AFF;
            font-size: 21px;
            letter-spacing: 1px;
        }

        .logo p {
            color: #81778F;
            font-size: 10px;
            letter-spacing: 2px;
            margin-top: 5px;
        }

        .menu-titulo {
            color: #71677F;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1.5px;
            padding: 0 12px;
            margin: 15px 0 10px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            border-radius: 10px;
            color: #A9A1B5;
            text-decoration: none;
            font-size: 13px;
            transition: .2s;
        }

        .menu a:hover,
        .menu a.ativo {
            background: #302344;
            color: #C8ACFF;
        }

        .menu .icone {
            width: 23px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-rodape {
            margin-top: auto;
            padding: 17px 12px;
            border-top: 1px solid #292333;
            color: #81778F;
            font-size: 11px;
        }

        /* CONTEÚDO */

        .conteudo {
            width: calc(100% - 255px);
            margin-left: 255px;
            padding: 35px;
        }

        .topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 35px;
        }

        .topo h1 {
            color: #FFFFFF;
            font-size: 25px;
        }

        .topo p {
            color: #958BA6;
            font-size: 13px;
            margin-top: 6px;
        }

        .data {
            background: #1C1825;
            border: 1px solid #30283D;
            color: #C4A7FF;
            padding: 11px 16px;
            border-radius: 10px;
            font-size: 12px;
        }

        /* DESTAQUE */

        .boas-vindas {
            background: linear-gradient(115deg, #302047, #1C1726);
            border: 1px solid #49336C;
            border-radius: 18px;
            padding: 27px;
            margin-bottom: 30px;
        }

        .boas-vindas span {
            color: #C4A7FF;
            font-size: 11px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .boas-vindas h2 {
            color: white;
            font-size: 22px;
            margin: 10px 0 7px;
        }

        .boas-vindas p {
            color: #B6AFC3;
            font-size: 13px;
        }

        /* MENSAGENS */

        .mensagem {
            padding: 14px 17px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .sucesso {
            background: rgba(74, 222, 128, .10);
            border: 1px solid rgba(74, 222, 128, .25);
            color: #4ADE80;
        }

        .erro {
            background: rgba(248, 113, 113, .10);
            border: 1px solid rgba(248, 113, 113, .25);
            color: #F87171;
        }

        /* BOTÕES */

        .botao {
            display: inline-block;
            background: #8B5CF6;
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: bold;
            transition: .2s;
        }

        .botao:hover {
            background: #7040D8;
        }

        .botao-secundario {
            display: inline-block;
            background: #292333;
            color: #C4A7FF;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 9px;
            font-size: 13px;
        }

        .botao-secundario:hover {
            background: #393044;
        }

        /* FILTROS */

        .filtro-container {
            background: #191620;
            border: 1px solid #30283D;
            border-radius: 15px;
            padding: 23px;
            margin-bottom: 25px;
        }

        .filtro-container h2 {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .filtro-form {
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            color: #C4A7FF;
            font-size: 12px;
            font-weight: bold;
        }

        input {
            background: #100E16;
            border: 1px solid #393044;
            border-radius: 9px;
            padding: 12px;
            color: white;
            outline: none;
        }

        input:focus {
            border-color: #8B5CF6;
        }

        /* RESUMO */

        .resumo {
            background: #191620;
            border: 1px solid #49336C;
            border-radius: 15px;
            padding: 23px;
            margin-bottom: 25px;
        }

        .resumo span {
            color: #958BA6;
            font-size: 12px;
        }

        .resumo h2 {
            color: #C4A7FF;
            font-size: 27px;
            margin-top: 10px;
        }

        /* TABELA */

        .tabela-container {
            background: #191620;
            border: 1px solid #30283D;
            border-radius: 15px;
            padding: 20px;
            overflow-x: auto;
        }

        .titulo-tabela {
            font-size: 17px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: #302344;
            color: #C4A7FF;
            padding: 15px;
            text-align: left;
            font-size: 12px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #30283D;
            color: #B6AFC3;
            font-size: 13px;
        }

        tbody tr:hover {
            background: #211B2B;
        }

        .valor {
            color: #C4A7FF;
            font-weight: bold;
        }

        .acoes-tabela {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .editar {
            background: #302344;
            color: #C4A7FF;
            padding: 8px 12px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 12px;
        }

        .editar:hover {
            background: #49336C;
        }

        .form-excluir {
            margin: 0;
            padding: 0;
            border: none;
            background: transparent;
        }

        .excluir {
            background: rgba(248, 113, 113, .12);
            color: #F87171;
            padding: 8px 12px;
            border-radius: 7px;
            border: none;
            font-size: 12px;
            cursor: pointer;
        }

        .excluir:hover {
            background: #7F1D1D;
            color: white;
        }

        .vazio {
            text-align: center;
            padding: 35px;
            color: #958BA6;
        }

        .rodape {
            color: #71677F;
            text-align: center;
            font-size: 11px;
            margin-top: 35px;
            padding: 20px;
        }

        @media (max-width: 700px) {
            .layout {
                display: block;
            }

            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .sidebar-rodape {
                margin-top: 20px;
            }

            .conteudo {
                width: 100%;
                margin-left: 0;
                padding: 20px;
            }

            .topo {
                align-items: flex-start;
                flex-direction: column;
            }

            .filtro-form {
                align-items: stretch;
                flex-direction: column;
            }

            .campo,
            .campo input {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="logo">
            <h2>DOCE CONTROLE.</h2>
            <p>GESTÃO INTELIGENTE</p>
        </div>

        <p class="menu-titulo">PRINCIPAL</p>

        <nav class="menu">
            <a href="../dashboard.php">
                <span class="icone">⌂</span>
                Dashboard
            </a>

            <a href="../produtos/listar.php">
                <span class="icone">▦</span>
                Produtos
            </a>

            <a href="listar.php" class="ativo">
                <span class="icone">🛒</span>
                Compras
            </a>

            <a href="../vendas/listar.php">
                <span class="icone">💰</span>
                Vendas
            </a>

            <a href="../despesas/listar.php">
                <span class="icone">↗</span>
                Despesas
            </a>
        </nav>

        <p class="menu-titulo">CONTROLE</p>

        <nav class="menu">
            <a href="../estoque/index.php">
                <span class="icone">▤</span>
                Estoque
            </a>

            <a href="../estoque/historico.php">
                <span class="icone">◷</span>
                Histórico
            </a>

            <a href="../relatorios/index.php">
                <span class="icone">▥</span>
                Relatórios
            </a>
        </nav>

        <div class="sidebar-rodape">
            Doce Controle © <?= date("Y") ?>
            <br>
            Sistema de gestão financeira
        </div>

    </aside>

    <main class="conteudo">

        <header class="topo">
            <div>
                <h1>Compras</h1>
                <p>Gerencie suas compras e acompanhe os gastos.</p>
            </div>

            <div class="data">
                ◷ <?= date("d/m/Y") ?>
            </div>
        </header>

        <section class="boas-vindas">
            <span>CONTROLE DE COMPRAS</span>
            <h2>Organize suas entradas e gastos.</h2>
            <p>
                Acompanhe o histórico das compras, consulte os valores
                e mantenha tudo organizado.
            </p>
        </section>

        <?php if (isset($_GET["sucesso"])): ?>
            <div class="mensagem sucesso">
                Compra cadastrada com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["editado"])): ?>
            <div class="mensagem sucesso">
                Compra atualizada com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["excluido"])): ?>
            <div class="mensagem sucesso">
                Compra excluída com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["erro"])): ?>
            <div class="mensagem erro">
                Não foi possível realizar a operação.
            </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:25px;">
            <a href="cadastrar.php" class="botao">
                + Nova compra
            </a>

            <a href="../dashboard.php" class="botao-secundario">
                Voltar ao painel
            </a>
        </div>

        <section class="filtro-container">

            <h2>Filtrar compras por período</h2>

            <form method="GET" class="filtro-form">

                <div class="campo">
                    <label for="inicio">Data inicial</label>

                    <input
                        type="date"
                        id="inicio"
                        name="inicio"
                        value="<?= htmlspecialchars($dataInicio) ?>"
                    >
                </div>

                <div class="campo">
                    <label for="fim">Data final</label>

                    <input
                        type="date"
                        id="fim"
                        name="fim"
                        value="<?= htmlspecialchars($dataFim) ?>"
                    >
                </div>

                <button type="submit" class="botao">
                    Filtrar
                </button>

                <a href="listar.php" class="botao-secundario">
                    Limpar filtro
                </a>

            </form>

        </section>

        <section class="resumo">
            <span>Total gasto no período</span>

            <h2>
                R$ <?= number_format($totalCompras, 2, ",", ".") ?>
            </h2>
        </section>

        <section class="tabela-container">

            <h2 class="titulo-tabela">Histórico de compras</h2>

            <table>

                <thead>
                    <tr>
                        <th>Estabelecimento</th>
                        <th>Data</th>
                        <th>Produtos</th>
                        <th>Valor total</th>
                        <th>Observação</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($compras) > 0): ?>

                        <?php foreach ($compras as $compra): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($compra["estabelecimento"]) ?>
                                </td>

                                <td>
                                    <?= date("d/m/Y", strtotime($compra["data_compra"])) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($compra["produtos"] ?? "Sem produtos") ?>
                                </td>

                                <td class="valor">
                                    R$ <?= number_format($compra["valor_total"], 2, ",", ".") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($compra["observacao"] ?? "") ?>
                                </td>

                                <td>
                                    <div class="acoes-tabela">

                                        <a
                                            href="editar.php?id=<?= (int)$compra["id"] ?>"
                                            class="editar"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            action="excluir.php"
                                            method="POST"
                                            class="form-excluir"
                                            onsubmit="return confirm('Tem certeza que deseja excluir esta compra?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$compra["id"] ?>"
                                            >

                                            <button type="submit" class="excluir">
                                                Excluir
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="vazio">
                                Nenhuma compra encontrada nesse período.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </section>

        <footer class="rodape">
            Doce Controle © <?= date("Y") ?> - Todos os direitos reservados.
        </footer>

    </main>

</div>

</body>
</html>
