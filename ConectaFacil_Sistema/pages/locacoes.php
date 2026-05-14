<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$user = exigirLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-icons-base="<?= htmlspecialchars(cf_icons_base_path(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <?php cfThemeHeadScript(); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ConectaFácil — Locações</title>
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-layout">
  <a href="#sidebar" class="sidebar-open-fab" aria-label="Abrir menu"><img src="../icons/menu.svg" class="sidebar-fab-icon" width="22" height="22" alt=""></a>
  <aside class="sidebar" id="sidebar">
    <?php renderSidebar('locacoes', $user); ?>
  </aside>
  <div class="main-content">
    <header class="page-header">
      <div>
        <div class="page-title"><?= cf_icon_img('clipboard.svg', 'page-title-icon', 24, 24) ?><span>Locações</span></div>
        <div class="page-subtitle">Registre e acompanhe as locações</div>
      </div>
      <button class="btn btn-primary" onclick="abrirModal()">+ Nova Locação</button>
    </header>
    <div class="page-body">
      <div id="alert-box"></div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">Todas as Locações</div>
          <div class="flex gap-2">
            <input class="search-input" style="width:200px" id="busca" placeholder="Buscar cliente..." oninput="renderTabela()">
            <select class="form-control" style="width:130px" id="filtro" onchange="renderTabela()">
              <option value="">Todos</option>
              <option value="ativa">Ativas</option>
              <option value="concluida">Concluídas</option>
            </select>
          </div>
        </div>
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>#</th><th>Cliente</th><th>Aparelho</th><th>Início</th><th>Devolução Prev.</th><th>Valor Est.</th><th>Status</th><th>Ações</th></tr>
              </thead>
              <tbody id="tbody"><tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted)">Carregando...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nova Locação -->
<div class="modal-overlay" id="modal" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Nova Locação</div>
      <button class="modal-close" onclick="fecharModal()"><?= cf_icon_img('x.svg', 'modal-close-icon', 14, 14, 'Fechar') ?></button>
    </div>
    <div class="modal-body">
      <div id="modal-error" class="alert alert-error" style="display:none"></div>
      <div class="form-group">
        <label>Nome do Cliente *</label>
        <input class="form-control" id="f-nome" placeholder="Nome ou Razão Social">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>CPF / CNPJ *</label>
          <input class="form-control" id="f-doc" placeholder="000.000.000-00">
        </div>
        <div class="form-group">
          <label>Telefone *</label>
          <input class="form-control" id="f-tel" placeholder="(00) 00000-0000">
        </div>
      </div>
      <div class="form-group">
        <label>Dispositivo *</label>
        <select class="form-control" id="f-disp" onchange="atualizarPreview()">
          <option value="">— Selecione um aparelho disponível —</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Data de Início *</label>
          <input class="form-control" id="f-inicio" type="date" onchange="atualizarPreview()">
        </div>
        <div class="form-group">
          <label>Data de Devolução Prevista *</label>
          <input class="form-control" id="f-fim" type="date" onchange="atualizarPreview()">
        </div>
      </div>
      <div class="form-group">
        <label>Observações</label>
        <textarea class="form-control" id="f-obs" rows="2" placeholder="Informações adicionais..."></textarea>
      </div>
      <div class="alert alert-warning" id="preview-valor" style="display:none"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="fecharModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="salvar()">Registrar Locação</button>
    </div>
  </div>
</div>

<!-- Modal Detalhe -->
<div class="modal-overlay" id="modal-detalhe" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Detalhes da Locação</div>
      <button class="modal-close" onclick="fecharDetalhe()"><?= cf_icon_img('x.svg', 'modal-close-icon', 14, 14, 'Fechar') ?></button>
    </div>
    <div class="modal-body" id="detalhe-body"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="fecharDetalhe()">Fechar</button>
    </div>
  </div>
</div>

<script>
  const IC = '../icons/';
  const ic = (name, cls = 'btn-icon-img', w = 17, h = 17) =>
    `<img src="${IC}${name}" class="${cls}" width="${w}" height="${h}" alt="">`;
  const icBadge = (name) =>
    `<img src="${IC}${name}" class="badge-icon-img" width="13" height="13" alt="">`;

  function fmt(val) { return 'R$ ' + parseFloat(val||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
  function fmtData(str) { if(!str) return '—'; const [y,m,d]=str.split('-'); return `${d}/${m}/${y}`; }
  function hoje() { return new Date().toISOString().split('T')[0]; }

  function statusBadge(s) {
    const m = {
      ativa: `<span class="badge badge-warning">${icBadge('clock.svg')}Ativa</span>`,
      concluida: `<span class="badge badge-success">${icBadge('check.svg')}Concluída</span>`,
      cancelada: `<span class="badge badge-muted">${icBadge('x.svg')}Cancelada</span>`
    };
    return m[s]||s;
  }

  function renderTabela() {
    const busca  = document.getElementById('busca').value;
    const filtro = document.getElementById('filtro').value;
    const params = new URLSearchParams({action:'listar'});
    if (busca)  params.append('busca', busca);
    if (filtro) params.append('status', filtro);

    fetch('../api/locacoes.php?' + params)
      .then(r => r.json())
      .then(locs => {
        const h = hoje();
        const tbody = document.getElementById('tbody');
        if (!locs.length) {
          tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><span class="empty-icon">' + ic('inbox.svg', 'empty-icon-img', 40, 40) + '</span><p>Nenhuma locação encontrada</p></div></td></tr>';
          return;
        }
        tbody.innerHTML = locs.map(l => {
          const atrasado = l.status === 'ativa' && l.data_fim_prevista < h;
          const dias = Math.max(1, Math.ceil((new Date(l.data_fim_prevista) - new Date(l.data_inicio)) / 86400000));
          const valorEst = l.valor_total != null ? fmt(l.valor_total) : fmt(dias * l.valor_diaria);
          return `<tr>
            <td class="text-muted">#${l.id}</td>
            <td><strong>${l.cliente_nome}</strong><br><span class="text-muted text-sm">${l.cliente_cpfcnpj}</span></td>
            <td>${l.modelo || '—'}</td>
            <td>${fmtData(l.data_inicio)}</td>
            <td style="color:${atrasado?'var(--danger)':'inherit'}">${fmtData(l.data_fim_prevista)}${atrasado?' ' + ic('warning.svg','table-warn-icon',14,14) : ''}</td>
            <td>${valorEst}</td>
            <td>${statusBadge(l.status)}</td>
            <td>
              <button type="button" class="btn-icon" onclick="verDetalhe(${l.id})" title="Detalhes">${ic('eye.svg')}</button>
              ${l.status==='ativa'?`<a class="btn-icon" href="devolucoes.php?id=${l.id}" title="Registrar devolução">${ic('reply.svg')}</a>`:''}
            </td>
          </tr>`;
        }).join('');
      });
  }

  function preencherDispositivosSelect() {
    fetch('../api/dispositivos.php?action=listar&status=disponivel')
      .then(r => r.json())
      .then(disps => {
        const sel = document.getElementById('f-disp');
        sel.innerHTML = '<option value="">— Selecione um aparelho disponível —</option>' +
          disps.map(d => `<option value="${d.id}" data-diaria="${d.diaria}">${d.modelo} (${d.marca}) — ${fmt(d.diaria)}/dia</option>`).join('');
      });
  }

  function atualizarPreview() {
    const sel    = document.getElementById('f-disp');
    const opt    = sel.options[sel.selectedIndex];
    const diaria = opt ? parseFloat(opt.dataset.diaria) : 0;
    const inicio = document.getElementById('f-inicio').value;
    const fim    = document.getElementById('f-fim').value;
    const prev   = document.getElementById('preview-valor');
    if (diaria && inicio && fim && fim > inicio) {
      const dias = Math.max(1, Math.ceil((new Date(fim) - new Date(inicio)) / 86400000));
      const val  = dias * diaria;
      prev.textContent = `💰 Valor estimado: ${fmt(val)} (${dias} dia${dias>1?'s':''} × ${fmt(diaria)})`;
      prev.style.display = 'block';
    } else {
      prev.style.display = 'none';
    }
  }

  function abrirModal() {
    preencherDispositivosSelect();
    ['f-nome','f-doc','f-tel','f-obs'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('f-inicio').value = hoje();
    document.getElementById('f-fim').value = '';
    document.getElementById('preview-valor').style.display = 'none';
    document.getElementById('modal-error').style.display = 'none';
    document.getElementById('modal').style.display = 'flex';
  }

  function fecharModal() { document.getElementById('modal').style.display = 'none'; }

  function salvar() {
    const errEl = document.getElementById('modal-error');
    const payload = {
      clienteNome:     document.getElementById('f-nome').value.trim(),
      clienteCpfcnpj:  document.getElementById('f-doc').value.trim(),
      clienteTelefone: document.getElementById('f-tel').value.trim(),
      dispositivoId:   parseInt(document.getElementById('f-disp').value),
      dataInicio:      document.getElementById('f-inicio').value,
      dataFimPrevista: document.getElementById('f-fim').value,
      observacoes:     document.getElementById('f-obs').value.trim(),
    };

    fetch('../api/locacoes.php?action=criar', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)})
      .then(r => r.json())
      .then(res => {
        if (res.erro) { errEl.textContent = res.erro; errEl.style.display = 'block'; return; }
        fecharModal();
        renderTabela();
        mostrarAlerta('Locação registrada com sucesso!', 'success');
      });
  }

  function verDetalhe(id) {
    fetch('../api/locacoes.php?action=buscar&id=' + id)
      .then(r => r.json())
      .then(l => {
        document.getElementById('detalhe-body').innerHTML = `
          <div class="grid-2" style="gap:12px;margin-bottom:16px">
            <div><div class="text-muted text-sm">Cliente</div><div class="font-bold">${l.cliente_nome}</div></div>
            <div><div class="text-muted text-sm">CPF/CNPJ</div><div>${l.cliente_cpfcnpj}</div></div>
            <div><div class="text-muted text-sm">Telefone</div><div>${l.cliente_telefone}</div></div>
            <div><div class="text-muted text-sm">Dispositivo</div><div>${l.modelo ? l.modelo+' ('+l.marca+')' : '—'}</div></div>
            <div><div class="text-muted text-sm">Início</div><div>${fmtData(l.data_inicio)}</div></div>
            <div><div class="text-muted text-sm">Devolução prevista</div><div>${fmtData(l.data_fim_prevista)}</div></div>
            <div><div class="text-muted text-sm">Devolução real</div><div>${fmtData(l.data_fim_real)}</div></div>
            <div><div class="text-muted text-sm">Diária</div><div>${fmt(l.valor_diaria)}</div></div>
            ${l.multa_atraso > 0 ? `<div><div class="text-muted text-sm">Multa de atraso</div><div style="color:var(--danger)">${fmt(l.multa_atraso)}</div></div>` : ''}
            ${l.multa_dano > 0 ? `<div><div class="text-muted text-sm">Multa de dano</div><div style="color:var(--danger)">${fmt(l.multa_dano)}</div></div>` : ''}
            ${l.valor_total != null ? `<div><div class="text-muted text-sm">Valor Total</div><div class="font-bold" style="color:var(--success)">${fmt(l.valor_total)}</div></div>` : ''}
            <div><div class="text-muted text-sm">Status</div><div>${statusBadge(l.status)}</div></div>
          </div>
          ${l.observacoes ? `<div class="form-group"><label>Observações</label><div style="color:var(--text-muted)">${l.observacoes}</div></div>` : ''}
          ${l.danos ? `<div class="alert alert-error">${ic('warning.svg','alert-warn-icon',18,18)} Danos: ${l.danos}</div>` : ''}
        `;
        document.getElementById('modal-detalhe').style.display = 'flex';
      });
  }

  function fecharDetalhe() { document.getElementById('modal-detalhe').style.display = 'none'; }

  function mostrarAlerta(msg, tipo) {
    const el = document.getElementById('alert-box');
    el.innerHTML = `<div class="alert alert-${tipo==='error'?'error':'success'}">${msg}</div>`;
    setTimeout(() => el.innerHTML = '', 3500);
  }

  renderTabela();
</script>
</body>
</html>
