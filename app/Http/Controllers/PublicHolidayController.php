<?php

namespace App\Http\Controllers;

use App\Year;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $year = $request->get('year') ?? date('Y');
        $holidays = [];

        if ($yearObj = Year::where('year', $year)->first()) {
            $holidays = $yearObj->holidays()->get();
        }

        return view('list', ['year' => $year, 'holidays' => $holidays]);
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'year' => ['required', 'numeric'],
        ]);
    }

    /**
     * @param Request $request
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findByYear(Request $request)
    {
        $validator = $this->validator($request->all());
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $year = $request->get('year');
        $params = [
            'action' => 'getHolidaysForYear', // method name to get holidays for given year in given country
            'year' => $year,
            'country' => 'zaf', // @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
            'holidayType' => 'public_holiday',
        ];

        if (!Year::where('year', $year)->count()) {
            if ($response = $this->endpointRequest('enrico/json/v2.0', $params)) {
                try {
                    $yearId = DB::table('years')->insertGetId(
                        ['year' => $year]
                    );

                    $yearHolidaysData = $this->buildYearHolidaysData($yearId, $response);

                    DB::table('year_holidays')->insert(
                        $yearHolidaysData
                    );

                    Session::flash('info', count($yearHolidaysData) . " new holidays for year {$year} added to the database.");
                } catch (\Exception $ex) {
                    Session::flash('Error', "There was an error trying to insert new year {$year}.");
                }
            }
        }

        return Redirect::to('/?year=' . $year);
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
        } catch (\Exception $ex) {
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
     * @param $response
     *
     * @return array
     */
    public function buildYearHolidaysData(int $yearId, $response)
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
