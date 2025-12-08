<?php
declare(strict_types=1);

namespace Backend\Modules\Admin;

use PDO;
use RuntimeException;

/**
 * AdminModel
 *
 * Доступ к данным Admin-домена.
 * Только хранение/извлечение, без бизнес-логики.
 */
final class AdminModel
{
    public function __construct(private PDO $db) {}

    /**
     * Пример чтения сущности админа по id.
     * (Сейчас заглушка — подставим реальные таблицы/поля позже.)
     */
    public function getAdminById(int $adminId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE id = :id');
        $stmt->execute(['id' => $adminId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException("Admin not found: {$adminId}");
        }

        return $row;
    }

    /**
     * Пример выборки списка админов.
     */
    public function listAdmins(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM admins
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Пример обновления админа.
     */
    public function updateAdmin(int $adminId, array $fields): array
    {
        // Заглушка: дальше мы сформируем whitelist полей и реальный UPDATE.
        if (empty($fields)) {
            return $this->getAdminById($adminId);
        }

        // ... реальный update будет добавлен по задаче

        return $this->getAdminById($adminId);
    }
}
