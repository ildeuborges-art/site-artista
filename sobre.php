<?php
require __DIR__ . '/inc/funcoes.php';

$titulo_pagina = 'Sobre';
$pagina_atual = 'sobre';

$retrato = imagem_opcional('retrato');           // imagens/retrato.jpg (opcional)
$tem_cv = is_file(__DIR__ . '/cv.pdf');           // cv.pdf na pasta principal (opcional)

require __DIR__ . '/inc/topo.php';
?>
<div class="sobre">
  <div class="sobre-foto">
    <?php if ($retrato): ?>
      <img src="<?= url_caminho($retrato) ?>" alt="Retrato de <?= e($config['nome']) ?>">
    <?php else: ?>
      <div class="espaco-foto">Coloque imagens/retrato.jpg</div>
    <?php endif; ?>
  </div>

  <div class="sobre-texto">
    <h1 class="titulo">Sobre</h1>

    <!-- Edite os parágrafos abaixo -->
  <p>Nascido em Brasília, em 1971, Ildeu Borges cresceu acompanhando os primeiros anos da capital federal. As grandes avenidas, os terrenos de terra avermelhada, a arquitetura moderna e os amplos espaços urbanos fizeram parte de sua experiência desde a infância. Essas referências seriam retomadas anos depois em sua pintura, especialmente na investigação das relações entre a figura humana, a arquitetura e a paisagem urbana.</p>

  <p>Seu interesse pelo desenho começou na infância, enquanto o contato com as artes visuais ganhou maior importância durante a adolescência, quando viveu em Caracas, na Venezuela. Nesse período, conheceu a produção de Jesús Rafael Soto e Carlos Cruz-Diez, artistas fundamentais da arte cinética, cujas investigações sobre cor, percepção e organização geométrica despertaram seu interesse pelas possibilidades da linguagem visual.</p>

  <p>De volta ao Brasil, em 1990, iniciou suas experiências com pintura a óleo, dedicando-se inicialmente a paisagens e retratos familiares. Paralelamente à graduação em Engenharia Elétrica pela Universidade de Brasília, concluída em 1996, frequentou o ateliê de Milton Ribeiro, artista formado pela Escola de Belas Artes do Rio de Janeiro que estudou com André Lhote, em Paris. Essa convivência contribuiu para sua formação em desenho, composição e teoria da cor, consolidando uma relação com a tradição pictórica e o conhecimento técnico da pintura.</p>

  <p>A partir de 2009, intensificou sua produção artística e passou a desenvolver uma pesquisa voltada à representação da vida urbana, tendo Brasília como uma de suas principais referências. A arquitetura modernista, os espaços públicos e as relações cotidianas entre as pessoas passaram a integrar composições nas quais a distribuição dos elementos, a escala e a incidência da luz recebem atenção particular.</p>

  <p>Sua pesquisa artística concentra-se na relação entre figura e espaço, considerando a proporção como um dos principais problemas da composição. Interessa-lhe compreender como a figura humana participa da organização visual, como sua escala modifica a percepção da arquitetura e de que maneira a luz, a cor e os limites do suporte interferem na construção da imagem. A luminosidade característica de Brasília, associada à altitude e ao clima seco, constitui uma referência importante para suas investigações pictóricas, especialmente no tratamento das superfícies, dos contrastes e das relações espaciais.</p>

  <p>O estudo dos procedimentos técnicos também integra sua pesquisa. Borges trabalha principalmente com óleo sobre tela, acrílica e aquarela, além de explorar o pastel, diferentes suportes e experiências relacionadas à abstração geométrica e à Op Art. O interesse pela história da pintura, pela composição e pelas possibilidades de cada material acompanha o desenvolvimento de sua produção, estabelecendo relações entre a observação do cotidiano e o conhecimento adquirido ao longo de sua formação.</p>

    <?php if (!empty($config['galerias'])): ?>
      <div class="bloco">
        <h2 class="rotulo">Representação</h2>
        <?php foreach ($config['galerias'] as $g): ?>
          <span><?= e($g['nome']) ?>, <?= e($g['cidade']) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($tem_cv): ?>
      <a class="link" href="cv.pdf" target="_blank" rel="noopener">Baixar CV completo (PDF)</a>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/inc/rodape.php'; ?>
