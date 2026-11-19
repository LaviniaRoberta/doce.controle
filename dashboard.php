
<?php
require_once "config/conexao.php";

$totalProdutos = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();

$totalVendas = $pdo->query("
    SELECT COALESCE(SUM(valor_total), 0) FROM vendas
")->fetchColumn();

$totalCompras = $pdo->query("
    SELECT COALESCE(SUM(valor_total), 0) FROM compras
")->fetchColumn();

$totalDespesas = $pdo->query("
    SELECT COALESCE(SUM(valor), 0) FROM despesas
")->fetchColumn();

$quantidadeVendas = $pdo->query("
    SELECT COUNT(*) FROM vendas
")->fetchColumn();

$estoqueBaixo = $pdo->query("
    SELECT COUNT(*) FROM produtos WHERE estoque <= 5
")->fetchColumn();

$resultado = $totalVendas - $totalCompras - $totalDespesas;

function moeda($valor) {
    return "R$ " . number_format((float)$valor, 2, ",", ".");
}

$dataAtual = date("d/m/Y");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Doce Controle | Dashboard</title>

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

        /* BOAS-VINDAS */

        .boas-vindas {
            background: linear-gradient(115deg, #302047, #1C1726);
            border: 1px solid #49336C;
            border-radius: 18px;
            padding: 30px;
            margin-bottom: 35px;
        }

        .boas-vindas span {
            color: #C4A7FF;
            font-size: 11px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .boas-vindas h2 {
            color: #FFFFFF;
            font-size: 25px;
            margin: 12px 0 8px;
        }

        .boas-vindas p {
            color: #B6AFC3;
            font-size: 13px;
            line-height: 1.7;
        }

        /* TÍTULOS */

        .titulo-secao {
            color: #FFFFFF;
            font-size: 17px;
            margin-bottom: 18px;
        }

        /* CARDS */

        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 17px;
            margin-bottom: 30px;
        }

        .card {
            background: #191620;
            border: 1px solid #30283D;
            border-radius: 15px;
            padding: 22px;
            transition: .3s;
        }

        .card:hover {
            transform: translateY(-3px);
            border-color: #8B5CF6;
        }

        .card-icone {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: #302344;
            color: #C4A7FF;
            font-size: 19px;
            margin-bottom: 18px;
        }

        .card span {
            color: #958BA6;
            font-size: 12px;
        }

        .card h3 {
            color: #FFFFFF;
            font-size: 20px;
            margin-top: 8px;
            overflow-wrap: anywhere;
        }

        .card.resultado {
            background: #291D3D;
            border-color: #7040D8;
        }

        .card.resultado h3 {
            color: #C4A7FF;
        }

        /* ATALHOS */

        .atalhos {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
        }

        .atalho {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #191620;
            border: 1px solid #30283D;
            border-radius: 13px;
            padding: 19px;
            text-decoration: none;
            transition: .3s;
        }

        .atalho:hover {
            background: #241D30;
            border-color: #8B5CF6;
            transform: translateY(-3px);
        }

        .atalho-icone {
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #302344;
            color: #C4A7FF;
            font-size: 19px;
        }

        .atalho h3 {
            color: #FFFFFF;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .atalho p {
            color: #958BA6;
            font-size: 11px;
        }

        /* INFORMAÇÕES */

        .informacoes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 17px;
            margin-top: 30px;
        }

        .info {
            background: #191620;
            border: 1px solid #30283D;
            border-radius: 14px;
            padding: 22px;
        }

        .info h3 {
            color: #B99AFF;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .info strong {
            display: block;
            color: #FFFFFF;
            font-size: 25px;
        }

        .info p {
            color: #958BA6;
            font-size: 11px;
            margin-top: 5px;
        }

        .rodape {
            color: #71677F;
            text-align: center;
            font-size: 11px;
            margin-top: 40px;
            padding: 20px;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 1100px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .atalhos {
                grid-template-columns: repeat(2, 1fr);
            }
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
                padding: 22px;
            }

            .cards,
            .atalhos,
            .informacoes {
                grid-template-columns: 1fr;
            }

            .topo {
                align-items: flex-start;
                flex-direction: column;
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
            <a href="dashboard.php" class="ativo">
                <span class="icone">⌂</span>
                Dashboard
            </a>

            <a href="produtos/listar.php">
                <span class="icone">▦</span>
                Produtos
            </a>

            <a href="compras/listar.php">
                <span class="icone">🛒</span>
                Compras
            </a>

            <a href="vendas/listar.php">
                <span class="icone">💰</span>
                Vendas
            </a>

            <a href="despesas/listar.php">
                <span class="icone">↗</span>
                Despesas
            </a>
        </nav>

        <p class="menu-titulo">CONTROLE</p>

        <nav class="menu">
            <a href="estoque/index.php">
                <span class="icone">▤</span>
                Estoque
            </a>

            <a href="estoque/historico.php">
                <span class="icone">◷</span>
                Histórico
            </a>

            <a href="relatorios/index.php">
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
                <h1>Dashboard</h1>
                <p>Bem-vinda! Aqui está o resumo do seu negócio.</p>
            </div>

            <div class="data">
                ◷ <?= $dataAtual ?>
            </div>
        </header>

        <section class="boas-vindas">
            <span>PAINEL DE CONTROLE</span>
            <h2>Olá! Seja bem-vinda de volta.</h2>
            <p>
                Organize suas vendas, acompanhe suas despesas e
                tenha uma visão completa do seu negócio.
            </p>
        </section>

        <h2 class="titulo-secao">Resumo financeiro</h2>

        <section class="cards">

            <div class="card">
                <div class="card-icone">R$</div>
                <span>Total de vendas</span>
                <h3><?= moeda($totalVendas) ?></h3>
            </div>

            <div class="card">
                <div class="card-icone">▣</div>
                <span>Total de compras</span>
                <h3><?= moeda($totalCompras) ?></h3>
            </div>

            <div class="card">
                <div class="card-icone">↗</div>
                <span>Total de despesas</span>
                <h3><?= moeda($totalDespesas) ?></h3>
            </div>

            <div class="card">
                <div class="card-icone">◇</div>
                <span>Produtos cadastrados</span>
                <h3><?= (int)$totalProdutos ?></h3>
            </div>

            <div class="card resultado">
                <div class="card-icone">✦</div>
                <span>Resultado estimado</span>
                <h3><?= moeda($resultado) ?></h3>
            </div>

        </section>

        <h2 class="titulo-secao">Acesso rápido</h2>

        <section class="atalhos">

            <a href="produtos/listar.php" class="atalho">
                <div class="atalho-icone">▦</div>
                <div>
                    <h3>Produtos</h3>
                    <p>Gerenciar produtos</p>
                </div>
            </a>

            <a href="compras/listar.php" class="atalho">
                <div class="atalho-icone">🛒</div>
                <div>
                    <h3>Compras</h3>
                    <p>Registrar compras</p>
                </div>
            </a>

            <a href="vendas/listar.php" class="atalho">
                <div class="atalho-icone">💰</div>
                <div>
                    <h3>Vendas</h3>
                    <p>Registrar vendas</p>
                </div>
            </a>

            <a href="despesas/listar.php" class="atalho">
                <div class="atalho-icone">↗</div>
                <div>
                    <h3>Despesas</h3>
                    <p>Controlar gastos</p>
                </div>
            </a>

            <a href="estoque/index.php" class="atalho">
                <div class="atalho-icone">▤</div>
                <div>
                    <h3>Estoque</h3>
                    <p>Conferir estoque</p>
                </div>
            </a>

            <a href="relatorios/index.php" class="atalho">
                <div class="atalho-icone">▥</div>
                <div>
                    <h3>Relatórios</h3>
                    <p>Ver resultados</p>
                </div>
            </a>

        </section>

        <h2 class="titulo-secao" style="margin-top: 35px;">
            Informações rápidas
        </h2>

        <section class="informacoes">

            <div class="info">
                <h3>Vendas realizadas</h3>
                <strong><?= (int)$quantidadeVendas ?></strong>
                <p>Total de vendas cadastradas</p>
            </div>

            <div class="info">
                <h3>Produtos que precisam de atenção</h3>
                <strong><?= (int)$estoqueBaixo ?></strong>
                <p>Produtos com estoque baixo ou zerado</p>
            </div>

        </section>

        <footer class="rodape">
            Doce Controle © <?= date("Y") ?> - Todos os direitos reservados.
        </footer>

    </main>

</div>

</body>
</html>
