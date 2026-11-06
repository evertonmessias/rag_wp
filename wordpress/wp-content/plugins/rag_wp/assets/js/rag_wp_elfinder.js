// Arquivo: wp-content/plugins/rag_wp/assets/js/rag_wp_elfinder.js
// elFinder do Gerenciador de Anexos + comando "Indexar PDFs"
(function ($) {
    'use strict';

    var cfg = window.RAG_WP;
    var fm;

    function ehPdf(file) {
        return file && file.mime !== 'directory' &&
            (file.mime === 'application/pdf' || /\.pdf$/i.test(file.name));
    }

    // Caminho relativo à pasta do post: "Anexos/sub/arquivo.pdf" -> "sub/arquivo.pdf"
    function caminhoRelativo(file) {
        return fm.path(file.hash).split('/').slice(1).join('/');
    }

    // ------------------------------------------------------------ Indexação
    var painel = document.getElementById('rag-wp-index');
    var barra = document.getElementById('rag-wp-barra');
    var lista = document.getElementById('rag-wp-index-status');

    document.getElementById('rag-wp-index-fechar').addEventListener('click', function () {
        painel.hidden = true;
    });

    function linha(texto, classe) {
        var li = document.createElement('li');
        li.className = classe || '';
        li.textContent = texto;
        lista.appendChild(li);
        return li;
    }

    // Um PDF por vez: arquivos grandes não estouram o tempo do PHP em paralelo
    async function indexar(files) {
        painel.hidden = false;
        lista.innerHTML = '';
        barra.style.width = '0%';
        barra.className = 'ativo';

        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            var li = linha(f.name + ': processando...', 'andamento');
            try {
                var resp = await fetch(cfg.ajax, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: new URLSearchParams({
                        action: 'rag_wp_indexar_pdf',
                        nonce: cfg.nonce,
                        post_id: cfg.postId,
                        arquivo: caminhoRelativo(f)
                    })
                });
                var r = await resp.json();
                li.textContent = f.name + ': ' + (r.data && r.data.msg ? r.data.msg : resp.statusText);
                li.className = r.success ? 'ok' : 'erro';
            } catch (e) {
                li.textContent = f.name + ': erro - ' + e.message;
                li.className = 'erro';
            }
            barra.style.width = Math.round(((i + 1) / files.length) * 100) + '%';
        }
        barra.className = 'fim';
    }

    elFinder.prototype.commands.indexarpdf = function () {
        this.title = 'Indexar PDFs';
        this.updateOnSelect = true;

        this.getstate = function (sel) {
            var files = this.files(sel);
            return files.length && files.every(ehPdf) ? 0 : -1;
        };

        this.exec = function (hashes) {
            indexar(this.files(hashes).filter(ehPdf));
            return $.Deferred().resolve();
        };
    };
    elFinder.prototype.i18.pt_BR.messages.cmdindexarpdf = 'Indexar PDFs';

    // ------------------------------------------------------------ elFinder
    $(function () {
        fm = $('#elfinder').elfinder({
            lang: 'pt_BR',
            url: cfg.connector,
            resizable: false,
            height: '100%',
            rememberLastDir: false,
            uiOptions: {
                toolbar: [
                    ['back', 'forward', 'reload'],
                    ['mkdir', 'upload', 'download'],
                    ['rm', 'rename'],
                    ['view', 'sort'],
                    ['indexarpdf']
                ]
            },
            contextmenu: {
                navbar: ['open', '|', 'rename', 'rm', '|', 'info'],
                cwd: ['reload', 'back', '|', 'upload', 'mkdir', '|', 'view', 'sort', '|', 'info'],
                files: ['open', 'quicklook', 'download', '|', 'indexarpdf', '|', 'copy', 'cut', 'paste', '|', 'rename', 'rm', '|', 'info']
            },
            // Duplo clique abre o PDF numa nova aba
            getFileCallback: function (file) {
                window.open(file.url, '_blank');
            }
        }).elfinder('instance');
    });
})(jQuery);
