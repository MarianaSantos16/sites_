<?php
// ===== AUTENTICAÇÃO =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna a URL base do projeto (ex: /ConectaFacil_Sistema)
 * Funciona independente de onde o arquivo está.
 */
function baseUrl(): string {
    // Sobe até a raiz do projeto a partir de qualquer subpasta
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    // Remove /pages/arquivo.php ou /api/arquivo.php ou /arquivo.php
    $base = preg_replace('#/(pages|api)/[^/]+$#', '', $script);
    $base = preg_replace('#/[^/]+\.php$#', '', $base);
    return rtrim($base, '/');
}

function getUsuarioLogado(): ?array {
    return $_SESSION['usuario'] ?? null;
}

function isAdmin(): bool {
    $u = getUsuarioLogado();
    return $u !== null && $u['perfil'] === 'admin';
}

function exigirLogin(): array {
    $u = getUsuarioLogado();
    if (!$u) {
        header('Location: ' . baseUrl() . '/index.php');
        exit;
    }
    return $u;
}

function exigirAdmin(): array {
    $u = exigirLogin();
    if ($u['perfil'] !== 'admin') {
        header('Location: ' . baseUrl() . '/pages/dashboard.php');
        exit;
    }
    return $u;
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . baseUrl() . '/index.php');
    exit;
}

/** Evita flash ao carregar: aplica modo escuro antes do body (use no início do head). */
function cfThemeHeadScript(): void {
    echo '<script>try{if(localStorage.getItem("cf-theme")==="dark")document.documentElement.setAttribute("data-theme","dark");}catch(e){}</script>';
}

/** Caminho relativo até /icons/ (raiz do projeto ou ../icons a partir de /pages/). */
function cf_icons_base_path(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return strpos($script, '/pages/') !== false ? '../icons/' : 'icons/';
}

/** Retorna tag img para ícone SVG (alt vazio = decorativo). */
function cf_icon_img(string $file, string $class = '', int $w = 20, int $h = 20, string $alt = ''): string {
    $src = htmlspecialchars(cf_icons_base_path() . $file, ENT_QUOTES, 'UTF-8');
    $altH = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    $c = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    return '<img src="' . $src . '"' . $c . ' width="' . $w . '" height="' . $h . '" alt="' . $altH . '" loading="lazy" decoding="async">';
}
