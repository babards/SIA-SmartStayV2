<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyApplication;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'landlord':
                return redirect()->route('landlord.properties.index');
            case 'tenant':
                return redirect()->route('tenant.properties.index');
            default:
                return redirect()->route('login');
        }
    }

    public function adminDashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_landlords' => User::where('role', 'landlord')->count(),
            'total_tenants' => User::where('role', 'tenant')->count(),
            'total_properties' => Property::count(),
            'available_properties' => Property::where('propertyStatus', 'Available')->count(),
            'total_applications' => PropertyApplication::count(),
            'pending_applications' => PropertyApplication::where('status', 'pending')->count(),
        ];

        //  Fetch properties with location data
        $properties = Property::select('propertyID', 'propertyName', 'propertyLocation', 'propertyRent', 'propertyStatus', 'latitude', 'longitude')->get();
        return view('admin.dashboard', compact('stats', 'properties'));

    }

}