<?php

namespace App\Tests\Model;

use App\Model\Scenario;
use PHPUnit\Framework\TestCase;

class ScenarioTest extends TestCase
{
    public function testNormalizeName(): void
    {
        $this->assertSame('Login', Scenario::normalizeName('  Login  '));
        $this->assertSame('Frontpage not logged', Scenario::normalizeName('Frontpage not logged'));
    }

    public function testGetTotalWithNumericIntegers(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['dbreads' => 10, 'dbwrites' => 2]);
        $scenario->addData(['dbreads' => 20, 'dbwrites' => 3]);

        $this->assertSame(30.0, $scenario->getTotal('dbreads'));
        $this->assertSame(5.0, $scenario->getTotal('dbwrites'));
    }

    public function testGetTotalWithNumericStrings(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['memoryused' => '4.2', 'filesincluded' => '405']);
        $scenario->addData(['memoryused' => '7.3', 'filesincluded' => '1049']);

        $this->assertSame(11.5, $scenario->getTotal('memoryused'));
        $this->assertSame(1454.0, $scenario->getTotal('filesincluded'));
    }

    public function testGetTotalWithNonBreakingSpaceBytes(): void
    {
        // Simulates actual Moodle output: "569\xC2\xA0bytes" (non-breaking space before "bytes").
        $scenario = new Scenario('Frontpage not logged');
        $scenario->addData(['sessionsize' => "569\xC2\xA0bytes"]);
        $scenario->addData(['sessionsize' => "539\xC2\xA0bytes"]);

        // Should extract the numeric portion and sum it.
        $total = $scenario->getTotal('sessionsize');
        $this->assertSame(1108.0, $total);
    }

    public function testGetTotalWithNonBreakingSpaceKB(): void
    {
        // Simulates "4.4\xC2\xA0" (non-breaking space, no unit suffix — already KB).
        $scenario = new Scenario('Login');
        $scenario->addData(['sessionsize' => "4.4\xC2\xA0"]);
        $scenario->addData(['sessionsize' => "5.0\xC2\xA0"]);

        $total = $scenario->getTotal('sessionsize');
        $this->assertSame(9.4, $total);
    }

    public function testGetTotalWithCleanNumericSessionSize(): void
    {
        // After the recorder.bsf fix, sessionsize should arrive as a clean number.
        $scenario = new Scenario('Login');
        $scenario->addData(['sessionsize' => '0.556']);
        $scenario->addData(['sessionsize' => '4.4']);

        $total = $scenario->getTotal('sessionsize');
        $this->assertEqualsWithDelta(4.956, $total, 0.001);
    }

    public function testGetTotalSkipsMissingKey(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['dbreads' => 10]);
        $scenario->addData(['dbwrites' => 3]); // no 'dbreads' key

        $this->assertSame(10.0, $scenario->getTotal('dbreads'));
    }

    public function testGetTotalReturnsZeroForAllMissingKeys(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['dbreads' => 10]);

        $this->assertSame(0.0, $scenario->getTotal('nonexistent'));
    }

    public function testGetTotalSkipsCompletelyNonNumericValues(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['sessionsize' => 'not-a-number']);
        $scenario->addData(['sessionsize' => '4.4']);

        // Non-numeric value is skipped, only 4.4 counted.
        $this->assertSame(4.4, $scenario->getTotal('sessionsize'));
    }

    public function testGetAverageWithNumericValues(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['dbreads' => 10]);
        $scenario->addData(['dbreads' => 20]);
        $scenario->addData(['dbreads' => 30]);

        $this->assertSame(20.0, $scenario->getAverage('dbreads'));
    }

    public function testGetAverageWithNonBreakingSpaceBytes(): void
    {
        $scenario = new Scenario('Frontpage');
        $scenario->addData(['sessionsize' => "569\xC2\xA0bytes"]);
        $scenario->addData(['sessionsize' => "539\xC2\xA0bytes"]);

        $average = $scenario->getAverage('sessionsize');
        $this->assertSame(554.0, $average);
    }

    public function testGetAverageSkipsMissingKeys(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['dbreads' => 10]);
        $scenario->addData([]); // missing key
        $scenario->addData(['dbreads' => 20]);

        // Average of 10 and 20 only (count=2).
        $this->assertSame(15.0, $scenario->getAverage('dbreads'));
    }

    public function testGetAverageReturnsZeroWhenNoData(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['something' => 'else']);

        $this->assertSame(0.0, $scenario->getAverage('dbreads'));
    }

    public function testGetTotalWithZeroValue(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['sessionsize' => '0']);
        $scenario->addData(['sessionsize' => '0']);

        $this->assertSame(0.0, $scenario->getTotal('sessionsize'));
    }

    public function testGetTotalWithFloatValues(): void
    {
        $scenario = new Scenario('Login');
        $scenario->addData(['dbquerytime' => 0.01037]);
        $scenario->addData(['dbquerytime' => 0.01178]);

        $this->assertEqualsWithDelta(0.02215, $scenario->getTotal('dbquerytime'), 0.00001);
    }
}

