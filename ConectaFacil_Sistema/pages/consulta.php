<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$user = exigirLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <?php cfThemeHeadScript(); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ConectaFácil — Consultar Aparelhos</title>
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-layout">
  <a href="#sidebar" class="sidebar-open-fab" aria-label="Abrir menu">☰</a>
  <aside class="sidebar" id="sidebar">
    <?php renderSidebar('consulta', $user); ?>
  </aside>
  <div class="main-content">
    <header class="page-header">
      <div>
        <div class="page-title">🔍 Consultar Aparelhos</div>
        <div class="page-subtitle">Veja a disponibilidade de todos os dispositivos</div>
      </div>
    </header>
    <div class="page-body">
      <div class="card" style="margin-bottom:24px">
        <div class="card-body">
          <div class="flex gap-3 items-center" style="flex-wrap:wrap">
            <input class="search-input" style="flex:1;min-width:200px" id="busca" placeholder="Buscar modelo ou marca..." oninput="renderCards()">
            <button class="btn btn-sm btn-primary"  id="btn-todos"       onclick="setFiltro('',this)">Todos</button>
            <button class="btn btn-sm btn-ghost"    id="btn-disponivel"  onclick="setFiltro('disponivel',this)">✓ Disponíveis</button>
            <button class="btn btn-sm btn-ghost"    id="btn-locado"      onclick="setFiltro('locado',this)">⏳ Locados</button>
            <button class="btn btn-sm btn-ghost"    id="btn-manutencao"  onclick="setFiltro('manutencao',this)">🔧 Manutenção</button>
          </div>
        </div>
      </div>
      <div id="cards-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
        <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)">Carregando...</div>
      </div>
    </div>
  </div>
</div>

<script>
  let filtroAtual = '';

  function fmt(val) { return 'R$ ' + parseFloat(val||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
  function fmtData(str) { if(!str) return '—'; const [y,m,d]=str.split('-'); return `${d}/${m}/${y}`; }

  function setFiltro(f, btn) {
    filtroAtual = f;
    document.querySelectorAll('.btn-sm').forEach(b => { b.classList.remove('btn-primary'); b.classList.add('btn-ghost'); });
    btn.classList.remove('btn-ghost');
    btn.classList.add('btn-primary');
    renderCards();
  }

  function renderCards() {
    const busca  = document.getElementById('busca').value;
    const params = new URLSearchParams({action:'listar'});
    if (busca)        params.append('busca', busca);
    if (filtroAtual)  params.append('status', filtroAtual);

    fetch('../api/dispositivos.php?' + params)
      .then(r => r.json())
      .then(disps => {
        const grid = document.getElementById('cards-grid');
        if (!disps.length) {
          grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">📭</div><p>Nenhum aparelho encontrado</p></div>';
          return;
        }

        // Busca locações ativas para mostrar info do cliente
        fetch('../api/locacoes.php?action=listar&status=ativa')
          .then(r => r.json())
          .then(locs => {
            const statusColors = {
              disponivel: {bg:'#E6F9F0', color:'#00864A', icon:'✓'},
              locado:     {bg:'#FEF3C7', color:'#92600A', icon:'⏳'},
              manutencao: {bg:'#FEE2E2', color:'#B91C1C', icon:'🔧'},
            };

            grid.innerHTML = disps.map(d => {
              const sc = statusColors[d.status] || statusColors.manutencao;
              const locAtiva = locs.find(l => l.dispositivo_id == d.id);
              const label = d.status.charAt(0).toUpperCase() + d.status.slice(1);

              let infoExtra = '';
              if (locAtiva) {
                infoExtra = `
                  <div style="margin-top:12px;padding:10px 12px;background:var(--bg);border-radius:8px;border:1px solid var(--border);font-size:12px">
                    <div class="text-muted">Locado para:</div>
                    <div class="font-bold">${locAtiva.cliente_nome}</div>
                    <div class="text-muted">Devolução: ${fmtData(locAtiva.data_fim_prevista)}</div>
                  </div>`;
              }

              return `
                <div class="card" style="overflow:hidden">
                  <div style="height:6px;background:${sc.bg};border-bottom:2px solid ${sc.color}22"></div>
                  <div class="card-body">
                    <div class="flex justify-between items-center" style="margin-bottom:10px">
                      <div>
                        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:15px">${d.modelo}</div>
                        <div class="text-muted text-sm">${d.marca}</div>
                      </div>
                      <span style="background:${sc.bg};color:${sc.color};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700">${sc.icon} ${label}</span>
                    </div>
                    <div class="grid-2" style="gap:6px;font-size:12px;margin-bottom:12px">
                      <div><span class="text-muted">Cor:</span> ${d.cor||'—'}</div>
                      <div><span class="text-muted">Storage:</span> ${d.armazenamento||'—'}</div>
                      ${d.imei ? `<div style="grid-column:1/-1"><span class="text-muted">IMEI:</span> ${d.imei}</div>` : ''}
                    </div>
                    <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--primary)">
                      ${fmt(d.diaria)}<span style="font-size:13px;font-weight:400;color:var(--text-muted)">/dia</span>
                    </div>
                    ${infoExtra}
                    ${d.status==='disponivel'?`<a href="locacoes.php" class="btn btn-primary btn-full" style="margin-top:14px;font-size:12px">+ Criar Locação</a>`:''}
                  </div>
                </div>`;
            }).join('');
          });
      });
  }

  renderCards();
</script>
</body>
</html>
