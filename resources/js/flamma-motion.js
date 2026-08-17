/* ============================================================
   Flamma — camada de movimento (home + para empresas)

   Substitui o companies-motion.js: cobre as duas páginas.

   • Home  → o Blade já traz as classes fm-* no markup, então aqui
             só roda o observer.
   • Para Empresas → o Blade não foi alterado, então o script
             encontra os elementos pelos ids das seções e aplica as
             classes (mesma lógica validada antes).

   Roda de novo depois de cada navegação/re-render do Livewire,
   e é idempotente — reaplicar não duplica nada.
   ============================================================ */
(function () {
  'use strict';

  var TAGGED = 'data-fm-tagged';

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function addClass(el, cls) { if (el) el.classList.add(cls); return el; }

  function reveal(el, variant, delay) {
    if (!el || el.hasAttribute(TAGGED)) return el;
    el.setAttribute(TAGGED, '1');
    el.classList.add('fm-' + (variant || 'reveal'));
    if (delay) el.setAttribute('data-fm-delay', String(delay));
    return el;
  }

  /* Seleciona pelo valor literal de uma classe utilitária com colchetes
     (ex.: aspect-[654/912]) sem precisar escapar seletor CSS. */
  function byClass(root, cls) {
    return $$('[class]', root).filter(function (el) { return el.classList.contains(cls); });
  }
  function firstByClass(root, cls) { return byClass(root, cls)[0] || null; }

  /* ============================================================
     Página PARA EMPRESAS — marcação automática
     ============================================================ */
  function tagCompanies() {
    var hero = $('#empresas');
    if (!hero) return false;

    var invest = $('#por-que-investir');
    var flow = $('#como-funciona');
    var tool = $('#consultor');
    var privacy = $('#privacidade');
    var plan = $('#simulador');
    var cta = $('#contratar');

    var scope = hero.closest('.overflow-x-clip') || $('.fi-page-content') || document.body;
    scope.classList.add('fm-page');

    /* Grafismos: estrela (347×347) gira, seta (270.23×271.694) flutua.
       Só os posicionados em absolute — os mesmos viewBox aparecem em
       tamanho pequeno como marcador dos pilares, e aqueles giram no
       hover, não sozinhos. */
    $$('svg[aria-hidden="true"]').forEach(function (svg) {
      if (!svg.classList.contains('absolute')) return;
      if (tool && tool.contains(svg) && svg.closest('li')) return;
      var vb = svg.getAttribute('viewBox') || '';
      if (vb.indexOf('347 347') !== -1) {
        addClass(svg, 'fm-spin');
      } else if (vb.indexOf('270.23') !== -1) {
        addClass(svg, svg.classList.contains('rotate-180') ? 'fm-bob-180' : 'fm-bob');
      }
    });

    $$('a[href="#simulador"], a[href="#contratar"]').forEach(function (a) { addClass(a, 'fm-btn'); });

    /* Hero — acima da dobra: entra imediatamente, mas em cascata */
    var heroGrid = $('.grid', hero);
    if (heroGrid) {
      var heroText = heroGrid.children[0];
      if (heroText) ['h1', 'p', 'a'].forEach(function (sel, i) { reveal($(sel, heroText), 'reveal', i); });
      reveal(heroGrid.children[1], 'reveal-scale', 2);
    }
    var heroFrame = firstByClass(hero, 'aspect-[654/912]');
    addClass(heroFrame, 'fm-zoom');
    addClass(heroFrame, 'fm-zoom--hero');

    if (invest) {
      var iGrid = $('.grid', invest);
      if (iGrid) {
        reveal(iGrid.children[0], 'reveal-left');
        reveal(iGrid.children[1], 'reveal-right');
      }
      addClass(firstByClass(invest, 'aspect-[706/981]'), 'fm-zoom');
    }

    if (flow) {
      reveal($('header', flow), 'reveal');
      $$('article', flow).forEach(function (card, i) {
        addClass(card, 'fm-card');
        reveal(card, 'reveal', i + 1);
        /* A foto do card é posicionada em px absolutos no palco do Figma, então o
           hook é um data-attribute: casar por classe utilitária quebrava a cada
           ajuste de layout. */
        addClass($('[data-card-prod]', card), 'fm-card-prod');
        addClass(byClass(card, 'size-[31px]')[0], 'fm-card-icon');
      });
    }

    if (tool) {
      /* Os dois boxes brancos ficam posicionados em % sobre o grafismo em escada,
         não num grid — por isso o hook é um data-attribute e não a estrutura. */
      $$('[data-tool-box]', tool).forEach(function (box, i) {
        reveal(box, 'reveal-left', i + 1);
      });
      reveal($('ul', tool), 'reveal-right');
      $$('li', tool).forEach(function (li) { addClass(li, 'fm-pillar'); });
    }

    if (privacy) {
      /* Texto e foto são posicionados em % sobre o palco de 1676×732, não num grid. */
      reveal($('[data-privacy-text]', privacy), 'reveal-left');
      reveal($('[data-privacy-media]', privacy), 'reveal-right');
    }

    if (plan) {
      reveal($('header', plan), 'reveal');
      var planFrame = firstByClass(plan, 'aspect-[1670/804]');
      reveal(planFrame, 'reveal-scale', 1);
      addClass(planFrame, 'fm-zoom');
      addClass(planFrame, 'fm-zoom--slow');
      reveal($('[wire\\:id]', plan), 'reveal', 2);
      $$('button', plan).forEach(function (b) {
        if (/orçamento/i.test(b.textContent)) addClass(b, 'fm-btn');
      });
    }

    if (cta) {
      reveal(cta.firstElementChild, 'reveal-scale');
      /* São duas molduras — a do mobile e a do desktop — e só a do mobile mantém
         a classe de proporção; a do desktop é posicionada em % do card. */
      $$('[data-cta-photo]', cta).forEach(function (f) { addClass(f, 'fm-zoom'); });
    }

    return true;
  }

  /* ============================================================
     Observer de entrada — comum às duas páginas
     ============================================================ */
  var io = null;
  var SEL = '.fm-reveal:not(.fm-in), .fm-reveal-left:not(.fm-in),' +
            '.fm-reveal-right:not(.fm-in), .fm-reveal-scale:not(.fm-in)';

  function observe() {
    var els = $$(SEL);
    if (!('IntersectionObserver' in window)) {
      els.forEach(function (el) { el.classList.add('fm-in'); });
      return;
    }
    if (!io) {
      io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('fm-in');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.14, rootMargin: '0px 0px -8% 0px' });
    }
    els.forEach(function (el) { io.observe(el); });
  }

  /* Rede de segurança: enquanto a aba está oculta (aberta em segundo
     plano, navegador in-app, volta pelo bfcache) o observer não entrega
     callbacks — e todo elemento com reveal fica em opacity 0, ou seja,
     página em branco. Ao voltar a ficar visível, tudo que já está na
     tela é revelado na hora. */
  function revealVisible() {
    var h = window.innerHeight || document.documentElement.clientHeight;
    $$(SEL).forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.top < h && r.bottom > 0) {
        el.classList.add('fm-in');
        if (io) io.unobserve(el);
      }
    });
  }

  /* ============================================================
     Voltar ao topo
     ============================================================ */
  function toTop() {
    if ($('.fm-to-top')) return;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'fm-to-top';
    btn.setAttribute('aria-label', 'Voltar ao topo');
    btn.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"' +
      ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<path d="M12 19V5M5 12l7-7 7 7"/></svg>';
    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    document.body.appendChild(btn);

    var ticking = false;
    function onScroll() {
      btn.classList.toggle('fm-show', window.scrollY > 600);
      ticking = false;
    }
    window.addEventListener('scroll', function () {
      if (!ticking) { window.requestAnimationFrame(onScroll); ticking = true; }
    }, { passive: true });
    onScroll();
  }

  /* ============================================================
     Boot
     ============================================================ */
  function init() {
    /* A home já traz .fm-page no Blade; Para Empresas ganha por tagCompanies(). */
    var isCompanies = tagCompanies();
    if (!isCompanies && !$('.fm-page')) return;   /* nenhuma das duas páginas */

    observe();
    /* O topo da página está acima da dobra: aparece já, sem esperar o scroll. */
    revealVisible();
    toTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* Livewire troca pedaços do DOM (calculadora de preço, navegação SPA).
     Reaplicar é barato e idempotente. */
  document.addEventListener('livewire:navigated', init);
  document.addEventListener('livewire:initialized', init);
  document.addEventListener('livewire:update', function () { tagCompanies(); observe(); });

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) revealVisible();
  });
  window.addEventListener('pageshow', revealVisible);
  if (document.hidden) revealVisible();
})();
