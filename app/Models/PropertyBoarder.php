<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyBoarder extends Model
{
    use HasFactory;

    // Define the table name if it doesn't follow Laravel's naming convention
    protected $table = 'property_boarders';

    protected $fillable = [
        'property_id',
        'user_id',
        'status',
    ];

    // Relationships
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'propertyID');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class ,'user_id');
    }
}
