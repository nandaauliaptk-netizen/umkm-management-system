// ================================================================
// assets/js/finance.js
// Finance UMKM — Frontend Logic
// ================================================================

/* ── FORMAT RUPIAH ─────────────────────────────────────────── */
function formatRp(n) {
  return 'Rp ' + Math.abs(n).toLocaleString('id-ID');
}

function formatTanggal(str) {
  if (!str) return '—';
  const [y, m, d] = str.split('-');
  const bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  return `${parseInt(d)} ${bln[parseInt(m) - 1]} ${y}`;
}

/* ── MODAL ──────────────────────────────────────────────────── */
function openModal(tipe) {
  const today = new Date().toISOString().split('T')[0];
  if (tipe === 'pendapatan') {
    document.getElementById('p-tanggal').value = today;
  } else {
    document.getElementById('e-tanggal').value = today;
  }
  document.getElementById('modal-' + tipe).classList.remove('hidden');
}

function closeModal(tipe) {
  document.getElementById('modal-' + tipe).classList.add('hidden');
  resetForm(tipe);
}

function resetForm(tipe) {
  const pre = tipe === 'pendapatan' ? 'p' : 'e';
  ['keterangan','jumlah'].forEach(f => {
    document.getElementById(`${pre}-${f}`).value = '';
  });
}

// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) {
      const tipe = overlay.id.replace('modal-', '');
      closeModal(tipe);
    }
  });
});

/* ── SIMPAN ─────────────────────────────────────────────────── */
async function simpan(tipe) {
  const pre = tipe === 'pendapatan' ? 'p' : 'e';

  const keterangan = document.getElementById(`${pre}-keterangan`).value.trim();
  const jumlah     = parseFloat(document.getElementById(`${pre}-jumlah`).value);
  const kategori   = document.getElementById(`${pre}-kategori`).value;
  const tanggal    = document.getElementById(`${pre}-tanggal`).value;

  if (!keterangan)        { showToast('Keterangan tidak boleh kosong!', 'warning'); return; }
  if (!jumlah || jumlah <= 0) { showToast('Jumlah harus lebih dari 0!', 'warning'); return; }
  if (!tanggal)           { showToast('Pilih tanggal terlebih dahulu!', 'warning'); return; }

  const btn = document.getElementById(`btn-save-${pre}`);
  btn.classList.add('loading');

  try {
    const res = await fetch(`api.php?tipe=${tipe}&action=create`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ keterangan, jumlah, kategori, tanggal }),
    });
    const data = await res.json();

    if (data.error) { showToast(data.error, 'error'); return; }

    showToast(data.message, 'success');
    closeModal(tipe);
    await refreshAll();

  } catch (err) {
    showToast('Gagal menyimpan: ' + err.message, 'error');
  } finally {
    btn.classList.remove('loading');
  }
}

/* ── HAPUS ──────────────────────────────────────────────────── */
async function hapus(tipe, id) {
  if (!confirm('Hapus data ini?')) return;
  try {
    const res = await fetch(`api.php?tipe=${tipe}&action=delete`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    });
    const data = await res.json();
    if (data.error) { showToast(data.error, 'error'); return; }
    showToast(data.message, 'info');
    await refreshAll();
  } catch (err) {
    showToast('Gagal menghapus: ' + err.message, 'error');
  }
}

/* ── FETCH & RENDER ─────────────────────────────────────────── */
async function fetchList(tipe) {
  const res = await fetch(`api.php?tipe=${tipe}&action=list`);
  const json = await res.json();
  return json.data || [];
}

async function fetchSummary() {
  const res = await fetch(`api.php?tipe=pendapatan&action=summary`);
  return await res.json();
}

function renderTable(tipe, rows) {
  const tbody = document.getElementById(`tbody-${tipe}`);
  const colorClass = tipe === 'pendapatan' ? 'amount-positive' : 'amount-negative';

  if (!rows || rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">Belum ada data ${tipe}.</div></td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map(row => `
    <tr>
      <td style="color:var(--text-muted);font-size:12px;white-space:nowrap;">${formatTanggal(row.tanggal)}</td>
      <td>${escHtml(row.keterangan)}</td>
      <td><span class="tag">${escHtml(row.kategori)}</span></td>
      <td class="text-right ${colorClass}" style="white-space:nowrap;">${formatRp(parseFloat(row.jumlah))}</td>
      <td>
        <button class="btn-hapus" onclick="hapus('${tipe}', ${row.id})" title="Hapus">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
            <path d="M10 11v6M14 11v6"/>
          </svg>
        </button>
      </td>
    </tr>
  `).join('');
}

function updateStats(summary) {
  const { pendapatan, pengeluaran, laba, count_p, count_e, chart } = summary;
  const pct = pendapatan > 0 ? ((laba / pendapatan) * 100).toFixed(2) : '0.00';

  // Stat cards
  setText('stat-pendapatan', formatRp(pendapatan));
  setText('stat-pengeluaran', formatRp(pengeluaran));
  setText('stat-total-trx', count_p + count_e);
  setText('stat-count-p', `${count_p} transaksi`);
  setText('stat-count-e', `${count_e} transaksi`);
  setText('stat-updated', 'Update: ' + new Date().toLocaleTimeString('id-ID'));

  // Laba Bersih
  const labaEl = document.getElementById('stat-laba');
  labaEl.textContent = (laba < 0 ? '- ' : '') + formatRp(laba);
  labaEl.className = 'stat-value ' + (laba >= 0 ? 'text-success' : 'text-danger');

  setText('stat-pct', pct + '%');
  const pctBig = document.getElementById('stat-pct-big');
  pctBig.textContent = pct + '%';
  pctBig.className = 'laba-pct ' + (laba >= 0 ? 'text-success' : 'text-danger');

  const statusEl = document.getElementById('stat-status');
  const labaCard = document.getElementById('labaCard');
  if (laba > 0) {
    statusEl.className = 'laba-status status-profit';
    statusEl.textContent = '✅ Untung';
    labaCard.classList.remove('rugi');
  } else if (laba < 0) {
    statusEl.className = 'laba-status status-loss';
    statusEl.textContent = '⚠️ Rugi';
    labaCard.classList.add('rugi');
  } else {
    statusEl.className = 'laba-status status-even';
    statusEl.textContent = '— Break Even';
    labaCard.classList.remove('rugi');
  }

  // Badges
  setText('badge-count-p', count_p + ' data');
  setText('badge-count-e', count_e + ' data');

  // Chart
  updateChart(chart || []);
}

/* ── CHART ──────────────────────────────────────────────────── */
let financeChart = null;

function initChart() {
  const ctx = document.getElementById('financeChart').getContext('2d');
  financeChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [],
      datasets: [
        {
          label: 'Pendapatan',
          data: [],
          backgroundColor: 'rgba(16,185,129,0.75)',
          borderColor: '#10b981',
          borderWidth: 1,
          borderRadius: 6,
          borderSkipped: false,
        },
        {
          label: 'Pengeluaran',
          data: [],
          backgroundColor: 'rgba(239,68,68,0.75)',
          borderColor: '#ef4444',
          borderWidth: 1,
          borderRadius: 6,
          borderSkipped: false,
        },
        {
          label: 'Laba Bersih',
          data: [],
          type: 'line',
          backgroundColor: 'rgba(59,130,246,0.08)',
          borderColor: '#3b82f6',
          borderWidth: 2.5,
          pointBackgroundColor: '#3b82f6',
          pointRadius: 5,
          tension: 0.4,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#111827',
          borderColor: 'rgba(255,255,255,0.08)',
          borderWidth: 1,
          titleColor: '#e2e8f0',
          bodyColor: '#94a3b8',
          padding: 14,
          callbacks: {
            label: ctx => '  ' + ctx.dataset.label + ': ' + formatRp(ctx.raw),
          },
        },
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#64748b', font: { size: 11 } },
          border: { color: 'rgba(255,255,255,0.06)' },
        },
        y: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: {
            color: '#64748b',
            font: { size: 11 },
            callback: v =>
              v >= 1_000_000 ? 'Rp ' + (v/1_000_000).toFixed(1) + 'jt'
              : v >= 1_000   ? 'Rp ' + (v/1_000).toFixed(0) + 'rb'
              : 'Rp ' + v,
          },
          border: { color: 'rgba(255,255,255,0.06)' },
        },
      },
    },
  });
}

function updateChart(chartRows) {
  if (!financeChart) return;

  const bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

  // If no data: placeholder
  if (!chartRows.length) {
    financeChart.data.labels = ['—'];
    financeChart.data.datasets[0].data = [0];
    financeChart.data.datasets[1].data = [0];
    financeChart.data.datasets[2].data = [0];
    financeChart.update('active');
    return;
  }

  const labels = chartRows.map(r => {
    const [y, m] = r.bulan.split('-');
    return bln[parseInt(m) - 1] + ' ' + y;
  });

  financeChart.data.labels = labels;
  financeChart.data.datasets[0].data = chartRows.map(r => parseFloat(r.pendapatan));
  financeChart.data.datasets[1].data = chartRows.map(r => parseFloat(r.pengeluaran));
  financeChart.data.datasets[2].data = chartRows.map(r =>
    parseFloat(r.pendapatan) - parseFloat(r.pengeluaran)
  );
  financeChart.update('active');
}

/* ── REFRESH ALL ────────────────────────────────────────────── */
async function refreshAll() {
  try {
    const [summary, rowsP, rowsE] = await Promise.all([
      fetchSummary(),
      fetchList('pendapatan'),
      fetchList('pengeluaran'),
    ]);
    updateStats(summary);
    renderTable('pendapatan', rowsP);
    renderTable('pengeluaran', rowsE);
  } catch (err) {
    console.error('refreshAll error:', err);
  }
}

/* ── SIDEBAR TOGGLE (mobile) ────────────────────────────────── */
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

/* ── TOAST ──────────────────────────────────────────────────── */
function showToast(msg, tipe = 'info') {
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const container = document.getElementById('toastContainer');
  const el = document.createElement('div');
  el.className = `toast toast-${tipe}`;
  el.innerHTML = `<span>${icons[tipe] || 'ℹ️'}</span><span>${msg}</span>`;
  container.appendChild(el);
  setTimeout(() => {
    el.style.transition = 'all .3s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateX(30px)';
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

/* ── HELPERS ────────────────────────────────────────────────── */
function setText(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/* ── AUTO-REFRESH tiap 30 detik ─────────────────────────────── */
let refreshInterval = null;

/* ── INIT ────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initChart();
  refreshAll();
  refreshInterval = setInterval(refreshAll, 30_000); // auto-refresh 30s
});
