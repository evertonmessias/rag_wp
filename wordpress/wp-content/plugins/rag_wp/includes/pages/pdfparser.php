<!-- Arquivo: wp-content/plugins/novoicode/includes/pages/pdfparser.php -->
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

    .progress {
        border-radius: 10px;
        overflow: hidden;
    }

    .form-control {
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    #agendamento_ativo {
        margin-top: 7px;
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

    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }

    .card .card-body.py-3 {
        padding: 1rem !important;
    }

    .card .card-title {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
    }

    .card .card-text {
        font-size: 1.5rem;
    }
</style>




<div class="row">

    <div class="col-6">
        <!-- Card de Indexação Manual -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-file-pdf me-2"></i>Indexação Manual de PDFs</h4>
            </div>
            <div class="card-body">

                <form method="post" class="row g-3">
                    <?php wp_nonce_field('indexar_pdfs_action', 'indexar_pdfs_nonce'); ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tipo de Post:</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione um tipo</option>
                            <?php
                            $args = array('public' => true);
                            $post_types = get_post_types($args, 'objects');
                            $excluir = array('post', 'page', 'attachment');
                            foreach ($post_types as $post_type) {
                                if (in_array($post_type->name, $excluir))
                                    continue;
                                $selected = isset($_POST['tipo']) && $_POST['tipo'] === $post_type->name ? 'selected' : '';
                                echo '<option value="' . esc_attr($post_type->name) . '" ' . $selected . '>' . esc_html($post_type->label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ano:</label>
                        <select name="ano" class="form-select" required>
                            <option value="">Selecione um ano</option>
                            <?php
                            $ano_atual = date('Y');
                            for ($ano = $ano_atual; $ano >= 2008; $ano--) {
                                $selected = isset($_POST['ano']) && $_POST['ano'] == $ano ? 'selected' : '';
                                echo '<option value="' . $ano . '" ' . $selected . '>' . $ano . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <button type="submit" name="indexar_pdfs" class="btn btn-primary">
                            <i class="fas fa-play me-2"></i>Iniciar Indexação
                        </button>
                    </div>
                </form>
                <?php
                // Processar indexação manual
                if (isset($_POST['indexar_pdfs']) && wp_verify_nonce($_POST['indexar_pdfs_nonce'], 'indexar_pdfs_action')) {
                    $tipo = sanitize_text_field($_POST['tipo']);
                    $ano = sanitize_text_field($_POST['ano']);

                    if (empty($tipo) || empty($ano)) {
                        echo '<div class="alert alert-danger mt-4"><i class="fas fa-exclamation-triangle me-2"></i>Por favor, selecione um tipo e ano.</div>';
                    } else {
                        novoicode_processar_indexacao_manual($tipo, $ano);
                    }
                }
                ?>

            </div>
        </div>

    </div>

    <div class="col-6">
        <!-- Card de Agendamento Automático -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="fas fa-clock me-2"></i>Agendamento Automático</h4>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <?php wp_nonce_field('salvar_agendamento_action', 'salvar_agendamento_nonce'); ?>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="agendamento_ativo"
                                id="agendamento_ativo" value="1" <?php checked(get_option('novoicode_agendamento_ativo'), '1'); ?> />
                            <label class="form-check-label fw-bold" for="agendamento_ativo">
                                Ativar Indexação Automática
                            </label>
                        </div>
                    </div>

                    <div class="col-12 d-flex align-items-end">
                        <button type="submit" name="salvar_agendamento" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar Configuração
                        </button>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Informação:</strong> A indexação automática processará todos os tipos de
                                conteúdo configurados no sistema (apenas ano atual).
                                <?php
                                $tipos_automaticos_str = get_option('portal_input_7');
                                if (!empty($tipos_automaticos_str)) {
                                    $tipos_automaticos = array_map('trim', explode(',', $tipos_automaticos_str));
                                    echo '<br>Tipos: ' . implode(', ', $tipos_automaticos);
                                } else {
                                    echo '<br>Nenhum tipo configurado no portal_input_7.';
                                }
                                ?>
                            </small>
                        </div>
                    </div>
                </form>

                <?php
                // Status do Agendamento
                $agendamento_ativo = get_option('novoicode_agendamento_ativo');
                if ($agendamento_ativo) {
                    echo '<div class="alert alert-warning mt-3">';
                    echo '<i class="fas fa-sync-alt me-2"></i>';
                    echo '<strong>Agendamento Ativo!</strong> A indexação automática está configurada.';

                    // Botão "Executar Agora"
                    echo '<form method="post" style="display:inline;" class="ms-2">';
                    wp_nonce_field('executar_agora_action', 'executar_agora_nonce');
                    echo '<button type="submit" name="executar_agora" class="btn btn-sm btn-outline-primary">';
                    echo '<i class="fas fa-play me-1"></i>Executar Agora';
                    echo '</button>';
                    echo '</form>';

                    echo '</div>';
                }

                // Processar configuração do agendamento
                if (isset($_POST['salvar_agendamento']) && wp_verify_nonce($_POST['salvar_agendamento_nonce'], 'salvar_agendamento_action')) {
                    $agendamento_ativo = isset($_POST['agendamento_ativo']) ? '1' : '0';
                    update_option('novoicode_agendamento_ativo', $agendamento_ativo);

                    if ($agendamento_ativo) {
                        echo '<div class="alert alert-success mt-3"><i class="fas fa-check-circle me-2"></i>Agendamento ativado com sucesso.</div>';
                    } else {
                        echo '<div class="alert alert-success mt-3"><i class="fas fa-check-circle me-2"></i>Agendamento desativado com sucesso.</div>';
                    }
                }

                // Execução manual via "Executar Agora"
                if (isset($_POST['executar_agora']) && wp_verify_nonce($_POST['executar_agora_nonce'], 'executar_agora_action')) {
                    echo executar_indexacao_manual_interface();
                }
                ?>
            </div>
        </div>
    </div>

</div>




<div class="row">
    <div class="col-12">
        <!-- Histórico de Indexação -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Indexações (PHP => MySQL)</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>DATA/HORA</th>
                                <th>NOVOS INDEXADOS</th>
                                <th>TEMPO</th>
                                <th>MEMÓRIA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $log_file = ICODE_LOGS_DIR . 'icode-pdf-indexacao.log';
                            $historico = [];

                            if (file_exists($log_file)) {

                                $conteudo = file_get_contents($log_file);
                                $linhas = explode("\n", $conteudo);

                                foreach ($linhas as $linha) {
                                    // Procura por linhas que contenham o padrão do novo log
                                    if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \| NOVOS INDEXADOS: (\d+) \| TEMPO: ([\d.]+s) \| MEMÓRIA: ([\d.]+MB)/', $linha, $matches)) {
                                        $historico[] = [
                                            'data_hora' => $matches[1],
                                            'novos_indexados' => (int) $matches[2],
                                            'tempo' => $matches[3],
                                            'memoria' => $matches[4]
                                        ];
                                    }
                                }

                                $historico = array_reverse($historico);
                                $historico = array_slice($historico, 0, 20); // Últimas 20 execuções
                            
                                if (empty($historico)) {
                                    echo '<tr><td colspan="4" class="text-center text-muted py-4">';
                                    echo 'Nenhum registro encontrado.';
                                    echo '</td></tr>';
                                } else {
                                    foreach ($historico as $registro) {
                                        $badge_class = $registro['novos_indexados'] > 0 ? 'bg-success' : 'bg-secondary';

                                        echo '<tr>';
                                        echo '<td><small class="text-muted">' . esc_html($registro['data_hora']) . '</small></td>';
                                        echo '<td><span class="badge ' . $badge_class . '">' . $registro['novos_indexados'] . '</span></td>';
                                        echo '<td><small class="text-muted">' . esc_html($registro['tempo']) . '</small></td>';
                                        echo '<td><small class="text-muted">' . esc_html($registro['memoria']) . '</small></td>';
                                        echo '</tr>';
                                    }
                                }

                            } else {
                                echo '<tr><td colspan="4" class="text-center text-danger py-4">';
                                echo 'Arquivo de log não encontrado: ' . esc_html($log_file);
                                echo '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-12">
        <!-- Histórico de Vetorização -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Vetorizações (Python => Postgre)</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>DATA/HORA</th>
                                <th>NOVOS TRECHOS</th>
                                <th>TEMPO</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            $log_file = ICODE_LOGS_DIR . 'icode-pdf-indexacao.log';
                            $historico = [];

                            if (file_exists($log_file)) {
                                $conteudo = file_get_contents($log_file);

                                // Cada execução do Python termina com "[data] Tempo total: X minutos".
                                // O bloco entre dois "Tempo total" é uma execução. Lê o formato novo
                                // ("Indexados agora: N documentos (M trechos)") e o antigo ("N novos chunks").
                                $partes = preg_split('/^\[([\d\- :]+)\]\s+Tempo total:\s+([\d.]+)\s+minutos.*$/mu', $conteudo, -1, PREG_SPLIT_DELIM_CAPTURE);
                                for ($i = 0; $i + 2 < count($partes); $i += 3) {
                                    $bloco = $partes[$i];
                                    $documentos = null;
                                    $falhas = 0;
                                    if (preg_match_all('/Indexados agora: (\d+) documentos \((\d+) trechos\)\. Falhas: (\d+)/u', $bloco, $m, PREG_SET_ORDER)) {
                                        $m = end($m);
                                        $documentos = (int) $m[1];
                                        $trechos = (int) $m[2];
                                        $falhas = (int) $m[3];
                                    } elseif (preg_match_all('/(\d+) novos chunks para processar/u', $bloco, $m)) {
                                        $trechos = (int) end($m[1]);
                                    } elseif (preg_match('/documentos para indexar/u', $bloco)) {
                                        $documentos = 0;
                                        $trechos = 0;
                                    } else {
                                        continue;
                                    }
                                    $historico[] = [
                                        'data_hora' => $partes[$i + 1],
                                        'documentos' => $documentos,
                                        'trechos' => $trechos,
                                        'falhas' => $falhas,
                                        'tempo' => $partes[$i + 2] . ' min',
                                    ];
                                }

                                $historico = array_slice(array_reverse($historico), 0, 20);

                                if (empty($historico)) {
                                    echo '<tr><td colspan="3" class="text-center text-muted py-4">Nenhum registro encontrado.</td></tr>';
                                } else {
                                    foreach ($historico as $registro) {
                                        $badge_class = $registro['trechos'] > 0 ? 'bg-success' : 'bg-secondary';
                                        $badge = '<span class="badge ' . $badge_class . '">' . $registro['trechos'] . '</span>';
                                        if ($registro['documentos'] !== null) {
                                            $badge .= ' <small class="text-muted">(' . $registro['documentos'] . ' doc.)</small>';
                                        }
                                        if ($registro['falhas'] > 0) {
                                            $badge .= ' <span class="badge bg-warning text-dark">' . $registro['falhas'] . ' falhas</span>';
                                        }
                                        echo '<tr>';
                                        echo '<td><small class="text-muted">' . htmlspecialchars($registro['data_hora']) . '</small></td>';
                                        echo '<td>' . $badge . '</td>';
                                        echo '<td><small class="text-muted">' . htmlspecialchars($registro['tempo']) . '</small></td>';
                                        echo '</tr>';
                                    }
                                }
                            } else {
                                echo '<tr><td colspan="3" class="text-center text-danger py-4">Arquivo de log não encontrado: ' . htmlspecialchars($log_file) . '</td></tr>';
                            }
                            ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    // Inicializar tooltips do Bootstrap
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
</script>