<!-- Arquivo: wp-content/plugins/novoicode/includes/pages/sync.php -->
<style>
    .card {
        border: none;
        border-radius: 10px;
        max-width: 100% !important;
    }

    .card-header {
        border-radius: 10px 10px 0 0 !important;
        font-weight: 600;
    }

    .alert {
        border-radius: 8px;
        border: none;
    }

    .table th {
        border-top: none;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
</style>

<?php
if (!current_user_can('manage_options')) {
    return;
}

// Execução manual via "Sincronizar Agora"
$sync_executado = false;
if (isset($_POST['sincronizar_agora']) && wp_verify_nonce($_POST['sincronizar_agora_nonce'], 'sincronizar_agora_action')) {
    icode_sincronizar_todos_usuarios('manual');
    $sync_executado = true;
}

$ultima = get_option('icode_ultima_sincronizacao', []);
$proxima = wp_next_scheduled('icode_sincronizar_usuarios_cron');
?>


<div class="row">

    <div class="col-6">
        <!-- Card de Sincronização -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-users-cog me-2"></i>Sincronizar Usuários</h4>
            </div>
            <div class="card-body">
                <p>Reaplica o papel (Administrador/Editor/Assinante) e as permissões de membro de todos os usuários,
                    conforme as listas das Configurações e a Intranet.</p>

                <div class="alert alert-warning">
                    <i class="fas fa-sync-alt me-2"></i>
                    <strong>Agendamento diário.</strong> Próxima execução:
                    <?php
                    if (!icode_eh_producao()) {
                        echo 'desativado fora da produção (' . esc_html(ICODE_HOST_PRODUCAO) . ')';
                    } else {
                        echo $proxima ? esc_html(get_date_from_gmt(gmdate('Y-m-d H:i:s', $proxima), 'd/m/Y H:i')) : 'não agendada';
                    }
                    ?>
                </div>

                <form method="post">
                    <?php wp_nonce_field('sincronizar_agora_action', 'sincronizar_agora_nonce'); ?>
                    <button type="submit" name="sincronizar_agora" class="btn btn-primary">
                        <i class="fas fa-play me-1"></i>Sincronizar Agora
                    </button>
                </form>

                <?php if ($sync_executado): ?>
                    <div class="alert alert-success mt-3"><i class="fas fa-check-circle me-2"></i>Sincronização executada.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>


<?php if ($ultima): ?>
<div class="row">
    <div class="col-12">
        <!-- Resultado da Última Sincronização -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="fas fa-history me-2"></i>Última Sincronização: <?php echo esc_html(mysql2date('d/m/Y H:i', $ultima['data'])); ?></h4>
            </div>
            <div class="card-body">
                <?php if (!empty($ultima['erro'])): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo esc_html($ultima['erro']); ?></div>
                <?php else: ?>
                    <p><?php echo (int) $ultima['total']; ?> usuários verificados,
                        <?php echo count($ultima['alterados']); ?> alterados.</p>

                    <?php if ($ultima['alterados']): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>USUÁRIO</th>
                                        <th>ALTERAÇÕES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultima['alterados'] as $login => $alteracoes): ?>
                                        <tr>
                                            <td><?php echo esc_html($login); ?></td>
                                            <td><?php echo implode('<br>', array_map('esc_html', $alteracoes)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<div class="row">
    <div class="col-12">
        <!-- Histórico de Sincronizações -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Histórico de Sincronizações (<?php echo esc_html(basename(ICODE_SYNC_LOG)); ?>)</h4>
            </div>
            <div class="card-body">
                <?php
                if (file_exists(ICODE_SYNC_LOG)) {
                    // Últimas 300 linhas, mais recentes no fim
                    $linhas = array_slice(file(ICODE_SYNC_LOG, FILE_IGNORE_NEW_LINES), -300);
                    echo '<pre class="mb-0" style="max-height:500px;overflow:auto;white-space:pre-wrap;">' . esc_html(implode("\n", $linhas)) . '</pre>';
                } else {
                    echo '<div class="text-center text-muted py-4">Nenhum registro encontrado.</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
