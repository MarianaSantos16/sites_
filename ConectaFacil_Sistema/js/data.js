// ===== CONECTAFÁCIL — DATA LAYER =====

const DB_KEY = 'conectafacil_db';

const DB_DEFAULT = {
  usuarios: [
    { id:1, nome:'Administrador', usuario:'admin', senha:'admin123', perfil:'admin' },
    { id:2, nome:'Operador João', usuario:'operador', senha:'op123', perfil:'operador' }
  ],
  dispositivos: [
    { id:1, modelo:'Galaxy S23', marca:'Samsung', diaria:35.00, status:'disponivel', imei:'350000000000001', cor:'Preto', armazenamento:'128GB' },
    { id:2, modelo:'iPhone 14', marca:'Apple', diaria:55.00, status:'disponivel', imei:'350000000000002', cor:'Branco', armazenamento:'256GB' },
    { id:3, modelo:'iPad Air 5', marca:'Apple', diaria:45.00, status:'locado', imei:'350000000000003', cor:'Cinza', armazenamento:'64GB' },
    { id:4, modelo:'Moto G82', marca:'Motorola', diaria:20.00, status:'disponivel', imei:'350000000000004', cor:'Azul', armazenamento:'128GB' },
    { id:5, modelo:'Galaxy A54', marca:'Samsung', diaria:28.00, status:'manutencao', imei:'350000000000005', cor:'Verde', armazenamento:'128GB' },
    { id:6, modelo:'Redmi Note 12', marca:'Xiaomi', diaria:18.00, status:'disponivel', imei:'350000000000006', cor:'Preto', armazenamento:'128GB' },
  ],
  locacoes: [
    {
      id:1,
      clienteNome:'Empresa TechCorp Ltda',
      clienteCPFCNPJ:'12.345.678/0001-99',
      clienteTelefone:'(11) 98765-4321',
      dispositivoId:3,
      dataInicio:'2025-05-10',
      dataFimPrevista:'2025-05-15',
      dataFimReal:null,
      status:'ativa',
      observacoes:'Cliente recorrente',
      valorDiaria:45.00,
      multaDano:0,
      valorTotal:null,
      danos:''
    }
  ],
  nextId: { dispositivos:7, locacoes:2, usuarios:3 }
};

function getDB() {
  try {
    const raw = localStorage.getItem(DB_KEY);
    if (!raw) {
      localStorage.setItem(DB_KEY, JSON.stringify(DB_DEFAULT));
      return JSON.parse(JSON.stringify(DB_DEFAULT));
    }
    return JSON.parse(raw);
  } catch(e) {
    return JSON.parse(JSON.stringify(DB_DEFAULT));
  }
}

function saveDB(db) {
  localStorage.setItem(DB_KEY, JSON.stringify(db));
}

function nextId(entidade) {
  const db = getDB();
  const id = db.nextId[entidade] || 1;
  db.nextId[entidade] = id + 1;
  saveDB(db);
  return id;
}

// ===== DISPOSITIVOS =====
function getDispositivos() { return getDB().dispositivos; }

function getDispositivoPorId(id) {
  return getDB().dispositivos.find(d => d.id === id);
}

function salvarDispositivo(dispositivo) {
  const db = getDB();
  if (dispositivo.id) {
    const idx = db.dispositivos.findIndex(d => d.id === dispositivo.id);
    if (idx >= 0) db.dispositivos[idx] = dispositivo;
  } else {
    dispositivo.id = nextId('dispositivos');
    db.dispositivos.push(dispositivo);
  }
  saveDB(db);
  return dispositivo;
}

function excluirDispositivo(id) {
  const db = getDB();
  // Verifica se tem locações ativas
  const temLocacao = db.locacoes.some(l => l.dispositivoId === id && l.status === 'ativa');
  if (temLocacao) return { erro: 'Dispositivo possui locação ativa e não pode ser excluído.' };
  db.dispositivos = db.dispositivos.filter(d => d.id !== id);
  saveDB(db);
  return { sucesso: true };
}

// ===== LOCAÇÕES =====
function getLocacoes() { return getDB().locacoes; }

function getLocacaoPorId(id) {
  return getDB().locacoes.find(l => l.id === id);
}

function salvarLocacao(locacao) {
  const db = getDB();
  if (locacao.id) {
    const idx = db.locacoes.findIndex(l => l.id === locacao.id);
    if (idx >= 0) db.locacoes[idx] = locacao;
  } else {
    locacao.id = nextId('locacoes');
    // Atualiza status do dispositivo
    const dIdx = db.dispositivos.findIndex(d => d.id === locacao.dispositivoId);
    if (dIdx >= 0) db.dispositivos[dIdx].status = 'locado';
    db.locacoes.push(locacao);
  }
  saveDB(db);
  return locacao;
}

function devolverLocacao(id, { dataFimReal, danos, multaDano }) {
  const db = getDB();
  const idx = db.locacoes.findIndex(l => l.id === id);
  if (idx < 0) return { erro: 'Locação não encontrada.' };

  const loc = db.locacoes[idx];
  const inicio = new Date(loc.dataInicio);
  const fim = new Date(dataFimReal);
  const dias = Math.max(1, Math.ceil((fim - inicio) / (1000*60*60*24)));

  const valorBase = dias * loc.valorDiaria;
  const multaAtraso = calcularMultaAtraso(loc.dataFimPrevista, dataFimReal, loc.valorDiaria);
  const multaDanoVal = parseFloat(multaDano) || 0;
  const valorTotal = valorBase + multaAtraso + multaDanoVal;

  loc.dataFimReal = dataFimReal;
  loc.danos = danos || '';
  loc.multaDano = multaDanoVal;
  loc.multaAtraso = multaAtraso;
  loc.valorTotal = parseFloat(valorTotal.toFixed(2));
  loc.status = 'concluida';

  // Atualiza dispositivo
  const dIdx = db.dispositivos.findIndex(d => d.id === loc.dispositivoId);
  if (dIdx >= 0) db.dispositivos[dIdx].status = danos ? 'manutencao' : 'disponivel';

  saveDB(db);
  return { sucesso: true, locacao: loc };
}

function calcularMultaAtraso(dataFimPrevista, dataFimReal, valorDiaria) {
  const prevista = new Date(dataFimPrevista);
  const real = new Date(dataFimReal);
  if (real <= prevista) return 0;
  const diasAtraso = Math.ceil((real - prevista) / (1000*60*60*24));
  return diasAtraso * valorDiaria * 0.5; // 50% da diária por dia de atraso
}

function calcularPreviewValor(dataInicio, dataFimPrevista, valorDiaria) {
  if (!dataInicio || !dataFimPrevista) return 0;
  const inicio = new Date(dataInicio);
  const fim = new Date(dataFimPrevista);
  const dias = Math.max(1, Math.ceil((fim - inicio) / (1000*60*60*24)));
  return dias * valorDiaria;
}

// ===== USUÁRIOS (apenas admin) =====
function getUsuarios() { return getDB().usuarios; }

function salvarUsuario(usuario) {
  const db = getDB();
  if (usuario.id) {
    const idx = db.usuarios.findIndex(u => u.id === usuario.id);
    if (idx >= 0) db.usuarios[idx] = usuario;
  } else {
    // Verifica username único
    if (db.usuarios.find(u => u.usuario === usuario.usuario)) {
      return { erro: 'Nome de usuário já cadastrado.' };
    }
    usuario.id = nextId('usuarios');
    db.usuarios.push(usuario);
  }
  saveDB(db);
  return usuario;
}

function excluirUsuario(id) {
  const db = getDB();
  if (id === 1) return { erro: 'O administrador principal não pode ser excluído.' };
  db.usuarios = db.usuarios.filter(u => u.id !== id);
  saveDB(db);
  return { sucesso: true };
}

// ===== HELPERS =====
function formatarMoeda(val) {
  return 'R$ ' + parseFloat(val || 0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function formatarData(str) {
  if (!str) return '—';
  const [y,m,d] = str.split('-');
  return `${d}/${m}/${y}`;
}

function statusDispositivoBadge(status) {
  const map = {
    disponivel: '<span class="badge badge-success">✓ Disponível</span>',
    locado:     '<span class="badge badge-warning">⏳ Locado</span>',
    manutencao: '<span class="badge badge-danger">🔧 Manutenção</span>',
  };
  return map[status] || status;
}

function statusLocacaoBadge(status) {
  const map = {
    ativa:     '<span class="badge badge-warning">⏳ Ativa</span>',
    concluida: '<span class="badge badge-success">✓ Concluída</span>',
    cancelada: '<span class="badge badge-muted">✕ Cancelada</span>',
  };
  return map[status] || status;
}

function hojeISO() {
  return new Date().toISOString().split('T')[0];
}
