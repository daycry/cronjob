<?php

use CodeIgniter\I18n\Time;
use Daycry\CronJob\JobLog;
use Daycry\CronJob\Job;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class JobLogTest extends TestCase
{
    public static function durationProvider()
    {
        return [
            [
                '2021-01-21 12:00:00',
                '2021-01-21 12:00:00',
                '00:00:00',
            ],
            [
                '2021-01-21 12:00:00',
                '2021-01-21 12:00:01',
                '00:00:01',
            ],
            [
                '2021-01-21 12:00:00',
                '2021-01-21 12:05:12',
                '00:05:12',
            ],
            [
                '2021-01-21 12:00:00',
                '2021-01-21 13:05:12',
                '01:05:12',
            ]
        ];
    }

    /**
     * @dataProvider durationProvider
     *
     * @param mixed $start
     * @param mixed $end
     * @param mixed $expected
     */
    public function testDuration($start, $end, $expected)
    {
        $job = new Job('command', 'foo:bar');
        $start = new Time($start);
        $end   = new Time($end);

        $job->startLog($start)->endLog($end);

        $this->assertSame($expected, $job->duration());
    }
}
