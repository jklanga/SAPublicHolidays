<?php


namespace App\Util;


use App\Year;
use App\YearHoliday;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class EnricoApi
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var bool
     */
    protected $isConsoleCommand;

    public function __construct(Client $client, $isConsoleCommand = false)
    {
        $this->client = $client;
        $this->isConsoleCommand = $isConsoleCommand;
    }

    /**
     * @param int $year
     * @param bool $updateHolidays
     *
     * @return array|mixed
     * @throws GuzzleException
     */
    public function findByYear(int $year, $updateHolidays = false)
    {
        $params = [
            'action' => 'getHolidaysForYear', // method name to get holidays for given year in given country
            'year' => $year,
            'country' => 'zaf', // @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
            'holidayType' => 'public_holiday',
        ];

        // Fetches SA holidays from the API
        $response = $this->endpointRequest('enrico/json/v2.0', $params);

        if (!is_array($response) && $response->error) {
            return $this->exceptionHandler(
                $response->error,
                null,
                'warning'
            );
        }

        $yearExists = Year::where('year', $year)->count();

        if (!$yearExists || $updateHolidays) {
            $currentDate = date('Y-m-d H:i:s');

            try {
                $yearId = Year::firstOrCreate(
                    ['year' => $year],
                    ['created_at' => $currentDate, 'updated_at' => $currentDate]
                )->id;

                $yearHolidaysData = [];

                if ($yearId) {
                    $yearHolidaysData = $this->buildYearHolidaysData($yearId, $response);

                    DB::table('year_holidays')->insert(
                        $yearHolidaysData
                    );
                }

                if ($updateHolidays) {
                    $msg = sprintf("%u new SA holiday(s) for year %s added to the database.", count($yearHolidaysData), $year);
                    if ($this->isConsoleCommand) {

                        echo $msg . PHP_EOL;
                    } else {
                        Session::flash('info', $msg);
                    }
                }
            } catch (\Exception $ex) {
                return $this->exceptionHandler(
                    "There was an error trying to insert new year {$year}",
                    $ex->getMessage()
                );
            }
        }

        return Year::where('year', $year)->first()->holidays()->get();
    }

    /**
     * @param string $url
     * @param array $params
     *
     * @return array|mixed
     * @throws GuzzleException
     */
    private function endpointRequest(string $url , array $params)
    {
        try {
            $response = $this->client->request('GET', $url, ['query' => $params]);
        } catch (\Exception $ex) {
            return $this->exceptionHandler(
                'There was an error fetching the SA holidays for year ' . $params['year'],
                $ex->getMessage()
            );
        }

        return $this->responseHandler($response->getBody()->getContents());
    }

    /**
     * @param $response
     *
     * @return array|mixed
     */
    private function responseHandler($response)
    {
        if ($response) {
            return json_decode($response);
        }

        return [];
    }

    /**
     * Build an array data that will be used to insert new records in the database
     *
     * @param int $yearId
     * @param $response
     *
     * @return array
     */
    private function buildYearHolidaysData(int $yearId, $response): array
    {
        $currentDate = date('Y-m-d H:i:s');
        $data = [];
        foreach ($response as $key => $result) {
            $date = $result->date;
            $name = $result->name;

            // Check if the record with the same date exists
            $dateAttributes = ['year_id' => $yearId, 'month' => $date->month, 'day' => $date->day];
            $recordExits = YearHoliday::where($dateAttributes)->first();

            if ($recordExits === null) {
                $data[$key]['year_id'] = $yearId;
                $data[$key]['name'] = is_array($name) ? $name[0]->text : 'No name';
                $data[$key]['day'] = $date->day;
                $data[$key]['month'] = $date->month;
                $data[$key]['day_of_week'] = $date->dayOfWeek;
                $data[$key]['created_at'] = $currentDate;
                $data[$key]['updated_at'] = $currentDate;
            }
        }

        return $data;
    }

    /**
     * @param string $msg
     * @param string|null $logMessage
     * @param string $msgType
     *
     * @return array
     */
    private function exceptionHandler(string $msg, string $logMessage = null, $msgType = 'error'): array
    {
        $logMessage = $logMessage ? $msg . ': ' . $logMessage : $msg;
        Log::error($logMessage);

        if ($this->isConsoleCommand) {
            return [$msgType => $msg];
        }

        Session::flash($msgType, $msg);

        return [];
    }
}
