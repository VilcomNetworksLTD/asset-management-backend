<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    protected $fillable = [
        'Asset_ID',
        'Employee_ID',
        'Sender_ID',
        'Receiver_ID',
        'Status_ID',
        'Transfer_Date',
        'Type',
        'Workflow_Status',
        'Sender_Condition',
        'Admin_Condition',
        'Included_Items',
        'Missing_Items',
        'Notes',
        'Actioned_By',
        'Actioned_At',
    ];

    protected $casts = [
        'Transfer_Date' => 'datetime',
        'Actioned_At' => 'datetime',
        'Included_Items' => 'array',
        'Missing_Items' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'Asset_ID', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Employee_ID', 'id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Sender_ID', 'id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Receiver_ID', 'id');
    }

    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Actioned_By', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'Status_ID', 'id');
    }
}
