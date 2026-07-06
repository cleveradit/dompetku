<?php

declare(strict_types=1);

namespace Modules\Shared\ValueObjects;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Shared\Enums\ReportInterval;

final readonly class DatePeriod
{
    private function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public ReportInterval $interval,
    ) {
        if ($this->start->greaterThan($this->end)) {
            throw new InvalidArgumentException('Period start must be before or equal to its end.');
        }
    }

    public static function daily(CarbonImmutable $date): self
    {
        return new self($date->startOfDay(), $date->endOfDay(), ReportInterval::Daily);
    }

    public static function weekly(CarbonImmutable $date): self
    {
        return new self(
            $date->startOfWeek(CarbonImmutable::MONDAY),
            $date->endOfWeek(CarbonImmutable::SUNDAY),
            ReportInterval::Weekly,
        );
    }

    public static function monthly(CarbonImmutable $date): self
    {
        return new self($date->startOfMonth(), $date->endOfMonth(), ReportInterval::Monthly);
    }

    public static function yearly(CarbonImmutable $date): self
    {
        return new self($date->startOfYear(), $date->endOfYear(), ReportInterval::Yearly);
    }

    public static function custom(CarbonImmutable $start, CarbonImmutable $end): self
    {
        $start = $start->startOfDay();
        $end = $end->endOfDay();

        if ($start->diffInDays($end) > 366) {
            throw new InvalidArgumentException('Custom period may not exceed 366 days.');
        }

        return new self($start, $end, ReportInterval::Custom);
    }

    public static function for(ReportInterval $interval, CarbonImmutable $anchor): self
    {
        return match ($interval) {
            ReportInterval::Daily => self::daily($anchor),
            ReportInterval::Weekly => self::weekly($anchor),
            ReportInterval::Monthly => self::monthly($anchor),
            ReportInterval::Yearly => self::yearly($anchor),
            ReportInterval::Custom => throw new InvalidArgumentException('Custom periods need explicit start and end dates.'),
        };
    }

    public function previous(): self
    {
        return match ($this->interval) {
            ReportInterval::Daily => self::daily($this->start->subDay()),
            ReportInterval::Weekly => self::weekly($this->start->subWeek()),
            ReportInterval::Monthly => self::monthly($this->start->subMonth()),
            ReportInterval::Yearly => self::yearly($this->start->subYear()),
            ReportInterval::Custom => self::shiftedCustom($this, -1),
        };
    }

    public function next(): self
    {
        return match ($this->interval) {
            ReportInterval::Daily => self::daily($this->start->addDay()),
            ReportInterval::Weekly => self::weekly($this->start->addWeek()),
            ReportInterval::Monthly => self::monthly($this->start->addMonth()),
            ReportInterval::Yearly => self::yearly($this->start->addYear()),
            ReportInterval::Custom => self::shiftedCustom($this, 1),
        };
    }

    private static function shiftedCustom(self $period, int $direction): self
    {
        $days = (int) $period->start->diffInDays($period->end->startOfDay()) + 1;

        return self::custom(
            $period->start->addDays($days * $direction),
            $period->end->startOfDay()->addDays($days * $direction),
        );
    }

    /** @return array{start: string, end: string, interval: string} */
    public function toArray(): array
    {
        return [
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
            'interval' => $this->interval->value,
        ];
    }
}
