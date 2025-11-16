<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyApplication;
use App\Models\PropertyBoarder;

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
                return redirect()->route('landlord.dashboard');
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
            'occupied_properties' => Property::where('propertyStatus', 'Fullyoccupied')->count(),
        ];

        //  Fetch properties with location data
        $properties = Property::select('propertyID', 'propertyName', 'propertyLocation', 'propertyRent', 'propertyStatus', 'latitude', 'longitude')->get();
        return view('Admin.dashboard', compact('stats', 'properties'));

    }

    public function landlordDashboard()
    {
        $landlordId = auth()->id();

        // Get all properties owned by the landlord
        $landlordProperties = Property::where('userID', $landlordId)->pluck('propertyID');

        // Property Statistics
        $propertyStats = [
            'total_properties' => Property::where('userID', $landlordId)->count(),
            'available_properties' => Property::where('userID', $landlordId)
                ->where('propertyStatus', 'Available')->count(),
            'occupied_properties' => Property::where('userID', $landlordId)
                ->where('propertyStatus', 'Fullyoccupied')->count(),
            'maintenance_properties' => Property::where('userID', $landlordId)
                ->where('propertyStatus', 'Maintenance')->count(),
        ];

        // Application Statistics - for landlord's properties only
        $applicationStats = [
            'total_applications' => PropertyApplication::whereIn('property_id', $landlordProperties)->count(),
            'pending_applications' => PropertyApplication::whereIn('property_id', $landlordProperties)
                ->where('status', 'pending')->count(),
            'approved_applications' => PropertyApplication::whereIn('property_id', $landlordProperties)
                ->where('status', 'approved')->count(),
            'rejected_applications' => PropertyApplication::whereIn('property_id', $landlordProperties)
                ->where('status', 'rejected')->count(),
            'cancelled_applications' => PropertyApplication::whereIn('property_id', $landlordProperties)
                ->where('status', 'cancelled')->count(),
        ];

        // Boarder Statistics - for landlord's properties only
        $boarderStats = [
            'total_boarders' => PropertyBoarder::whereIn('property_id', $landlordProperties)->count(),
            'active_boarders' => PropertyBoarder::whereIn('property_id', $landlordProperties)
                ->where('status', 'active')->count(),
            'kicked_boarders' => PropertyBoarder::whereIn('property_id', $landlordProperties)
                ->where('status', 'kicked')->count(),
        ];

        // Fetch properties with location data for map (only landlord's properties)
        $properties = Property::where('userID', $landlordId)
            ->select('propertyID', 'propertyName', 'propertyLocation', 'propertyRent', 'propertyStatus', 'latitude', 'longitude')
            ->get();

        return view('Landlord.dashboard', compact('propertyStats', 'applicationStats', 'boarderStats', 'properties'));
    }

}