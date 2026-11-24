<?php

namespace App\Enums;

enum MarketChatIntent: string
{
    case Summary = 'summary';
    case Latest = 'latest';
    case OnDate = 'on_date';
    case Change = 'change';
    case Average = 'average';
    case Minimum = 'minimum';
    case Maximum = 'maximum';
    case Range = 'range';
    case OtherFilter = 'other_filter';
    case Unclear = 'unclear';
    case Unsupported = 'unsupported';

    public static function fromQuickQuestion(string $question, int $period): ?self
    {
        return match (mb_strtolower($question)) {
            'ringkas tren', "ringkas tren {$period} hari" => self::Summary,
            'harga terakhir' => self::Latest,
            'naik atau turun?' => self::Change,
            'rata-rata berapa?' => self::Average,
            'komoditas termahal' => self::Maximum,
            'komoditas termurah' => self::Minimum,
            default => null,
        };
    }
}
