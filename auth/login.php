<?php
require dirname(__DIR__) . '/config/db.php';

// Iniciar sessão antes de qualquer output (HTML)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Redirecionar se já estiver logado
if (isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

// 2. Processar o Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    if (empty($login) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        try {
            // 3. Buscar utilizador pelo Email
            // Nota: Usamos UPPER() ou LOWER() se quiseres ignorar maiúsculas/minúsculas no email
            $stmt = $pdo->prepare("SELECT ID_UTILIZADOR, NOME, EMAIL, SENHA, TIPO_PERMISSAO FROM AAA_UTILIZADORES WHERE EMAIL = :email");
            $stmt->execute([':email' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Verificar Senha
            if ($user && password_verify($password, $user['SENHA'])) {
                // 5. Iniciar Sessão
                $_SESSION['user_id'] = $user['ID_UTILIZADOR'];
                $_SESSION['user_name'] = $user['NOME'];
                $_SESSION['user_email'] = $user['EMAIL'];
                $_SESSION['tipo_permissao'] = $user['TIPO_PERMISSAO'];

                // Redirecionar para a página inicial
                if (!empty($redirect)) {
                    header("Location: " . $redirect);
                } else {
                    header("Location: /index.php");
                }
                exit;
            } else {
                $error = "Email ou palavra-pass incorretos.";
            }
        } catch (PDOException $e) {
            $error = "Erro no sistema: " . $e->getMessage();
        }
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<style>
    body {
        background-color: #121212;
        /* Fundo com imagem cinematográfica, sobreposição escura e desfoque */
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.9)), url('https://picsum.photos/1920/1080?grayscale&blur=2');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
    }

    .login-container {
        max-width: 450px;
        margin: 60px auto;
        background-color: #1f1f1f;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border: 1px solid #333;
    }
    
    .form-control {
        background-color: #121212;
        border: 1px solid #333;
        color: #fff;
        padding: 12px 15px;
    }
    
    .form-control:focus {
        background-color: #121212;
        border-color: #e50914;
        color: #fff;
        box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
    }

    .btn-login {
        background: linear-gradient(135deg, #e50914 0%, #ff4d4d 100%);
        border: none;
        color: white;
        padding: 12px;
        font-weight: bold;
        width: 100%;
        border-radius: 8px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
        color: white;
    }

    .link-danger-custom {
        color: #e50914;
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .link-danger-custom:hover {
        color: #ff4d4d;
        text-decoration: underline;
    }
</style>

<div class="container">
    <div class="login-container">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-white">Bem-vindo de volta</h2>
            <p class="text-secondary">Introduza os seus dados para entrar</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <div class="mb-3">
                <label for="loginInput" class="form-label text-secondary">Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="loginInput" name="login" placeholder="exemplo@email.com ou 912345678" required>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label for="passwordInput" class="form-label text-secondary">Password</label>
                    <a href="recuperar.php" class="link-danger-custom small">Esqueceu-se da password?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="passwordInput" name="password" placeholder="********" required>
                    <button class="btn btn-outline-secondary bg-dark border-secondary text-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-login mt-3 mb-4">Entrar</button>

            <div class="text-center text-secondary">
                Não tem conta? <a href="/auth/registar.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" class="link-danger-custom fw-bold">Criar nova conta</a>
            </div>
        </form>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#passwordInput');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
