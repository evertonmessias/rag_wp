<?php
// Arquivo: wp-content/plugins/rag_wp/includes/src/exibir.php
// Exibição dos anexos no site:
//   - automática, abaixo do conteúdo, quando "Exibir anexos no post?" está marcado
//   - ou pelo shortcode [rag_wp_anexos], no ponto escolhido do conteúdo
// Os dois só mostram os anexos do próprio post, e só na página desse post.

if (!defined('ABSPATH')) {
    exit;
}

define('RAG_WP_META_EXIBIR', '_rag_wp_exibir_anexos');


// Estamos na página do próprio post? (evita listagens, widgets, feed e outros posts)
function rag_wp_no_proprio_post($post_id)
{
    return $post_id
        && is_singular(rag_wp_post_types())
        && (int) get_queried_object_id() === (int) $post_id;
}


// Agrupa os PDFs do post por pasta: '' = raiz, 'pautas' = subpasta...
function rag_wp_anexos_agrupados($post_id)
{
    $base = rag_wp_attachments_dir($post_id);
    $grupos = [];

    foreach (rag_wp_listar_pdfs_post($post_id) as $arquivo) {
        $relativo = substr($arquivo, strlen($base) + 1);
        $partes = explode('/', $relativo);
        $pasta = count($partes) > 1 ? $partes[0] : '';
        $grupos[$pasta][] = [
            'nome' => count($partes) > 1 ? implode('/', array_slice($partes, 1)) : $relativo,
            'url' => rag_wp_url_arquivo($post_id, $arquivo),
            'tamanho' => size_format(filesize($arquivo), 2),
        ];
    }

    // Raiz primeiro, depois as subpastas em ordem alfabética
    uksort($grupos, function ($a, $b) {
        if ($a === '' || $b === '') {
            return $a === '' ? -1 : 1;
        }
        return strnatcasecmp($a, $b);
    });
    foreach ($grupos as &$itens) {
        usort($itens, function ($a, $b) {
            return strnatcasecmp($a['nome'], $b['nome']);
        });
    }
    return $grupos;
}


// Nome de arquivo exatamente como está no disco: o tema pode passar a página pelo wptexturize,
// que troca " - " por travessão e "..." por reticências. Hífen e ponto como entidade escapam disso.
function rag_wp_nome_literal($nome)
{
    return strtr(esc_html($nome), ['-' => '&#45;', '.' => '&#46;']);
}


// HTML da lista de anexos
function rag_wp_html_anexos($post_id, $pasta = null)
{
    $grupos = rag_wp_anexos_agrupados($post_id);
    if ($pasta !== null) {
        $grupos = array_intersect_key($grupos, [$pasta => true]);
    }
    if (!$grupos) {
        return '';
    }

    ob_start();
    ?>
    <section class="rag-wp-anexos">
        <h2 class="rag-wp-anexos-titulo">Anexos</h2>
        <?php foreach ($grupos as $nome_pasta => $itens) : ?>
            <div class="rag-wp-anexos-grupo">
                <?php if ($nome_pasta !== '') : ?>
                    <h3><?php echo esc_html(ucfirst(str_replace(['-', '_'], ' ', $nome_pasta))); ?></h3>
                <?php endif; ?>
                <table>
                    <thead>
                        <tr><th>Arquivo</th><th class="rag-wp-tamanho">Tamanho</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item) : ?>
                            <tr>
                                <td><a href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener"><?php echo rag_wp_nome_literal($item['nome']); ?></a></td>
                                <td class="rag-wp-tamanho"><?php echo esc_html($item['tamanho']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </section>
    <?php
    return ob_get_clean();
}


// Shortcode [rag_wp_anexos id="123"] ou [rag_wp_anexos id="123" pasta="Pautas"] ***********************
// Só funciona no post cujo id está no shortcode: copiado para outro post, não exibe nada.
function rag_wp_shortcode_anexos($atts)
{
    $atts = shortcode_atts(['id' => 0, 'pasta' => null], $atts, 'rag_wp_anexos');

    $post = get_post();
    if (!$post || (int) $atts['id'] !== (int) $post->ID) {
        return '';
    }
    if (!rag_wp_no_proprio_post($post->ID) || !has_shortcode($post->post_content, 'rag_wp_anexos')) {
        return '';
    }

    // "pasta" só é comparada com as pastas existentes do post (não acessa o disco com ela)
    return rag_wp_html_anexos($post->ID, $atts['pasta'] !== null ? trim($atts['pasta'], " /") : null);
}
add_shortcode('rag_wp_anexos', 'rag_wp_shortcode_anexos');


// Exibição automática abaixo do conteúdo ***********************************************************
// Prioridade 20: roda depois dos shortcodes (11). Se o post usa o shortcode, não duplica.
function rag_wp_conteudo_com_anexos($content)
{
    $post = get_post();
    if (!$post || !rag_wp_no_proprio_post($post->ID)) {
        return $content;
    }
    if (!get_post_meta($post->ID, RAG_WP_META_EXIBIR, true) || has_shortcode($post->post_content, 'rag_wp_anexos')) {
        return $content;
    }
    return $content . rag_wp_html_anexos($post->ID);
}
add_filter('the_content', 'rag_wp_conteudo_com_anexos', 20);


// CSS do site (só na página de um post) ******************************************************
add_action('wp_enqueue_scripts', function () {
    if (is_singular(rag_wp_post_types())) {
        $css = 'assets/css/rag_wp_front.css';
        wp_enqueue_style('rag_wp_front', RAG_WP_URL . $css, [], filemtime(RAG_WP_DIR . $css));
    }
});
