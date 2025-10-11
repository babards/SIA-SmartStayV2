<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyApplication;
use Illuminate\Support\Facades\Mail;

class GuestPropertyController extends Controller
{
    // Show property details to guests
    public function show($property)
    {
        $property = Property::with('landlord')->findOrFail($property);
        return view('Guest.properties.show', compact('property'));
    }

    // Handle guest application
    public function apply(Request $request, $propertyId)
    {
        $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $property = Property::findOrFail($propertyId);

        // Optionally, you can save to PropertyApplication or send an email to landlord
        // Here, we'll save as a PropertyApplication with guest info in the message
        PropertyApplication::create([
            'property_id' => $property->propertyID,
            'tenant_id' => null, // guest
            'message' => "Guest Name: {$request->guest_name}\nGuest Email: {$request->guest_email}\nMessage: {$request->message}",
            'status' => 'pending',
        ]);

        // Optionally, notify landlord via email (uncomment if needed)
        // Mail::to($property->landlord->email)->send(new GuestPropertyApplicationMail($property, $request->guest_name, $request->guest_email, $request->message));

        return back()->with('crud_success', 'Your application has been submitted!');
    }

    // Landing page with filters
    public function index(Request $request)
    {
        $query = Property::where('propertyStatus', 'Available');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('propertyName', 'like', "%{$search}%")
                    ->orWhere('propertyLocation', 'like', "%{$search}%")
                    ->orWhere('propertyDescription', 'like', "%{$search}%")
                    ->orWhere('propertyRent', 'like', "%{$search}%");
            });
        }

        // Location filter
        if ($request->filled('location_filter')) {
            $barangay = $request->input('location_filter');
            $query->where('propertyLocation', 'like', "%$barangay%");
        }

        // Price filter
        if ($request->filled('price_filter')) {
            switch ($request->input('price_filter')) {
                case 'below_1000':
                    $query->where('propertyRent', '<', 1000);
                    break;
                case '1000_2000':
                    $query->whereBetween('propertyRent', [1000, 2000]);
                    break;
                case '2000_3000':
                    $query->whereBetween('propertyRent', [2000, 3000]);
                    break;
                case 'above_3000':
                    $query->where('propertyRent', '>', 3000);
                    break;
            }
        }

        $properties = $query->orderBy('propertyCreatedAt', 'desc')->paginate(9);
        return view('welcome', compact('properties'));
    }
}
