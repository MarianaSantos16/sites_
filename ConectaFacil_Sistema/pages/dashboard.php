<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$user = exigirLogin();
$partesNome = preg_split('/\s+/', trim($user['nome'] ?? ''), 2, PREG_SPLIT_NO_EMPTY);
$primeiroNome = $partesNome[0] ?? 'Olá';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-icons-base="<?= htmlspecialchars(cf_icons_base_path(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <?php cfThemeHeadScript(); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ConectaFácil — Dashboard</title>
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-layout">
  <a href="#sidebar" class="sidebar-open-fab" aria-label="Abrir menu"><img src="../icons/menu.svg" class="sidebar-fab-icon" width="22" height="22" alt=""></a>
  <aside class="sidebar" id="sidebar"><?php renderSidebar('dashboard', $user); ?></aside>
  <div class="main-content">

    <header class="page-header">
      <div>
        <div class="page-title">Dashboard</div>
        <div class="page-subtitle" id="data-hora"></div>
      </div>
      <div class="flex gap-2 items-center">
        <span id="badge-ativas" class="badge badge-warning" style="display:none"></span>
        <a href="locacoes.php" class="btn btn-primary btn-sm">+ Nova Locação</a>
      </div>
    </header>

    <div class="page-body">

      <section class="hero-panel" aria-label="Boas-vindas">
        <div class="hero-panel-main">
          <p class="hero-kicker">Visão geral</p>
          <h1 class="hero-heading">Olá, <?= htmlspecialchars($primeiroNome) ?> — tudo sob controle</h1>
          <p class="hero-lead">Acompanhe aparelhos disponíveis, locações ativas e receita do mês em um só lugar.</p>
        </div>
        <div class="hero-panel-accent" aria-hidden="true"><?= cf_icon_img('phone.svg', 'hero-panel-icon', 56, 56) ?></div>
      </section>

      <!-- Stats -->
      <div class="stats-grid" id="stats-grid">
        <?php for($i=0;$i<4;$i++): ?>
        <div class="stat-card">
          <div class="stat-top">
            <div class="stat-icon-wrap" style="background:var(--bg-2)"><?= cf_icon_img('clock.svg', 'stat-icon-img', 22, 22) ?></div>
          </div>
          <div class="stat-value" style="color:var(--border)">—</div>
          <div class="stat-label">Carregando...</div>
        </div>
        <?php endfor; ?>
      </div>

      <!-- Tabelas -->
      <div class="grid-2" style="gap:20px">
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Locações Ativas</div>
              <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Acompanhamento em tempo real</div>
            </div>
            <a href="locacoes.php" class="btn btn-neutral btn-sm">Ver todas →</a>
          </div>
          <div class="table-wrap">
            <table id="tbl-locacoes-ativas">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Aparelho</th>
                  <th>Devolução</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--text-muted)">Carregando...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Aparelhos Disponíveis</div>
              <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Prontos para locação</div>
            </div>
            <a href="consulta.php" class="btn btn-neutral btn-sm">Ver todos →</a>
          </div>
          <div class="table-wrap">
            <table id="tbl-disponiveis">
              <thead>
                <tr><th>Modelo</th><th>Marca</th><th>Diária</th></tr>
              </thead>
              <tbody>
                <tr><td colspan="3" style="text-align:center;padding:32px;color:var(--text-muted)">Carregando...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
const IC = '../icons/';
const ic = (name, cls = 'stat-icon-img', w = 22, h = 22) =>
  `<img src="${IC}${name}" class="${cls}" width="${w}" height="${h}" alt="">`;
const fmt = v => 'R$ ' + parseFloat(v||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
const fmtD = s => { if(!s) return '—'; const [y,m,d]=s.split('-'); return `${d}/${m}/${y}`; };

function atualizarHora() {
  const n = new Date();
  document.getElementById('data-hora').textContent =
    n.toLocaleDateString('pt-BR',{weekday:'long',day:'2-digit',month:'long',year:'numeric'}) +
    ' · ' + n.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
}
atualizarHora(); setInterval(atualizarHora, 30000);

fetch('../api/dashboard.php')
  .then(r => r.json())
  .then(d => {
    const hoje = d.hoje;

    // Badge de ativas
    if (d.locados > 0) {
      const b = document.getElementById('badge-ativas');
      b.textContent = d.locados + ' locaç' + (d.locados>1?'ões':'ão') + ' ativa' + (d.locados>1?'s':'');
      b.style.display = 'inline-flex';
    }

    document.getElementById('stats-grid').innerHTML = `
      <div class="stat-card blue">
        <div class="stat-top">
          <div class="stat-icon-wrap" style="background:#EEF2FF">${ic('phone.svg')}</div>
          <span class="stat-trend up">+${d.disponiveis}</span>
        </div>
        <div class="stat-value" style="color:#6366F1">${d.disponiveis}</div>
        <div class="stat-label">Disponíveis para locação</div>
      </div>
      <div class="stat-card amber">
        <div class="stat-top">
          <div class="stat-icon-wrap" style="background:#FFFBEB">${ic('clock.svg')}</div>
        </div>
        <div class="stat-value" style="color:#D97706">${d.locados}</div>
        <div class="stat-label">Aparelhos locados</div>
      </div>
      <div class="stat-card red">
        <div class="stat-top">
          <div class="stat-icon-wrap" style="background:#FEF2F2">${ic('wrench.svg')}</div>
        </div>
        <div class="stat-value" style="color:#EF4444">${d.manutencao}</div>
        <div class="stat-label">Em manutenção</div>
      </div>
      <div class="stat-card green">
        <div class="stat-top">
          <div class="stat-icon-wrap" style="background:#ECFDF5">${ic('money.svg')}</div>
          <span class="stat-trend up">mês</span>
        </div>
        <div class="stat-value" style="color:#10B981;font-size:20px">${fmt(d.receitaMes)}</div>
        <div class="stat-label">Receita do mês</div>
      </div>
    `;

    // Locações ativas
    const tbL = document.querySelector('#tbl-locacoes-ativas tbody');
    if (!d.locacoesAtivas.length) {
      tbL.innerHTML = '<tr><td colspan="4"><div class="empty-state"><span class="empty-icon">' + ic('check.svg','empty-icon-img',40,40) + '</span><p>Nenhuma locação ativa</p></div></td></tr>';
    } else {
      tbL.innerHTML = d.locacoesAtivas.map(l => {
        const atrasado = l.data_fim_prevista < hoje;
        return `<tr>
          <td><strong style="color:var(--text)">${l.cliente_nome}</strong></td>
          <td style="color:var(--text-muted)">${l.modelo||'—'}</td>
          <td style="color:${atrasado?'var(--danger)':'var(--text-2)'}">
            ${fmtD(l.data_fim_prevista)}${atrasado?' <span class="badge badge-danger" style="font-size:10px">Atrasado</span>':''}
          </td>
          <td><span class="badge badge-warning">Ativa</span></td>
        </tr>`;
      }).join('');
    }

    // Disponíveis
    const tbD = document.querySelector('#tbl-disponiveis tbody');
    if (!d.disponivelLista.length) {
      tbD.innerHTML = '<tr><td colspan="3"><div class="empty-state"><span class="empty-icon">' + ic('inbox.svg','empty-icon-img',40,40) + '</span><p>Nenhum disponível</p></div></td></tr>';
    } else {
      tbD.innerHTML = d.disponivelLista.map(x => `
        <tr>
          <td><strong style="color:var(--text)">${x.modelo}</strong></td>
          <td><span class="badge badge-muted">${x.marca}</span></td>
          <td style="color:var(--primary);font-weight:700">${fmt(x.diaria)}<span style="color:var(--text-muted);font-weight:400">/dia</span></td>
        </tr>`).join('');
    }
  })
  .catch(() => {
    document.getElementById('stats-grid').innerHTML =
      '<div class="alert alert-error" style="grid-column:1/-1">Erro ao carregar dados da API.</div>';
  });
</script>
</body>
</html>
