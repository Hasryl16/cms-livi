<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Published->value => 'Published',
            self::Archived->value => 'Archived',
        ];
    }
}