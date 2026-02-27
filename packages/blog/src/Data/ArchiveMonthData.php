<?php

declare(strict_types=1);

namespace Capell\Blog\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class ArchiveMonthData extends Data
{
    public function __construct(
        public int $year,
        public int $month,
        public ?int $total = null,
    ) {}

    public static function fromDate(Carbon $date): self
    {
        return new self(
            year: (int) $date->format('Y'),
            month: (int) $date->format('m'),
        );
    }

    public function getDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m', sprintf('%d-%d', $this->year, $this->month));
    }
}
