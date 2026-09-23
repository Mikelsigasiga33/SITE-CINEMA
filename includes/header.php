<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// includes/header.php

// Garante que base_url existe (caso db.php não tenha sido incluído)
if (!isset($base_url)) { $base_url = ''; }

// Contar notificações ativas do utilizador
$notifCount = 0;
if (isset($pdo) && isset($_SESSION['user_id'])) {
    try {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM AAA_NOTIFICACOES WHERE ID_UTILIZADOR = :user_id");
        $stmtCount->execute([':user_id' => $_SESSION['user_id']]);
        $notifCount = $stmtCount->fetchColumn();
    } catch (Exception $e) {
        // Ignora erros se a tabela não existir ou falhar
    }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Interface Cinema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
        }
        .navbar {
            background-color: rgba(0, 0, 0, 0.9) !important; /* Fundo semi-transparente */
            backdrop-filter: blur(10px); /* Efeito de vidro fosco */
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .navbar-brand {
            font-weight: bold;
            color: #E50914 !important; /* Vermelho estilo cinema */
            letter-spacing: 1px;
        }
        .nav-link {
            color: #ccc !important;
            transition: color 0.3s;
            position: relative;
            font-weight: 500;
        }
        .nav-link:hover {
            color: #fff !important;
        }
        /* Animação de sublinhado nos links */
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: #E50914;
            transition: all 0.3s ease-in-out;
            transform: translateX(-50%);
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .input-group {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        /* Brilho vermelho ao clicar na pesquisa */
        .input-group:focus-within {
            background: rgba(0, 0, 0, 0.5);
            border-color: #E50914;
            box-shadow: 0 0 25px rgba(229, 9, 20, 0.35);
            transform: scale(1.02);
        }
        .search-input {
            background-color: transparent;
            border: none;
            color: #fff !important;
            box-shadow: none !important;
        }
        .search-input:focus {
            background-color: transparent;
            color: #fff !important;
        }
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        /* Botões Modernos */
        .btn-glass {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            transition: all 0.3s ease;
        }
        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: #fff;
            transform: translateY(-2px);
        }
        .btn-gradient {
            background: linear-gradient(135deg, #E50914 0%, #ff4d4d 100%);
            border: none;
            color: #fff;
            box-shadow: 0 4px 15px rgba(229, 9, 20, 0.4);
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.6);
            color: #fff;
        }
        /* Perfil Moderno */
        .user-profile-btn {
            background: transparent;
            border: 1px solid transparent;
            padding: 4px 12px 4px 4px;
            border-radius: 50px;
            transition: all 0.3s ease;
            color: #e0e0e0;
        }
        .user-profile-btn:hover, .user-profile-btn.show {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }
        .user-avatar {
            width: 35px; height: 35px;
            background: linear-gradient(135deg, #E50914, #b20710);
            box-shadow: 0 2px 8px rgba(229, 9, 20, 0.4);
        }
        .hover-effect { transition: color 0.3s ease; }
        .hover-effect:hover { color: #E50914 !important; }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4 sticky-top py-3">
        <div class="container-fluid px-5">
            <a class="navbar-brand" href="<?= $base_url ?>/index.php">
                <img src="<?= $base_url ?>/assets/logo.png" alt="CineHub" height="80">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Links à esquerda -->
                <ul class="navbar-nav mb-2 mb-lg-0 me-4 fs-5">
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/pages/movies.php?tab=movies">Filmes</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/pages/cinemas.php">Cinemas</a></li>
                </ul>
                
                <!-- Barra de Pesquisa -->
                <?php if (basename($_SERVER['PHP_SELF']) !== 'login.php' && basename($_SERVER['PHP_SELF']) !== 'registar.php'): ?>
                <form class="d-flex ms-auto me-4" role="search" method="GET" action="<?= $base_url ?>/pages/movies.php">
                    <div class="input-group py-1" style="width: 500px;">
                        <input class="form-control search-input ps-4 py-3" type="search" name="q" placeholder="Pesquisar por filmes..." aria-label="Search" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>" <?= isset($_GET['auto_focus']) ? 'autofocus' : '' ?> autocomplete="off">
                        <span class="input-group-text bg-transparent border-0 text-secondary pe-3"><i class="bi bi-search"></i></span>
                    </div>
                </form>
                <?php endif; ?>

                <!-- Grupo da Direita: Carteira + Perfil -->
                <div class="d-flex align-items-center gap-4">
                    
                    <!-- Ícone Carteira/Bilhetes -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?= $base_url ?>/pages/my_tickets.php" class="text-decoration-none text-white d-flex align-items-center gap-2 hover-effect">
                            <i class="bi bi-ticket-perforated fs-4"></i>
                            <span class="d-none d-xl-inline fw-medium fs-5">Bilhetes</span>
                        </a>
                    <?php endif; ?>

                    <!-- Perfil / Login -->
                    <?php if (isset($_SESSION['user_name'])): ?>
                        <!-- Menu de Utilizador (Logado) -->
                        <div class="dropdown">
                            <button class="user-profile-btn dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar rounded-circle d-flex align-items-center justify-content-center me-2">
                                    <span class="fw-bold text-white fs-4"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
                                </div>
                                <span class="fw-medium me-1 d-none d-lg-inline fs-5"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark bg-dark border-secondary shadow-lg mt-3 p-2" style="min-width: 260px;">
                                <li><h6 class="dropdown-header text-uppercase small text-secondary fw-bold mb-2">Notificações</h6></li>
                                <li>
                                    <a class="dropdown-item rounded d-flex align-items-center justify-content-between py-2 mb-1" href="<?= $base_url ?>/auth/notifications.php">
                                        <span><i class="bi bi-bell-fill text-danger me-2"></i>Estreias Seguidas</span>
                                        <span class="badge bg-danger rounded-pill"><?= $notifCount ?></span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider border-secondary my-2"></li>
                                <li><h6 class="dropdown-header text-uppercase small text-secondary fw-bold mb-2">Minha Conta</h6></li>
                                <li><a class="dropdown-item rounded py-2 mb-1" href="<?= $base_url ?>/pages/faturas.php"><i class="bi bi-receipt text-danger me-2"></i>Resumo e Faturas</a></li>
                                <?php if (isset($_SESSION['tipo_permissao']) && strtoupper($_SESSION['tipo_permissao']) === 'ADMINISTRADOR'): ?>
                                    <li><a class="dropdown-item rounded py-2 mb-1 text-warning fw-bold" href="<?= $base_url ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Painel Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider border-secondary my-2"></li>
                                <li><a class="dropdown-item rounded py-2 text-danger fw-bold" href="<?= $base_url ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Terminar Sessão</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= $base_url ?>/auth/login.php" class="text-decoration-none text-white d-flex align-items-center gap-2 hover-effect">
                            <i class="bi bi-person fs-4"></i>
                            <span class="d-none d-md-inline fw-medium fs-5">Login</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <div class="container">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="q"]');
    if (!searchInput) return;

    // CORREÇÃO: Garante que o cursor fica no final do texto ao carregar com autofocus
    if (searchInput.hasAttribute('autofocus')) {
        const val = searchInput.value;
        searchInput.focus();
        searchInput.value = '';
        searchInput.value = val;
    }

    const isMoviesPage = window.location.pathname.includes('/pages/movies.php');

    searchInput.addEventListener('input', function() {
        const query = this.value;
        
        if (isMoviesPage) {
            // --- Lógica para a página de Filmes (Mantida) ---
            const gridContainer = document.querySelector('#pills-movies .row'); // Grelha da aba "Todos os Filmes"
            const moviesTabBtn = document.querySelector('#pills-movies-tab');
            
            if (moviesTabBtn && !moviesTabBtn.classList.contains('active')) {
                bootstrap.Tab.getInstance(moviesTabBtn)?.show() || new bootstrap.Tab(moviesTabBtn).show();
            }

            const url = new URL(window.location);
            query ? url.searchParams.set('q', query) : url.searchParams.delete('q');
            window.history.pushState({}, '', url);

            fetch(`${window.location.pathname}?q=${encodeURIComponent(query)}&ajax=1`)
                .then(response => response.text())
                .then(html => { if (gridContainer) gridContainer.innerHTML = html; })
                .catch(err => console.error('Erro na pesquisa:', err));
        } else {
            // Se não estiver na página de filmes, redireciona logo ao começar a escrever
            if (query.length > 0) {
                const form = searchInput.closest('form');
                const actionUrl = form ? form.getAttribute('action') : '/pages/movies.php';
                window.location.href = actionUrl + '?q=' + encodeURIComponent(query) + '&auto_focus=1';
            }
        }
    });
});
</script>
</body>