// Domácí rozpočet - lehká knihovna grafů nad <canvas> bez závislosti na internetu.
// Validovaná kategorická paleta (CVD-safe pořadí) + stavové barvy shodné s CSS proměnnými.
window.CHART_PALETTE = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];

(function () {
  var tooltip;
  function getTooltip() {
    if (!tooltip) {
      tooltip = document.createElement('div');
      tooltip.className = 'chart-tooltip';
      Object.assign(tooltip.style, {
        position: 'fixed', pointerEvents: 'none', zIndex: 1000,
        background: 'var(--card)', border: '1px solid var(--card-border)', borderRadius: '8px',
        padding: '6px 10px', fontSize: '12.5px', boxShadow: 'var(--shadow-md)', display: 'none',
        color: 'var(--text)', fontWeight: '600',
      });
      document.body.appendChild(tooltip);
    }
    return tooltip;
  }

  function cssVar(name, fallback) {
    var v = getComputedStyle(document.documentElement).getPropertyValue(name);
    return v ? v.trim() : fallback;
  }

  function setupCanvas(canvas, cssHeight) {
    var dpr = window.devicePixelRatio || 1;
    var cssWidth = canvas.parentElement.clientWidth || 400;
    canvas.width = cssWidth * dpr;
    canvas.height = cssHeight * dpr;
    canvas.style.width = cssWidth + 'px';
    canvas.style.height = cssHeight + 'px';
    var ctx = canvas.getContext('2d');
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    return { ctx: ctx, w: cssWidth, h: cssHeight };
  }

  function roundRectTop(ctx, x, y, w, h, r) {
    r = Math.min(r, w / 2, h);
    if (h <= 0) return;
    ctx.beginPath();
    ctx.moveTo(x, y + h);
    ctx.lineTo(x, y + r);
    ctx.arcTo(x, y, x + r, y, r);
    ctx.lineTo(x + w - r, y);
    ctx.arcTo(x + w, y, x + w, y + r, r);
    ctx.lineTo(x + w, y + h);
    ctx.closePath();
  }

  function attachInteractions(canvas, regions, formatter) {
    var tip = getTooltip();
    canvas.style.cursor = 'default';
    canvas.addEventListener('mousemove', function (e) {
      var rect = canvas.getBoundingClientRect();
      var x = e.clientX - rect.left, y = e.clientY - rect.top;
      var hit = regions.find(function (r) { return r.test(x, y); });
      if (hit) {
        canvas.style.cursor = hit.href ? 'pointer' : 'default';
        tip.style.display = 'block';
        tip.style.left = (e.clientX + 14) + 'px';
        tip.style.top = (e.clientY + 10) + 'px';
        tip.textContent = formatter(hit.data);
      } else {
        canvas.style.cursor = 'default';
        tip.style.display = 'none';
      }
    });
    canvas.addEventListener('mouseleave', function () { tip.style.display = 'none'; });
    canvas.addEventListener('click', function (e) {
      var rect = canvas.getBoundingClientRect();
      var x = e.clientX - rect.left, y = e.clientY - rect.top;
      var hit = regions.find(function (r) { return r.test(x, y); });
      if (hit && hit.href) window.location.href = hit.href;
    });
  }

  /** Sloupcový graf. items: [{label, value, color, href, valueLabel}] */
  function drawBarChart(canvas, items, opts) {
    opts = opts || {};
    var setup = setupCanvas(canvas, opts.height || 220);
    var ctx = setup.ctx, W = setup.w, H = setup.h;
    ctx.clearRect(0, 0, W, H);
    if (!items.length) { drawEmpty(ctx, W, H); return; }

    var padding = { top: 18, right: 8, bottom: 30, left: 8 };
    var max = Math.max.apply(null, items.map(function (d) { return d.value; })) || 1;
    max *= 1.18;
    var chartW = W - padding.left - padding.right;
    var chartH = H - padding.top - padding.bottom;
    var gap = 14;
    var barW = Math.max(10, chartW / items.length - gap);
    var regions = [];

    items.forEach(function (d, i) {
      var x = padding.left + i * (chartW / items.length) + (chartW / items.length - barW) / 2;
      var barH = (d.value / max) * chartH;
      var y = padding.top + (chartH - barH);
      ctx.fillStyle = d.color || window.CHART_PALETTE[i % window.CHART_PALETTE.length];
      roundRectTop(ctx, x, y, barW, barH, 5);
      ctx.fill();

      ctx.fillStyle = cssVar('--text-muted', '#667085');
      ctx.font = '600 11px -apple-system, sans-serif';
      ctx.textAlign = 'center';
      var label = d.label.length > 10 ? d.label.slice(0, 9) + '…' : d.label;
      ctx.fillText(label, x + barW / 2, H - padding.bottom + 15);

      var rx = x, ry = padding.top, rw = barW, rh = chartH;
      regions.push({
        test: function (px, py) { return px >= rx && px <= rx + rw && py >= ry && py <= ry + rh; },
        data: d, href: d.href,
      });
    });

    attachInteractions(canvas, regions, function (d) { return d.label + ': ' + (d.valueLabel || d.value); });
  }

  /** Donut graf. items: [{label, value, color, href, valueLabel}] */
  function drawDonutChart(canvas, items, opts) {
    opts = opts || {};
    var setup = setupCanvas(canvas, opts.height || 220);
    var ctx = setup.ctx, W = setup.w, H = setup.h;
    ctx.clearRect(0, 0, W, H);
    var total = items.reduce(function (s, d) { return s + d.value; }, 0);
    if (!items.length || total <= 0) { drawEmpty(ctx, W, H); return; }

    var cx = W / 2, cy = H / 2, r = Math.min(cx, cy) - 10, inner = r * 0.62;
    var start = -Math.PI / 2;
    var regions = [];
    var gapRad = 0.018;

    items.forEach(function (d, i) {
      var frac = d.value / total;
      var end = start + frac * Math.PI * 2;
      var segStart = start + gapRad / 2, segEnd = Math.max(segStart, end - gapRad / 2);
      var color = d.color || window.CHART_PALETTE[i % window.CHART_PALETTE.length];
      ctx.beginPath();
      ctx.arc(cx, cy, r, segStart, segEnd);
      ctx.arc(cx, cy, inner, segEnd, segStart, true);
      ctx.closePath();
      ctx.fillStyle = color;
      ctx.fill();

      (function (s0, s1) {
        regions.push({
          test: function (px, py) {
            var dx = px - cx, dy = py - cy;
            var dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < inner || dist > r) return false;
            var ang = Math.atan2(dy, dx);
            if (ang < -Math.PI / 2) ang += Math.PI * 2;
            var a0 = s0, a1 = s1;
            if (a0 < -Math.PI / 2) a0 += Math.PI * 2;
            if (a1 < -Math.PI / 2) a1 += Math.PI * 2;
            return ang >= a0 && ang <= a1;
          },
          data: d, href: d.href,
        });
      })(start, end);

      start = end;
    });

    ctx.textAlign = 'center';
    ctx.fillStyle = cssVar('--text', '#1f2532');
    ctx.font = '700 15px -apple-system, sans-serif';
    ctx.fillText(opts.centerLabel || '', cx, cy - 2);
    ctx.font = '500 11px -apple-system, sans-serif';
    ctx.fillStyle = cssVar('--text-muted', '#667085');
    ctx.fillText(opts.centerSubLabel || '', cx, cy + 15);

    attachInteractions(canvas, regions, function (d) {
      var pct = ((d.value / total) * 100).toFixed(1);
      return d.label + ': ' + (d.valueLabel || d.value) + ' (' + pct + '%)';
    });
  }

  /** Čárový graf s více řadami. series: [{name, color, points:[{x,y,label,href}]}] */
  function drawLineChart(canvas, series, opts) {
    opts = opts || {};
    var setup = setupCanvas(canvas, opts.height || 240);
    var ctx = setup.ctx, W = setup.w, H = setup.h;
    ctx.clearRect(0, 0, W, H);
    var allPoints = series.reduce(function (a, s) { return a.concat(s.points); }, []);
    if (!allPoints.length) { drawEmpty(ctx, W, H); return; }

    var padding = { top: 16, right: 14, bottom: 28, left: 14 };
    var maxY = Math.max.apply(null, allPoints.map(function (p) { return p.y; }).concat([1])) * 1.15;
    var chartW = W - padding.left - padding.right;
    var chartH = H - padding.top - padding.bottom;
    var n = series[0].points.length;
    var stepX = n > 1 ? chartW / (n - 1) : 0;

    // gridline
    ctx.strokeStyle = cssVar('--card-border', '#e6e9ef');
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(padding.left, padding.top + chartH);
    ctx.lineTo(padding.left + chartW, padding.top + chartH);
    ctx.stroke();

    var regions = [];

    series.forEach(function (s) {
      ctx.beginPath();
      s.points.forEach(function (p, i) {
        var x = padding.left + i * stepX;
        var y = padding.top + chartH - (p.y / maxY) * chartH;
        if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
      });
      ctx.strokeStyle = s.color;
      ctx.lineWidth = 2.5;
      ctx.lineJoin = 'round';
      ctx.stroke();

      s.points.forEach(function (p, i) {
        var x = padding.left + i * stepX;
        var y = padding.top + chartH - (p.y / maxY) * chartH;
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, Math.PI * 2);
        ctx.fillStyle = s.color;
        ctx.fill();
        ctx.lineWidth = 2;
        ctx.strokeStyle = cssVar('--card', '#fff');
        ctx.stroke();

        regions.push({
          test: function (px, py) { return Math.abs(px - x) < stepX / 2 && Math.abs(py - y) < 40; },
          data: { label: p.label + ' · ' + s.name, value: p.y, valueLabel: p.valueLabel },
          href: p.href,
        });
      });
    });

    ctx.fillStyle = cssVar('--text-muted', '#667085');
    ctx.font = '500 11px -apple-system, sans-serif';
    ctx.textAlign = 'center';
    series[0].points.forEach(function (p, i) {
      var x = padding.left + i * stepX;
      ctx.fillText(p.axisLabel || p.label, x, H - padding.bottom + 18);
    });

    attachInteractions(canvas, regions, function (d) { return d.label + ': ' + (d.valueLabel || d.value); });
  }

  function drawEmpty(ctx, W, H) {
    ctx.fillStyle = cssVar('--text-faint', '#98a2b3');
    ctx.font = '500 13px -apple-system, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Zatím nejsou k dispozici žádná data', W / 2, H / 2);
  }

  window.drawBarChart = drawBarChart;
  window.drawDonutChart = drawDonutChart;
  window.drawLineChart = drawLineChart;
})();
