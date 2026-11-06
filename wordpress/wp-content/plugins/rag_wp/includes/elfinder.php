<?php
// Arquivo: wp-content/plugins/rag_wp/includes/elfinder.php
// elFinder do Gerenciador de Anexos. Tudo passa pelo admin-ajax.php (exige login):
//   ?action=rag_wp_arquivos&post_id=ID   -> página do elFinder (carregada no iframe do meta box)
//   ?action=rag_wp_elfinder&post_id=ID   -> connector PHP do elFinder
// Raiz única: /wp-content/uploads/attachments/{ID}/

if (!defined('ABSPATH')) {
    exit;
}

// Valida o post e a permissão; retorna o ID ou encerra
function rag_wp_elfinder_post_id()
{
    $post_id = intval($_GET['post_id'] ?? 0);

    if (!$post_id || !get_post($post_id)) {
        wp_die('Post inválido.', '', ['response' => 400]);
    }
    if (!current_user_can('edit_post', $post_id)) {
        wp_die('Acesso negado.', '', ['response' => 403]);
    }
    if (!is_dir(rag_wp_attachments_dir($post_id))) {
        wp_die('Salve o post para criar a pasta de anexos.', '', ['response' => 404]);
    }
    return $post_id;
}


// Connector ***************************************************************************************
function rag_wp_elfinder_connector()
{
    $post_id = rag_wp_elfinder_post_id();

    require_once RAG_WP_DIR . 'includes/elFinder/php/autoload.php';

    $opts = [
        'roots' => [[
            'driver' => 'LocalFileSystem',
            'alias' => 'Anexos',
            'path' => rag_wp_attachments_dir($post_id) . '/',
            'URL' => rag_wp_attachments_url($post_id) . '/',
            'mimeDetect' => 'internal',
            'uploadDeny' => ['all'],
            'uploadAllow' => ['application/pdf'],
            'uploadOrder' => ['deny', 'allow'],
            'disabled' => ['mkfile', 'edit', 'archive', 'extract', 'resize', 'netmount'],
            'attributes' => [[
                // Esconde arquivos ocultos e qualquer .php
                'pattern' => '/(^|\/)\.|\.php$/i',
                'read' => false,
                'write' => false,
                'hidden' => true,
                'locked' => true,
            ]],
        ]],
    ];

    $connector = new elFinderConnector(new elFinder($opts));
    $connector->run(); // envia a resposta e encerra
}
add_action('wp_ajax_rag_wp_elfinder', 'rag_wp_elfinder_connector');


// Página do elFinder (iframe) *************************************************************************
function rag_wp_elfinder_pagina()
{
    $post_id = rag_wp_elfinder_post_id();
    $elfinder = RAG_WP_URL . 'includes/elFinder/';

    $config = [
        'connector' => add_query_arg(['action' => 'rag_wp_elfinder', 'post_id' => $post_id], admin_url('admin-ajax.php')),
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rag_wp_indexar'),
        'postId' => $post_id,
    ];

    nocache_headers();
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Anexos do post <?php echo $post_id; ?></title>

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="<?php echo esc_url($elfinder . 'css/elfinder.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo esc_url($elfinder . 'css/theme.css'); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(RAG_WP_URL . 'assets/css/rag_wp.css'); ?>">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="<?php echo esc_url($elfinder . 'js/elfinder.min.js'); ?>"></script>
    <script src="<?php echo esc_url($elfinder . 'js/i18n/elfinder.pt_BR.js'); ?>"></script>
</head>
<body class="rag-wp-frame">
    <div id="elfinder"></div>

    <!-- Progresso da indexação -->
    <div id="rag-wp-index" class="rag-wp-index" hidden>
        <div class="rag-wp-index-box">
            <div class="rag-wp-index-head">
                <strong>Indexando PDFs</strong>
                <button type="button" id="rag-wp-index-fechar" title="Fechar">&times;</button>
            </div>
            <div class="rag-wp-barra"><div id="rag-wp-barra"></div></div>
            <ul id="rag-wp-index-status"></ul>
        </div>
    </div>

    <script>
        window.RAG_WP = <?php echo wp_json_encode($config); ?>;
    </script>
    <script src="<?php echo esc_url(RAG_WP_URL . 'assets/js/rag_wp_elfinder.js'); ?>"></script>
</body>
</html>
    <?php
    exit;
}
add_action('wp_ajax_rag_wp_arquivos', 'rag_wp_elfinder_pagina');
