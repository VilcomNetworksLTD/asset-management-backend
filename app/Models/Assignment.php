<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'Employee_ID');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'Asset_ID');
    }
}