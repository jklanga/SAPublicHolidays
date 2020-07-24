<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{!!   Config::get('app.name') !!}</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <!-- Bootstrap 4 -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
    </head>
    <body>
        <div class="container">
            <h2>
                SA Public Holidays <small>{!! $year ?? $year !!}</small>
            </h2>

            <div class="row">
                <div class="col-md-12">
                    <form action="{{ URL::route('findbyyear') }}" method="POST">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <label for="year">Year</label>
                            <input type="text" class="form-control" name="year" placeholder="Enter year">
                            <small id="yearHelp" class="form-text text-muted">Fetch SA public holidays by year.</small>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" value="1" name="updateHolidays">
                            <label class="form-check-label" for="updateHolidays">Update holidays if exists in the database</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped table-condensed cf wt-responsive-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Day of Week</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if ($holidays->count())
                            @php
                                $dayOfWeekMap = ['', 'Monday', 'Tuesday', 'Wednesay', 'Thurday', 'Friday', 'Saturday', 'Sunday'];
                            @endphp

                            @foreach($holidays as $holiday)
                                @php
                                    $ymd = "{$holiday->year->year}/{$holiday->month}/{$holiday->day}";
                                    $date = date('F, jS', strtotime($ymd));
                                @endphp
                                <tr>
                                    <td><b>{!! $holiday->name !!}</b></td>
                                    <td>{!! $date !!}</td>
                                    <td>{!! $dayOfWeekMap[$holiday->day_of_week] !!}</td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
