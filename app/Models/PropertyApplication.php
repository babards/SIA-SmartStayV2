<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'user_id',
        'status',
        'application_date',
        'message',
    ];

    protected $casts = [
        'application_date' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'propertyID');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
