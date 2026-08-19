/* ============================================================
   Flamma — interações, tema, animações de entrada
   ============================================================ */
(function () {
  'use strict';

  const root = document.documentElement;

  /* ---------- Tema claro/escuro (persistente) ---------- */
  const themeToggle = document.getElementById('themeToggle');
  const stored = (function () { try { return localStorage.getItem('flamma-theme'); } catch (e) { return null; } })();
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  root.setAttribute('data-theme', stored || (prefersDark ? 'dark' : 'light'));

  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('flamma-theme', next); } catch (e) {}
      const meta = document.querySelector('meta[name="theme-color"]');
      if (meta) meta.setAttribute('content', next === 'dark' ? '#0e0e12' : '#fd0342');
    });
  }

  /* ---------- Header: sombra ao rolar + botão voltar ao topo ---------- */
  const header = document.querySelector('.site-header');
  const toTop = document.getElementById('toTop');
  let ticking = false;
  function onScroll() {
    const y = window.scrollY;
    if (header) header.classList.toggle('scrolled', y > 8);
    if (toTop) toTop.classList.toggle('show', y > 600);
    ticking = false;
  }
  window.addEventListener('scroll', function () {
    if (!ticking) { window.requestAnimationFrame(onScroll); ticking = true; }
  }, { passive: true });
  onScroll();

  if (toTop) {
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ---------- Busca expansível ---------- */
  const searchToggle = document.getElementById('searchToggle');
  const searchField = document.getElementById('searchField');
  if (searchToggle && searchField) {
    const input = searchField.querySelector('input');
    searchToggle.addEventListener('click', function () {
      const open = searchField.classList.toggle('open');
      if (open && input) setTimeout(function () { input.focus(); }, 120);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') searchField.classList.remove('open');
    });
  }

  /* ---------- Reveal on scroll (IntersectionObserver) ---------- */
  const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.14, rootMargin: '0px 0px -8% 0px' });
    revealEls.forEach(function (el) { io.observe(el); });

    /* Rede de segurança: enquanto a aba está oculta (abrir em segundo plano,
       navegador in-app, volta pelo bfcache) o observer não entrega callbacks —
       e todo elemento com .reveal fica em opacity 0, ou seja, página em branco.
       Ao voltar a ficar visível, tudo que já está na tela é revelado na hora. */
    const revealVisible = function () {
      const h = window.innerHeight || document.documentElement.clientHeight;
      revealEls.forEach(function (el) {
        if (el.classList.contains('in')) return;
        const r = el.getBoundingClientRect();
        if (r.top < h && r.bottom > 0) { el.classList.add('in'); io.unobserve(el); }
      });
    };
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) revealVisible();
    });
    window.addEventListener('pageshow', revealVisible);
    if (document.hidden) revealVisible();
  } else {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  }

  /* ---------- Newsletter: envia para o Netlify Forms ---------- */
  const form = document.getElementById('newsletterForm');
  if (form) {
    const btn = form.querySelector('.footer__btn');
    const input = form.querySelector('input[type="email"]');
    const label = btn ? btn.querySelector('span') : null;
    const msg = document.getElementById('newsletterMsg');

    function reset() {
      if (label) label.textContent = 'Inscrever-se';
      if (btn) { btn.classList.remove('done', 'error'); btn.disabled = false; }
    }
    function say(text, isError) {
      if (!msg) return;
      msg.textContent = text;
      msg.classList.toggle('is-error', !!isError);
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const val = (input && input.value || '').trim();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        say('Digite um e-mail válido para continuar.', true);
        if (input) {
          input.focus();
          input.animate(
            [{ transform: 'translateX(0)' }, { transform: 'translateX(-6px)' },
             { transform: 'translateX(6px)' }, { transform: 'translateX(0)' }],
            { duration: 300 }
          );
        }
        return;
      }

      if (btn) btn.disabled = true;
      if (label) label.textContent = 'Enviando…';
      say('');

      /* O Netlify captura o POST na própria URL do site, em urlencoded.
         O corpo precisa levar o campo form-name para o envio ser associado
         ao formulário certo. */
      fetch('/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(new FormData(form)).toString()
      })
        .then(function (res) {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          if (label) label.textContent = '✓ Inscrito!';
          if (btn) btn.classList.add('done');
          if (input) { input.value = ''; input.blur(); }
          say('Pronto! Você vai receber nossos próximos conteúdos.');
          setTimeout(reset, 3600);
        })
        .catch(function () {
          if (label) label.textContent = 'Tentar de novo';
          if (btn) { btn.classList.add('error'); btn.disabled = false; }
          say('Não conseguimos enviar agora. Tente novamente em instantes.', true);
          setTimeout(reset, 4200);
        });
    });
  }

  /* ---------- Botões que rolam suave para âncoras ---------- */
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      const id = a.getAttribute('href');
      if (id.length < 2) return;
      const target = document.querySelector(id);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
})();
