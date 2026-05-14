<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$user = exigirLogin();
$paramId = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <?php cfThemeHeadScript(); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ConectaFácil — Devoluções</title>
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-layout">
  <a href="#sidebar" class="sidebar-open-fab" aria-label="Abrir menu">☰</a>
  <aside class="sidebar" id="sidebar">
    <?php renderSidebar('devolucoes', $user); ?>
  </aside>
  <div class="main-content">
    <header class="page-header">
      <div>
        <div class="page-title">↩️ Devoluções</div>
        <div class="page-subtitle">Registre a devolução de aparelhos</div>
      </div>
    </header>
    <div class="page-body">
      <div id="alert-box"></div>
      <div class="grid-2">
        <!-- Lista de locações ativas -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Locações Ativas</div>
            <input class="search-input" style="width:160px" id="busca" placeholder="Buscar..." oninput="renderLista()">
          </div>
          <div class="card-body" style="padding:0">
            <div id="lista-locacoes"><div style="padding:20px;text-align:center;color:var(--text-muted)">Carregando...</div></div>
          </div>
        </div>

        <!-- Formulário de devolução -->
        <div class="card" id="card-form" style="display:none">
          <div class="card-header">
            <div class="card-title">Registrar Devolução</div>
          </div>
          <div class="card-body">
            <div id="form-error" class="alert alert-error" style="display:none"></div>
            <div id="info-locacao" style="margin-bottom:20px;padding:16px;background:var(--bg);border-radius:10px;border:1px solid var(--border)"></div>
            <div class="form-group">
              <label>Data de Devolução *</label>
              <input class="form-control" id="f-data-dev" type="date" onchange="calcularPreview()">
            </div>
            <div class="form-group">
              <label>Danos no Aparelho</label>
              <textarea class="form-control" id="f-danos" rows="3" placeholder="Descreva os danos (deixe em branco se não houver)..." oninput="calcularPreview()"></textarea>
            </div>
            <div class="form-group">
              <label>Multa por Dano (R$)</label>
              <input class="form-control" id="f-multa" type="number" min="0" step="0.01" placeholder="0,00" oninput="calcularPreview()">
            </div>
            <div id="preview-calculo" class="alert alert-warning" style="display:none"></div>
            <div class="modal-footer" style="padding:0;margin-top:16px">
              <button class="btn btn-ghost" onclick="fecharForm()">Cancelar</button>
              <button class="btn btn-success" onclick="confirmarDevolucao()">✓ Confirmar Devolução</button>
            </div>
          </div>
        </div>

        <div class="card" id="card-vazio" style="display:flex;align-items:center;justify-content:center">
          <div class="empty-state">
            <div class="empty-icon">↩️</div>
            <p>Selecione uma locação ativa à esquerda para registrar a devolução</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let locacaoSelecionada = null;
  const paramId = <?= $paramId ?>;

  function fmt(val) { return 'R$ ' + parseFloat(val||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
  function fmtData(str) { if(!str) return '—'; const [y,m,d]=str.split('-'); return `${d}/${m}/${y}`; }
  function hoje() { return new Date().toISOString().split('T')[0]; }

  function renderLista() {
    const busca = document.getElementById('busca').value;
    const params = new URLSearchParams({action:'listar', status:'ativa'});
    if (busca) params.append('busca', busca);

    fetch('../api/locacoes.php?' + params)
      .then(r => r.json())
      .then(locs => {
        const el = document.getElementById('lista-locacoes');
        const h  = hoje();
        if (!locs.length) {
          el.innerHTML = '<div class="empty-state"><div class="empty-icon">✅</div><p>Nenhuma locação ativa</p></div>';
          return;
        }
        el.innerHTML = locs.map(l => {
          const atrasado = l.data_fim_prevista < h;
          const sel = locacaoSelecionada && l.id === locacaoSelecionada.id;
          return `
            <div onclick="selecionarLocacao(${l.id})" style="padding:14px 20px;cursor:pointer;border-bottom:1px solid var(--border);background:${sel?'var(--primary-light)':'white'};transition:background 0.1s">
              <div class="flex justify-between items-center">
                <div>
                  <div class="font-bold" style="font-size:13px">${l.cliente_nome}</div>
                  <div class="text-muted text-sm">${l.modelo || '—'}</div>
                </div>
                <div style="text-align:right">
                  <div style="font-size:11px;color:${atrasado?'var(--danger)':'var(--text-muted)'}">
                    ${atrasado?'⚠️ Atrasado':'Previsto'}<br>${fmtData(l.data_fim_prevista)}
                  </div>
                </div>
              </div>
            </div>`;
        }).join('');
      });
  }

  function selecionarLocacao(id) {
    fetch('../api/locacoes.php?action=buscar&id=' + id)
      .then(r => r.json())
      .then(l => {
        locacaoSelecionada = l;
        renderLista();

        const dias = Math.max(1, Math.ceil((new Date(l.data_fim_prevista) - new Date(l.data_inicio)) / 86400000));
        document.getElementById('info-locacao').innerHTML = `
          <div class="grid-2" style="gap:8px">
            <div><div class="text-muted text-sm">Cliente</div><strong>${l.cliente_nome}</strong></div>
            <div><div class="text-muted text-sm">Dispositivo</div><strong>${l.modelo || '—'}</strong></div>
            <div><div class="text-muted text-sm">Início</div>${fmtData(l.data_inicio)}</div>
            <div><div class="text-muted text-sm">Devolução prevista</div>${fmtData(l.data_fim_prevista)}</div>
            <div><div class="text-muted text-sm">Diária</div>${fmt(l.valor_diaria)}</div>
            <div><div class="text-muted text-sm">Estimativa (${dias} dias)</div><strong>${fmt(dias * l.valor_diaria)}</strong></div>
          </div>`;

        document.getElementById('f-data-dev').value = hoje();
        document.getElementById('f-danos').value = '';
        document.getElementById('f-multa').value = '';
        document.getElementById('preview-calculo').style.display = 'none';
        document.getElementById('form-error').style.display = 'none';
        document.getElementById('card-vazio').style.display = 'none';
        document.getElementById('card-form').style.display = 'block';
        calcularPreview();
      });
  }

  function calcularPreview() {
    if (!locacaoSelecionada) return;
    const l       = locacaoSelecionada;
    const dataFim = document.getElementById('f-data-dev').value;
    const multa   = parseFloat(document.getElementById('f-multa').value) || 0;
    const prev    = document.getElementById('preview-calculo');
    if (!dataFim) { prev.style.display = 'none'; return; }

    const dias      = Math.max(1, Math.ceil((new Date(dataFim) - new Date(l.data_inicio)) / 86400000));
    const valorBase = dias * parseFloat(l.valor_diaria);

    // Multa de atraso (50% da diária por dia)
    let multaAtraso = 0;
    if (dataFim > l.data_fim_prevista) {
      const diasAtraso = Math.ceil((new Date(dataFim) - new Date(l.data_fim_prevista)) / 86400000);
      multaAtraso = diasAtraso * parseFloat(l.valor_diaria) * 0.5;
    }
    const total = valorBase + multaAtraso + multa;

    let html = `<strong>💰 Cálculo de Valores:</strong><br>`;
    html += `${dias} dia${dias>1?'s':''} × ${fmt(l.valor_diaria)} = ${fmt(valorBase)}<br>`;
    if (multaAtraso > 0) html += `⚠️ Multa de atraso: +${fmt(multaAtraso)}<br>`;
    if (multa > 0) html += `🔧 Multa por dano: +${fmt(multa)}<br>`;
    html += `<strong>Total: ${fmt(total)}</strong>`;

    prev.innerHTML = html;
    prev.style.display = 'block';
  }

  function fecharForm() {
    locacaoSelecionada = null;
    renderLista();
    document.getElementById('card-form').style.display = 'none';
    document.getElementById('card-vazio').style.display = 'flex';
  }

  function confirmarDevolucao() {
    const dataFim = document.getElementById('f-data-dev').value;
    const danos   = document.getElementById('f-danos').value.trim();
    const multa   = parseFloat(document.getElementById('f-multa').value) || 0;
    const errEl   = document.getElementById('form-error');

    if (!dataFim) { errEl.textContent = 'Informe a data de devolução.'; errEl.style.display='block'; return; }

    fetch('../api/locacoes.php?action=devolver', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({id: locacaoSelecionada.id, dataFimReal: dataFim, danos, multaDano: multa})
    })
    .then(r => r.json())
    .then(res => {
      if (res.erro) { errEl.textContent = res.erro; errEl.style.display='block'; return; }
      mostrarAlerta(`Devolução registrada! Valor total: ${fmt(res.valorTotal)}`, 'success');
      fecharForm();
    });
  }

  function mostrarAlerta(msg, tipo) {
    const el = document.getElementById('alert-box');
    el.innerHTML = `<div class="alert alert-${tipo==='error'?'error':'success'}">${msg}</div>`;
    setTimeout(() => el.innerHTML = '', 5000);
  }

  renderLista();
  if (paramId) {
    setTimeout(() => selecionarLocacao(paramId), 300);
  }
</script>
</body>
</html>
