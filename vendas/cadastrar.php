
<?php
require_once "../config/conexao.php";

// Busca os produtos cadastrados
$stmt = $pdo->query("
    SELECT id, nome, preco_venda, estoque
    FROM produtos
    ORDER BY nome ASC
");

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $produtoId = filter_input(INPUT_POST, "produto_id", FILTER_VALIDATE_INT);
    $quantidade = filter_input(INPUT_POST, "quantidade", FILTER_VALIDATE_INT);
    $dataVenda = $_POST["data_venda"] ?? "";

    if (!$produtoId || !$quantidade || $quantidade <= 0 || $dataVenda === "") {

        $erro = "Preencha todos os campos corretamente.";

    } else {

        try {

            $pdo->beginTransaction();

            // Busca preço e estoque do produto
            $stmt = $pdo->prepare("
                SELECT nome, preco_venda, estoque
                FROM produtos
                WHERE id = ?
            ");

            $stmt->execute([$produtoId]);
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$produto) {
                throw new Exception("Produto não encontrado.");
            }

            // Verifica se existe estoque suficiente
            if ($quantidade > (int)$produto["estoque"]) {
                throw new Exception("Estoque insuficiente para essa venda.");
            }

            $valorUnitario = (float)$produto["preco_venda"];
            $valorTotal = $valorUnitario * $quantidade;

            // Registra a venda
            $stmt = $pdo->prepare("
                INSERT INTO vendas (data_venda, valor_total)
                VALUES (?, ?)
            ");

            $stmt->execute([$dataVenda, $valorTotal]);

            $vendaId = $pdo->lastInsertId();

            // Registra o produto vendido
            $stmt = $pdo->prepare("
                INSERT INTO itens_venda
                (venda_id, produto_id, quantidade, valor_unitario)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $vendaId,
                $produtoId,
                $quantidade,
                $valorUnitario
            ]);

            // Desconta a quantidade do estoque
            $stmt = $pdo->prepare("
                UPDATE produtos
                SET estoque = estoque - ?
                WHERE id = ? AND estoque >= ?
            ");

            $stmt->execute([
                $quantidade,
                $produtoId,
                $quantidade
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Não foi possível atualizar o estoque.");
            }

            // Registra a saída no histórico
            $stmt = $pdo->prepare("
                INSERT INTO movimentacoes_estoque
                (produto_id, tipo, quantidade, descricao)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $produtoId,
                "Saída",
                $quantidade,
                "Saída por venda nº " . $vendaId
            ]);

            // Confirma todas as operações
            $pdo->commit();

            header("Location: listar.php?sucesso=1");
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getMessage() === "Estoque insuficiente para essa venda.") {
                $erro = $e->getMessage();
            } else {
                $erro = "Não foi possível registrar a venda. Confira os dados e tente novamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Venda | Doce Controle</title>

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

        .form-container {
            background: #191620;
            border: 1px solid #30283D;
            border-radius: 15px;
            padding: 28px;
            max-width: 750px;
        }

        .form-container h2 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .descricao-form {
            color: #958BA6;
            font-size: 13px;
            margin-bottom: 25px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 9px;
            margin-bottom: 22px;
        }

        label {
            color: #C4A7FF;
            font-size: 12px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            background: #100E16;
            border: 1px solid #393044;
            border-radius: 9px;
            padding: 14px;
            color: white;
            outline: none;
            font-size: 13px;
        }

        input:focus,
        select:focus {
            border-color: #8B5CF6;
        }

        select option {
            background: #191620;
            color: white;
        }

        .aviso-estoque {
            background: rgba(196, 167, 255, .08);
            border: 1px solid rgba(196, 167, 255, .2);
            color: #C4A7FF;
            padding: 14px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 22px;
        }

        /* MENSAGEM */

        .mensagem {
            padding: 14px 17px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .erro {
            background: rgba(248, 113, 113, .10);
            border: 1px solid rgba(248, 113, 113, .25);
            color: #F87171;
        }

        /* BOTÕES */

        .acoes {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
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

        .botao-secundario {
            display: inline-block;
            background: #292333;
            color: #C4A7FF;
            text-decoration: none;
            padding: 13px 20px;
            border-radius: 9px;
            font-size: 13px;
        }

        .botao-secundario:hover {
            background: #393044;
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

            .form-container {
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

            <a href="../produtos/listar.php">
                <span class="icone">▦</span>
                Produtos
            </a>

            <a href="../compras/listar.php">
                <span class="icone">🛒</span>
                Compras
            </a>

            <a href="listar.php" class="ativo">
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
                <h1>Cadastrar venda</h1>
                <p>Registre uma nova venda no sistema.</p>
            </div>

            <div class="data">
                ◷ <?= date("d/m/Y") ?>
            </div>
        </header>

        <section class="boas-vindas">
            <span>NOVA VENDA</span>
            <h2>Registre suas vendas com facilidade.</h2>
            <p>
                Selecione o produto, informe a quantidade e a data.
                O sistema calculará o valor e atualizará o estoque automaticamente.
            </p>
        </section>

        <?php if ($erro): ?>
            <div class="mensagem erro">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <section class="form-container">

            <h2>Informações da venda</h2>

            <p class="descricao-form">
                Preencha os campos abaixo para registrar a venda.
            </p>

            <form method="POST">

                <div class="campo">

                    <label for="produto_id">Produto</label>

                    <select name="produto_id" id="produto_id" required>

                        <option value="">Selecione um produto</option>

                        <?php foreach ($produtos as $produto): ?>

                            <option
                                value="<?= (int)$produto["id"] ?>"
                                <?= ((string)($_POST["produto_id"] ?? "") === (string)$produto["id"]) ? "selected" : "" ?>
                                <?= (int)$produto["estoque"] <= 0 ? "disabled" : "" ?>
                            >

                                <?= htmlspecialchars($produto["nome"]) ?>

                                - R$ <?= number_format(
                                    $produto["preco_venda"],
                                    2,
                                    ",",
                                    "."
                                ) ?>

                                <?php if ((int)$produto["estoque"] <= 0): ?>

                                    - SEM ESTOQUE

                                <?php elseif ((int)$produto["estoque"] <= 5): ?>

                                    - ESTOQUE BAIXO:
                                    <?= (int)$produto["estoque"] ?>

                                <?php else: ?>

                                    - Estoque:
                                    <?= (int)$produto["estoque"] ?>

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="campo">

                    <label for="quantidade">Quantidade</label>

                    <input
                        type="number"
                        name="quantidade"
                        id="quantidade"
                        min="1"
                        value="<?= htmlspecialchars($_POST["quantidade"] ?? "1") ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="data_venda">Data da venda</label>

                    <input
                        type="date"
                        name="data_venda"
                        id="data_venda"
                        value="<?= htmlspecialchars($_POST["data_venda"] ?? date("Y-m-d")) ?>"
                        required
                    >

                </div>

                <p class="aviso-estoque">
                    Produtos sem estoque não podem ser vendidos.
                    Ao registrar a venda, a quantidade será descontada automaticamente do estoque.
                </p>

                <div class="acoes">

                    <button type="submit" class="botao">
                        Registrar venda
                    </button>

                    <a href="listar.php" class="botao-secundario">
                        Voltar para vendas
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
