<?php

namespace App\Http\Controllers;

use App\Year;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PublicHolidayController extends Controller
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param Request $request
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findByYear(Request $request)
    {
        $year = $request->get('year');
        $params = [
            'action' => 'getHolidaysForYear', // method name to get holidays for given year in given country
            'year' => $year,
            'country' => 'zaf', // @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
            'holidayType' => 'public_holiday',
        ];

        if (!Year::where('year', $year)->count()) {
            $response = $this->endpointRequest('enrico/json/v2.0', $params);

            $yearId = DB::table('years')->insertGetId(
                ['year' => $year]
            );

            $yearHolidaysData = $this->buildYearHolidaysData($yearId, $response);

            DB::table('year_holidays')->insert(
                $yearHolidaysData
            );

            return Session::flash('info', "New year {$year} added to the database.");
        }
    }

    /**
     * @param string $url
     * @param array $params
     *
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function endpointRequest(string $url , array $params)
    {
        try {
            $response = $this->client->request('GET', $url, ['query' => $params]);
        } catch (\Exception $e) {
            return [];
        }

        return $this->responseHandler($response->getBody()->getContents());
    }

    /**
     * @param $response
     *
     * @return array|mixed
     */
    public function responseHandler($response)
    {
        if ($response) {
            return json_decode($response);
        }

        return [];
    }

    /**
     * Build any array data that will be used to insert new records in the database
     *
     * @param int $yearId
     * @param array $response
     *
     * @return array
     */
    public function buildYearHolidaysData(int $yearId, array $response)
    {
        $data = [];
        foreach ($response as $key => $result) {
            $date = $result->date;
            $name = $result->name;

            $data[$key]['year_id'] = $yearId;
            $data[$key]['name'] = is_array($name) ? $name[0]->text : 'No name';
            $data[$key]['day'] = $date->day;
            $data[$key]['month'] = $date->month;
            $data[$key]['day_of_week'] = $date->dayOfWeek;
        }

        return $data;
    }

}
