
<?php
require_once "../config/conexao.php";

// Só permite exclusão por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: listar.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // Busca os itens da compra
    $stmt = $pdo->prepare("
        SELECT produto_id, descricao, quantidade
        FROM itens_compra
        WHERE compra_id = ?
    ");

    $stmt->execute([$id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verifica e retira as quantidades do estoque
    foreach ($itens as $item) {

        $produtoId = $item["produto_id"];
        $quantidade = (int) $item["quantidade"];

        if ($produtoId && $quantidade > 0) {

            // Consulta o estoque atual
            $stmt = $pdo->prepare("
                SELECT estoque
                FROM produtos
                WHERE id = ?
            ");

            $stmt->execute([$produtoId]);
            $estoqueAtual = $stmt->fetchColumn();

            if ($estoqueAtual === false) {
                throw new Exception("Produto não encontrado.");
            }

            // Impede que o estoque fique negativo
            if ((int)$estoqueAtual < $quantidade) {
                throw new Exception(
                    "Não é possível excluir esta compra porque parte dos produtos já foi utilizada."
                );
            }

            // Retira a quantidade comprada
            $stmt = $pdo->prepare("
                UPDATE produtos
                SET estoque = estoque - ?
                WHERE id = ?
            ");

            $stmt->execute([
                $quantidade,
                $produtoId
            ]);

            // Registra a saída no histórico
            $stmt = $pdo->prepare("
                INSERT INTO movimentacoes_estoque
                (produto_id, tipo, quantidade, descricao)
                VALUES (?, 'Saída', ?, ?)
            ");

            $stmt->execute([
                $produtoId,
                $quantidade,
                "Estorno da compra excluída nº " . $id
            ]);
        }
    }

    // Exclui os itens vinculados à compra
    $stmt = $pdo->prepare("
        DELETE FROM itens_compra
        WHERE compra_id = ?
    ");

    $stmt->execute([$id]);

    // Exclui a compra
    $stmt = $pdo->prepare("
        DELETE FROM compras
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $pdo->commit();

    header("Location: listar.php?excluido=1");
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: listar.php?erro=exclusao");
    exit;
}

