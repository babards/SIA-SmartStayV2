<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends Model
{
    use HasFactory;

    protected $primaryKey = 'propertyID';
    public $timestamps = false; // We'll handle timestamps manually

    protected $table = 'properties';

    protected $fillable = [
        'userID',
        'propertyName',
        'propertyDescription',
        'propertyLocation',
        'propertyRent',
        'propertyImage',
        'property_images',
        'propertyStatus',
        'latitude',
        'longitude',
        'vacancy',
        'propertyCreatedAt',
        'propertyUpdatedAt',
        'number_of_boarders'
    ];

    protected $casts = [
        'property_images' => 'array',
    ];

    // Relationship to landlord
    public function landlord()
    {
        return $this->belongsTo(User::class, 'userID');
    }

    // Relationship to applications
    public function applications()
    {
        return $this->hasMany(PropertyApplication::class, 'property_id', 'propertyID');
    }

    public function boarders()
    {
        return $this->hasMany(PropertyBoarder::class, 'property_id', 'propertyID');
    }

    // Helper method to get the main image (first image or fallback to propertyImage)
    public function getMainImageAttribute()
    {
        if ($this->property_images && count($this->property_images) > 0) {
            return $this->property_images[0];
        }
        return $this->propertyImage;
    }

    // Helper method to get all images
    public function getAllImagesAttribute()
    {
        $images = [];
        
        // Add images from property_images array
        if ($this->property_images && is_array($this->property_images)) {
            $images = array_merge($images, $this->property_images);
        }
        
        // If we have less than 3 images and propertyImage exists, add it as fallback
        if (count($images) === 0 && $this->propertyImage) {
            $images[] = $this->propertyImage;
        }
        
        return $images;
    }
}
