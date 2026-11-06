<?php
// Arquivo: wp-content/plugins/rag_wp/includes/pages/pdfparser.php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabela = $wpdb->prefix . 'pdf_index';

$total_pdfs = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabela");
$total_posts = (int) $wpdb->get_var("SELECT COUNT(DISTINCT id_post) FROM $tabela");
$total_chars = (int) $wpdb->get_var("SELECT COALESCE(SUM(CHAR_LENGTH(texto)), 0) FROM $tabela");

$indexados = $wpdb->get_results(
    "SELECT id_post, arquivo, url, CHAR_LENGTH(texto) AS caracteres, indexado_em
     FROM $tabela ORDER BY indexado_em DESC LIMIT 200"
);
$base = rag_wp_attachments_dir();
?>
<div class="wrap rag-wp-page">
    <h1>PDFParser</h1>

    <div class="rag-wp-cards">
        <div class="card"><span>PDFs indexados</span><strong><?php echo number_format_i18n($total_pdfs); ?></strong></div>
        <div class="card"><span>Posts com PDFs</span><strong><?php echo number_format_i18n($total_posts); ?></strong></div>
        <div class="card"><span>Caracteres extraídos</span><strong><?php echo number_format_i18n($total_chars); ?></strong></div>
    </div>

    <div class="card rag-wp-card-wide">
        <h2>Indexar todos os PDFs</h2>
        <p>Percorre <code>wp-content/uploads/<?php echo esc_html(RAG_WP_ATTACHMENTS); ?>/</code>, indexa os PDFs que ainda
            não estão no banco e remove do índice os que foram apagados. Os já indexados são pulados.</p>
        <p>
            <button type="button" class="button button-primary" id="rag-wp-indexar-todos"
                data-nonce="<?php echo esc_attr(wp_create_nonce('rag_wp_indexar')); ?>">Indexar todos</button>
        </p>
        <div id="rag-wp-progresso" hidden>
            <div class="rag-wp-barra"><div id="rag-wp-barra"></div></div>
            <p id="rag-wp-resumo"></p>
            <ul id="rag-wp-index-status" class="rag-wp-log"></ul>
        </div>
    </div>

    <div class="card rag-wp-card-wide">
        <h2>Últimos PDFs indexados</h2>
        <?php if (!$indexados) : ?>
            <p>Nenhum PDF indexado ainda.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Arquivo</th>
                        <th>Caracteres</th>
                        <th>Indexado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($indexados as $item) :
                        $titulo = get_the_title($item->id_post) ?: '(sem título)';
                        $nome = substr($item->arquivo, strlen($base . '/' . $item->id_post . '/'));
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($item->id_post)); ?>"><?php echo esc_html($titulo); ?></a>
                                <small>#<?php echo (int) $item->id_post; ?></small>
                            </td>
                            <td><a href="<?php echo esc_url($item->url); ?>" target="_blank"><?php echo esc_html($nome); ?></a></td>
                            <td><?php echo number_format_i18n($item->caracteres); ?></td>
                            <td><?php echo esc_html(mysql2date('d/m/Y H:i', $item->indexado_em)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
