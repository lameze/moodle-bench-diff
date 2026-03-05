<?php

namespace App\Tests\Service;

use App\Model\Dataset;
use App\Service\DatasetComparator;
use PHPUnit\Framework\TestCase;

class DatasetComparatorTest extends TestCase
{
    /**
     * Helper to create a Dataset from a results array, bypassing the file loader.
     */
    private function createDataset(string $name, array $results): Dataset
    {
        $data = (object) [
            'host' => 'testhost',
            'sitepath' => '/',
            'group' => '502',
            'rundesc' => $name,
            'users' => '1',
            'loopcount' => '5',
            'rampup' => '1',
            'throughput' => '120',
            'size' => 'XS',
            'baseversion' => '2026021700',
            'siteversion' => '502',
            'sitebranch' => '502',
            'sitecommit' => sha1($name),
            'runTime' => new \DateTimeImmutable(),
            'results' => $results,
        ];

        return Dataset::loadFullDataset($name, $data);
    }

    /**
     * Build a sample row of performance data for use in tests.
     */
    private function makeSample(
        string $name,
        int $dbreads = 10,
        int $dbwrites = 1,
        float $dbquerytime = 0.01,
        string $memoryused = '4.2',
        string $filesincluded = '405',
        string $serverload = '2.5',
        string $sessionsize = '4.4',
        string $timeused = '0.15',
        string $bytes = '30000',
        string $time = '500',
        string $latency = '300',
    ): object {
        return (object) [
            'thread' => 0,
            'starttime' => time() * 1000,
            'dbreads' => $dbreads,
            'dbwrites' => $dbwrites,
            'dbquerytime' => $dbquerytime,
            'memoryused' => $memoryused,
            'filesincluded' => $filesincluded,
            'serverload' => $serverload,
            'sessionsize' => $sessionsize,
            'timeused' => $timeused,
            'name' => $name,
            'url' => 'http://testhost/',
            'bytes' => $bytes,
            'time' => $time,
            'latency' => $latency,
        ];
    }

    public function testIsKeyIgnoredReturnsTrueForIgnoredKeys(): void
    {
        $this->assertTrue(DatasetComparator::isKeyIgnored('dbquerytime'));
        $this->assertTrue(DatasetComparator::isKeyIgnored('timeused'));
        $this->assertTrue(DatasetComparator::isKeyIgnored('time'));
        $this->assertTrue(DatasetComparator::isKeyIgnored('latency'));
        $this->assertTrue(DatasetComparator::isKeyIgnored('bytes'));
        $this->assertTrue(DatasetComparator::isKeyIgnored('serverload'));
        $this->assertTrue(DatasetComparator::isKeyIgnored('filesincluded'));
    }

    public function testIsKeyIgnoredReturnsFalseForComparedKeys(): void
    {
        $this->assertFalse(DatasetComparator::isKeyIgnored('dbreads'));
        $this->assertFalse(DatasetComparator::isKeyIgnored('dbwrites'));
        $this->assertFalse(DatasetComparator::isKeyIgnored('memoryused'));
        $this->assertFalse(DatasetComparator::isKeyIgnored('sessionsize'));
    }

    public function testGetComparedKeysExcludesIgnoredKeys(): void
    {
        $compared = DatasetComparator::getComparedKeys();

        $this->assertArrayHasKey('dbreads', $compared);
        $this->assertArrayHasKey('dbwrites', $compared);
        $this->assertArrayHasKey('sessionsize', $compared);
        $this->assertArrayHasKey('memoryused', $compared);

        $this->assertArrayNotHasKey('dbquerytime', $compared);
        $this->assertArrayNotHasKey('timeused', $compared);
        $this->assertArrayNotHasKey('time', $compared);
        $this->assertArrayNotHasKey('latency', $compared);
        $this->assertArrayNotHasKey('bytes', $compared);
        $this->assertArrayNotHasKey('serverload', $compared);
        $this->assertArrayNotHasKey('filesincluded', $compared);
    }

    public function testCompareIdenticalDatasetsSucceeds(): void
    {
        $samples = [
            [
                $this->makeSample('Login'),
                $this->makeSample('Frontpage'),
            ]
        ];

        $before = $this->createDataset('before', $samples);
        $after = $this->createDataset('after', $samples);

        $comparator = new DatasetComparator();
        $result = $comparator->compare($before, $after);

        $this->assertTrue($result->isSuccessful());
        $this->assertEmpty($result->getFailures());
    }

    public function testCompareDetectsRegression(): void
    {
        $beforeSamples = [
            [
                $this->makeSample('Login', dbreads: 10),
            ]
        ];

        // After has significantly more dbreads (exceeds threshold of 2).
        $afterSamples = [
            [
                $this->makeSample('Login', dbreads: 20),
            ]
        ];

        $before = $this->createDataset('before', $beforeSamples);
        $after = $this->createDataset('after', $afterSamples);

        $comparator = new DatasetComparator();
        $result = $comparator->compare($before, $after);

        $this->assertFalse($result->isSuccessful());

        // Check that at least one failure is for dbreads.
        $failures = $result->getFailures();
        $dbreadFailures = array_filter($failures, fn($r) => $r->key === 'dbreads');
        $this->assertNotEmpty($dbreadFailures);
    }

    public function testCompareIgnoresTimingRegressions(): void
    {
        $beforeSamples = [
            [
                $this->makeSample('Login', time: '500', latency: '300', timeused: '0.15'),
            ]
        ];

        // After has much worse timing — but these keys are "ignored".
        $afterSamples = [
            [
                $this->makeSample('Login', time: '50000', latency: '30000', timeused: '15.0'),
            ]
        ];

        $before = $this->createDataset('before', $beforeSamples);
        $after = $this->createDataset('after', $afterSamples);

        $comparator = new DatasetComparator();
        $result = $comparator->compare($before, $after);

        // Timing keys are ignored, so there should be no failures from them.
        $failures = $result->getFailures();
        $timingFailures = array_filter($failures, fn($r) => in_array($r->key, ['time', 'latency', 'timeused', 'dbquerytime', 'bytes']));
        $this->assertEmpty($timingFailures, 'Timing keys should be ignored and not produce failures');
    }

    public function testCompareIgnoresServerLoadDifferences(): void
    {
        // Reproduces build 259: same code on different workers with different
        // system loads (worker01 load ~1.1 vs worker13 load ~3.6).
        // serverload is Linux load average — purely infrastructure-dependent.
        $beforeSamples = [
            [$this->makeSample('Login', serverload: '1.12')],
            [$this->makeSample('Login', serverload: '1.10')],
            [$this->makeSample('Login', serverload: '1.08')],
            [$this->makeSample('Login', serverload: '1.12')],
            [$this->makeSample('Login', serverload: '1.16')],
        ];
        $afterSamples = [
            [$this->makeSample('Login', serverload: '3.62')],
            [$this->makeSample('Login', serverload: '3.62')],
            [$this->makeSample('Login', serverload: '3.54')],
            [$this->makeSample('Login', serverload: '3.48')],
            [$this->makeSample('Login', serverload: '3.42')],
        ];

        $before = $this->createDataset('before', $beforeSamples);
        $after = $this->createDataset('after', $afterSamples);

        $comparator = new DatasetComparator();
        $result = $comparator->compare($before, $after);

        $serverloadFailures = array_filter($result->getFailures(), fn($r) => $r->key === 'serverload');
        $this->assertEmpty($serverloadFailures, 'Server load differences should be ignored — infrastructure metric, not code metric');
    }

    public function testCompareWithSessionSizeNonBreakingSpaces(): void
    {
        // Before: clean numeric sessionsize (as produced by fixed recorder.bsf).
        $beforeSamples = [
            [
                $this->makeSample('Login', sessionsize: '4.4'),
            ]
        ];

        // After: same value but with legacy non-breaking space format.
        $afterSamples = [
            [
                $this->makeSample('Login', sessionsize: "4.4\xC2\xA0"),
            ]
        ];

        $before = $this->createDataset('before', $beforeSamples);
        $after = $this->createDataset('after', $afterSamples);

        $comparator = new DatasetComparator();
        // This should NOT throw, thanks to sanitizeValue.
        $result = $comparator->compare($before, $after);

        // Sessionsize values are equal after sanitization.
        $sessionFailures = array_filter($result->getFailures(), fn($r) => $r->key === 'sessionsize');
        $this->assertEmpty($sessionFailures);
    }

    public function testCompareWithSessionSizeBytesNonBreakingSpaces(): void
    {
        // Legacy format: "569\xC2\xA0bytes".
        $beforeSamples = [
            [
                $this->makeSample('Frontpage', sessionsize: "569\xC2\xA0bytes"),
            ]
        ];
        $afterSamples = [
            [
                $this->makeSample('Frontpage', sessionsize: "569\xC2\xA0bytes"),
            ]
        ];

        $before = $this->createDataset('before', $beforeSamples);
        $after = $this->createDataset('after', $afterSamples);

        $comparator = new DatasetComparator();
        // Must not throw.
        $result = $comparator->compare($before, $after);
        $this->assertTrue($result->isSuccessful());
    }

    public function testMarginalRegressionDoesNotFail(): void
    {
        // dbreads threshold is 2, so a difference of 1 should pass.
        $beforeSamples = [
            [
                $this->makeSample('Login', dbreads: 10),
            ]
        ];
        $afterSamples = [
            [
                $this->makeSample('Login', dbreads: 11),
            ]
        ];

        $before = $this->createDataset('before', $beforeSamples);
        $after = $this->createDataset('after', $afterSamples);

        $comparator = new DatasetComparator();
        $result = $comparator->compare($before, $after);

        // Within threshold — should succeed.
        $dbreadFailures = array_filter($result->getFailures(), fn($r) => $r->key === 'dbreads');
        $this->assertEmpty($dbreadFailures, 'Marginal regression within threshold should not fail');
    }

    public function testSessionWriteNoiseDoesNotFailDbwritesTotal(): void
    {
        // Simulates build 256: same code, but the "before" run had 1 session write
        // across 5 loops while "after" had 1 per loop (5 total). This is session
        // handler timing noise, not a real regression.
        $beforeSamples = [
            [$this->makeSample('Login', dbwrites: 0)],
            [$this->makeSample('Login', dbwrites: 0)],
            [$this->makeSample('Login', dbwrites: 0)],
            [$this->makeSample('Login', dbwrites: 1)],
            [$this->makeSample('Login', dbwrites: 0)],
        ];
        $afterSamples = [
            [$this->makeSample('Login', dbwrites: 1)],
            [$this->makeSample('Login', dbwrites: 1)],
            [$this->makeSample('Login', dbwrites: 1)],
            [$this->makeSample('Login', dbwrites: 1)],
            [$this->makeSample('Login', dbwrites: 1)],
        ];

        $before = $this->createDataset('before', $beforeSamples);
        $after = $this->createDataset('after', $afterSamples);

        $comparator = new DatasetComparator();
        $result = $comparator->compare($before, $after);

        // The total diff is 4 (1 vs 5), which is within the dbwrites total threshold
        // of 6. This should not be flagged as a failure.
        $dbwriteFailures = array_filter($result->getFailures(), fn($r) => $r->key === 'dbwrites');
        $this->assertEmpty($dbwriteFailures, 'Session write noise should not trigger dbwrites failure');
    }

    public function testRealDbwriteRegressionStillCaught(): void
    {
        // A real regression adding +2 dbwrites per request × 5 loops = diff of 10.
        // This should exceed the threshold of 6 and be caught.
        $beforeSamples = [
            [$this->makeSample('Login', dbwrites: 1)],
            [$this->makeSample('Login', dbwrites: 1)],
            [$this->makeSample('Login', dbwrites: 1)],
            [$this->makeSample('Login', dbwrites: 1)],
            [$this->makeSample('Login', dbwrites: 1)],
        ];
        $afterSamples = [
            [$this->makeSample('Login', dbwrites: 3)],
            [$this->makeSample('Login', dbwrites: 3)],
            [$this->makeSample('Login', dbwrites: 3)],
            [$this->makeSample('Login', dbwrites: 3)],
            [$this->makeSample('Login', dbwrites: 3)],
        ];

        $before = $this->createDataset('before', $beforeSamples);
        $after = $this->createDataset('after', $afterSamples);

        $comparator = new DatasetComparator();
        $result = $comparator->compare($before, $after);

        // Total diff = 10 (5 vs 15), exceeds threshold of 6.
        $dbwriteFailures = array_filter($result->getFailures(), fn($r) => $r->key === 'dbwrites');
        $this->assertNotEmpty($dbwriteFailures, 'Real dbwrite regression of +2 per request should be caught');
    }
}

