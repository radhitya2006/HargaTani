<?php

namespace App\Support;

final class PriceCommodities
{
    public const DEFAULT = 'bawang-merah';

    public const PERIODS = [14, 30, 90];

    public const ALL = [
        'bawang-merah' => ['name' => 'Bawang Merah Ukuran Sedang', 'tone' => 'shallot'],
        'cabai-merah-besar' => ['name' => 'Cabai Merah Besar', 'tone' => 'chili'],
        'cabai-rawit-merah' => ['name' => 'Cabai Rawit Merah', 'tone' => 'tomato'],
    ];
}
