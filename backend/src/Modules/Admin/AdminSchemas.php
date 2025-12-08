<?php
declare(strict_types=1);

namespace Backend\Modules\Admin;

use InvalidArgumentException;

/**
 * AdminSchemas
 *
 * Здесь живут:
 *  - схемы входных DTO (из контроллера в сервис)
 *  - схемы выходных DTO (из сервиса в контроллер)
 *  - валидация на уровне формата данных
 *
 * НЕ кладём сюда бизнес-валидацию — только форму/типы.
 */
final class AdminSchemas
{
    /**
     * Простейший валидатор "обязательные поля + типы".
     * Дальше можно заменить на вашу библиотеку схем.
     */
    public static function validate(array $data, array $required, array $types = []): array
    {
        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException("Missing required field: {$key}");
            }
        }

        foreach ($types as $key => $type) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            $ok = match ($type) {
                'int' => is_int($value),
                'string' => is_string($value),
                'bool' => is_bool($value),
                'array' => is_array($value),
                default => true,
            };
            if (!$ok) {
                throw new InvalidArgumentException("Invalid type for {$key}, expected {$type}");
            }
        }

        return $data;
    }

    /**
     * Пример схемы запроса.
     * Когда добавляем эндпоинт — создаём новый метод вида toXDto().
     */
    public static function toExampleActionDto(array $body): array
    {
        return self::validate($body, ['example'], [
            'example' => 'string',
            'options' => 'array',
        ]);
    }

    /**
     * Пример схемы ответа.
     * Используем для унификации формата ответов.
     */
    public static function exampleActionResponse(array $payload): array
    {
        return [
            'ok' => true,
            'data' => $payload,
        ];
    }
}
