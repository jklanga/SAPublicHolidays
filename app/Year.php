<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Year extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['year'];

    public function holidays()
    {
        return $this->hasMany(YearHoliday::class);
    }
}
