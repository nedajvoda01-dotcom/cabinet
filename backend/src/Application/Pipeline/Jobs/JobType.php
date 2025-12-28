<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Jobs;

final class JobType
{
    public const PHOTOS = 'photos';
    public const EXPORT = 'export';
    public const PUBLISH = 'publish';
    public const PARSER = 'parser';
    public const ROBOT_STATUS = 'robot_status';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::PHOTOS,
            self::EXPORT,
            self::PUBLISH,
            self::PARSER,
            self::ROBOT_STATUS,
        ];
    }
}
