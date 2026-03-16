<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    /**
     * Since reports pull from other tables, we don't need a 
     * dedicated 'reports' table. We use this model for logic.
     */
    protected $guarded = [];

    // You can add helper methods here for specific calculations.
}