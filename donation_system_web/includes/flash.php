<?php
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $icons = ['success'=>'check-circle','danger'=>'x-circle','warning'=>'exclamation-triangle','info'=>'info-circle'];
    $icon  = $icons[$flash['type']] ?? 'info-circle';
    echo '<div class="alert alert-'.$flash['type'].' alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-'.$icon.'"></i>
        <span>'.htmlspecialchars($flash['message']).'</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}