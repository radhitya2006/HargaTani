<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'menunggu';
    case Accepted = 'diterima';
    case Rejected = 'ditolak';
    case Cancelled = 'dibatalkan';
    case Completed = 'selesai';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
