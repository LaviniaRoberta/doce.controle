
<?php
require_once "../config/conexao.php";

// Busca o ID da venda
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: listar.php");
    exit;
}

// Busca a venda cadastrada
$sql = "
    SELECT
        vendas.*,
        itens_venda.id AS item_id,
        itens_venda.produto_id,
        itens_venda.quantidade
    FROM vendas
    LEFT JOIN itens_venda
        ON vendas.id = itens_venda.venda_id
    WHERE vendas.id = ?
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venda) {
    header("Location: listar.php");
    exit;
}

// Busca os produtos cadastrados
$stmtProdutos = $pdo->query("
    SELECT *
    FROM produtos
    ORDER BY nome ASC
");

$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

$erro = "";

// Atualiza a venda
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $produto_id = filter_input(INPUT_POST, "produto_id", FILTER_VALIDATE_INT);
    $quantidade = filter_input(INPUT_POST, "quantidade", FILTER_VALIDATE_INT);
    $data_venda = $_POST["data_venda"] ?? "";

    if (!$produto_id || !$quantidade || $quantidade <= 0 || $data_venda === "") {

        $erro = "Preencha todos os campos corretamente.";

    } elseif (!$venda["item_id"]) {

        $erro = "Não foi possível localizar o item desta venda.";

    } else {

        try {

            $pdo->beginTransaction();

            // Busca novamente o item da venda
            $stmt = $pdo->prepare("
                SELECT produto_id, quantidade
                FROM itens_venda
                WHERE id = ? AND venda_id = ?
            ");

            $stmt->execute([$venda["item_id"], $id]);
            $itemAntigo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$itemAntigo) {
                throw new Exception("Item da venda não encontrado.");
            }

            $produtoAntigoId = (int)$itemAntigo["produto_id"];
            $quantidadeAntiga = (int)$itemAntigo["quantidade"];

            // Devolve a quantidade antiga ao estoque
            $stmt = $pdo->prepare("
                UPDATE produtos
                SET estoque = estoque + ?
                WHERE id = ?
            ");

            $stmt->execute([
                $quantidadeAntiga,
                $produtoAntigoId
            ]);

            // Busca o novo produto
            $stmt = $pdo->prepare("
                SELECT nome, preco_venda, estoque
                FROM produtos
                WHERE id = ?
            ");

            $stmt->execute([$produto_id]);
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$produto) {
                throw new Exception("Produto não encontrado.");
            }

            // Verifica se existe estoque suficiente
            if ($quantidade > (int)$produto["estoque"]) {
                throw new Exception("Estoque insuficiente para essa alteração.");
            }

            $valor_unitario = (float)$produto["preco_venda"];
            $valor_total = $valor_unitario * $quantidade;

            // Desconta a nova quantidade do estoque
            $stmt = $pdo->prepare("
                UPDATE produtos
                SET estoque = estoque - ?
                WHERE id = ? AND estoque >= ?
            ");

            $stmt->execute([
                $quantidade,
                $produto_id,
                $quantidade
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Não foi possível atualizar o estoque.");
            }

            // Atualiza os dados da venda
            $stmt = $pdo->prepare("
                UPDATE vendas
                SET data_venda = ?, valor_total = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data_venda,
                $valor_total,
                $id
            ]);

            // Atualiza o item vendido
            $stmt = $pdo->prepare("
                UPDATE itens_venda
                SET produto_id = ?,
                    quantidade = ?,
                    valor_unitario = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $produto_id,
                $quantidade,
                $valor_unitario,
                $venda["item_id"]
            ]);

            // Registra a devolução da quantidade antiga
            $stmt = $pdo->prepare("
                INSERT INTO movimentacoes_estoque
                (produto_id, tipo, quantidade, descricao)
                VALUES (?, 'Entrada', ?, ?)
            ");

            $stmt->execute([
                $produtoAntigoId,
                $quantidadeAntiga,
                "Estorno da venda editada nº " . $id
            ]);

            // Registra a nova saída
            $stmt = $pdo->prepare("
                INSERT INTO movimentacoes_estoque
                (produto_id, tipo, quantidade, descricao)
                VALUES (?, 'Saída', ?, ?)
            ");

            $stmt->execute([
                $produto_id,
                $quantidade,
                "Venda editada nº " . $id
            ]);

            $pdo->commit();

            header("Location: listar.php?editado=1");
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getMessage() === "Estoque insuficiente para essa alteração.") {
                $erro = $e->getMessage();
            } else {
                $erro = "Não foi possível atualizar a venda. Confira os dados e tente novamente.";
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

    <title>Editar Venda | Doce Controle</title>

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
                <h1>Editar venda</h1>
                <p>Atualize as informações da venda cadastrada.</p>
            </div>

            <div class="data">
                ◷ <?= date("d/m/Y") ?>
            </div>
        </header>

        <section class="boas-vindas">
            <span>EDIÇÃO DE VENDA</span>
            <h2>Atualize os dados da sua venda.</h2>
            <p>
                Altere o produto, a quantidade ou a data.
                O sistema atualizará o estoque automaticamente.
            </p>
        </section>

        <?php if ($erro !== ""): ?>
            <div class="mensagem erro">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <section class="form-container">

            <h2>Informações da venda</h2>

            <p class="descricao-form">
                Confira os dados e faça as alterações necessárias.
            </p>

            <form method="POST">

                <div class="campo">

                    <label for="produto_id">Produto</label>

                    <select name="produto_id" id="produto_id" required>

                        <option value="">Selecione um produto</option>

                        <?php foreach ($produtos as $produto): ?>

                            <option
                                value="<?= (int)$produto["id"] ?>"
                                <?= (int)($_POST["produto_id"] ?? $venda["produto_id"]) === (int)$produto["id"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($produto["nome"]) ?>

                                - R$ <?= number_format(
                                    $produto["preco_venda"],
                                    2,
                                    ",",
                                    "."
                                ) ?>

                                (Estoque: <?= (int)$produto["estoque"] ?>)
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
                        value="<?= htmlspecialchars($_POST["quantidade"] ?? $venda["quantidade"]) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="data_venda">Data da venda</label>

                    <input
                        type="date"
                        name="data_venda"
                        id="data_venda"
                        value="<?= htmlspecialchars($_POST["data_venda"] ?? $venda["data_venda"]) ?>"
                        required
                    >

                </div>

                <div class="acoes">

                    <button type="submit" class="botao">
                        Salvar alterações
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
