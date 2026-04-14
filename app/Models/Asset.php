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
        'system_name',
        'system_manufacturer',
        'evidence_image',
        
        // --- NEW DYNAMIC FIELDS ---
        'category_id', // Replaces 'Asset_Category'
        'Asset_Category', // Kept for backward compatibility/DB constraints
        'location_id', // Replaces 'location'
        'barcode',     // New Barcode field
        // --------------------------

        'Serial_No', 
        'Supplier_ID', 
        'Employee_ID', 
        'Status_ID', 
        'Warranty_Details',
        'License_Info',
        'Price',
        'depreciation_value',
        'current_value',
        'Timestamp',
        'Issue_ID',
        'Purchase_Date',
        'warranty_expiry',
        'warranty_image_path',
        'custom_attributes',
        'created_by',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['warranty_image_url', 'transfer_reason', 'return_reason', 'specific_data'];

    protected $casts = [
        'custom_attributes' => 'array',
    ];

    /* ==========================================
       NEW DYNAMIC RELATIONSHIPS
       ========================================== */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function locationModel(): BelongsTo
    {
        // Named 'locationModel' just in case you still have the old string 'location' 
        // column hanging around in your DB to prevent naming conflicts.
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Link to the central polymorphic specification table.
     * This REPLACES the old `public function specs()`
     */
    public function specification(): HasOne
    {
        return $this->hasOne(AssetSpecification::class);
    }

    /* ==========================================
       EXISTING RELATIONSHIPS (Untouched)
       ========================================== */

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

    public function assignments(): HasMany
    {
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

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class, 'Asset_ID', 'id');
    }

    public function toners()
    {
        return $this->hasMany(AssetConsumable::class, 'asset_id');
    }

    public function activeToners()
    {
        return $this->hasMany(AssetConsumable::class, 'asset_id')->whereNull('depleted_at');
    }

    public function tonerHistory()
    {
        return $this->hasMany(\App\Models\AssetConsumable::class, 'asset_id');
    }

    /* ==========================================
       ACCESSORS
       ========================================== */

    /**
     * HELPER METHOD: This makes getting dynamic spec data much easier in Vue!
     * It appends 'specific_data' to your JSON responses.
     */
    public function getSpecificDataAttribute()
    {
        return $this->specification ? $this->specification->specificationable : null;
    }

    public function getWarrantyImageUrlAttribute(): ?string
    {
        if ($this->warranty_image_path) {
            return Storage::disk('public')->url($this->warranty_image_path);
        }
        return null;
    }

    public function getTransferReasonAttribute()
    {
        return $this->transfers()
            ->whereNotIn('Workflow_Status', ['closed', 'rejected'])
            ->latest()
            ->first()?->reason;
    }

    public function getReturnReasonAttribute()
    {
        return $this->returnRequests()
            ->whereNotIn('Workflow_Status', ['closed', 'rejected'])
            ->latest()
            ->first()?->reason;
    }
}