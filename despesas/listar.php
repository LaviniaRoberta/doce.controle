
<?php
require_once "../config/conexao.php";

// Recebe as datas do filtro
$dataInicio = $_GET["inicio"] ?? "";
$dataFim = $_GET["fim"] ?? "";

// Consulta as despesas
$sql = "
    SELECT *
    FROM despesas
    WHERE 1 = 1
";

$parametros = [];

if ($dataInicio !== "") {
    $sql .= " AND date(data_despesa) >= date(?)";
    $parametros[] = $dataInicio;
}

if ($dataFim !== "") {
    $sql .= " AND date(data_despesa) <= date(?)";
    $parametros[] = $dataFim;
}

$sql .= " ORDER BY data_despesa DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);

$despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula o total das despesas
$totalDespesas = 0;

foreach ($despesas as $despesa) {
    $totalDespesas += $despesa["valor"];
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Despesas | Doce Controle</title>

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
            margin-bottom: 25px;
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

        /* BOTÕES */

        .acoes-topo {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .botao {
            display: inline-block;
            background: #8B5CF6;
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: bold;
            border: none;
            cursor: pointer;
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

        /* MENSAGENS */

        .mensagem {
            padding: 14px 17px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .sucesso {
            background: rgba(74, 222, 128, .10);
            border: 1px solid rgba(74, 222, 128, .25);
            color: #86EFAC;
        }

        .erro {
            background: rgba(248, 113, 113, .10);
            border: 1px solid rgba(248, 113, 113, .25);
            color: #F87171;
        }

        /* CARDS */

        .card-total {
            background: #191620;
            border: 1px solid #49336C;
            border-radius: 14px;
            padding: 23px;
            margin-bottom: 25px;
            max-width: 350px;
        }

        .card-total p {
            color: #958BA6;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .card-total h2 {
            color: #C4A7FF;
            font-size: 27px;
        }

        /* FILTRO */

        .painel {
            background: #191620;
            border: 1px solid #30283D;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .painel h2 {
            font-size: 17px;
            margin-bottom: 20px;
        }

        .filtros {
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 180px;
        }

        label {
            color: #C4A7FF;
            font-size: 12px;
            font-weight: bold;
        }

        input {
            width: 100%;
            background: #100E16;
            border: 1px solid #393044;
            border-radius: 9px;
            padding: 13px;
            color: white;
            outline: none;
            font-size: 13px;
        }

        input:focus {
            border-color: #8B5CF6;
        }

        /* TABELA */

        .tabela-container {
            background: #191620;
            border: 1px solid #30283D;
            border-radius: 15px;
            padding: 25px;
            overflow-x: auto;
        }

        .tabela-container h2 {
            font-size: 17px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        thead {
            background: #241D30;
        }

        th {
            color: #C4A7FF;
            font-size: 11px;
            text-align: left;
            padding: 15px;
            letter-spacing: .5px;
        }

        td {
            padding: 16px 15px;
            border-bottom: 1px solid #30283D;
            color: #D8D2E2;
            font-size: 12px;
        }

        tbody tr:hover {
            background: #211B2B;
        }

        .valor {
            color: #F0ABFC;
            font-weight: bold;
        }

        .acoes-tabela {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .editar {
            color: #C4A7FF;
            background: #302344;
            padding: 8px 11px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 11px;
        }

        .editar:hover {
            background: #49336C;
        }

        .excluir {
            background: #3A202A;
            color: #FCA5A5;
            border: none;
            padding: 8px 11px;
            border-radius: 7px;
            font-size: 11px;
            cursor: pointer;
        }

        .excluir:hover {
            background: #542936;
        }

        .form-excluir {
            display: inline;
        }

        .vazio {
            text-align: center;
            padding: 30px;
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

            .filtros {
                flex-direction: column;
                align-items: stretch;
            }

            .campo {
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

            <a href="../compras/listar.php">
                <span class="icone">🛒</span>
                Compras
            </a>

            <a href="../vendas/listar.php">
                <span class="icone">💰</span>
                Vendas
            </a>

            <a href="listar.php" class="ativo">
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
                <h1>Despesas</h1>
                <p>Gerencie e acompanhe os gastos da confeitaria.</p>
            </div>

            <div class="data">
                ◷ <?= date("d/m/Y") ?>
            </div>

        </header>

        <section class="boas-vindas">

            <span>CONTROLE FINANCEIRO</span>

            <h2>Organize suas despesas.</h2>

            <p>
                Acompanhe seus gastos, consulte períodos anteriores
                e mantenha suas finanças organizadas.
            </p>

        </section>

        <div class="acoes-topo">

            <a href="cadastrar.php" class="botao">
                + Nova despesa
            </a>

            <a href="../dashboard.php" class="botao-secundario">
                Voltar ao painel
            </a>

        </div>

        <?php if (isset($_GET["sucesso"])): ?>
            <div class="mensagem sucesso">
                Despesa cadastrada com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["editado"])): ?>
            <div class="mensagem sucesso">
                Despesa atualizada com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["excluido"])): ?>
            <div class="mensagem sucesso">
                Despesa excluída com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["erro"])): ?>
            <div class="mensagem erro">
                Não foi possível realizar a operação.
            </div>
        <?php endif; ?>

        <section class="card-total">

            <p>TOTAL GASTO NO PERÍODO</p>

            <h2>
                R$ <?= number_format($totalDespesas, 2, ",", ".") ?>
            </h2>

        </section>

        <section class="painel">

            <h2>Filtrar despesas por período</h2>

            <form method="GET" class="filtros">

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

        <section class="tabela-container">

            <h2>Histórico de despesas</h2>

            <table>

                <thead>
                    <tr>
                        <th>DESCRIÇÃO</th>
                        <th>CATEGORIA</th>
                        <th>DATA</th>
                        <th>VALOR</th>
                        <th>AÇÕES</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($despesas) > 0): ?>

                        <?php foreach ($despesas as $despesa): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($despesa["descricao"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($despesa["categoria"] ?? "") ?>
                                </td>

                                <td>
                                    <?= date("d/m/Y", strtotime($despesa["data_despesa"])) ?>
                                </td>

                                <td class="valor">
                                    R$ <?= number_format($despesa["valor"], 2, ",", ".") ?>
                                </td>

                                <td>

                                    <div class="acoes-tabela">

                                        <a
                                            href="editar.php?id=<?= (int)$despesa["id"] ?>"
                                            class="editar"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            action="excluir.php"
                                            method="POST"
                                            class="form-excluir"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$despesa["id"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="excluir"
                                            >
                                                Excluir
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="vazio">
                                Nenhuma despesa encontrada nesse período.
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
