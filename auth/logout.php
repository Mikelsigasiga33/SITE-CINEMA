<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();   // Limpa todas as variáveis de sessão
session_destroy(); // Destrói a sessão no servidor
header("Location: /auth/login.php"); // Redireciona para o login
exit;
