<?php
require dirname(__DIR__) . '/config/db.php';

// Iniciar sessão se necessário (pois o header.php agora é incluído depois)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['REQUEST_URI']);
    header("Location: /auth/login.php?redirect=" . $redirect);
    exit;
}

include dirname(__DIR__) . '/includes/header.php';

// Captura dados da URL
$movieId = $_GET['movie_id'] ?? 0;
$movieTitle = $_GET['title'] ?? 'Filme';
$sessionTime = $_GET['time'] ?? '00:00';
$seats = $_GET['seats'] ?? '';
$seatCount = intval($_GET['count'] ?? 0);
$paymentSuccess = false;

if ($seatCount === 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Nenhum lugar selecionado.'); window.location.href='/pages/movies.php';</script>";
    exit;
}

// Processar Pagamento e Inserir na BD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieIdPost = $_POST['movie_id'];
    $seatsList = explode(',', $_POST['seats_list']);
    $qtyAdult = intval($_POST['qty_adult']);
    $qtyChild = intval($_POST['qty_child']);
    $qtyFamily = intval($_POST['qty_family']);
    
    // Distribuição dos bilhetes pelos lugares selecionados
    $ticketsToInsert = [];
    
    // 1. Pack Família (4 lugares por pack)
    for ($i = 0; $i < $qtyFamily; $i++) {
        for ($j = 0; $j < 4; $j++) {
            $seat = array_shift($seatsList);
            if ($seat) $ticketsToInsert[] = ['type' => 'Pack Família', 'price' => 5.50, 'seat' => $seat];
        }
    }
    // 2. Adulto
    for ($i = 0; $i < $qtyAdult; $i++) {
        $seat = array_shift($seatsList);
        if ($seat) $ticketsToInsert[] = ['type' => 'Adulto', 'price' => 7.50, 'seat' => $seat];
    }
    // 3. Criança
    for ($i = 0; $i < $qtyChild; $i++) {
        $seat = array_shift($seatsList);
        if ($seat) $ticketsToInsert[] = ['type' => 'Criança', 'price' => 5.00, 'seat' => $seat];
    }

    try {
        // 1. Obter ou Criar ID da Sessão (Resolve o erro ORA-02291)
        // Verifica se já existe uma sessão para este filme e horário na tabela AAA_SESSOES
        $sessionId = null;
        $dataHoraStr = date('Y-m-d') . ' ' . $sessionTime;
        
        // Tenta encontrar sessão existente (compara ID_FILME e a DATA/HORA exata)
        $stmtCheck = $pdo->prepare("SELECT ID_SESSAO FROM AAA_SESSOES WHERE ID_FILME = :fid AND TO_CHAR(DATA_HORA, 'YYYY-MM-DD HH24:MI') = :dh");
        $stmtCheck->execute([':fid' => $movieIdPost, ':dh' => $dataHoraStr]);
        $sessao = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($sessao) {
            $sessionId = $sessao['ID_SESSAO'];
        } else {
            // Se não existe, cria uma nova sessão para hoje nesse horário
            $stmtCreate = $pdo->prepare("INSERT INTO AAA_SESSOES (ID_FILME, DATA_HORA, SALA) VALUES (:fid, TO_DATE(:dh, 'YYYY-MM-DD HH24:MI'), 1) RETURNING ID_SESSAO INTO :id_out");
            $stmtCreate->bindParam(':fid', $movieIdPost);
            $stmtCreate->bindParam(':dh', $dataHoraStr);
            $stmtCreate->bindParam(':id_out', $sessionId, PDO::PARAM_INT, 10);
            $stmtCreate->execute();
            
            // Fallback: Se o RETURNING INTO falhar em popular a variável (comum em alguns drivers), buscamos o ID manualmente
            if (empty($sessionId)) {
                $stmtGet = $pdo->prepare("SELECT ID_SESSAO FROM AAA_SESSOES WHERE ID_FILME = :fid AND TO_CHAR(DATA_HORA, 'YYYY-MM-DD HH24:MI') = :dh");
                $stmtGet->execute([':fid' => $movieIdPost, ':dh' => $dataHoraStr]);
                $res = $stmtGet->fetch(PDO::FETCH_ASSOC);
                if ($res) {
                    $sessionId = $res['ID_SESSAO'];
                }
            }
        }

        if (empty($sessionId)) {
            throw new Exception("Não foi possível criar ou recuperar a sessão para o bilhete.");
        }

        // 2. Inserir Bilhetes com o ID_SESSAO correto
        // Definir timestamp único para a transação para permitir agrupamento correto
        $transactionTime = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("INSERT INTO AAA_BILHETES (ID_SESSAO, ID_CLIENTE, ASSENTO, TIPO_BILHETE, PRECO, STATUS_PAGAMENTO, DATA_COMPRA) VALUES (:sessao, :cliente, :assento, :tipo, :preco, 'Pago', TO_TIMESTAMP(:dcompra, 'YYYY-MM-DD HH24:MI:SS'))");
        
        foreach ($ticketsToInsert as $ticket) {
            // Usa $sessionId em vez de $movieIdPost
            $stmt->execute([
                ':sessao' => $sessionId, 
                ':cliente' => $_SESSION['user_id'], 
                ':assento' => $ticket['seat'], 
                ':tipo' => $ticket['type'], 
                ':preco' => $ticket['price'],
                ':dcompra' => $transactionTime
            ]);
        }
        $paymentSuccess = true;
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger m-3'>Erro ao processar compra: " . $e->getMessage() . "</div>";
    }
}
?>

<style>
    .ticket-card {
        background-color: #1f1f1f;
        border: 1px solid #333;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .price-tag {
        font-size: 1.2rem;
        font-weight: bold;
        color: #e50914;
    }
    .counter-btn {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: 1px solid #555;
        background: #333;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .counter-btn:hover {
        background: #e50914;
        border-color: #e50914;
    }
    .counter-value {
        width: 40px;
        text-align: center;
        font-weight: bold;
        font-size: 1.1rem;
    }
    .summary-box {
        background-color: #181818;
        border: 1px solid #333;
        border-radius: 10px;
        padding: 25px;
        position: sticky;
        top: 100px;
    }
</style>

<div class="container py-5">
    <div class="row">
        <!-- Formulário Oculto para Envio -->
        <form id="paymentForm" method="POST" action="">
            <input type="hidden" name="movie_id" value="<?= htmlspecialchars($movieId) ?>">
            <input type="hidden" name="seats_list" value="<?= htmlspecialchars($seats) ?>">
            <input type="hidden" name="qty_adult" id="input_adult" value="0">
            <input type="hidden" name="qty_child" id="input_child" value="0">
            <input type="hidden" name="qty_family" id="input_family" value="0">
        </form>

        <!-- Coluna da Esquerda: Seleção de Bilhetes -->
        <div class="col-lg-8">
            <h2 class="fw-bold text-white mb-4 border-start border-4 border-danger ps-3">Escolha os seus Bilhetes</h2>
            <p class="text-secondary mb-4">Selecionou <strong><?= $seatCount ?></strong> lugares (<?= htmlspecialchars($seats) ?>). Por favor, distribua-os pelos tipos de bilhete abaixo.</p>

            <!-- Bilhete Adulto -->
            <div class="ticket-card d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-white mb-1">Adulto</h5>
                    <p class="text-secondary mb-0 small">Bilhete normal</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="price-tag">7,50 €</span>
                    <div class="d-flex align-items-center gap-2">
                        <button class="counter-btn" onclick="updateCount('adult', -1)">-</button>
                        <span id="adult-count" class="counter-value">0</span>
                        <button class="counter-btn" onclick="updateCount('adult', 1)">+</button>
                    </div>
                </div>
            </div>

            <!-- Bilhete Criança -->
            <div class="ticket-card d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-white mb-1">Criança</h5>
                    <p class="text-secondary mb-0 small">Até aos 12 anos</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="price-tag">5,00 €</span>
                    <div class="d-flex align-items-center gap-2">
                        <button class="counter-btn" onclick="updateCount('child', -1)">-</button>
                        <span id="child-count" class="counter-value">0</span>
                        <button class="counter-btn" onclick="updateCount('child', 1)">+</button>
                    </div>
                </div>
            </div>

            <!-- Bilhete Família -->
            <div class="ticket-card d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-white mb-1">Pack Família</h5>
                    <p class="text-secondary mb-0 small">Válido para 4 pessoas (2 Adultos + 2 Crianças)</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="price-tag">22,00 €</span>
                    <div class="d-flex align-items-center gap-2">
                        <button class="counter-btn" onclick="updateCount('family', -1)">-</button>
                        <span id="family-count" class="counter-value">0</span>
                        <button class="counter-btn" onclick="updateCount('family', 1)">+</button>
                    </div>
                </div>
            </div>

            <!-- Pagamento -->
            <h4 class="fw-bold text-white mt-5 mb-3">Pagamento</h4>
            <div class="ticket-card">
                <div class="mb-3">
                    <label class="form-label text-secondary">Nome no Cartão</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" placeholder="Como aparece no cartão">
                </div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label text-secondary">Número do Cartão</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" placeholder="0000 0000 0000 0000">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label text-secondary">Validade</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" placeholder="MM/AA">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label text-secondary">CVV</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" placeholder="123">
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna da Direita: Resumo -->
        <div class="col-lg-4">
            <div class="summary-box">
                <h4 class="text-white fw-bold mb-4">Resumo da Compra</h4>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Filme</p>
                    <h5 class="text-white"><?= htmlspecialchars($movieTitle) ?></h5>
                </div>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Sessão</p>
                    <h5 class="text-white"><?= htmlspecialchars($sessionTime) ?></h5>
                </div>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Lugares (<?= $seatCount ?>)</p>
                    <h5 class="text-white small"><?= htmlspecialchars($seats) ?></h5>
                </div>
                <hr class="border-secondary my-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Lugares Atribuídos:</span>
                    <span id="assigned-seats" class="text-white fw-bold">0 / <?= $seatCount ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <span class="text-white fs-5">Total:</span>
                    <span id="total-price" class="text-danger fs-3 fw-bold">0,00 €</span>
                </div>
                <button id="btnPay" class="btn btn-gradient w-100 mt-4 py-3 fw-bold" disabled onclick="processPayment()">Pagar e Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Sucesso -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" <?= $paymentSuccess ? 'data-show="true"' : '' ?>>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-success text-center p-4">
            <div class="modal-body">
                <div class="mb-3">
                    <i class="bi bi-check-circle-fill text-success display-1"></i>
                </div>
                <h3 class="fw-bold mb-3">Pagamento Efetuado!</h3>
                <p class="text-secondary mb-4">A sua compra foi processada com sucesso.</p>
                <a href="/pages/my_tickets.php" class="btn btn-success w-100 py-2 fw-bold"><i class="bi bi-receipt me-2"></i>Ver Bilhetes / Fatura</a>
            </div>
        </div>
    </div>
</div>

<script>
    const maxSeats = <?= $seatCount ?>;
    const prices = { adult: 7.50, child: 5.00, family: 22.00 };
    const seatsPerType = { adult: 1, child: 1, family: 4 }; // Família conta como 4 lugares
    
    let counts = { adult: 0, child: 0, family: 0 };

    function updateCount(type, change) {
        const currentTotalSeats = (counts.adult * seatsPerType.adult) + (counts.child * seatsPerType.child) + (counts.family * seatsPerType.family);
        const potentialSeats = change * seatsPerType[type];

        if (change > 0 && (currentTotalSeats + potentialSeats) > maxSeats) {
            alert('Não pode selecionar mais bilhetes do que os lugares escolhidos (' + maxSeats + ').');
            return;
        }
        if (change < 0 && counts[type] <= 0) return;

        counts[type] += change;
        document.getElementById(type + '-count').innerText = counts[type];
        updateSummary();
    }

    function updateSummary() {
        const totalSeatsUsed = (counts.adult * 1) + (counts.child * 1) + (counts.family * 4);
        const totalCost = (counts.adult * prices.adult) + (counts.child * prices.child) + (counts.family * prices.family);

        document.getElementById('assigned-seats').innerText = totalSeatsUsed + ' / ' + maxSeats;
        document.getElementById('total-price').innerText = totalCost.toFixed(2).replace('.', ',') + ' €';
        
        // Habilita botão apenas se todos os lugares estiverem atribuídos
        document.getElementById('btnPay').disabled = (totalSeatsUsed !== maxSeats);
    }

    function processPayment() {
        const btn = document.getElementById('btnPay');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A Processar...';
        btn.disabled = true;
        
        // Atualiza inputs ocultos e submete
        document.getElementById('input_adult').value = counts.adult;
        document.getElementById('input_child').value = counts.child;
        document.getElementById('input_family').value = counts.family;
        
        setTimeout(() => {
            document.getElementById('paymentForm').submit();
        }, 1000);
    }

    // Mostrar modal se PHP confirmar sucesso
    if (document.getElementById('successModal').getAttribute('data-show') === 'true') {
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('successModal')).show();
        });
    }
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
