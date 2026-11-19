
<?php
require_once "../config/conexao.php";

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
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: listar.php?excluido=1");
    exit;

} catch (PDOException $e) {
    header("Location: listar.php?erro=produto_vinculado");
    exit;
}
