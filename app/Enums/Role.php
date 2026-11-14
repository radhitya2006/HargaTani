<?php

namespace App\Enums;

enum Role: string
{
    case Petani = 'petani';
    case Pembeli = 'pembeli';
    case Admin = 'admin';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
