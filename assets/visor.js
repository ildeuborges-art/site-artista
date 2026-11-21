// Visualizador das obras: ao clicar numa imagem, mostra a obra em destaque
// e, abaixo, uma simulação dela numa parede, em escala, ao lado de uma pessoa.
// As dimensões vêm da legenda (ex.: "60 x 90 cm"); sem elas, só a imagem aparece.
(function () {
  var visor = document.querySelector('.visor');
  if (!visor || typeof visor.showModal !== 'function') return; // navegador antigo: o link abre a imagem

  var links = Array.prototype.slice.call(document.querySelectorAll('.abrir-visor'));
  var imagem = visor.querySelector('.visor-imagem');
  var legenda = visor.querySelector('.visor-legenda');
  var parede = visor.querySelector('.visor-parede');
  var cena = visor.querySelector('.visor-cena');
  var contador = visor.querySelector('.visor-contador');
  var btnAnterior = visor.querySelector('.visor-anterior');
  var btnProxima = visor.querySelector('.visor-proxima');
  var atual = 0;

  // Medidas da cena, em centímetros
  var PESSOA_ALTURA = 170;
  var PESSOA_LARGURA = 60;
  var CENTRO_OBRA = 150;   // altura do centro da obra a partir do chão (padrão de galeria)
  var ALTURA_PAREDE = 200;  // parede visível acima do chão (cena enquadrada de perto)
  var ALTURA_PISO = 10;
  var MARGEM = 28;         // parede nas laterais
  var DISTANCIA = 35;      // entre a obra e a pessoa
  var SVG = 'http://www.w3.org/2000/svg';

  function el(nome, atributos, pai) {
    var no = document.createElementNS(SVG, nome);
    for (var k in atributos) no.setAttribute(k, atributos[k]);
    if (pai) pai.appendChild(no);
    return no;
  }

  // Silhueta de um homem de terno, braços ao longo do corpo, numa caixa de 60 × 170 cm (pés em y = 170)
  var SILHUETA = [
    // cabeça
    'M30.5 0C35.5-.3 38.6 3 38.5 8.5C38.8 10 38.4 11.5 37.9 12.5C37.5 16.5 35.4 20 31.4 21C27 21.5 24.1 18 23.4 13.5C22.5 12.5 22.4 10.8 22.9 9.5C22.5 3.5 25.5.2 30.5 0Z',
    // pescoço
    'M26 17L35 17L35.6 25.5L25.4 25.5Z',
    // tronco (paletó)
    'M12.5 30.5C17 28.6 22 26.4 26 24.4L35 24.4C39 26.4 44 28.6 48.5 30.5C50.6 31.8 51 34 50.6 37.5L48.2 52L45.3 63L46 91.5C40.5 93.5 35.5 93.6 30.8 92.8C26 93.6 20.5 93.5 15 91.5L15.8 63L13 52L10.4 37.5C10 34 10.4 31.8 12.5 30.5Z',
    // braços ao longo do corpo
    'M11.7 32.8C9.2 35.5 8.4 40 8.2 46L7.4 78C7 86 7.4 93 8.8 98C9.7 100.6 12.6 100.6 13 97.6L13.4 85L13.8 66L13.2 50Z',
    'M49.3 32.8C51.8 35.5 52.6 40 52.8 46L53.6 78C54 86 53.6 93 52.2 98C51.3 100.6 48.4 100.6 48 97.6L47.6 85L47.2 66L47.8 50Z',
    // pernas
    'M15.5 88L30.2 88L28.6 127L26.4 163.5L19.8 163.5L18.6 127Z',
    'M31 88L45.8 88L42.6 127L41.6 163.5L35 163.5L33 127Z',
    // sapatos
    'M19.4 162L26.8 162L27.4 168Q27.2 170 24.6 170L14.6 170Q12.4 169.6 13.8 167.4Z',
    'M34.6 162L42 162L45.6 166.8Q47.2 169.6 44.8 170L35.6 170Q33.6 170 33.9 168Z'
  ];

  function pessoa(pai, x, chao) {
    var g = el('g', {
      transform: 'translate(' + x + ',' + (chao - PESSOA_ALTURA) + ')',
      fill: '#a6a29b'
    }, pai);
    SILHUETA.forEach(function (d) { el('path', { d: d }, g); });
  }

  function desenharParede(url, largura, altura) {
    cena.textContent = '';
    if (!largura || !altura) {
      parede.hidden = true;
      return;
    }
    parede.hidden = false;

    var centro = Math.max(CENTRO_OBRA, altura / 2 + 30); // obras muito altas não encostam no chão
    var alturaParede = Math.max(ALTURA_PAREDE, centro + altura / 2 + 20);
    var larguraCena = Math.max(MARGEM + largura + DISTANCIA + PESSOA_LARGURA + MARGEM, 220);
    var sobra = larguraCena - (largura + DISTANCIA + PESSOA_LARGURA);
    var xObra = sobra / 2;
    var chao = alturaParede;

    var svg = el('svg', {
      viewBox: '0 0 ' + larguraCena + ' ' + (alturaParede + ALTURA_PISO),
      role: 'img',
      'aria-label': 'A obra, com ' + fmt(largura) + ' × ' + fmt(altura) + ' cm, numa parede ao lado de uma pessoa de 1,70 m'
    });

    var defs = el('defs', {}, svg);
    var luz = el('linearGradient', { id: 'visor-luz', x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
    el('stop', { offset: 0, 'stop-color': '#f1eee9' }, luz);
    el('stop', { offset: 1, 'stop-color': '#e4e0d9' }, luz);
    var sombra = el('filter', { id: 'visor-sombra', x: '-20%', y: '-20%', width: '140%', height: '150%' }, defs);
    el('feDropShadow', { dx: 0, dy: 2.5, stdDeviation: 2.5, 'flood-color': '#000', 'flood-opacity': 0.28 }, sombra);

    el('rect', { x: 0, y: 0, width: larguraCena, height: alturaParede, fill: 'url(#visor-luz)' }, svg); // parede
    el('rect', { x: 0, y: chao - 8, width: larguraCena, height: 8, fill: '#f7f5f1' }, svg);             // rodapé
    el('rect', { x: 0, y: chao, width: larguraCena, height: ALTURA_PISO, fill: '#cfc8bd' }, svg);       // piso

    var yObra = chao - centro - altura / 2;
    el('rect', { x: xObra, y: yObra, width: largura, height: altura, fill: '#ddd', filter: 'url(#visor-sombra)' }, svg);
    var img = el('image', {
      x: xObra, y: yObra, width: largura, height: altura, preserveAspectRatio: 'xMidYMid slice'
    }, svg);
    img.setAttribute('href', url);
    img.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', url);

    pessoa(svg, xObra + largura + DISTANCIA, chao);
    cena.appendChild(svg);
  }

  function fmt(n) {
    return String(n).replace('.', ',');
  }

  function mostrar(i) {
    atual = (i + links.length) % links.length;
    var link = links[atual];
    var miniatura = link.querySelector('img');
    var figura = link.closest('figure');
    var cap = figura && figura.querySelector('figcaption');

    imagem.src = link.getAttribute('href');
    imagem.alt = miniatura ? miniatura.alt : '';
    legenda.innerHTML = cap ? cap.innerHTML : '';
    contador.textContent = (atual + 1) + ' / ' + links.length;
    desenharParede(
      link.getAttribute('href'),
      parseFloat(link.dataset.larguraCm),
      parseFloat(link.dataset.alturaCm)
    );
    visor.scrollTop = 0;
  }

  var multiplas = links.length > 1;
  btnAnterior.hidden = btnProxima.hidden = !multiplas;

  links.forEach(function (link, i) {
    link.addEventListener('click', function (ev) {
      if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button !== 0) return; // nova aba continua funcionando
      ev.preventDefault();
      mostrar(i);
      visor.showModal();
      document.documentElement.classList.add('visor-aberto');
    });
  });

  btnAnterior.addEventListener('click', function () { mostrar(atual - 1); });
  btnProxima.addEventListener('click', function () { mostrar(atual + 1); });
  visor.querySelector('.visor-fechar').addEventListener('click', function () { visor.close(); });
  visor.addEventListener('close', function () {
    document.documentElement.classList.remove('visor-aberto');
    links[atual].focus();
  });
  visor.addEventListener('keydown', function (ev) {
    if (!multiplas) return;
    if (ev.key === 'ArrowLeft') mostrar(atual - 1);
    if (ev.key === 'ArrowRight') mostrar(atual + 1);
  });
})();
