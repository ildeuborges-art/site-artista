<?php
require __DIR__ . '/inc/funcoes.php';

$titulo_pagina = 'Início';
$pagina_atual = 'inicio';

// Imagem em destaque: imagens/destaque.jpg, se existir (fixa, sem troca automática);
// senão, as obras da primeira série em ordem sorteada, trocando a cada poucos segundos.
$obras_destaque = array();
$arquivo_destaque = imagem_opcional('destaque');
if ($arquivo_destaque) {
    $obras_destaque[] = array(
        'url' => url_caminho($arquivo_destaque), 'titulo' => '', 'ano' => '',
        'serie' => '', 'link' => 'obras.php',
    );
} else {
    // Só a primeira série (a pasta com o menor número em obras/).
    // Para usar todas as séries, troque array_slice(listar_series(), 0, 1) por listar_series().
    foreach (array_slice(listar_series(), 0, 1) as $s) {
        $legendas = ler_legendas($s['pasta']);
        foreach (listar_imagens($s['pasta']) as $arquivo) {
            $obra = dados_obra($s['pasta'], $arquivo, $legendas);
            $obras_destaque[] = array(
                'url'    => $obra['url'],
                'titulo' => $obra['titulo'],
                'ano'    => $obra['ano'],
                'serie'  => $s['nome'],
                'link'   => 'obras.php?serie=' . rawurlencode($s['pasta']),
            );
        }
    }
    shuffle($obras_destaque);
}
$destaque = $obras_destaque ? $obras_destaque[0] : null;

require __DIR__ . '/inc/topo.php';
?>
<h1 class="oculto"><?php echo e($config['nome']) ?></h1>

<?php if ($destaque): ?>
  <div class="destaque-area" aria-roledescription="carrossel" aria-label="Obras em destaque">
    <a class="destaque" href="<?php echo e($destaque['link']) ?>">
      <img src="<?php echo $destaque['url'] ?>" alt="<?php echo e($destaque['titulo'] ?: 'Obra em destaque') ?>">
    </a>
    <div class="destaque-legenda">
      <span aria-live="polite">
        <?php if ($destaque['titulo']): ?>
          <em class="destaque-titulo"><?php echo e($destaque['titulo']) ?></em><span class="destaque-ano"><?php echo $destaque['ano'] ? ', ' . e($destaque['ano']) : '' ?></span>
          <span class="secundario destaque-serie">— série <?php echo e($destaque['serie']) ?></span>
        <?php else: ?>
          <?php echo e($config['linguagens']) ?>
        <?php endif; ?>
      </span>
      <span class="destaque-acoes">
        <?php if (count($obras_destaque) > 1): ?>
          <button type="button" class="destaque-pausa" hidden>Pausar</button>
        <?php endif; ?>
        <a class="destaque-link" href="<?php echo e($destaque['link']) ?>">Ver obras</a>
      </span>
    </div>
  </div>

  <?php if (count($obras_destaque) > 1): ?>
    <script type="application/json" id="obras-destaque"><?php echo json_encode($obras_destaque, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script src="assets/destaque.js" defer></script>
  <?php endif; ?>
<?php else: ?>
  <p class="aviso">Adicione imagens dentro de uma pasta em <code>obras/</code> para que a obra em destaque apareça aqui.</p>
<?php endif; ?>

<?php require __DIR__ . '/inc/rodape.php'; ?>
