<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    /**
     * Since reports pull from other tables, we don't need a 
     * dedicated 'reports' table. We use this model for logic.
     */
    protected $guarded = [];

    // You can add helper methods here for specific calculations.
}