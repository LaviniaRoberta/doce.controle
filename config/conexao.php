
<?php

try {
    $pdo = new PDO(
        "sqlite:" . __DIR__ . "/../banco/doce_controle.db"
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS produtos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            categoria TEXT,
            preco_venda REAL NOT NULL,
            custo_producao REAL DEFAULT 0,
            estoque INTEGER DEFAULT 0,
            data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}


    // Tabela de compras no mercado
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS compras (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            estabelecimento TEXT,
            data_compra DATE NOT NULL,
            valor_total REAL NOT NULL,
            observacao TEXT
        )
    ");

    // Itens de cada compra
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS itens_compra (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            compra_id INTEGER NOT NULL,
            descricao TEXT NOT NULL,
            quantidade REAL NOT NULL,
            valor_unitario REAL NOT NULL,
            FOREIGN KEY (compra_id) REFERENCES compras(id)
        )
    ");

    // Tabela de despesas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS despesas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao TEXT NOT NULL,
            valor REAL NOT NULL,
            data_despesa DATE NOT NULL,
            categoria TEXT
        )
    ");

    // Tabela de vendas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vendas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            data_venda DATE NOT NULL,
            valor_total REAL NOT NULL,
            forma_pagamento TEXT
        )
    ");

    // Produtos de cada venda
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS itens_venda (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            venda_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            quantidade INTEGER NOT NULL,
            valor_unitario REAL NOT NULL,
            FOREIGN KEY (venda_id) REFERENCES vendas(id),
            FOREIGN KEY (produto_id) REFERENCES produtos(id)
        )
    ");
