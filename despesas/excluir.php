
<?php
require_once "../config/conexao.php";

// Só permite exclusão por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

// Recebe o ID da despesa
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: listar.php?erro=exclusao");
    exit;
}

try {

    // Inicia a transação
    $pdo->beginTransaction();

    // Verifica se a despesa existe
    $stmt = $pdo->prepare("
        SELECT id
        FROM despesas
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();

        header("Location: listar.php?erro=exclusao");
        exit;
    }

    // Exclui a despesa
    $stmt = $pdo->prepare("
        DELETE FROM despesas
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    // Confirma a exclusão
    $pdo->commit();

    header("Location: listar.php?excluido=1");
    exit;

} catch (Throwable $e) {

    // Cancela a operação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: listar.php?erro=exclusao");
    exit;
}
?>
