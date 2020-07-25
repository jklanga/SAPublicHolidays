<?php

namespace App\Console\Commands;

use App\Util\EnricoApi;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PublicHolidaysCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SAHolidays:fetch
                            {year : The year [yyyy]}
                            {--U|update=no : Update the existing holidays in database [no, yes, n, y]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches SA public holidays';

    /**
     * @var Client
     */
    protected $client;

    /**
     * Create a new command instance.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->client = $client;
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $year = $this->argument('year');
        $update = strtolower($this->option('update'));

        $validator = $this->validator(['year' => $year, 'update' => $update]);
        if ($validator->fails()) {
            $this->displayErrorMessages($validator);
        } else {
            $this->info("\u{23F3} Fetching SA holidays found for year {$year}...");

            $updateHolidays = in_array($update, ['y', 'yes']);
            $holidays = (new EnricoApi($this->client, true))->findByYear($year, $updateHolidays);

            $this->checkExceptionMessages($holidays);

            $this->info("\u{2705} " . sprintf("%u SA holidays found for year %s", count($holidays), $year));
        }
    }

    /**
     * @param array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $rules = [
            'year' => ['required', 'numeric', 'digits:4'],
            'update' => [Rule::in(['no', 'yes', 'n', 'y'])],
        ];
        $messages = [
            'in' => 'The :attribute option must be one of the following types: :values',
            'digits' => 'The :attribute must be :digits digits [yyyy]',
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * @param $holidays
     */
    private function checkExceptionMessages($holidays): void
    {
        if (is_array($holidays) && array_key_exists('error', $holidays)) {
            $this->error("\u{274C} " . $holidays['error']);
            exit;
        }

        if (is_array($holidays) && array_key_exists('warning', $holidays)) {
            $this->warn("\u{26A0} " . $holidays['warning']);
            exit;
        }
    }

    /**
     * @param \Illuminate\Contracts\Validation\Validator $validator
     */
    private function displayErrorMessages(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        foreach ($validator->messages()->all() as $error) {
            $this->error("\u{274C} " . $error);
        }

        $this->call('help', ['command_name' => 'SAHolidays:fetch', 'format' => 'raw']);
    }
}
