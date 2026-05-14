// ===== AUTH =====

const AUTH_KEY = 'conectafacil_user';

function fazerLogin() {
  const usuario = document.getElementById('login-user').value.trim();
  const senha = document.getElementById('login-pass').value.trim();
  const errEl = document.getElementById('login-error');

  if (!usuario || !senha) {
    errEl.textContent = 'Preencha usuário e senha.';
    errEl.style.display = 'block';
    return;
  }

  const db = getDB();
  const user = db.usuarios.find(u => u.usuario === usuario && u.senha === senha);

  if (!user) {
    errEl.textContent = 'Usuário ou senha incorretos.';
    errEl.style.display = 'block';
    return;
  }

  sessionStorage.setItem(AUTH_KEY, JSON.stringify(user));
  window.location.href = 'pages/dashboard.html';
}

function getUsuarioLogado() {
  try {
    return JSON.parse(sessionStorage.getItem(AUTH_KEY));
  } catch { return null; }
}

function isAdmin() {
  const u = getUsuarioLogado();
  return u && u.perfil === 'admin';
}

function exigirLogin() {
  if (!getUsuarioLogado()) {
    window.location.href = '../index.html';
    return null;
  }
  return getUsuarioLogado();
}

function exigirAdmin() {
  const u = exigirLogin();
  if (!u || u.perfil !== 'admin') {
    window.location.href = 'dashboard.html';
    return null;
  }
  return u;
}

function logout() {
  sessionStorage.removeItem(AUTH_KEY);
  window.location.href = '../index.html';
}

function renderSidebar(paginaAtiva) {
  const user = getUsuarioLogado();
  if (!user) return;

  const adminLinks = user.perfil === 'admin' ? `
    <div class="nav-section">Administração</div>
    <a class="nav-item ${paginaAtiva==='dispositivos'?'active':''}" href="dispositivos.html">
      <span class="nav-icon">📱</span> Dispositivos
    </a>
    <a class="nav-item ${paginaAtiva==='usuarios'?'active':''}" href="usuarios.html">
      <span class="nav-icon">👥</span> Usuários
    </a>
    <a class="nav-item ${paginaAtiva==='relatorios'?'active':''}" href="relatorios.html">
      <span class="nav-icon">📊</span> Relatórios
    </a>
  ` : '';

  const html = `
    <div class="sidebar-brand">
      <div class="brand-icon">📱</div>
      <div class="brand-name">ConectaFácil</div>
      <div class="brand-sub">Locações Tecnológicas</div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">Principal</div>
      <a class="nav-item ${paginaAtiva==='dashboard'?'active':''}" href="dashboard.html">
        <span class="nav-icon">🏠</span> Dashboard
      </a>
      <a class="nav-item ${paginaAtiva==='locacoes'?'active':''}" href="locacoes.html">
        <span class="nav-icon">📋</span> Locações
      </a>
      <a class="nav-item ${paginaAtiva==='devolucoes'?'active':''}" href="devolucoes.html">
        <span class="nav-icon">↩️</span> Devoluções
      </a>
      <a class="nav-item ${paginaAtiva==='consulta'?'active':''}" href="consulta.html">
        <span class="nav-icon">🔍</span> Consultar Aparelhos
      </a>
      ${adminLinks}
    </nav>
    <div class="sidebar-user">
      <div class="user-avatar">${user.nome[0].toUpperCase()}</div>
      <div>
        <div class="user-name">${user.nome}</div>
        <div class="user-role">${user.perfil === 'admin' ? 'Administrador' : 'Operador'}</div>
      </div>
      <button class="btn-logout" onclick="logout()" title="Sair">⏻</button>
    </div>
  `;

  document.getElementById('sidebar').innerHTML = html;
}
