
<?php
require_once "../config/conexao.php";

// Busca a despesa pelo ID
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: listar.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM despesas WHERE id = ?");
$stmt->execute([$id]);
$despesa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$despesa) {
    header("Location: listar.php");
    exit;
}

$erro = "";

// Categorias permitidas
$categoriasPermitidas = [
    "Embalagens",
    "Ingredientes",
    "Transporte",
    "Energia",
    "Gás",
    "Outros"
];

// Valores iniciais
$descricao = $despesa["descricao"];
$valor = $despesa["valor"];
$data_despesa = $despesa["data_despesa"];
$categoriaSelecionada = $despesa["categoria"];

// Atualiza a despesa
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $descricao = trim($_POST["descricao"] ?? "");
    $valor = (float) ($_POST["valor"] ?? 0);
    $data_despesa = $_POST["data_despesa"] ?? "";
    $categoriaSelecionada = $_POST["categoria"] ?? "";

    if (
        $descricao === "" ||
        $valor <= 0 ||
        $data_despesa === "" ||
        !in_array($categoriaSelecionada, $categoriasPermitidas, true)
    ) {

        $erro = "Preencha todos os campos corretamente.";

    } else {

        try {

            $sql = "
                UPDATE despesas
                SET descricao = ?,
                    valor = ?,
                    data_despesa = ?,
                    categoria = ?
                WHERE id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $descricao,
                $valor,
                $data_despesa,
                $categoriaSelecionada,
                $id
            ]);

            header("Location: listar.php?editado=1");
            exit;

        } catch (PDOException $e) {

            $erro = "Não foi possível atualizar a despesa. Tente novamente.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar despesa | Doce Controle</title>

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
                <h1>Editar despesa</h1>
                <p>Atualize as informações da despesa cadastrada.</p>
            </div>

            <div class="data">
                ◷ <?= date("d/m/Y") ?>
            </div>

        </header>

        <section class="boas-vindas">

            <span>CONTROLE FINANCEIRO</span>

            <h2>Atualize os dados da sua despesa.</h2>

            <p>
                Altere a descrição, o valor, a data ou a categoria
                para manter seu controle financeiro atualizado.
            </p>

        </section>

        <?php if ($erro !== ""): ?>

            <div class="mensagem erro">
                <?= htmlspecialchars($erro) ?>
            </div>

        <?php endif; ?>

        <section class="form-container">

            <h2>Informações da despesa</h2>

            <p class="descricao-form">
                Confira os dados e faça as alterações necessárias.
            </p>

            <form method="POST">

                <div class="campo">

                    <label for="descricao">Descrição da despesa</label>

                    <input
                        type="text"
                        id="descricao"
                        name="descricao"
                        value="<?= htmlspecialchars($descricao) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="valor">Valor (R$)</label>

                    <input
                        type="number"
                        id="valor"
                        name="valor"
                        step="0.01"
                        min="0.01"
                        value="<?= htmlspecialchars((string)$valor) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="data_despesa">Data da despesa</label>

                    <input
                        type="date"
                        id="data_despesa"
                        name="data_despesa"
                        value="<?= htmlspecialchars($data_despesa) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="categoria">Categoria</label>

                    <select name="categoria" id="categoria" required>

                        <?php foreach ($categoriasPermitidas as $categoria): ?>

                            <option
                                value="<?= htmlspecialchars($categoria) ?>"
                                <?= $categoriaSelecionada === $categoria ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($categoria) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="acoes">

                    <button type="submit" class="botao">
                        Salvar alterações
                    </button>

                    <a href="listar.php" class="botao-secundario">
                        Voltar para despesas
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
