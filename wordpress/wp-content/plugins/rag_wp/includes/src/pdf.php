<?php
// Arquivo: wp-content/plugins/rag_wp/includes/src/pdf.php
// Extração de texto e indexação dos PDFs anexados aos posts (tabela {prefix}pdf_index)

if (!defined('ABSPATH')) {
    exit;
}

// Extrair texto do PDF ***********************************************************************
function rag_wp_extrair_texto_pdf($arquivo)
{
    // 1) Smalot PdfParser (PHP puro)
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $texto = $parser->parseFile($arquivo)->getText();
        $texto = trim(preg_replace('/\s+/u', ' ', $texto));
        if ($texto !== '') {
            return $texto;
        }
    } catch (\Throwable $e) {
        error_log('rag_wp: Smalot falhou em ' . $arquivo . ' - ' . $e->getMessage());
    }

    // 2) pdftotext (poppler-utils, instalado pelo docker-compose)
    if (function_exists('shell_exec') && is_executable('/usr/bin/pdftotext')) {
        $texto = shell_exec('/usr/bin/pdftotext -q -enc UTF-8 ' . escapeshellarg($arquivo) . ' - 2>/dev/null');
        $texto = trim(preg_replace('/\s+/u', ' ', (string) $texto));
        if ($texto !== '') {
            return $texto;
        }
    }

    return '';
}


// Lista os PDFs de um post (recursivo) *******************************************************
function rag_wp_listar_pdfs_post($post_id)
{
    $base = rag_wp_attachments_dir($post_id);
    if (!is_dir($base)) {
        return [];
    }

    $pdfs = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $arquivo) {
        // Ignora a lixeira/temporários do elFinder (pastas que começam com ".")
        if (strpos($arquivo->getPathname(), '/.') !== false) {
            continue;
        }
        if (strtolower($arquivo->getExtension()) === 'pdf') {
            $pdfs[] = $arquivo->getPathname();
        }
    }
    sort($pdfs);
    return $pdfs;
}


// Remove do índice os PDFs de um post que não existem mais no disco ****************************
function rag_wp_limpar_indice_post($post_id)
{
    global $wpdb;
    $tabela = $wpdb->prefix . 'pdf_index';

    $arquivos = $wpdb->get_col($wpdb->prepare("SELECT arquivo FROM $tabela WHERE id_post = %d", $post_id));
    $removidos = 0;
    foreach ($arquivos as $arquivo) {
        if (!file_exists($arquivo)) {
            $removidos += (int) $wpdb->delete($tabela, ['arquivo' => $arquivo]);
        }
    }
    return $removidos;
}


// Indexa um PDF (caminho absoluto, já validado) *******************************************
// Retorna ['ok' => bool, 'msg' => string]
function rag_wp_indexar_arquivo($post_id, $arquivo)
{
    global $wpdb;
    $tabela = $wpdb->prefix . 'pdf_index';

    $existe = $wpdb->get_var($wpdb->prepare("SELECT id FROM $tabela WHERE arquivo = %s", $arquivo));
    if ($existe) {
        return ['ok' => true, 'msg' => 'Já indexado'];
    }

    set_time_limit(300);
    $texto = rag_wp_extrair_texto_pdf($arquivo);
    if ($texto === '') {
        return ['ok' => false, 'msg' => 'Não foi possível extrair texto (PDF escaneado?)'];
    }

    $relativo = substr($arquivo, strlen(rag_wp_attachments_dir($post_id)));
    $url = rag_wp_attachments_url($post_id) . implode('/', array_map('rawurlencode', explode('/', $relativo)));

    $ok = $wpdb->insert($tabela, [
        'id_post' => $post_id,
        'arquivo' => $arquivo,
        'url' => $url,
        'texto' => $texto,
        'indexado_em' => current_time('mysql'),
    ], ['%d', '%s', '%s', '%s', '%s']);

    if (!$ok) {
        return ['ok' => false, 'msg' => 'Erro no banco: ' . $wpdb->last_error];
    }
    return ['ok' => true, 'msg' => 'Indexado (' . number_format_i18n(mb_strlen($texto)) . ' caracteres)'];
}


// Resolve um caminho relativo enviado pelo navegador, sem deixar sair da pasta do post ************
function rag_wp_resolver_arquivo($post_id, $relativo)
{
    $base = realpath(rag_wp_attachments_dir($post_id));
    $arquivo = realpath($base . '/' . ltrim($relativo, '/'));

    if (!$base || !$arquivo || strpos($arquivo, $base . '/') !== 0 || !is_file($arquivo)) {
        return false;
    }
    if (strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)) !== 'pdf') {
        return false;
    }
    return $arquivo;
}


// AJAX: indexar um PDF selecionado no elFinder **********************************************
function rag_wp_ajax_indexar_pdf()
{
    check_ajax_referer('rag_wp_indexar', 'nonce');

    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['msg' => 'Sem permissão']);
    }

    rag_wp_limpar_indice_post($post_id);

    $arquivo = rag_wp_resolver_arquivo($post_id, wp_unslash($_POST['arquivo'] ?? ''));
    if (!$arquivo) {
        wp_send_json_error(['msg' => 'Arquivo inválido ou não encontrado']);
    }

    $r = rag_wp_indexar_arquivo($post_id, $arquivo);
    $r['ok'] ? wp_send_json_success(['msg' => $r['msg']]) : wp_send_json_error(['msg' => $r['msg']]);
}
add_action('wp_ajax_rag_wp_indexar_pdf', 'rag_wp_ajax_indexar_pdf');


// AJAX: lista de todos os PDFs anexados (página PDFParser) ************************************
function rag_wp_ajax_listar_pdfs()
{
    check_ajax_referer('rag_wp_indexar', 'nonce');
    if (!current_user_can('edit_others_posts')) {
        wp_send_json_error(['msg' => 'Sem permissão']);
    }

    $itens = [];
    foreach (glob(rag_wp_attachments_dir() . '/*', GLOB_ONLYDIR) as $dir) {
        $post_id = intval(basename($dir));
        if (!$post_id) {
            continue;
        }
        rag_wp_limpar_indice_post($post_id);
        foreach (rag_wp_listar_pdfs_post($post_id) as $arquivo) {
            $itens[] = [
                'post_id' => $post_id,
                'arquivo' => substr($arquivo, strlen($dir) + 1),
            ];
        }
    }

    wp_send_json_success(['itens' => $itens]);
}
add_action('wp_ajax_rag_wp_listar_pdfs', 'rag_wp_ajax_listar_pdfs');


// Ao excluir o post definitivamente, apaga o índice (os arquivos ficam no disco) ****************
function rag_wp_excluir_indice_post($post_id)
{
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'pdf_index', ['id_post' => $post_id], ['%d']);
}
add_action('before_delete_post', 'rag_wp_excluir_indice_post');
