<?php
require dirname(__DIR__) . '/config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login necessário']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $movieId = $_POST['movie_id'] ?? 0;

    if (!$movieId) {
        echo json_encode(['success' => false, 'message' => 'ID do filme inválido']);
        exit;
    }

    try {
        // Verifica se já subscreveu
        $check = $pdo->prepare("SELECT 1 FROM AAA_NOTIFICACOES WHERE ID_UTILIZADOR = :user_id AND ID_FILME = :movie_id");
        $check->execute([':user_id' => $userId, ':movie_id' => $movieId]);
        
        if ($check->fetch()) {
            echo json_encode(['success' => true, 'message' => 'Já subscrito']);
            exit;
        }

        // Insere notificação
        $stmt = $pdo->prepare("INSERT INTO AAA_NOTIFICACOES (ID_UTILIZADOR, ID_FILME, DATA_REGISTO, STATUS_NOTIFICADO) VALUES (:user_id, :movie_id, SYSDATE, 'N')");
        $stmt->execute([':user_id' => $userId, ':movie_id' => $movieId]);

        echo json_encode(['success' => true, 'message' => 'Notificação ativada']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro BD: ' . $e->getMessage()]);
    }
}
?>