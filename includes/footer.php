<?php
// includes/footer.php
?>

</div> <!-- /container -->

<!-- Footer Moderno -->
<footer class="bg-black text-white pt-5 pb-4 mt-5 border-top border-secondary border-opacity-25">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-md-6 mb-4 mb-lg-0">
                <h5 class="text-uppercase fw-bold text-danger mb-3">Cinema Hub</h5>
                <p class="text-secondary small">A sua plataforma de eleição para reservar bilhetes de cinema, consultar estreias e descobrir os melhores filmes em cartaz.</p>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h6 class="text-uppercase fw-bold mb-3 text-white">Navegação</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?= $base_url ?? '' ?>/index.php" class="footer-link">Início</a></li>
                    <li class="mb-2"><a href="<?= $base_url ?? '' ?>/pages/movies.php" class="footer-link">Filmes</a></li>
                    <li class="mb-2"><a href="<?= $base_url ?? '' ?>/pages/cinemas.php" class="footer-link">Cinemas</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h6 class="text-uppercase fw-bold mb-3 text-white">Conta</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?= $base_url ?? '' ?>/auth/login.php" class="footer-link">Login</a></li>
                    <li class="mb-2"><a href="<?= $base_url ?? '' ?>/auth/registar.php" class="footer-link">Registar</a></li>
                    <li class="mb-2"><a href="<?= $base_url ?? '' ?>/pages/my_tickets.php" class="footer-link">Meus Bilhetes</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary border-opacity-25 my-4">
        <div class="row align-items-center">
            <div class="col-12 text-center">
                <p class="text-secondary small mb-0">&copy; <?= date('Y') ?> CineHub. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Botão Voltar ao Topo -->
<button id="backToTopBtn" title="Voltar ao Topo">
    <i class="bi bi-arrow-up"></i>
</button>

<script>
window.onscroll = function() {scrollFunction()};

function scrollFunction() {
    const btn = document.getElementById("backToTopBtn");
    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        btn.classList.add("show");
    } else {
        btn.classList.remove("show");
    }
}

document.getElementById("backToTopBtn").addEventListener('click', function() {
    topFunction();
});

// Função para rolar suavemente para o topo
function topFunction() {
  // Para navegadores modernos
  document.documentElement.scrollTo({
    top: 0,
    behavior: "smooth"
  });

  // Para Safari
  document.body.scrollTo({
      top: 0,
      behavior: "smooth"
  });
};
</script>

<style>
    #backToTopBtn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 99;
        border: none;
        outline: none;
        background: linear-gradient(135deg, #e50914, #b20710);
        color: white;
        cursor: pointer;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transform: scale(0.8);
    }
    #backToTopBtn.show {
        opacity: 1;
        visibility: visible;
        transform: scale(1);
    }
    #backToTopBtn:hover {
        background: linear-gradient(135deg, #ff4d4d, #e50914);
        box-shadow: 0 8px 25px rgba(229, 9, 20, 0.6);
        transform: scale(1.1);
    }
    
    /* Estilos do Footer */
    .footer-link {
        color: #aaa;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    .footer-link:hover { color: #E50914; }
</style>

</body>

</html>