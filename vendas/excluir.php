
<?php
require_once "../config/conexao.php";

// Recebe o ID da venda
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: listar.php");
    exit;
}

try {

    $pdo->beginTransaction();

    // Verifica se a venda existe
    $stmt = $pdo->prepare("
        SELECT id
        FROM vendas
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Venda não encontrada.");
    }

    // Busca os produtos e quantidades da venda
    $stmt = $pdo->prepare("
        SELECT produto_id, quantidade
        FROM itens_venda
        WHERE venda_id = ?
    ");

    $stmt->execute([$id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Devolve os produtos ao estoque e registra no histórico
    foreach ($itens as $item) {

        $produtoId = (int)$item["produto_id"];
        $quantidade = (int)$item["quantidade"];

        // Atualiza o estoque
        $stmtEstoque = $pdo->prepare("
            UPDATE produtos
            SET estoque = estoque + ?
            WHERE id = ?
        ");

        $stmtEstoque->execute([
            $quantidade,
            $produtoId
        ]);

        // Registra a entrada no histórico
        $stmtHistorico = $pdo->prepare("
            INSERT INTO movimentacoes_estoque
            (produto_id, tipo, quantidade, descricao)
            VALUES (?, 'Entrada', ?, ?)
        ");

        $stmtHistorico->execute([
            $produtoId,
            $quantidade,
            "Estorno da venda excluída nº " . $id
        ]);
    }

    // Exclui os itens da venda
    $stmt = $pdo->prepare("
        DELETE FROM itens_venda
        WHERE venda_id = ?
    ");

    $stmt->execute([$id]);

    // Exclui a venda
    $stmt = $pdo->prepare("
        DELETE FROM vendas
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

    die("Não foi possível excluir a venda. Tente novamente.");
}
?>
