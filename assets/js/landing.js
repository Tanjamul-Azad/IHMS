/* ============================================================
   IHMS landing — interactions
   Lucide icons · AOS reveal · count-up · sticky nav · menu
   ============================================================ */
(function () {
  "use strict";

  function ready(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }

  ready(function () {
    /* ---- Lucide icons ---- */
    if (window.lucide && typeof window.lucide.createIcons === "function") {
      window.lucide.createIcons();
    }

    /* ---- AOS scroll reveal (graceful fallback) ---- */
    if (window.AOS && typeof window.AOS.init === "function") {
      window.AOS.init({
        duration: 700,
        easing: "ease-out-cubic",
        once: true,
        offset: 70,
        disable: window.matchMedia("(prefers-reduced-motion: reduce)").matches,
      });
    } else {
      document.body.classList.add("no-aos");
    }

    /* ---- Sticky navbar condense ---- */
    var nav = document.querySelector(".nav");
    var toTop = document.querySelector(".to-top");
    function onScroll() {
      var y = window.scrollY || window.pageYOffset;
      if (nav) nav.classList.toggle("scrolled", y > 20);
      if (toTop) toTop.classList.toggle("show", y > 480);
    }
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });

    /* ---- Back to top ---- */
    if (toTop) {
      toTop.addEventListener("click", function () {
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }

    /* ---- Mobile menu ---- */
    var toggle = document.querySelector(".nav__toggle");
    var menu = document.querySelector(".nav__menu");
    if (toggle && menu) {
      toggle.addEventListener("click", function () {
        var open = menu.classList.toggle("open");
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        if (window.lucide) window.lucide.createIcons();
      });
      menu.querySelectorAll("a").forEach(function (a) {
        a.addEventListener("click", function () { menu.classList.remove("open"); });
      });
    }

    /* ---- Count-up on view ---- */
    var counters = document.querySelectorAll("[data-count]");
    if (counters.length) {
      var animate = function (el) {
        var target = parseFloat(el.getAttribute("data-count"));
        var suffix = el.getAttribute("data-suffix") || "";
        var dur = 1500, start = null;
        var step = function (ts) {
          if (!start) start = ts;
          var p = Math.min((ts - start) / dur, 1);
          var eased = 1 - Math.pow(1 - p, 3);
          var val = target * eased;
          el.textContent = (target % 1 === 0 ? Math.round(val) : val.toFixed(1)) + suffix;
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      };
      if ("IntersectionObserver" in window) {
        var io = new IntersectionObserver(function (entries) {
          entries.forEach(function (e) {
            if (e.isIntersecting) { animate(e.target); io.unobserve(e.target); }
          });
        }, { threshold: 0.4 });
        counters.forEach(function (c) { io.observe(c); });
      } else {
        counters.forEach(animate);
      }
    }

    /* ---- Newsletter (demo only) ---- */
    var nl = document.querySelector(".newsletter");
    if (nl) {
      nl.addEventListener("submit", function (e) {
        e.preventDefault();
        var input = nl.querySelector("input");
        var btn = nl.querySelector("button");
        if (input && input.value.trim()) {
          btn.innerHTML = '<i data-lucide="check"></i>';
          input.value = "";
          input.placeholder = "Subscribed — thank you!";
          if (window.lucide) window.lucide.createIcons();
          setTimeout(function () {
            btn.innerHTML = '<i data-lucide="send"></i>';
            input.placeholder = "Your email address";
            if (window.lucide) window.lucide.createIcons();
          }, 2600);
        }
      });
    }
  });
})();
