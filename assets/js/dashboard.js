/* ============================================================
   IHMS dashboard — icons, sidebar, ApexCharts theme helpers
   ============================================================ */
(function () {
  "use strict";

  function init() {
    if (window.lucide) window.lucide.createIcons();

    // Sidebar toggle (mobile)
    var side = document.getElementById("side");
    var toggle = document.getElementById("sideToggle");
    var backdrop = document.getElementById("sideBackdrop");
    function close() { if (side) side.classList.remove("open"); if (backdrop) backdrop.classList.remove("show"); }
    if (toggle && side) {
      toggle.addEventListener("click", function () {
        side.classList.toggle("open");
        if (backdrop) backdrop.classList.toggle("show");
      });
    }
    if (backdrop) backdrop.addEventListener("click", close);
    document.querySelectorAll(".side__link").forEach(function (a) {
      a.addEventListener("click", function () { if (window.innerWidth <= 920) close(); });
    });

    // Segmented controls (visual only)
    document.querySelectorAll(".seg").forEach(function (seg) {
      seg.querySelectorAll("button").forEach(function (b) {
        b.addEventListener("click", function () {
          seg.querySelectorAll("button").forEach(function (x) { x.classList.remove("active"); });
          b.classList.add("active");
        });
      });
    });
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);

  /* ---- ApexCharts shared palette + factory helpers ---- */
  var C = {
    ink: "#001B48", brand: "#02457A", accent: "#018ABE", soft: "#97CADB",
    mist: "#D6E8EE", green: "#16a37a", amber: "#e8920c", rose: "#e1467c",
    font: "'Plus Jakarta Sans', 'Inter', sans-serif",
  };
  window.IHMS = window.IHMS || {};
  window.IHMS.colors = C;

  // Tiny sparkline for KPI tiles
  window.IHMS.spark = function (el, data, color) {
    if (!el || !window.ApexCharts) return;
    new ApexCharts(el, {
      chart: { type: "area", height: 46, sparkline: { enabled: true }, animations: { easing: "easeinout", speed: 700 } },
      stroke: { curve: "smooth", width: 2.5 },
      fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 100] } },
      colors: [color || C.accent],
      series: [{ data: data }],
      tooltip: { enabled: false },
    }).render();
  };

  // Area/line trend chart
  window.IHMS.area = function (el, categories, series, opts) {
    opts = opts || {};
    if (!el || !window.ApexCharts) return;
    new ApexCharts(el, {
      chart: { type: "area", height: opts.height || 300, fontFamily: C.font, toolbar: { show: false }, zoom: { enabled: false }, animations: { speed: 700 } },
      colors: opts.colors || [C.accent, C.soft],
      dataLabels: { enabled: false },
      stroke: { curve: "smooth", width: 3 },
      fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.03, stops: [0, 90] } },
      grid: { borderColor: C.mist, strokeDashArray: 5, padding: { left: 8, right: 8 } },
      xaxis: { categories: categories, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: "#7790ab", fontWeight: 600 } } },
      yaxis: { labels: { style: { colors: "#7790ab" } }, min: 0, forceNiceScale: true },
      legend: { show: !!opts.legend, position: "top", horizontalAlign: "right", markers: { radius: 12 }, fontWeight: 600 },
      series: series,
      tooltip: { theme: "light" },
    }).render();
  };

  // Bar chart
  window.IHMS.bar = function (el, categories, series, opts) {
    opts = opts || {};
    if (!el || !window.ApexCharts) return;
    new ApexCharts(el, {
      chart: { type: "bar", height: opts.height || 300, fontFamily: C.font, toolbar: { show: false }, animations: { speed: 700 } },
      colors: opts.colors || [C.brand],
      plotOptions: { bar: { borderRadius: 7, columnWidth: opts.columnWidth || "48%", distributed: !!opts.distributed } },
      dataLabels: { enabled: false },
      grid: { borderColor: C.mist, strokeDashArray: 5 },
      xaxis: { categories: categories, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: "#7790ab", fontWeight: 600 } } },
      yaxis: { labels: { style: { colors: "#7790ab" } } },
      legend: { show: false },
      series: series,
      tooltip: { theme: "light" },
    }).render();
  };

  // Donut chart
  window.IHMS.donut = function (el, labels, data, opts) {
    opts = opts || {};
    if (!el || !window.ApexCharts) return;
    new ApexCharts(el, {
      chart: { type: "donut", height: opts.height || 290, fontFamily: C.font, animations: { speed: 700 } },
      colors: opts.colors || [C.brand, C.accent, C.soft, C.amber, C.green, C.rose],
      labels: labels,
      series: data,
      stroke: { width: 0 },
      legend: { position: "bottom", fontWeight: 600, markers: { radius: 12 } },
      dataLabels: { enabled: false },
      plotOptions: { pie: { donut: { size: "72%", labels: { show: true, total: { show: true, label: opts.totalLabel || "Total", fontFamily: C.font, color: C.ink, fontWeight: 700 } } } } },
      tooltip: { theme: "light" },
    }).render();
  };
})();
