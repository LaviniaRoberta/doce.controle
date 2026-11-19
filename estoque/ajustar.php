
<?php
require_once "../config/conexao.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nome, estoque
    FROM produtos
    WHERE id = ?
");

$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header("Location: index.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $operacao = $_POST["operacao"] ?? "";
    $quantidade = filter_input(INPUT_POST, "quantidade", FILTER_VALIDATE_INT);

    if (!$quantidade || $quantidade <= 0) {

        $erro = "Informe uma quantidade válida.";

    } elseif (!in_array($operacao, ["adicionar", "retirar"], true)) {

        $erro = "Selecione uma operação válida.";

    } else {

        try {

            $pdo->beginTransaction();

            if ($operacao === "adicionar") {

                $stmt = $pdo->prepare("
                    UPDATE produtos
                    SET estoque = estoque + ?
                    WHERE id = ?
                ");

                $stmt->execute([$quantidade, $id]);

                $tipo = "Entrada";
                $descricao = "Adição manual ao estoque";

            } else {

                $stmt = $pdo->prepare("
                    UPDATE produtos
                    SET estoque = estoque - ?
                    WHERE id = ? AND estoque >= ?
                ");

                $stmt->execute([
                    $quantidade,
                    $id,
                    $quantidade
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception("Quantidade maior que o estoque disponível.");
                }

                $tipo = "Saída";
                $descricao = "Retirada manual do estoque";
            }

            // Registra a movimentação
            $stmt = $pdo->prepare("
                INSERT INTO movimentacoes_estoque
                (produto_id, tipo, quantidade, descricao)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $id,
                $tipo,
                $quantidade,
                $descricao
            ]);

            $pdo->commit();

            header("Location: index.php?ajustado=1");
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getMessage() === "Quantidade maior que o estoque disponível.") {
                $erro = $e->getMessage();
            } else {
                $erro = "Não foi possível atualizar o estoque.";
            }
        }
    }

    $stmt = $pdo->prepare("
        SELECT id, nome, estoque
        FROM produtos
        WHERE id = ?
    ");

    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustar Estoque | Doce Controle</title>

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

        .card {
            max-width: 650px;
            background: #191620;
            border: 1px solid #292333;
            border-radius: 16px;
            padding: 30px;
        }

        .produto-info {
            background: #211B2C;
            border: 1px solid #49336C;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .produto-info p {
            color: #A99BB8;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .produto-info h2 {
            color: #C4A7FF;
            font-size: 21px;
            margin-bottom: 12px;
        }

        .produto-info strong {
            color: #F5F0FA;
            font-size: 18px;
        }

        label {
            display: block;
            color: #C4A7FF;
            font-size: 14px;
            margin-bottom: 9px;
            font-weight: bold;
        }

        select,
        input {
            width: 100%;
            padding: 14px;
            background: #0D0B12;
            border: 1px solid #393044;
            border-radius: 9px;
            color: #F5F0FA;
            font-size: 14px;
            outline: none;
            margin-bottom: 22px;
        }

        select:focus,
        input:focus {
            border-color: #A78BFA;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #8B5CF6;
            border: none;
            border-radius: 9px;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        button:hover {
            background: #A78BFA;
        }

        .erro {
            background: #3B2028;
            border: 1px solid #71333F;
            color: #FF929F;
            padding: 14px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .voltar {
            display: inline-block;
            color: #C4A7FF;
            text-decoration: none;
            margin-top: 22px;
            font-size: 14px;
        }

        .voltar:hover {
            color: white;
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

            .card {
                padding: 20px;
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
        <h1>Ajustar Estoque</h1>
        <p>Adicione ou retire unidades do estoque de um produto.</p>
    </section>

    <section class="card">

        <div class="produto-info">
            <p>Produto selecionado</p>

            <h2>
                <?= htmlspecialchars($produto["nome"]) ?>
            </h2>

            <p>Estoque atual</p>

            <strong>
                <?= (int)$produto["estoque"] ?> unidades
            </strong>
        </div>

        <?php if ($erro !== ""): ?>
            <div class="erro">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label for="operacao">O que deseja fazer?</label>

            <select name="operacao" id="operacao" required>
                <option value="">Selecione uma operação</option>

                <option value="adicionar"
                    <?= ($_POST["operacao"] ?? "") === "adicionar" ? "selected" : "" ?>>
                    Adicionar ao estoque
                </option>

                <option value="retirar"
                    <?= ($_POST["operacao"] ?? "") === "retirar" ? "selected" : "" ?>>
                    Retirar do estoque
                </option>
            </select>

            <label for="quantidade">Quantidade</label>

            <input
                type="number"
                name="quantidade"
                id="quantidade"
                min="1"
                placeholder="Digite a quantidade"
                value="<?= htmlspecialchars($_POST["quantidade"] ?? "") ?>"
                required
            >

            <button type="submit">
                Atualizar estoque
            </button>

        </form>

        <a href="index.php" class="voltar">
            ← Voltar para o estoque
        </a>

    </section>

</main>

</body>
</html>
