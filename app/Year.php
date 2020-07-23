<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Year extends Model
{
    public function holidays()
    {
        return $this->hasMany(YearHoliday::class);
    }
}
