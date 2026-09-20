<?php

namespace Tests\Support;

use Whilesmart\Engagement\Contracts\MetricProvider;
use Whilesmart\Engagement\Support\Metric;
use Whilesmart\Engagement\Support\Period;

class MeasurementsMetricProvider implements MetricProvider
{
    public function key(): string
    {
        return 'users';
    }

    public function label(): string
    {
        return 'Users';
    }

    public function metrics(Period $period): array
    {
        return [
            Metric::count('registered_users', 'Registered users', $period->clientKey === 'website' ? 7 : 12),
            Metric::series('registrations', 'Registrations', [
                ['date' => $period->start->toDateString(), 'value' => 4],
            ]),
        ];
    }
}
