<?php
/**
 * Role-Based Access Control (RBAC) & Authentication Helpers
 * Employee Management System
 */

/**
 * Check if the user is authenticated.
 *
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Require authentication; redirect to login if not authenticated.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to access this page.');
        redirect('auth/login.php');
    }
}

/**
 * Get current authenticated user details.
 *
 * @return array|null
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if current user has a specific permission.
 * Admins automatically have all permissions.
 *
 * @param string $permissionName
 * @return bool
 */
function hasPermission(string $permissionName): bool {
    if (!isLoggedIn()) {
        return false;
    }

    $user = getCurrentUser();

    // Admin role has full access
    if (strtolower($user['role_name'] ?? '') === 'admin') {
        return true;
    }

    $permissions = $user['permissions'] ?? [];
    return in_array($permissionName, $permissions, true);
}

/**
 * Check if current user has a specific role name.
 *
 * @param string $roleName
 * @return bool
 */
function hasRole(string $roleName): bool {
    if (!isLoggedIn()) {
        return false;
    }
    return strcasecmp($_SESSION['user']['role_name'] ?? '', $roleName) === 0;
}

/**
 * Enforce permission requirement; redirect to forbidden page if missing.
 *
 * @param string $permissionName
 */
function requirePermission(string $permissionName): void {
    requireLogin();
    if (!hasPermission($permissionName)) {
        http_response_code(403);
        redirect('auth/forbidden.php');
    }
}

/**
 * Load fresh user permissions from database and store in session.
 *
 * @param int $roleId
 * @return array
 */
function fetchRolePermissions(int $roleId): array {
    $db = getDBConnection();
    $stmt = $db->prepare('
        SELECT p.name 
        FROM permissions p
        INNER JOIN role_permissions rp ON p.id = rp.permission_id
        WHERE rp.role_id = :role_id
    ');
    $stmt->execute(['role_id' => $roleId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}
