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
  <title>ConectaFácil — Usuários</title>
  <link rel="stylesheet" href="../css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-layout">
  <a href="#sidebar" class="sidebar-open-fab" aria-label="Abrir menu"><img src="../icons/menu.svg" class="sidebar-fab-icon" width="22" height="22" alt=""></a>
  <aside class="sidebar" id="sidebar">
    <?php renderSidebar('usuarios', $user); ?>
  </aside>
  <div class="main-content">
    <header class="page-header">
      <div>
        <div class="page-title"><?= cf_icon_img('users.svg', 'page-title-icon', 24, 24) ?><span>Usuários</span></div>
        <div class="page-subtitle">Gerencie os usuários do sistema</div>
      </div>
      <button class="btn btn-primary" onclick="abrirModal()">+ Novo Usuário</button>
    </header>
    <div class="page-body">
      <div id="alert-box"></div>
      <div class="alert alert-warning" style="display:flex;align-items:center;gap:10px"><?= cf_icon_img('lock.svg', '', 18, 18) ?><span>Esta área é exclusiva para administradores.</span></div>
      <div class="card" style="margin-top:20px">
        <div class="card-header"><div class="card-title">Usuários Cadastrados</div></div>
        <div class="card-body">
          <table>
            <thead><tr><th>#</th><th>Nome</th><th>Usuário</th><th>Perfil</th><th>Ações</th></tr></thead>
            <tbody id="tbody"><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">Carregando...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Novo Usuário</div>
      <button class="modal-close" onclick="fecharModal()"><?= cf_icon_img('x.svg', 'modal-close-icon', 14, 14, 'Fechar') ?></button>
    </div>
    <div class="modal-body">
      <div id="modal-error" class="alert alert-error" style="display:none"></div>
      <div class="form-group">
        <label>Nome Completo *</label>
        <input class="form-control" id="f-nome" placeholder="Nome do usuário">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Login (usuário) *</label>
          <input class="form-control" id="f-usuario" placeholder="nome.login">
        </div>
        <div class="form-group">
          <label>Senha <span id="senha-hint" style="font-weight:400;color:var(--text-muted)">(obrigatória)</span></label>
          <input class="form-control" id="f-senha" type="password" placeholder="Senha de acesso">
        </div>
      </div>
      <div class="form-group">
        <label>Perfil de Acesso *</label>
        <select class="form-control" id="f-perfil">
          <option value="operador">Operador — acesso às locações e consultas</option>
          <option value="admin">Administrador — acesso total</option>
        </select>
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

  function renderTabela() {
    fetch('../api/usuarios.php?action=listar')
      .then(r => r.json())
      .then(users => {
        const tbody = document.getElementById('tbody');
        tbody.innerHTML = users.map(u => `
          <tr>
            <td class="text-muted">#${u.id}</td>
            <td><strong>${u.nome}</strong></td>
            <td><code style="background:var(--bg);padding:2px 8px;border-radius:4px;font-size:12px">${u.usuario}</code></td>
            <td>${u.perfil==='admin'?`<span class="badge badge-primary">${icBadge('crown.svg')}Administrador</span>`:`<span class="badge badge-muted">${icBadge('user.svg')}Operador</span>`}</td>
            <td>
              <div class="flex gap-2">
                <button type="button" class="btn-icon" onclick="abrirModal(${u.id})" title="Editar">${ic('pencil.svg')}</button>
                ${u.id !== 1 ? `<button type="button" class="btn-icon" onclick="excluir(${u.id})" title="Excluir">${ic('trash.svg')}</button>` : '<span style="opacity:.3;font-size:12px">protegido</span>'}
              </div>
            </td>
          </tr>`).join('');
      });
  }

  function abrirModal(id) {
    editId = id || null;
    document.getElementById('modal-error').style.display = 'none';
    document.getElementById('modal-title').textContent = id ? 'Editar Usuário' : 'Novo Usuário';
    document.getElementById('senha-hint').textContent = id ? '(deixe em branco para manter)' : '(obrigatória)';

    if (id) {
      fetch('../api/usuarios.php?action=listar')
        .then(r => r.json())
        .then(users => {
          const u = users.find(u => u.id === id);
          if (!u) return;
          document.getElementById('f-nome').value = u.nome;
          document.getElementById('f-usuario').value = u.usuario;
          document.getElementById('f-senha').value = '';
          document.getElementById('f-perfil').value = u.perfil;
          document.getElementById('modal').style.display = 'flex';
        });
    } else {
      ['f-nome','f-usuario','f-senha'].forEach(i => document.getElementById(i).value = '');
      document.getElementById('f-perfil').value = 'operador';
      document.getElementById('modal').style.display = 'flex';
    }
  }

  function fecharModal() { document.getElementById('modal').style.display = 'none'; editId = null; }

  function salvar() {
    const errEl = document.getElementById('modal-error');
    const payload = {
      id:      editId,
      nome:    document.getElementById('f-nome').value.trim(),
      usuario: document.getElementById('f-usuario').value.trim(),
      senha:   document.getElementById('f-senha').value.trim(),
      perfil:  document.getElementById('f-perfil').value,
    };

    fetch('../api/usuarios.php?action=salvar', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)})
      .then(r => r.json())
      .then(res => {
        if (res.erro) { errEl.textContent = res.erro; errEl.style.display='block'; return; }
        fecharModal();
        renderTabela();
        mostrarAlerta('Usuário salvo com sucesso!', 'success');
      });
  }

  function excluir(id) {
    if (!confirm('Deseja excluir este usuário?')) return;
    fetch('../api/usuarios.php?action=excluir', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})})
      .then(r => r.json())
      .then(res => {
        if (res.erro) { mostrarAlerta(res.erro, 'error'); return; }
        renderTabela();
        mostrarAlerta('Usuário excluído.', 'success');
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
