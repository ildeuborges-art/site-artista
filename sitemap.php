<?php
// Mapa do site para o Google (sitemap). Gerado automaticamente: lista as páginas,
// todas as séries e as imagens de cada obra. Não é preciso editar.
// Envie o endereço deste arquivo no Google Search Console (ex.: https://seusite/sitemap.php).
require __DIR__ . '/inc/funcoes.php';

/** Data de modificação mais recente entre os arquivos, no formato do sitemap. */
function data_sitemap($arquivos)
{
    $maior = 0;
    foreach ($arquivos as $arquivo) {
        if (is_file($arquivo)) {
            $maior = max($maior, filemtime($arquivo));
        }
    }
    return $maior ? date('Y-m-d', $maior) : '';
}

$paginas = array(
    array('caminho' => '',            'data' => data_sitemap(array(__DIR__ . '/index.php', __DIR__ . '/config.php')), 'imagens' => array()),
    array('caminho' => 'sobre.php',   'data' => data_sitemap(array(__DIR__ . '/sobre.php')),   'imagens' => array()),
    array('caminho' => 'contato.php', 'data' => data_sitemap(array(__DIR__ . '/contato.php')), 'imagens' => array()),
);

$retrato = imagem_opcional('retrato');
if ($retrato) {
    $paginas[1]['imagens'][] = array('url' => url_caminho($retrato), 'titulo' => 'Retrato de ' . $config['nome']);
}

foreach (listar_series() as $serie) {
    $pasta = PASTA_OBRAS . '/' . $serie['pasta'];
    $legendas = ler_legendas($serie['pasta']);
    $imagens = array();
    $arquivos = array($pasta . '/legendas.txt', $pasta . '/descricao.txt');
    foreach (listar_imagens($serie['pasta']) as $arquivo) {
        $obra = dados_obra($serie['pasta'], $arquivo, $legendas);
        $imagens[] = array('url' => $obra['url'], 'titulo' => $obra['titulo']);
        $arquivos[] = $pasta . '/' . $arquivo;
    }
    $paginas[] = array(
        'caminho' => 'obras.php?serie=' . rawurlencode($serie['pasta']),
        'data'    => data_sitemap($arquivos),
        'imagens' => $imagens,
    );
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($paginas as $p): ?>
  <url>
    <loc><?php echo e(url_absoluta($p['caminho'])) ?></loc>
<?php if ($p['data']): ?>
    <lastmod><?php echo $p['data'] ?></lastmod>
<?php endif; ?>
<?php foreach ($p['imagens'] as $img): ?>
    <image:image>
      <image:loc><?php echo e(url_absoluta($img['url'])) ?></image:loc>
    </image:image>
<?php endforeach; ?>
  </url>
<?php endforeach; ?>
</urlset>
