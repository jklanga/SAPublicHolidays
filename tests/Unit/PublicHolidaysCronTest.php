<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicHolidaysCronTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test with a valid year.
     *
     * @return void
     */
    public function testValidYearArgument()
    {
        $this->artisan('SAHolidays:fetch 2020')
            ->expectsOutput("⏳ Fetching SA holidays found for year 2020...")
            ->assertExitCode(0);
    }

    /**
     * Test required year.
     *
     * @return void
     */
    public function testRequiredYearArgument()
    {
        $this->expectExceptionMessage('Not enough arguments (missing: "year").');

        $this->artisan('SAHolidays:fetch')
            ->expectsOutput('Not enough arguments (missing: "year").')
            ->assertExitCode(0);
    }

    /**
     * Test invalid year digits.
     *
     * @return void
     */
    public function testInvalidDigitsYearArgument()
    {
        $this->artisan('SAHolidays:fetch 20')
            ->expectsOutput('❌ The year must be 4 digits [yyyy]')
            ->assertExitCode(0);
    }

    /**
     * Test non-numeric year.
     *
     * @return void
     */
    public function testNonNumericYearArgument()
    {
        $this->artisan('SAHolidays:fetch abc')
            ->expectsOutput('❌ The year must be a number.')
            ->assertExitCode(0);
    }

    /**
     * Test the valid update option.
     *
     * @return void
     */
    public function testValidUpdateHolidaysOption()
    {
        $this->artisan('SAHolidays:fetch  2020 -U y')
            ->expectsOutput('⏳ Fetching SA holidays found for year 2020...')
            ->assertExitCode(0);
    }

    /**
     * Test the invalid update option.
     *
     * @return void
     */
    public function testInvalidUpdateHolidaysOption()
    {
        $this->artisan('SAHolidays:fetch  2020 -U z')
            ->expectsOutput('❌ The update option must be one of the following types: no, yes, n, y')
            ->assertExitCode(0);
    }


    /**
     * Test not supported year.
     *
     * @return void
     */
    public function testNotSupportedYearArgument()
    {
        $this->expectExceptionMessage('Dates before 1 Jan 2013 are not supported');

        $this->artisan('SAHolidays:fetch 1999')
            ->expectsOutput('⏳ Fetching SA holidays found for year 1999...')
            ->expectsOutput('⚠ Dates before 1 Jan 2013 are not supported')
            ->assertExitCode(0);
    }
}
