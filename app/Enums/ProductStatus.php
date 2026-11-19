<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draf';
    case Active = 'aktif';
    case Archived = 'arsip';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
