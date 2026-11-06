<?php
// Arquivo: wp-content/plugins/novoicode/includes/src/sync.php
// ************************************************************************************** Sincronização de Usuários
// Reaplica papel e permissões de todos os usuários conforme portal_input_X + Intranet,
// sem que o usuário precise fazer login novamente.


// Arquivo de histórico das sincronizações (texto puro, em src/logs/)
define('ICODE_SYNC_LOG', ICODE_LOGS_DIR . 'icode-sync-usuarios.log');


// ************* Sincroniza todos os usuários
// $origem: 'automática' (WP-Cron) ou 'manual' (botão "Sincronizar Agora")
function icode_sincronizar_todos_usuarios($origem = 'automática')
{
    error_log('🔄 sync.php - Sincronização de usuários INICIADA');

    // Busca as listas da Intranet uma vez só; se algo falhar, aborta sem alterar ninguém
    icode_limpar_cache_intranet();
    icode_intranet_erro(false);
    lista_docentes_intranet();
    lista_administracao_intranet();
    foreach (get_tipos_configurados() as $slug) {
        if (!empty($slug)) {
            lista_usuarios_colegiado($slug);
        }
    }

    if (icode_intranet_erro()) {
        $resultado = [
            'data' => current_time('mysql'),
            'origem' => $origem,
            'erro' => 'Intranet indisponível. Nenhum usuário foi alterado.',
            'total' => 0,
            'alterados' => []
        ];
        icode_registrar_sincronizacao($resultado);
        error_log('❌ sync.php - Intranet indisponível, sincronização abortada');
        return $resultado;
    }

    $alterados = [];
    $total = 0;

    foreach (get_users() as $user) {
        // Admin local e usuários sem e-mail não são gerenciados pela Intranet
        if ($user->user_login === 'admin' || empty($user->user_email)) {
            continue;
        }

        $total++;
        $alteracoes = sincronizar_usuario($user, $user->user_email);
        if ($alteracoes) {
            $alterados[$user->user_login] = $alteracoes;
            error_log('🔄 sync.php - ' . $user->user_login . ': ' . implode(' | ', $alteracoes));
        }
    }

    $resultado = [
        'data' => current_time('mysql'),
        'origem' => $origem,
        'erro' => '',
        'total' => $total,
        'alterados' => $alterados
    ];
    icode_registrar_sincronizacao($resultado);

    error_log('✅ sync.php - Sincronização FINALIZADA: ' . $total . ' usuários, ' . count($alterados) . ' alterados');
    return $resultado;
}


// ************* Grava o resultado (última execução na option + histórico no log)
function icode_registrar_sincronizacao($resultado)
{
    update_option('icode_ultima_sincronizacao', $resultado, false);

    $usuario = wp_get_current_user();
    $origem = $resultado['origem'] . ($usuario->exists() ? ' por ' . $usuario->user_login : '');

    $texto = '=============== Sincronização de usuários (' . $origem . ') em ' . $resultado['data'] . " ===\n";
    if ($resultado['erro']) {
        $texto .= 'ERRO: ' . $resultado['erro'] . "\n";
    } else {
        $texto .= $resultado['total'] . ' usuários verificados, ' . count($resultado['alterados']) . " alterados\n";
        foreach ($resultado['alterados'] as $login => $alteracoes) {
            $texto .= $login . ': ' . implode(' | ', $alteracoes) . "\n";
        }
    }
    $texto .= "\n";

    if (file_put_contents(ICODE_SYNC_LOG, $texto, FILE_APPEND | LOCK_EX) === false) {
        error_log('❌ sync.php - Não foi possível gravar ' . ICODE_SYNC_LOG . ' (verifique a permissão de escrita da pasta logs/)');
    }
}


// ************* Agendamento diário (WP-Cron) - somente em produção
// Em dev (Docker) a sincronização só roda pelo botão "Sincronizar Agora"
define('ICODE_HOST_PRODUCAO', 'icode.ic.unicamp.br');

function icode_eh_producao()
{
    return parse_url(home_url(), PHP_URL_HOST) === ICODE_HOST_PRODUCAO;
}

function icode_sincronizar_usuarios_cron()
{
    if (icode_eh_producao()) {
        icode_sincronizar_todos_usuarios();
    }
}
add_action('icode_sincronizar_usuarios_cron', 'icode_sincronizar_usuarios_cron');

function icode_agendar_sincronizacao()
{
    if (!icode_eh_producao()) {
        // Remove agendamento que tenha ficado de antes (ex.: banco copiado da produção)
        if (wp_next_scheduled('icode_sincronizar_usuarios_cron')) {
            wp_clear_scheduled_hook('icode_sincronizar_usuarios_cron');
        }
        return;
    }

    if (!wp_next_scheduled('icode_sincronizar_usuarios_cron')) {
        wp_schedule_event(strtotime('tomorrow 03:00'), 'daily', 'icode_sincronizar_usuarios_cron');
    }
}
add_action('init', 'icode_agendar_sincronizacao');
