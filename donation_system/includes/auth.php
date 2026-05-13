<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

// Block inactive/pending users
    if (in_array($_SESSION['user_status'] ?? 'active', ['pending','inactive'])) {
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php?error=not_approved');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        header('Location: ' . BASE_URL . '/login.php?error=unauthorized');
        exit;
    }
}

function canEdit(): bool {
    return in_array($_SESSION['user_role'] ?? '', ['admin','procurement'], true);
}

function canDelete(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

function currentUser(): array {
    return [
        'id'      => $_SESSION['user_id']   ?? 0,
        'name'    => $_SESSION['user_name'] ?? '',
        'email'   => $_SESSION['user_email'] ?? '',
        'role'    => $_SESSION['user_role'] ?? '',
        'picture' => $_SESSION['user_picture'] ?? null,
    ];
}

function dashboardUrl(): string {
    $role = $_SESSION['user_role'] ?? 'volunteer';
    return BASE_URL . "/$role/dashboard.php";
}

function roleBadgeClass(string $role): string {
    return match($role) {
        'admin'       => 'bg-danger',
        'procurement' => 'bg-warning text-dark',
        default       => 'bg-success',
    };
}