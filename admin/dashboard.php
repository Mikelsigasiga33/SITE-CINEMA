<?php
require dirname(__DIR__) . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificação de Segurança: Apenas ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_permissao']) || strtoupper($_SESSION['tipo_permissao']) !== 'ADMINISTRADOR') {
    header("Location: /index.php");
    exit;
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <h2 class="fw-bold text-white mb-4 border-start border-4 border-warning ps-3">Painel de Administração</h2>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <!-- Card Utilizadores -->
        <div class="col">
            <div class="card bg-dark text-white border-secondary h-100">
                <div class="card-body text-center p-5">
                    <i class="bi bi-people-fill display-1 text-primary mb-3"></i>
                    <h4 class="card-title fw-bold">Utilizadores</h4>
                    <p class="card-text text-secondary">Gerir contas de clientes e administradores.</p>
                    <a href="users.php" class="btn btn-outline-primary w-100 mt-3">Gerir Utilizadores</a>
                </div>
            </div>
        </div>

        <!-- Card Filmes -->
        <div class="col">
            <div class="card bg-dark text-white border-secondary h-100">
                <div class="card-body text-center p-5">
                    <i class="bi bi-film display-1 text-danger mb-3"></i>
                    <h4 class="card-title fw-bold">Filmes</h4>
                    <p class="card-text text-secondary">Adicionar, editar e remover filmes do catálogo.</p>
                    <a href="movies.php" class="btn btn-outline-danger w-100 mt-3">Gerir Filmes</a>
                </div>
            </div>
        </div>

        <!-- Card Sessões -->
        <div class="col">
            <div class="card bg-dark text-white border-secondary h-100">
                <div class="card-body text-center p-5">
                    <i class="bi bi-calendar-event display-1 text-success mb-3"></i>
                    <h4 class="card-title fw-bold">Sessões</h4>
                    <p class="card-text text-secondary">Agendar sessões e gerir horários.</p>
                    <a href="sessions.php" class="btn btn-outline-success w-100 mt-3">Gerir Sessões</a>
                </div>
            </div>
        </div>

        <!-- Card Bilhetes -->
        <div class="col">
            <div class="card bg-dark text-white border-secondary h-100">
                <div class="card-body text-center p-5">
                    <i class="bi bi-ticket-perforated display-1 text-warning mb-3"></i>
                    <h4 class="card-title fw-bold">Bilhetes</h4>
                    <p class="card-text text-secondary">Visualizar histórico de vendas e bilhetes.</p>
                    <a href="tickets.php" class="btn btn-outline-warning w-100 mt-3">Ver Vendas</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>