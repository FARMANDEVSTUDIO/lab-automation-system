<?php
declare(strict_types=1);
// Audit trail logger — records all entity changes (Naveera)

function audit_log(
    PDO $pdo,
    ?int $actorId,
    string $action,
    string $entityType,
    int $entityId,
    ?array $before = null,
    ?array $after = null
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO audit_log (actor_id, action, entity_type, entity_id, before_json, after_json, ip_address, user_agent)
         VALUES (:actor_id, :action, :entity_type, :entity_id, :before_json, :after_json, :ip_address, :user_agent)'
    );

    $stmt->execute([
        'actor_id'    => $actorId,
        'action'      => $action,
        'entity_type' => $entityType,
        'entity_id'   => $entityId,
        'before_json' => $before !== null ? json_encode($before) : null,
        'after_json'  => $after !== null ? json_encode($after) : null,
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'  => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 300) : null,
    ]);
}
