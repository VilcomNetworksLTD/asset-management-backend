<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $table = 'maintenance';

    protected $fillable = [
        'Asset_ID',         
        'Ticket_ID',        
        'Request_Date',     
        'Completion_Date',  
        'Maintenance_Type', 
        'Description',      
        'Cost',             
        'Status_ID',        
        'Maintenance_Date'  
    ];

    
    public function asset() {
        return $this->belongsTo(Asset::class, 'Asset_ID');
    }

    public function status() {
        return $this->belongsTo(Status::class, 'Status_ID');
    }
}