// Arquivo: wp-content/plugins/rag_wp/assets/js/rag_wp.js
// Página PDFParser: "Indexar todos"
jQuery(function ($) {
    'use strict';

    var $btn = $('#rag-wp-indexar-todos');
    if (!$btn.length) return;

    var nonce = $btn.data('nonce');
    var $barra = $('#rag-wp-barra');
    var $resumo = $('#rag-wp-resumo');
    var $lista = $('#rag-wp-index-status');

    function post(dados) {
        return $.post(ajaxurl, $.extend({ nonce: nonce }, dados));
    }

    $btn.on('click', async function () {
        $btn.prop('disabled', true);
        $('#rag-wp-progresso').prop('hidden', false);
        $lista.empty();
        $barra.css('width', '0%').attr('class', 'ativo');
        $resumo.text('Procurando PDFs...');

        var itens;
        try {
            var r = await post({ action: 'rag_wp_listar_pdfs' });
            if (!r.success) throw new Error(r.data.msg);
            itens = r.data.itens;
        } catch (e) {
            $resumo.text('Erro ao listar PDFs: ' + (e.message || e.statusText));
            $btn.prop('disabled', false);
            return;
        }

        var novos = 0, erros = 0;
        for (var i = 0; i < itens.length; i++) {
            var it = itens[i];
            var nome = '#' + it.post_id + ' / ' + it.arquivo;
            $resumo.text('Processando ' + (i + 1) + ' de ' + itens.length + '...');
            try {
                var res = await post({ action: 'rag_wp_indexar_pdf', post_id: it.post_id, arquivo: it.arquivo });
                if (!res.success) {
                    erros++;
                    $('<li class="erro">').text(nome + ': ' + res.data.msg).appendTo($lista);
                } else if (res.data.msg !== 'Já indexado') {
                    novos++;
                    $('<li class="ok">').text(nome + ': ' + res.data.msg).appendTo($lista);
                }
            } catch (e) {
                erros++;
                $('<li class="erro">').text(nome + ': erro na requisição').appendTo($lista);
            }
            $barra.css('width', Math.round(((i + 1) / itens.length) * 100) + '%');
        }

        $barra.css('width', '100%').attr('class', 'fim');
        $resumo.text('Concluído: ' + itens.length + ' PDF(s) encontrados, ' + novos + ' novo(s) indexado(s), ' + erros + ' erro(s). Atualizando a página em 4 segundos...');

        // Dá tempo de ler o resultado e recarrega para atualizar os totais e a tabela
        setTimeout(function () {
            window.location.reload();
        }, 4000);
    });
});
