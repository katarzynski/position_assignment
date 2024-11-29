<?php

namespace App\Models;

use App\Models\Part;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    public function parts()
    {
        return $this->hasMany(Part::class);
    }
}
