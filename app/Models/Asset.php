<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $fillable = [
        'Asset_Name', 
        'Asset_Category', 
        'Serial_No', 
        'Supplier_ID', 
        'Employee_ID', 
        'Status_ID', 
        'Warranty_Details',
        'License_Info',
        'Price',
        'Timestamp',
        'Issue-ID',
    ];

    /* RELATIONSHIPS */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Employee_ID', 'id');
    }

    
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'Supplier_ID', 'id');
    }

    
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'Status_ID', 'id');
    }

    
    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'Asset_ID', 'id');
    }

    
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'Asset_ID', 'id');
    }

   
    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'Asset_ID', 'id');
    }
    
   
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class, 'Asset_ID', 'id');
    }
}