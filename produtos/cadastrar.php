
<?php
require_once "../config/conexao.php";

$mensagem = "";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        produto_id INTEGER,
        tipo TEXT NOT NULL,
        quantidade REAL NOT NULL,
        descricao TEXT,
        data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
    )
");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $preco = (float) ($_POST["preco_venda"] ?? 0);
    $custo = (float) ($_POST["custo_producao"] ?? 0);
    $estoque = (int) ($_POST["estoque"] ?? 0);

    if ($nome !== "" && $preco > 0 && $custo >= 0 && $estoque >= 0) {

        try {
            $pdo->beginTransaction();

            $sql = "
                INSERT INTO produtos
                (nome, categoria, preco_venda, custo_producao, estoque)
                VALUES (:nome, :categoria, :preco, :custo, :estoque)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":nome" => $nome,
                ":categoria" => $categoria,
                ":preco" => $preco,
                ":custo" => $custo,
                ":estoque" => $estoque
            ]);

            $produto_id = $pdo->lastInsertId();

            if ($estoque > 0) {
                $sql = "
                    INSERT INTO movimentacoes_estoque
                    (produto_id, tipo, quantidade, descricao)
                    VALUES (?, ?, ?, ?)
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $produto_id,
                    "Entrada",
                    $estoque,
                    "Estoque inicial do produto"
                ]);
            }

            $pdo->commit();

            header("Location: listar.php?sucesso=1");
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mensagem = "Erro ao cadastrar produto: " . $e->getMessage();
        }

    } else {
        $mensagem = "Preencha os campos corretamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar produto | Doce Controle</title>

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

        /* FORMULÁRIO */

        .formulario-container {
            background: #191620;
            border: 1px solid #30283D;
            border-radius: 16px;
            padding: 30px;
            max-width: 850px;
        }

        .formulario-container h2 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .subtitulo {
            color: #958BA6;
            font-size: 13px;
            margin-bottom: 28px;
        }

        .campos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .campo.completo {
            grid-column: 1 / -1;
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
            padding: 14px;
            color: white;
            font-size: 13px;
            outline: none;
            transition: .2s;
        }

        input:focus {
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, .12);
        }

        input::placeholder {
            color: #71677F;
        }

        .dica {
            color: #81778F;
            font-size: 11px;
        }

        .botoes {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .botao {
            display: inline-block;
            background: #8B5CF6;
            color: white;
            text-decoration: none;
            padding: 13px 20px;
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

        .botao-voltar {
            display: inline-block;
            background: #292333;
            color: #C4A7FF;
            text-decoration: none;
            padding: 13px 20px;
            border-radius: 9px;
            font-size: 13px;
        }

        .botao-voltar:hover {
            background: #393044;
        }

        .erro {
            background: rgba(248, 113, 113, .10);
            border: 1px solid rgba(248, 113, 113, .25);
            color: #F87171;
            padding: 14px 17px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            overflow-wrap: anywhere;
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

            .campos {
                grid-template-columns: 1fr;
            }

            .campo.completo {
                grid-column: auto;
            }

            .formulario-container {
                padding: 20px;
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

            <a href="listar.php" class="ativo">
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
                <h1>Cadastrar produto</h1>
                <p>Adicione um novo produto ao seu negócio.</p>
            </div>

            <div class="data">
                ◷ <?= date("d/m/Y") ?>
            </div>
        </header>

        <section class="boas-vindas">
            <span>NOVO CADASTRO</span>
            <h2>Vamos adicionar um produto?</h2>
            <p>
                Preencha as informações abaixo para registrar
                seu produto no Doce Controle.
            </p>
        </section>

        <?php if ($mensagem !== ""): ?>
            <div class="erro">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <section class="formulario-container">

            <h2>Informações do produto</h2>

            <p class="subtitulo">
                Informe os dados necessários para o cadastro.
            </p>

            <form method="POST">

                <div class="campos">

                    <div class="campo completo">
                        <label for="nome">Nome do produto</label>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            placeholder="Ex: Bolo de chocolate"
                            value="<?= htmlspecialchars($_POST["nome"] ?? "") ?>"
                            required
                        >
                    </div>

                    <div class="campo completo">
                        <label for="categoria">Categoria</label>

                        <input
                            type="text"
                            id="categoria"
                            name="categoria"
                            placeholder="Ex: Bolos, tortas, doces..."
                            value="<?= htmlspecialchars($_POST["categoria"] ?? "") ?>"
                        >
                    </div>

                    <div class="campo">
                        <label for="preco_venda">Preço de venda (R$)</label>

                        <input
                            type="number"
                            id="preco_venda"
                            name="preco_venda"
                            step="0.01"
                            min="0.01"
                            placeholder="0,00"
                            value="<?= htmlspecialchars($_POST["preco_venda"] ?? "") ?>"
                            required
                        >

                        <span class="dica">
                            Valor pelo qual o produto será vendido.
                        </span>
                    </div>

                    <div class="campo">
                        <label for="custo_producao">Custo de produção (R$)</label>

                        <input
                            type="number"
                            id="custo_producao"
                            name="custo_producao"
                            step="0.01"
                            min="0"
                            placeholder="0,00"
                            value="<?= htmlspecialchars($_POST["custo_producao"] ?? "0") ?>"
                        >

                        <span class="dica">
                            Quanto custa produzir uma unidade.
                        </span>
                    </div>

                    <div class="campo completo">
                        <label for="estoque">Quantidade em estoque</label>

                        <input
                            type="number"
                            id="estoque"
                            name="estoque"
                            min="0"
                            step="1"
                            placeholder="0"
                            value="<?= htmlspecialchars($_POST["estoque"] ?? "0") ?>"
                        >

                        <span class="dica">
                            A quantidade inicial será registrada no histórico de estoque.
                        </span>
                    </div>

                </div>

                <div class="botoes">

                    <button type="submit" class="botao">
                        + Cadastrar produto
                    </button>

                    <a href="listar.php" class="botao-voltar">
                        Cancelar
                    </a>

                </div>

            </form>

        </section>

        <footer class="rodape">
            Doce Controle © <?= date("Y") ?> - Todos os direitos reservados.
        </footer>

    </main>

</div>

</body>
</html>
