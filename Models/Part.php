<?php

namespace App\Models;

use App\Models\Episode;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    public $fillable = [
        'position'
    ];

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
