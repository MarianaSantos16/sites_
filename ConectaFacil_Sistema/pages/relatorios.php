<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$user = exigirAdmin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <?php cfThemeHeadScript(); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ConectaFácil — Relatórios</title>
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-layout">
  <a href="#sidebar" class="sidebar-open-fab" aria-label="Abrir menu">☰</a>
  <aside class="sidebar" id="sidebar">
    <?php renderSidebar('relatorios', $user); ?>
  </aside>
  <div class="main-content">
    <header class="page-header">
      <div>
        <div class="page-title">📊 Relatórios</div>
        <div class="page-subtitle">Análise financeira e operacional</div>
      </div>
      <button class="btn btn-ghost" onclick="window.print()">🖨️ Imprimir</button>
    </header>
    <div class="page-body">

      <!-- Período -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-body">
          <div class="flex gap-3 items-center" style="flex-wrap:wrap">
            <div class="form-group" style="margin:0">
              <label>Período — De</label>
              <input class="form-control" type="date" id="f-de" style="width:160px">
            </div>
            <div class="form-group" style="margin:0">
              <label>Até</label>
              <input class="form-control" type="date" id="f-ate" style="width:160px">
            </div>
            <div style="margin-top:20px">
              <button class="btn btn-primary" onclick="gerarRelatorio()">Gerar Relatório</button>
            </div>
          </div>
        </div>
      </div>

      <!-- KPIs -->
      <div class="stats-grid" id="kpis" style="margin-bottom:20px"></div>

      <div class="grid-2">
        <div class="card">
          <div class="card-header"><div class="card-title">Locações Concluídas</div></div>
          <div class="card-body">
            <div class="table-wrap">
              <table>
                <thead><tr><th>Cliente</th><th>Aparelho</th><th>Dias</th><th>Total</th></tr></thead>
                <tbody id="tbl-concluidas"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title">Aparelhos Mais Locados</div></div>
          <div class="card-body">
            <div id="ranking-aparelhos"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function fmt(val) { return 'R$ ' + parseFloat(val||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
  function fmtData(str) { if(!str) return '—'; const [y,m,d]=str.split('-'); return `${d}/${m}/${y}`; }

  // Período padrão: mês atual
  const hoje = new Date().toISOString().split('T')[0];
  document.getElementById('f-ate').value = hoje;
  document.getElementById('f-de').value  = hoje.substring(0,7) + '-01';

  function gerarRelatorio() {
    const de  = document.getElementById('f-de').value;
    const ate = document.getElementById('f-ate').value;

    fetch(`../api/relatorios.php?de=${de}&ate=${ate}`)
      .then(r => r.json())
      .then(data => {
        document.getElementById('kpis').innerHTML = `
          <div class="stat-card">
            <div class="stat-icon" style="background:#E8F0FF">💰</div>
            <div><div class="stat-value" style="color:var(--primary);font-size:18px">${fmt(data.receita)}</div><div class="stat-label">Receita no período</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background:#FEE2E2">⚠️</div>
            <div><div class="stat-value" style="color:var(--danger);font-size:18px">${fmt(data.multas)}</div><div class="stat-label">Total em multas</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background:#E6F9F0">📋</div>
            <div><div class="stat-value" style="color:var(--success)">${data.concluidas}</div><div class="stat-label">Locações concluídas</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background:#FEF3C7">🎟️</div>
            <div><div class="stat-value" style="color:var(--warning);font-size:18px">${fmt(data.ticket)}</div><div class="stat-label">Ticket médio</div></div>
          </div>
        `;

        const tbody = document.getElementById('tbl-concluidas');
        if (!data.lista.length) {
          tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px">Nenhuma locação concluída no período</td></tr>';
        } else {
          tbody.innerHTML = data.lista.map(l => {
            const dias = Math.max(1, Math.ceil((new Date(l.data_fim_real) - new Date(l.data_inicio)) / 86400000));
            return `<tr>
              <td>${l.cliente_nome}</td>
              <td>${l.modelo || '—'}</td>
              <td>${dias}</td>
              <td><strong>${fmt(l.valor_total)}</strong></td>
            </tr>`;
          }).join('');
        }

        const maxVal = data.ranking[0]?.total || 1;
        document.getElementById('ranking-aparelhos').innerHTML = !data.ranking.length
          ? '<div class="empty-state"><div class="empty-icon">📊</div><p>Sem dados</p></div>'
          : data.ranking.map((r, i) => `
            <div style="margin-bottom:14px">
              <div class="flex justify-between" style="margin-bottom:4px">
                <span style="font-size:13px;font-weight:600">${i+1}. ${r.modelo || '—'}</span>
                <span class="text-muted text-sm">${r.total} locaç${r.total>1?'ões':'ão'}</span>
              </div>
              <div style="height:6px;background:var(--bg);border-radius:4px;overflow:hidden">
                <div style="height:100%;width:${(r.total/maxVal*100)}%;background:var(--primary);border-radius:4px;transition:width 0.5s"></div>
              </div>
            </div>`).join('');
      });
  }

  gerarRelatorio();
</script>
</body>
</html>
