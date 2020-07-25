<?php

namespace App\Http\Controllers;

use App\Util\EnricoApi;
use App\Year;
use Barryvdh\DomPDF\Facade as PDF;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PublicHolidayController extends Controller
{
    /**
     * @var Client
     */
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

        $viewData = ['year' => $year, 'holidays' => $holidays, 'downloadPdf' => false];

        return view('list', $viewData);
    }

    /**
     * @param array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator(array $data)
    {
        $rules = [
            'year' => ['required', 'numeric', 'digits:4'],
        ];
        $messages = [
            'digits' => 'The :attribute must be :digits digits [yyyy]',
        ];

        return Validator::make($data, $rules, $messages);
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
            return Redirect::to('/')->withErrors($validator)->withInput();
        }

        $year = $request->get('year');
        $updateHolidays = $request->get('updateHolidays');

        $holidays = (new EnricoApi($this->client))->findByYear($year, $updateHolidays);

        Session::flash('info', count($holidays) . " SA holidays found for year {$year}.");

        $viewData = ['year' => $year, 'holidays' => $holidays, 'downloadPdf' => false];

        return view('list', $viewData);
    }

    /**
     * @param $year
     *
     * @return mixed
     */
    public function downloadPDF($year) {
        $holidays = Year::where('year', $year)->first()->holidays()->get();
        $viewData = ['year' => $year, 'holidays' => $holidays, 'downloadPdf' => true];
        $pdf = PDF::loadView('list', $viewData);

        return $pdf->download("{$year}_SA_Public_Holidays.pdf");
    }
}
