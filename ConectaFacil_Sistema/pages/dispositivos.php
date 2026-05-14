<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$user = exigirAdmin();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-icons-base="<?= htmlspecialchars(cf_icons_base_path(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <?php cfThemeHeadScript(); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ConectaFácil — Dispositivos</title>
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-layout">
  <a href="#sidebar" class="sidebar-open-fab" aria-label="Abrir menu"><img src="../icons/menu.svg" class="sidebar-fab-icon" width="22" height="22" alt=""></a>
  <aside class="sidebar" id="sidebar">
    <?php renderSidebar('dispositivos', $user); ?>
  </aside>
  <div class="main-content">
    <header class="page-header">
      <div>
        <div class="page-title"><?= cf_icon_img('phone.svg', 'page-title-icon', 24, 24) ?><span>Dispositivos</span></div>
        <div class="page-subtitle">Gerencie o cadastro de aparelhos</div>
      </div>
      <button class="btn btn-primary" onclick="abrirModal()">+ Novo Dispositivo</button>
    </header>
    <div class="page-body">
      <div id="alert-box"></div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">Todos os Dispositivos</div>
          <div class="search-bar" style="margin:0">
            <input class="search-input" style="min-width:220px" id="busca" placeholder="Buscar modelo, marca..." oninput="renderTabela()">
            <select class="form-control" style="width:140px" id="filtro-status" onchange="renderTabela()">
              <option value="">Todos status</option>
              <option value="disponivel">Disponível</option>
              <option value="locado">Locado</option>
              <option value="manutencao">Manutenção</option>
            </select>
          </div>
        </div>
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>#</th><th>Modelo</th><th>Marca</th><th>Cor / Armazenamento</th><th>Diária</th><th>Status</th><th>Ações</th></tr>
              </thead>
              <tbody id="tbody"><tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted)">Carregando...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modal" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Novo Dispositivo</div>
      <button class="modal-close" onclick="fecharModal()"><?= cf_icon_img('x.svg', 'modal-close-icon', 14, 14, 'Fechar') ?></button>
    </div>
    <div class="modal-body">
      <div id="modal-error" class="alert alert-error" style="display:none"></div>
      <div class="form-row">
        <div class="form-group">
          <label>Modelo *</label>
          <input class="form-control" id="f-modelo" placeholder="Ex: Galaxy S23">
        </div>
        <div class="form-group">
          <label>Marca *</label>
          <input class="form-control" id="f-marca" placeholder="Ex: Samsung">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Cor</label>
          <input class="form-control" id="f-cor" placeholder="Ex: Preto">
        </div>
        <div class="form-group">
          <label>Armazenamento</label>
          <input class="form-control" id="f-armazenamento" placeholder="Ex: 128GB">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Valor da Diária (R$) *</label>
          <input class="form-control" id="f-diaria" type="number" min="0" step="0.01" placeholder="0,00">
        </div>
        <div class="form-group">
          <label>Status *</label>
          <select class="form-control" id="f-status">
            <option value="disponivel">Disponível</option>
            <option value="manutencao">Manutenção</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>IMEI</label>
        <input class="form-control" id="f-imei" placeholder="IMEI do dispositivo">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="fecharModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="salvar()">Salvar</button>
    </div>
  </div>
</div>

<script>
  const IC = '../icons/';
  const ic = (name, cls = 'btn-icon-img', w = 17, h = 17) =>
    `<img src="${IC}${name}" class="${cls}" width="${w}" height="${h}" alt="">`;
  const icBadge = (name) =>
    `<img src="${IC}${name}" class="badge-icon-img" width="13" height="13" alt="">`;

  let editId = null;

  function fmt(val) { return 'R$ ' + parseFloat(val||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }

  function statusBadge(s) {
    const m = {
      disponivel: `<span class="badge badge-success">${icBadge('check.svg')}Disponível</span>`,
      locado: `<span class="badge badge-warning">${icBadge('clock.svg')}Locado</span>`,
      manutencao: `<span class="badge badge-danger">${icBadge('wrench.svg')}Manutenção</span>`
    };
    return m[s]||s;
  }

  function renderTabela() {
    const busca  = document.getElementById('busca').value;
    const filtro = document.getElementById('filtro-status').value;
    const params = new URLSearchParams({action:'listar'});
    if (busca)  params.append('busca', busca);
    if (filtro) params.append('status', filtro);

    fetch('../api/dispositivos.php?' + params)
      .then(r => r.json())
      .then(disps => {
        const tbody = document.getElementById('tbody');
        if (!disps.length) {
          tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><span class="empty-icon">' + ic('inbox.svg', 'empty-icon-img', 40, 40) + '</span><p>Nenhum dispositivo encontrado</p></div></td></tr>';
          return;
        }
        tbody.innerHTML = disps.map(d => `
          <tr>
            <td class="text-muted">#${d.id}</td>
            <td><strong>${d.modelo}</strong></td>
            <td>${d.marca}</td>
            <td class="text-muted">${d.cor||'—'} / ${d.armazenamento||'—'}</td>
            <td>${fmt(d.diaria)}</td>
            <td>${statusBadge(d.status)}</td>
            <td>
              <div class="flex gap-2">
                <button type="button" class="btn-icon" onclick="abrirModal(${d.id})" title="Editar">${ic('pencil.svg')}</button>
                <button type="button" class="btn-icon" onclick="excluir(${d.id})" title="Excluir">${ic('trash.svg')}</button>
              </div>
            </td>
          </tr>`).join('');
      });
  }

  function abrirModal(id) {
    editId = id || null;
    document.getElementById('modal-error').style.display = 'none';
    document.getElementById('modal-title').textContent = id ? 'Editar Dispositivo' : 'Novo Dispositivo';

    if (id) {
      fetch('../api/dispositivos.php?action=buscar&id=' + id)
        .then(r => r.json())
        .then(d => {
          document.getElementById('f-modelo').value = d.modelo;
          document.getElementById('f-marca').value = d.marca;
          document.getElementById('f-cor').value = d.cor || '';
          document.getElementById('f-armazenamento').value = d.armazenamento || '';
          document.getElementById('f-diaria').value = d.diaria;
          document.getElementById('f-status').value = d.status;
          document.getElementById('f-imei').value = d.imei || '';
          document.getElementById('modal').style.display = 'flex';
        });
    } else {
      ['f-modelo','f-marca','f-cor','f-armazenamento','f-imei'].forEach(i => document.getElementById(i).value = '');
      document.getElementById('f-diaria').value = '';
      document.getElementById('f-status').value = 'disponivel';
      document.getElementById('modal').style.display = 'flex';
    }
  }

  function fecharModal() { document.getElementById('modal').style.display = 'none'; editId = null; }

  function salvar() {
    const errEl = document.getElementById('modal-error');
    const payload = {
      id:            editId,
      modelo:        document.getElementById('f-modelo').value.trim(),
      marca:         document.getElementById('f-marca').value.trim(),
      cor:           document.getElementById('f-cor').value.trim(),
      armazenamento: document.getElementById('f-armazenamento').value.trim(),
      diaria:        parseFloat(document.getElementById('f-diaria').value),
      status:        document.getElementById('f-status').value,
      imei:          document.getElementById('f-imei').value.trim(),
    };

    fetch('../api/dispositivos.php?action=salvar', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)})
      .then(r => r.json())
      .then(res => {
        if (res.erro) { errEl.textContent = res.erro; errEl.style.display='block'; return; }
        fecharModal();
        renderTabela();
        mostrarAlerta('Dispositivo salvo com sucesso!', 'success');
      });
  }

  function excluir(id) {
    if (!confirm('Deseja realmente excluir este dispositivo?')) return;
    fetch('../api/dispositivos.php?action=excluir', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})})
      .then(r => r.json())
      .then(res => {
        if (res.erro) { mostrarAlerta(res.erro, 'error'); return; }
        renderTabela();
        mostrarAlerta('Dispositivo excluído.', 'success');
      });
  }

  function mostrarAlerta(msg, tipo) {
    const el = document.getElementById('alert-box');
    el.innerHTML = `<div class="alert alert-${tipo==='error'?'error':'success'}">${msg}</div>`;
    setTimeout(() => el.innerHTML = '', 3500);
  }

  renderTabela();
</script>
</body>
</html>
