<?php
require_once __DIR__ . '/db.php';

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('scada_session');
        session_start();
    }
}

function estaLogado(): bool
{
    iniciarSessao();
    return isset($_SESSION['uid']);
}

function usuarioAtual(): ?array
{
    if (!estaLogado()) return null;
    return [
        'id'    => $_SESSION['uid'],
        'nome'  => $_SESSION['nome'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'],
    ];
}

function ehAdmin(): bool
{
    $u = usuarioAtual();
    return $u !== null && $u['role'] === 'admin';
}

// ── Login ───────────────────────────────────────────────────────

function login(string $email, string $senha): array
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM usuarios WHERE email = ? AND ativo = 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($senha, $u['senha_hash']))
        return ['ok' => false, 'erro' => 'E-mail ou senha inválidos.'];

    $db->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')
       ->execute([$u['id']]);

    iniciarSessao();
    session_regenerate_id(true);
    $_SESSION['uid']   = $u['id'];
    $_SESSION['nome']  = $u['nome'];
    $_SESSION['email'] = $u['email'];
    $_SESSION['role']  = $u['role'];

    return ['ok' => true];
}

// ── Registro (1º usuário → admin) ────────────────────────────────

function registrar(string $nome, string $email, string $senha): array
{
    $nome  = trim($nome);
    $email = trim($email);

    if (mb_strlen($nome) < 2)
        return ['ok' => false, 'erro' => 'Nome muito curto (mínimo 2 caracteres).'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['ok' => false, 'erro' => 'E-mail inválido.'];
    if (strlen($senha) < 6)
        return ['ok' => false, 'erro' => 'Senha deve ter ao menos 6 caracteres.'];

    $db  = getDB();
    $dup = $db->prepare('SELECT id FROM usuarios WHERE email = ?');
    $dup->execute([$email]);
    if ($dup->fetch())
        return ['ok' => false, 'erro' => 'E-mail já cadastrado.'];

    $total = (int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $role  = ($total === 0) ? 'admin' : 'visitor';

    $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare('INSERT INTO usuarios (nome, email, senha_hash, role) VALUES (?, ?, ?, ?)')
       ->execute([$nome, $email, $hash, $role]);

    return ['ok' => true, 'role' => $role];
}

// ── Logout ──────────────────────────────────────────────────────

function logout(): void
{
    iniciarSessao();
    session_unset();
    session_destroy();
}

// ── Admin: gerenciar usuários ────────────────────────────────────

function alterarRole(int $userId, string $novaRole): bool
{
    if (!in_array($novaRole, ['admin', 'visitor'], true)) return false;
    getDB()->prepare('UPDATE usuarios SET role = ? WHERE id = ?')
           ->execute([$novaRole, $userId]);
    return true;
}

function alterarAtivo(int $userId, bool $ativo): void
{
    getDB()->prepare('UPDATE usuarios SET ativo = ? WHERE id = ?')
           ->execute([(int) $ativo, $userId]);
}

function listarUsuarios(): array
{
    return getDB()
        ->query('SELECT id, nome, email, role, ativo, criado_em, ultimo_login FROM usuarios ORDER BY id')
        ->fetchAll();
}
