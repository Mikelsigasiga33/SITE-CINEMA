<?php
require dirname(__DIR__) . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

include dirname(__DIR__) . '/includes/header.php';

$invoices = [];
$error = '';

try {
    $userId = $_SESSION['user_id'];
    
    // Agrupa bilhetes pela data exata da compra para simular uma fatura por transação
    $sql = "SELECT 
                b.ID_BILHETE, 
                b.PRECO, 
                b.TIPO_BILHETE, 
                b.ASSENTO, 
                b.STATUS_PAGAMENTO,
                TO_CHAR(b.DATA_COMPRA, 'YYYY-MM-DD HH24:MI:SS') as DATA_TRANSACAO,
                f.TITULO
            FROM AAA_BILHETES b
            LEFT JOIN AAA_SESSOES s ON b.ID_SESSAO = s.ID_SESSAO
            LEFT JOIN AAA_FILMES f ON s.ID_FILME = f.ID_FILME
            WHERE b.ID_CLIENTE = :user_id
            ORDER BY DATA_TRANSACAO DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = $row['DATA_TRANSACAO'];
        if (!isset($invoices[$date])) {
            $invoices[$date] = [
                'DATA_TRANSACAO' => $date,
                'TOTAL' => 0,
                'QTD_ITENS' => 0,
                'DESCRICAO_PRINCIPAL' => $row['TITULO'],
                'STATUS' => $row['STATUS_PAGAMENTO'],
                'ITENS' => []
            ];
        }
        $invoices[$date]['ITENS'][] = $row;
        $invoices[$date]['TOTAL'] += $row['PRECO'];
        $invoices[$date]['QTD_ITENS']++;
    }

} catch (PDOException $e) {
    $error = "Erro ao carregar faturas: " . $e->getMessage();
}
?>

<style>
    .invoice-table {
        background-color: #1f1f1f;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #333;
    }
    .invoice-table th {
        background-color: #151515;
        color: #aaa;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        padding: 15px;
        border-bottom: 1px solid #333;
    }
    .invoice-table td {
        padding: 15px;
        color: #e0e0e0;
        border-bottom: 1px solid #2a2a2a;
        vertical-align: middle;
    }
    .invoice-table tr:last-child td { border-bottom: none; }
    .invoice-table tr:hover td { background-color: rgba(255,255,255,0.02); }
    
    .status-dot {
        height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 6px;
    }
    .status-paid { background-color: #198754; box-shadow: 0 0 5px rgba(25, 135, 84, 0.5); }

    /* Estilo da Fatura Digital (Modal) */
    .digital-invoice {
        background-color: #fff;
        color: #333;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 0 50px rgba(0,0,0,0.8);
    }
    .invoice-header {
        padding: 30px;
        border-bottom: 2px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .invoice-body { padding: 30px; }
    .invoice-footer {
        background-color: #f8f9fa;
        padding: 20px 30px;
        border-top: 1px solid #eee;
    }
</style>

<div class="container py-5">
    <h2 class="fw-bold text-white mb-4 border-start border-4 border-danger ps-3">Resumo Financeiro</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if (empty($invoices)): ?>
        <div class="text-center py-5 text-secondary">
            <i class="bi bi-receipt display-1 mb-3 d-block opacity-25"></i>
            <h4>Sem faturas disponíveis</h4>
            <p>Ainda não efetuou nenhuma compra.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive invoice-table">
            <table class="table table-dark table-borderless mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Itens</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th class="text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($inv['DATA_TRANSACAO'])) ?> <small class="ms-1"><?= date('H:i', strtotime($inv['DATA_TRANSACAO'])) ?></small></td>
                            <td class="fw-bold text-white"><?= htmlspecialchars($inv['DESCRICAO_PRINCIPAL'] ?? 'Compra de Bilhetes') ?></td>
                            <td><?= $inv['QTD_ITENS'] ?> bilhete(s)</td>
                            <td class="fw-bold"><?= number_format($inv['TOTAL'], 2, ',', '.') ?> €</td>
                            <td><span class="status-dot status-paid"></span><?= ucfirst(strtolower($inv['STATUS'])) ?></td>
                            <td class="text-end"><button onclick='openInvoiceModal(<?= htmlspecialchars(json_encode($inv), ENT_QUOTES, "UTF-8") ?>)' class="btn btn-sm btn-outline-light"><i class="bi bi-eye me-1"></i>Ver Fatura</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Fatura -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="digital-invoice">
                <div class="invoice-header">
                    <div>
                        <img src="<?= $base_url ?>/assets/logo.png" alt="CineHub" height="60" class="d-block mb-2">
                        <small class="text-muted">Cinema & Entretenimento</small>
                    </div>
                    <div class="text-end">
                        <h5 class="fw-bold m-0">FATURA-RECIBO</h5>
                        <small class="text-muted" id="invDate">--/--/----</small>
                    </div>
                </div>
                <div class="invoice-body">
                    <div class="row mb-4">
                        <div class="col-6">
                            <h6 class="fw-bold">Cliente</h6>
                            <p class="mb-0"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                            <small class="text-muted">Consumidor Final</small>
                        </div>
                        <div class="col-6 text-end">
                            <h6 class="fw-bold">Nº Fatura</h6>
                            <p class="mb-0" id="invNo">FT 000/000</p>
                        </div>
                    </div>
                    
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Descrição</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Preço</th>
                            </tr>
                        </thead>
                        <tbody id="invItems"></tbody>
                    </table>
                </div>
                <div class="invoice-footer">
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Processado por computador.</small>
                        </div>
                        <div class="col-6 text-end">
                            <h5 class="fw-bold">Total: <span class="text-danger" id="invTotal">0,00 €</span></h5>
                            <small class="text-muted">IVA incluído à taxa legal</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openInvoiceModal(invoice) {
        document.getElementById('invDate').innerText = invoice.DATA_TRANSACAO;
        // Gera um número de fatura fictício baseado na data
        document.getElementById('invNo').innerText = 'FT ' + invoice.DATA_TRANSACAO.replace(/[^0-9]/g, '').substring(0, 12);
        document.getElementById('invTotal').innerText = parseFloat(invoice.TOTAL).toFixed(2).replace('.', ',') + ' €';
        
        const tbody = document.getElementById('invItems');
        tbody.innerHTML = '';
        
        invoice.ITENS.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <span class="fw-bold">${item.TITULO}</span><br>
                    <small class="text-muted">${item.TIPO_BILHETE} - Lugar ${item.ASSENTO}</small>
                </td>
                <td class="text-center">1</td>
                <td class="text-end">${parseFloat(item.PRECO).toFixed(2).replace('.', ',')} €</td>
            `;
            tbody.appendChild(tr);
        });
        
        new bootstrap.Modal(document.getElementById('invoiceModal')).show();
    }
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>