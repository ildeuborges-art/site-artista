// Página inicial: troca a obra em destaque a cada poucos segundos, mesclando
// a imagem atual com a próxima (as duas ficam empilhadas durante a transição).
// Pausa quando o mouse está sobre a imagem, quando a aba não está visível
// e pelo botão "Pausar". Quem prefere menos movimento no sistema começa pausado.
(function () {
  var INTERVALO = 12000; // milissegundos entre uma obra e outra
  // A duração da mescla fica no CSS (.destaque img { transition: opacity ... })

  var dados = document.getElementById('obras-destaque');
  var area = document.querySelector('.destaque-area');
  if (!dados || !area) return;
  var obras = JSON.parse(dados.textContent);
  if (obras.length < 2) return;

  var img = area.querySelector('.destaque img'); // a imagem visível
  var outra = img.cloneNode(false);               // a que entra por cima na troca
  outra.classList.add('saindo');
  outra.alt = '';
  outra.setAttribute('aria-hidden', 'true');
  img.parentNode.appendChild(outra);
  var links = area.querySelectorAll('.destaque, .destaque-link');
  var titulo = area.querySelector('.destaque-titulo');
  var ano = area.querySelector('.destaque-ano');
  var serie = area.querySelector('.destaque-serie');
  var botao = area.querySelector('.destaque-pausa');

  var atual = 0;
  var timer = null;
  var sobre = false;
  var pausadoPeloUsuario = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function carregar(url, pronto) {
    var pre = new Image();
    pre.onload = pre.onerror = function () { pronto(); };
    pre.src = url;
  }

  function mostrar(i) {
    var obra = obras[i];
    carregar(obra.url, function () {
      var entrando = outra;
      var saindo = img;
      entrando.src = obra.url;
      entrando.alt = obra.titulo || 'Obra em destaque';
      entrando.removeAttribute('aria-hidden');
      saindo.alt = '';
      saindo.setAttribute('aria-hidden', 'true');
      // as duas transições acontecem ao mesmo tempo: uma aparece enquanto a outra some
      entrando.classList.remove('saindo');
      saindo.classList.add('saindo');
      img = entrando;
      outra = saindo;

      for (var k = 0; k < links.length; k++) links[k].href = obra.link;
      if (titulo) titulo.textContent = obra.titulo;
      if (ano) ano.textContent = obra.ano ? ', ' + obra.ano : '';
      if (serie) serie.textContent = '— série ' + obra.serie;
      atual = i;
      carregar(obras[(i + 1) % obras.length].url, function () {}); // já baixa a próxima
    });
  }

  function agendar() {
    clearTimeout(timer);
    timer = null;
    if (pausadoPeloUsuario || sobre || document.hidden) return;
    timer = setTimeout(function () {
      mostrar((atual + 1) % obras.length);
      agendar();
    }, INTERVALO);
  }

  function atualizarBotao() {
    botao.textContent = pausadoPeloUsuario ? 'Continuar' : 'Pausar';
    botao.setAttribute('aria-pressed', pausadoPeloUsuario ? 'true' : 'false');
  }

  botao.hidden = false;
  atualizarBotao();
  botao.addEventListener('click', function () {
    pausadoPeloUsuario = !pausadoPeloUsuario;
    atualizarBotao();
    agendar();
  });
  area.addEventListener('mouseenter', function () { sobre = true; agendar(); });
  area.addEventListener('mouseleave', function () { sobre = false; agendar(); });
  document.addEventListener('visibilitychange', agendar);

  carregar(obras[1].url, function () {});
  agendar();
})();
