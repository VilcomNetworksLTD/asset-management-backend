<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;


class Asset extends Model
{
    use SoftDeletes;

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
        'depreciation_value',
        'current_value',
        'location',
        'Timestamp',
        'Issue_ID',
        'Purchase_Date',
        'warranty_expiry',
        'warranty_image_path',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['warranty_image_url'];

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
        return $this->belongsTo(Status::class, 'Status_ID');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'Asset_ID', 'id');
    }

    /**
     * ADDED: The assignments relationship
     */
    public function assignments(): HasMany
    {
        // Assuming your assignments table uses 'Asset_ID' as the foreign key
        return $this->hasMany(Assignment::class, 'Asset_ID', 'id');
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
    public function specs(): HasOne
{
    return $this->hasOne(AssetSpec::class, 'asset_id');

}
public function activityLogs()
{
    return $this->hasMany(ActivityLog::class);
}

    /* ACCESSORS */

    /**
     * Get the full URL for the warranty image.
     *
     * @return string|null
     */
    public function getWarrantyImageUrlAttribute(): ?string
    {
        if ($this->warranty_image_path) {
            return Storage::disk('public')->url($this->warranty_image_path);
        }
        return null;
    }
    public function toners()
{
    // A printer has many toner lifecycle logs
    return $this->hasMany(AssetConsumable::class, 'asset_id');
}

// Helper to get ONLY the currently active ink in the printer
public function activeToners()
{
    return $this->hasMany(AssetConsumable::class, 'asset_id')->whereNull('depleted_at');
}
/**
 * Get all toner replacement logs for this printer.
 */
public function tonerHistory()
{
    return $this->hasMany(\App\Models\AssetConsumable::class, 'asset_id');
}
}