<?php
require __DIR__ . '/inc/funcoes.php';

$titulo_pagina = 'Obras';
$pagina_atual = 'obras';

$series = listar_series();

// Série escolhida no sub-menu. Só aceita nomes de pastas que existem;
// sem escolha (ou com um nome inválido), abre a primeira série.
$indice = 0;
if (isset($_GET['serie'])) {
    foreach ($series as $i => $s) {
        if ($s['pasta'] === $_GET['serie']) {
            $indice = $i;
            break;
        }
    }
}
$serie = $series ? $series[$indice] : null;

if ($serie) {
    $titulo_pagina = $serie['nome'] . ' — Obras';
    $legendas = ler_legendas($serie['pasta']);
    $obras = array();
    foreach (listar_imagens($serie['pasta']) as $arquivo) {
        $obras[] = dados_obra($serie['pasta'], $arquivo, $legendas);
    }
    $descricao = ler_descricao($serie['pasta']);

    // SEO: cada série tem um endereço próprio, com descrição e imagem
    $caminho_canonico = 'obras.php?serie=' . rawurlencode($serie['pasta']);
    $descricao_pagina = $descricao !== ''
        ? $descricao
        : 'Série ' . $serie['nome'] . ', de ' . $config['nome'] . ': ' . count($obras) . ' obras.';
    $imagem_pagina = $obras ? $obras[0]['url'] : '';

    $itens = array();
    foreach ($obras as $n => $obra) {
        $item = array(
            '@type'   => 'VisualArtwork',
            'name'    => $obra['titulo'],
            'image'   => url_absoluta($obra['url']),
            'creator' => array('@id' => url_base() . '#artista'),
            'artform' => 'Pintura',
        );
        if (preg_match('/^\d{4}$/', $obra['ano'])) {
            $item['dateCreated'] = $obra['ano'];
        }
        if ($obra['tecnica'] !== '') {
            $item['artMedium'] = $obra['tecnica'];
        }
        if ($obra['largura_cm']) {
            $item['width']  = array('@type' => 'Distance', 'name' => str_replace('.', ',', $obra['largura_cm']) . ' cm');
            $item['height'] = array('@type' => 'Distance', 'name' => str_replace('.', ',', $obra['altura_cm']) . ' cm');
        }
        $itens[] = array('@type' => 'ListItem', 'position' => $n + 1, 'item' => $item);
    }
    $artista = dados_artista();
    $dados_estruturados = array(
        array(
            '@type'      => 'CollectionPage',
            'name'       => $serie['nome'],
            'url'        => url_absoluta($caminho_canonico),
            'inLanguage' => 'pt-BR',
            'description' => resumo($descricao_pagina, 300),
            'author'     => array('@id' => $artista['@id']),
            'mainEntity' => array('@type' => 'ItemList', 'numberOfItems' => count($itens), 'itemListElement' => $itens),
        ),
        $artista,
    );
} else {
    $caminho_canonico = 'obras.php';
}

require __DIR__ . '/inc/topo.php';
?>
<h1 class="titulo">Obras</h1>

<?php if (!$series): ?>
  <p class="aviso">Nenhuma série encontrada. Crie uma pasta dentro de <code>obras/</code> com o nome da série e coloque as imagens nela.</p>
<?php else: ?>

  <nav aria-label="Séries" class="submenu">
    <?php foreach ($series as $i => $s): ?>
      <a href="obras.php?serie=<?php echo rawurlencode($s['pasta']) ?>"<?php echo $i === $indice ? ' aria-current="page"' : '' ?>><?php echo e($s['nome']) ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($descricao !== ''): ?>
    <div class="serie-intro">
      <h2><?php echo e($serie['nome']) ?></h2>
      <?php foreach (preg_split('/(?:\r\n|\r|\n){2,}/', $descricao) as $paragrafo): ?>
        <p><?php echo nl2br(e($paragrafo)) ?></p>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <h2 class="oculto"><?php echo e($serie['nome']) ?></h2>
  <?php endif; ?>

  <?php if (!$obras): ?>
    <p class="aviso">Esta série ainda não tem imagens.</p>
  <?php else: ?>
    <div class="obras">
      <?php foreach ($obras as $n => $obra): ?>
        <figure class="obra">
          <a href="<?php echo $obra['url'] ?>" target="_blank" rel="noopener" class="abrir-visor"
             <?php echo $obra['largura_cm'] ? 'data-largura-cm="' . $obra['largura_cm'] . '" data-altura-cm="' . $obra['altura_cm'] . '"' : '' ?>>
            <img src="<?php echo $obra['url'] ?>"
                 alt="<?php echo e($obra['titulo']) ?>"
                 <?php echo $obra['largura'] ? 'width="' . $obra['largura'] . '" height="' . $obra['altura'] . '"' : '' ?>
                 <?php echo $n > 0 ? 'loading="lazy"' : '' ?>
                 decoding="async">
          </a>
          <figcaption>
            <span><em><?php echo e($obra['titulo']) ?></em><?php echo $obra['ano'] ? ', ' . e($obra['ano']) : '' ?></span>
            <?php if ($obra['tecnica']): ?><span><?php echo e($obra['tecnica']) ?></span><?php endif; ?>
            <?php if ($obra['medidas']): ?><span><?php echo e($obra['medidas']) ?></span><?php endif; ?>
            <?php if ($obra['colecao']): ?><span class="secundario"><?php echo e($obra['colecao']) ?></span><?php endif; ?>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>

    <dialog class="visor" aria-label="Visualização da obra">
      <div class="visor-barra">
        <button type="button" class="visor-anterior" aria-label="Obra anterior">←</button>
        <span class="visor-contador"></span>
        <button type="button" class="visor-proxima" aria-label="Próxima obra">→</button>
        <button type="button" class="visor-fechar" aria-label="Fechar" autofocus>Fechar ✕</button>
      </div>
      <figure class="visor-obra">
        <img class="visor-imagem" alt="">
        <figcaption class="visor-legenda"></figcaption>
      </figure>
      <section class="visor-parede" aria-label="A obra em escala, numa parede">
        <p class="rotulo">Na parede</p>
        <div class="visor-cena"></div>
        <p class="visor-nota">Simulação em escala aproximada, ao lado de uma pessoa de 1,70 m.</p>
      </section>
    </dialog>
    <script src="assets/visor.js" defer></script>
  <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/inc/rodape.php'; ?>
