<?php
// ===== HELPERS =====
require_once __DIR__ . '/auth.php';

function formatarMoeda(float $val): string {
    return 'R$ ' . number_format($val, 2, ',', '.');
}

function formatarData(?string $str): string {
    if (!$str) return '—';
    $d = DateTime::createFromFormat('Y-m-d', $str);
    return $d ? $d->format('d/m/Y') : '—';
}

function hojeISO(): string { return date('Y-m-d'); }

function calcularMultaAtraso(string $dataFimPrevista, string $dataFimReal, float $valorDiaria): float {
    $prevista = new DateTime($dataFimPrevista);
    $real     = new DateTime($dataFimReal);
    if ($real <= $prevista) return 0.0;
    $dias = (int) ceil(($real->getTimestamp() - $prevista->getTimestamp()) / 86400);
    return $dias * $valorDiaria * 0.5;
}

function calcularDias(string $dataInicio, string $dataFim): int {
    $inicio = new DateTime($dataInicio);
    $fim    = new DateTime($dataFim);
    return max(1, (int) $inicio->diff($fim)->days);
}

function renderSidebar(string $paginaAtiva, array $user): void {
    $isAdmin = $user['perfil'] === 'admin';
    $inicial = strtoupper(mb_substr($user['nome'], 0, 1));
    $perfil  = $isAdmin ? 'Administrador' : 'Operador';

    $nav = [
        ['key' => 'dashboard',   'icon' => 'home.svg',      'label' => 'Dashboard',           'href' => 'dashboard.php'],
        ['key' => 'locacoes',    'icon' => 'clipboard.svg', 'label' => 'Locações',            'href' => 'locacoes.php'],
        ['key' => 'devolucoes',  'icon' => 'reply.svg',     'label' => 'Devoluções',          'href' => 'devolucoes.php'],
        ['key' => 'consulta',    'icon' => 'search.svg',    'label' => 'Consultar Aparelhos', 'href' => 'consulta.php'],
    ];

    $navAdmin = [
        ['key' => 'dispositivos','icon' => 'phone.svg',     'label' => 'Dispositivos',        'href' => 'dispositivos.php'],
        ['key' => 'usuarios',    'icon' => 'users.svg',     'label' => 'Usuários',            'href' => 'usuarios.php'],
        ['key' => 'relatorios',  'icon' => 'chart.svg',     'label' => 'Relatórios',          'href' => 'relatorios.php'],
    ];

    $renderItem = function(array $item) use ($paginaAtiva): string {
        $active = $paginaAtiva === $item['key'] ? ' active' : '';
        $icon = cf_icon_img($item['icon'], 'nav-icon-img', 20, 20);
        return '<a class="nav-item' . $active . '" href="' . htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') . '">'
             . '<span class="nav-icon-wrap">' . $icon . '</span>'
             . '<span>' . htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') . '</span>'
             . '</a>';
    };

    echo '<a class="sidebar-close" href="#" aria-label="Fechar menu">' . cf_icon_img('x.svg', 'sidebar-close-icon', 16, 16, 'Fechar') . '</a>';

    echo '<div class="sidebar-brand">'
       . '<div class="sidebar-brand-icon">' . cf_icon_img('phone-brand.svg', 'sidebar-brand-img', 22, 22) . '</div>'
       . '<div>'
       . '<div class="sidebar-brand-name">ConectaFácil</div>'
       . '<div class="sidebar-brand-sub">Locações Tecnológicas</div>'
       . '</div>'
       . '</div>';

    echo '<nav class="sidebar-nav">';
    echo '<div class="nav-section">Principal</div>';
    foreach ($nav as $item) echo $renderItem($item);

    if ($isAdmin) {
        echo '<div class="nav-section">Administração</div>';
        foreach ($navAdmin as $item) echo $renderItem($item);
    }
    echo '</nav>';

    echo '<div class="sidebar-theme">'
       . '<button type="button" class="theme-toggle-btn" aria-pressed="false" aria-label="Ativar modo escuro">'
       . cf_icon_img('moon.svg', 'theme-toggle-icon', 18, 18)
       . '<span class="theme-toggle-label">Modo escuro</span>'
       . '</button>'
       . '</div>';

    echo '<div class="sidebar-footer">'
       . '<div class="sidebar-user">'
       . '<div class="user-avatar">' . htmlspecialchars($inicial) . '</div>'
       . '<div>'
       . '<div class="user-name">' . htmlspecialchars($user['nome']) . '</div>'
       . '<div class="user-role">' . $perfil . '</div>'
       . '</div>'
       . '<a href="../logout.php" class="btn-logout" title="Sair">Sair</a>'
       . '</div>'
       . '</div>'
       . '<script src="../js/theme.js" defer></script>';
}
