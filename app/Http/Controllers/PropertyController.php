<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\PropertyApplication;
use App\Models\PropertyBoarder;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsActivity;
use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationRejectedMail;
use Illuminate\Support\Facades\Mail;
use App\Mail\BoarderKickedMail;
use App\Mail\ApplicationReceivedMail;
use App\Mail\ApplicationCancelledMail;
use App\Services\WeatherService;

class PropertyController extends Controller
{
    use LogsActivity;

    // Show the landlord's property management page
    public function index(Request $request)
    {
        $query = Property::where('userID', auth()->id());

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('propertyName', 'like', "%{$search}%")
                    ->orWhere('propertyLocation', 'like', "%{$search}%")
                    ->orWhere('propertyDescription', 'like', "%{$search}%")
                    ->orWhere('propertyRent', 'like', "%{$search}%")
                    ->orWhere('propertyStatus', 'like', "%{$search}%");
            });
        }

        // Add location filter
        if ($request->filled('location_filter')) {
            $location = $request->input('location_filter');
            
            // Handle different location formats
            // For "Valencia City", also search for "Valencia"
            // For "Malaybalay City", also search for "Malaybalay"
            $searchTerms = [$location];
            
            // Add variations for city names
            if (strpos($location, ' City') !== false) {
                $searchTerms[] = str_replace(' City', '', $location);
            } else {
                $searchTerms[] = $location . ' City';
            }
            
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('propertyLocation', 'like', "%$term%");
                }
            });
        }

        // Add price range filter
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

        $properties = $query->orderBy('propertyCreatedAt', 'desc')->paginate(8);

        return view('landlord.properties.index', compact('properties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'propertyName' => 'required',
            'propertyLocation' => 'required',
            'propertyRent' => 'required|numeric',
            'vacancy' => 'required|numeric',
            'propertyStatus' => 'required|in:Available,Fullyoccupied,Maintenance',
            'propertyImages' => 'nullable|array|max:3',
            'propertyImages.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $data = $request->only([
            'propertyName',
            'propertyDescription',
            'propertyLocation',
            'propertyRent',
            'vacancy',
            'propertyStatus',
            'latitude',
            'longitude'
        ]);

        $data['userID'] = auth()->id();
        $data['propertyCreatedAt'] = now();
        $data['propertyUpdatedAt'] = now();

        // Handle multiple images
        if ($request->hasFile('propertyImages')) {
            $imagePaths = [];
            foreach ($request->file('propertyImages') as $image) {
                $imagePaths[] = $image->store('properties', 'public');
            }
            $data['property_images'] = $imagePaths;
        }

        $property = Property::create($data);

        //check if the vacancy is full and if full update the status of the property into occupied, not full into available
        $this->checkAndUpdatePropertyStatus($property->propertyID);


        $this->logActivity('create_property', "Created new property: {$property->propertyName}");

        return redirect()->route('landlord.properties.index')->with('crud_success', 'Property created successfully!');
    }


    public function update(Request $request, $id)
    {
        $property = \App\Models\Property::findOrFail($id);

        $request->validate([
            'propertyName' => 'required',
            'propertyLocation' => 'required',
            'propertyRent' => 'required|numeric',
            'vacancy' => 'required|numeric',
            'propertyStatus' => 'required|in:Available,Fullyoccupied,Maintenance',
            'propertyImages' => 'nullable|array|max:3',
            'propertyImages.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'removed_images' => 'nullable|array',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $data = $request->only([
            'propertyName',
            'propertyDescription',
            'propertyLocation',
            'propertyRent',
            'vacancy',
            'propertyStatus',
            'latitude',
            'longitude',
        ]);

        $data['propertyUpdatedAt'] = now();

        // Handle image updates
        $currentImages = $property->property_images ?? [];
        
        // Handle removed images
        if ($request->has('removed_images')) {
            $removedImages = $request->input('removed_images');
            foreach ($removedImages as $removedImage) {
                // Remove from current images array
                $currentImages = array_filter($currentImages, function($image) use ($removedImage) {
                    return $image !== $removedImage;
                });
                
                // Delete the physical file
                $imagePath = storage_path('app/public/' . $removedImage);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            // Reindex array
            $currentImages = array_values($currentImages);
        }
        
        // Handle new images
        if ($request->hasFile('propertyImages')) {
            $newImagePaths = [];
            foreach ($request->file('propertyImages') as $image) {
                $newImagePaths[] = $image->store('properties', 'public');
            }
            
            // Merge current images with new images (up to 3 total)
            $allImages = array_merge($currentImages, $newImagePaths);
            $data['property_images'] = array_slice($allImages, 0, 3);
        } else {
            $data['property_images'] = $currentImages;
        }

        $property->update($data);

        //check if the vacancy is full and if full update the status of the property into occupied, not full into available
        $this->checkAndUpdatePropertyStatus($property->propertyID);

        $this->logActivity('update_property', "Updated property: {$property->propertyName}");

        // Check redirect_to parameter to determine where to redirect
        if ($request->input('redirect_to') === 'index') {
            return redirect()->route('landlord.properties.index')->with('crud_success', 'Property updated successfully!');
        } else {
            return redirect()->route('landlord.properties.show', $property->propertyID)->with('crud_success', 'Property updated successfully!');
        }
    }


    public function destroy($id)
    {
        $property = \App\Models\Property::findOrFail($id);
        $propertyName = $property->propertyName;
        $property->delete();

        $this->logActivity('delete_property', "Deleted property: {$propertyName}");

        return redirect()->route('landlord.properties.index')->with('crud_success', 'Property deleted successfully!');
    }

    public function show($id)
    {
        $property = \App\Models\Property::findOrFail($id);
        return view('landlord.properties.show', compact('property'));
    }

    // Admin view of all properties
    public function adminIndex(Request $request)
    {
        $query = Property::with('landlord');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('propertyName', 'like', "%{$search}%")
                    ->orWhere('propertyLocation', 'like', "%{$search}%")
                    ->orWhere('propertyDescription', 'like', "%{$search}%")
                    ->orWhere('propertyRent', 'like', "%{$search}%")
                    ->orWhere('propertyStatus', 'like', "%{$search}%")
                    ->orWhereHas('landlord', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            })->orWhereHas('landlord', function ($q) use ($search) { // Search by landlord name
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Add landlord filter
        if ($request->filled('landlord_filter')) {
            $query->where('userID', $request->input('landlord_filter'));
        }

        // Add location filter
        if ($request->filled('location_filter')) {
            $location = $request->input('location_filter');
            
            // Handle different location formats
            // For "Valencia City", also search for "Valencia"
            // For "Malaybalay City", also search for "Malaybalay"
            $searchTerms = [$location];
            
            // Add variations for city names
            if (strpos($location, ' City') !== false) {
                $searchTerms[] = str_replace(' City', '', $location);
            } else {
                $searchTerms[] = $location . ' City';
            }
            
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('propertyLocation', 'like', "%$term%");
                }
            });
        }

        // Add price range filter
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

        $properties = $query->orderBy('propertyCreatedAt', 'desc')->paginate(8);
        $landlords = User::where('role', 'landlord')->orderBy('first_name')->get(); // Fetch landlords for dropdowns

        return view('admin.properties.index', compact('properties', 'landlords')); // Pass landlords to the index view
    }

    public function adminstore(Request $request)
    {
        // Debug: Log the request data
        \Log::info('Admin Store Request Data:', [
            'has_files' => $request->hasFile('propertyImages'),
            'files_count' => $request->hasFile('propertyImages') ? count($request->file('propertyImages')) : 0,
            'all_data' => $request->except(['propertyImages'])
        ]);
        
        $validator = Validator::make($request->all(), [
            'propertyName' => 'required|string|max:255',
            'propertyLocation' => 'required|string|max:255',
            'propertyRent' => 'required|numeric|min:0',
            'vacancy' => 'required|numeric|min:0',
            'propertyStatus' => 'required|in:Available,Fullyoccupied,Maintenance',
            'userID' => 'required|exists:users,id',
            'propertyImages' => 'nullable|array|max:3',
            'propertyImages.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ], [
            'propertyImages.*.image' => 'Each file must be a valid image.',
            'propertyImages.*.mimes' => 'Each image must be a file of type: jpeg, png, jpg, gif.',
            'propertyImages.*.max' => 'Each image may not be greater than 2MB.',
            'propertyImages.max' => 'You may upload a maximum of 3 images.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.properties.index')
                ->withErrors($validator)
                ->withInput()
                ->with('form_type', 'create');
        }

        $data = $request->only([
            'propertyName',
            'propertyDescription',
            'propertyLocation',
            'propertyRent',
            'vacancy',
            'propertyStatus',
            'userID',
            'latitude',
            'longitude'
        ]);

        $data['propertyCreatedAt'] = now();
        $data['propertyUpdatedAt'] = now();

        // Handle multiple images
        if ($request->hasFile('propertyImages')) {
            $imagePaths = [];
            foreach ($request->file('propertyImages') as $index => $image) {
                if ($image && $image->isValid()) {
                    try {
                        $imagePaths[] = $image->store('properties', 'public');
                    } catch (\Exception $e) {
                        \Log::error('Image upload failed for index ' . $index . ': ' . $e->getMessage());
                        return redirect()->route('admin.properties.index')
                            ->withErrors(['propertyImages.' . $index => 'Failed to upload image: ' . $e->getMessage()])
                            ->withInput()
                            ->with('form_type', 'create');
                    }
                }
            }
            $data['property_images'] = $imagePaths;
        }

        $property = Property::create($data);

        //check if the vacancy is full and if full update the status of the property into occupied, not full into available
        $this->checkAndUpdatePropertyStatus($property->propertyID);

        $this->logActivity('admin_create_property', "Admin created property: {$property->propertyName}");

        return redirect()->route('admin.properties.index')->with('crud_success', 'Property created successfully!');
    }


    public function adminupdate(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'propertyName' => 'required|string|max:255',
            'propertyLocation' => 'required|string|max:255',
            'propertyRent' => 'required|numeric|min:0',
            'vacancy' => 'required|numeric|min:0',
            'propertyStatus' => 'required|in:Available,Fullyoccupied,Maintenance',
            'userID' => 'required|exists:users,id',
            'propertyImages' => 'nullable|array|max:3',
            'propertyImages.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'removed_images' => 'nullable|array',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.properties.index')
                ->withErrors($validator)
                ->withInput()
                ->with('form_type', 'edit')
                ->with('failed_property_id', $id);
        }

        $data = $request->only([
            'propertyName',
            'propertyDescription',
            'propertyLocation',
            'propertyRent',
            'vacancy',
            'propertyStatus',
            'userID',
            'latitude',
            'longitude'
        ]);
        $data['propertyUpdatedAt'] = now();

        // Handle image updates
        $currentImages = $property->property_images ?? [];
        
        // Handle removed images
        if ($request->has('removed_images')) {
            $removedImages = $request->input('removed_images');
            foreach ($removedImages as $removedImage) {
                // Remove from current images array
                $currentImages = array_filter($currentImages, function($image) use ($removedImage) {
                    return $image !== $removedImage;
                });
                
                // Delete the physical file
                $imagePath = storage_path('app/public/' . $removedImage);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            // Reindex array
            $currentImages = array_values($currentImages);
        }
        
        // Handle new images
        if ($request->hasFile('propertyImages')) {
            $newImagePaths = [];
            foreach ($request->file('propertyImages') as $image) {
                $newImagePaths[] = $image->store('properties', 'public');
            }
            
            // Merge current images with new images (up to 3 total)
            $allImages = array_merge($currentImages, $newImagePaths);
            $data['property_images'] = array_slice($allImages, 0, 3);
        } else {
            $data['property_images'] = $currentImages;
        }

        $property->update($data);

        //check if the vacancy is full and if full update the status of the property into occupied, not full into available
        $this->checkAndUpdatePropertyStatus($property->propertyID);

        $this->logActivity('admin_update_property', "Admin updated property: {$property->propertyName}");

        // Check redirect_to parameter to determine where to redirect
        if ($request->input('redirect_to') === 'index') {
            return redirect()->route('admin.properties.index')->with('crud_success', 'Property updated successfully!');
        } else {
            return redirect()->route('admin.properties.show', $property->propertyID)->with('crud_success', 'Property updated successfully!');
        }
    }


    public function admindestroy($id)
    {
        $property = \App\Models\Property::findOrFail($id);
        $propertyName = $property->propertyName;
        $property->delete();

        $this->logActivity('admin_delete_property', "Admin deleted property: {$propertyName}");

        return redirect()->route('admin.properties.index')->with('crud_success', 'Property deleted successfully!');
    }

    public function adminShow($id)
    {
        $property = \App\Models\Property::findOrFail($id);
        $landlords = \App\Models\User::where('role', 'landlord')->orderBy('first_name')->get();
        return view('admin.properties.show', compact('property', 'landlords'));
    }

    // Tenant view of available properties
    public function tenantIndex(Request $request)
    {
        $query = Property::where('propertyStatus', 'Available');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('propertyName', 'like', "%{$search}%")
                    ->orWhere('propertyLocation', 'like', "%{$search}%")
                    ->orWhere('propertyDescription', 'like', "%{$search}%")
                    ->orWhere('propertyRent', 'like', "%{$search}%")
                    ->orWhere('propertyStatus', 'like', "%{$search}%")
                    ->orWhereHas('landlord', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Landlord filter
        if ($request->filled('landlord_filter')) {
            $query->where('userID', $request->input('landlord_filter'));
        }

        // Location filter
        if ($request->filled('location_filter')) {
            $location = $request->input('location_filter');
            
            // Handle different location formats
            // For "Valencia City", also search for "Valencia"
            // For "Malaybalay City", also search for "Malaybalay"
            $searchTerms = [$location];
            
            // Add variations for city names
            if (strpos($location, ' City') !== false) {
                $searchTerms[] = str_replace(' City', '', $location);
            } else {
                $searchTerms[] = $location . ' City';
            }
            
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('propertyLocation', 'like', "%$term%");
                }
            });
        }

        // Price range filter
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
        return view('tenant.properties.index', compact('properties'));
    }

    public function tenantShow($id)
    {
        $property = \App\Models\Property::findOrFail($id);
        return view('tenant.properties.show', compact('property'));
    }


    // Tenant applies for a property
    public function tenantApply(Request $request, $propertyId)
    {
        $property = Property::where('propertyID', $propertyId)->where('propertyStatus', 'Available')->firstOrFail();
        $tenant = Auth::user();

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $existingApplication = PropertyApplication::where('property_id', $property->propertyID)
            ->where('user_id', $tenant->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingApplication) {
            return redirect()->back()->with('crud_error', 'You already have an active application for this property.');
        }

        $application = PropertyApplication::create([
            'property_id' => $property->propertyID,
            'user_id' => $tenant->id,
            'status' => 'pending',
            'application_date' => now(),
            'message' => $request->input('message'),
        ]);

        // Send email notification to landlord
        if ($property->landlord && $property->landlord->email) {
            try {
                Mail::to($property->landlord->email)->send(new ApplicationReceivedMail($application));
            } catch (\Exception $e) {
                // Log the error but don't stop the application process
                \Log::error('Failed to send application notification email: ' . $e->getMessage());
            }
        }

        $this->logActivity('apply_property', "Applied for property: {$property->propertyName}");

        return redirect()->route('tenant.properties.index')->with('crud_success', 'Application submitted successfully!');
    }

    // Tenant views their applications
    public function tenantMyApplications(Request $request)
    {
        $applications = PropertyApplication::with('property')
            ->where('user_id', Auth::id());

        // Search filter (searches property name, location, message, and status)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $applications = $applications->where(function ($q) use ($search) {
                $q->whereHas('property', function ($q2) use ($search) {
                    $q2->where('propertyName', 'like', "%{$search}%")
                        ->orWhere('propertyLocation', 'like', "%{$search}%");
                })
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        // Property name filter
        if ($request->filled('property_filter')) {
            $applications = $applications->whereHas('property', function ($q) use ($request) {
                $q->where('propertyName', $request->input('property_filter'));
            });
        }

        // Status filter
        if ($request->filled('status_filter')) {
            $applications = $applications->where('status', $request->input('status_filter'));
        }

        $applications = $applications->orderBy('application_date', 'desc')->paginate(10);
        return view('tenant.applications.index', compact('applications'));
    }

    // Landlord views applications for a specific property
    public function landlordViewApplications($propertyId)
    {
        $property = Property::where('propertyID', $propertyId)->where('userID', Auth::id())->firstOrFail();
        $applications = PropertyApplication::with('tenant')
            ->where('property_id', $property->propertyID)
            ->orderBy('application_date', 'desc')
            ->paginate(10);

        return view('landlord.properties.application', compact('property', 'applications'));
    }

    // Landlord approves an application
    public function landlordApproveApplication(Request $request, $applicationId)
    {
        $application = PropertyApplication::with(['property', 'tenant'])->findOrFail($applicationId);
        $property = $application->property;
        $tenant = $application->tenant;

        // Ensure the authenticated user is the landlord of the property
        if ($property->userID !== Auth::id()) {
            return redirect()->back()->with('crud_error', 'Unauthorized action.');
        }

        if ($application->status === 'pending') {
            $application->status = 'approved';
            $application->save();

            // Increment number of boarders
            $property->increment('number_of_boarders');

            $this->checkAndUpdatePropertyStatus($property->propertyID);

            // Add the tenant to property_boarders table
            PropertyBoarder::create([
                'property_id' => $property->propertyID,
                'user_id' => $application->user_id,
                'status' => 'active',
            ]);

            // Send approval email
            if ($tenant && $tenant->email) {
                Mail::to($tenant->email)->send(new ApplicationApprovedMail($application));
            }

            $tenantInfo = $tenant ? $tenant->first_name . ' ' . $tenant->last_name . ' (' . $tenant->email . ')' : 'Unknown Tenant';
            $this->logActivity('approve_application', "Approved application for property: {$property->propertyName} | Tenant: {$tenantInfo}");

            return redirect()->back()->with('crud_success', 'Application approved successfully.');
        }
        return redirect()->back()->with('crud_error', 'Application cannot be approved.');
    }

    // Landlord rejects an application
    public function landlordRejectApplication(Request $request, $applicationId)
    {
        $application = PropertyApplication::with(['property', 'tenant'])->findOrFail($applicationId);
        $property = $application->property;
        $tenant = $application->tenant;

        // Ensure the authenticated user is the landlord of the property
        if ($property->userID !== Auth::id()) {
            return redirect()->back()->with('crud_error', 'Unauthorized action.');
        }

        if ($application->status === 'pending') {
            $application->status = 'rejected';
            $application->save();

            // Send rejection email
            if ($tenant && $tenant->email) {
                Mail::to($tenant->email)->send(new ApplicationRejectedMail($application));
            }

            $tenantInfo = $tenant ? $tenant->first_name . ' ' . $tenant->last_name . ' (' . $tenant->email . ')' : 'Unknown Tenant';
            $this->logActivity('reject_application', "Rejected application for property: {$property->propertyName} | Tenant: {$tenantInfo}");

            return redirect()->back()->with('crud_success', 'Application rejected successfully.');
        }
        return redirect()->back()->with('crud_error', 'Application cannot be rejected.');
    }

    public function landlordAllApplications(Request $request)
    {
        $applications = PropertyApplication::with(['property', 'tenant'])
            ->whereHas('property', function ($query) {
                $query->where('userID', auth()->id());
            });

        // Search filter (searches property name, tenant name, and message)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $applications = $applications->where(function ($q) use ($search) {
                $q->whereHas('property', function ($q2) use ($search) {
                    $q2->where('propertyName', 'like', "%{$search}%");
                })
                    ->orWhereHas('tenant', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Property name filter
        if ($request->filled('property_filter')) {
            $applications = $applications->whereHas('property', function ($q) use ($request) {
                $q->where('propertyName', $request->input('property_filter'));
            });
        }

        // Tenant filter
        if ($request->filled('tenant_filter')) {
            $applications = $applications->where('user_id', $request->input('tenant_filter'));
        }

        // Status filter
        if ($request->filled('status_filter')) {
            $applications = $applications->where('status', $request->input('status_filter'));
        }

        $applications = $applications->orderBy('application_date', 'desc')->paginate(10);

        return view('landlord.applications.index', compact('applications'));
    }

    // landlords view boarders
    public function landlordViewBoarders($propertyId)
    {
        $property = Property::where('propertyID', $propertyId)->where('userID', Auth::id())->firstOrFail();
        $boarders = PropertyBoarder::with('tenant')
            ->where('property_id', $property->propertyID)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('landlord.properties.boarders', compact('property', 'boarders'));
    }

    // view all boarders
    public function landlordAllBoarders(Request $request)
    {
        $boarders = PropertyBoarder::with(['property', 'tenant'])
            ->whereHas('property', function ($query) {
                $query->where('userID', auth()->id());
            });

        // Search filter (searches property name, tenant name, and message)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $boarders = $boarders->where(function ($q) use ($search) {
                $q->whereHas('property', function ($q2) use ($search) {
                    $q2->where('propertyName', 'like', "%{$search}%");
                })
                    ->orWhereHas('tenant', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Property name filter
        if ($request->filled('property_filter')) {
            $boarders = $boarders->whereHas('property', function ($q) use ($request) {
                $q->where('propertyName', $request->input('property_filter'));
            });
        }

        // Tenant filter
        if ($request->filled('tenant_filter')) {
            $boarders = $boarders->where('user_id', $request->input('tenant_filter'));
        }

        // Status filter
        if ($request->filled('status_filter')) {
            $boarders = $boarders->where('status', $request->input('status_filter'));
        }

        $boarders = $boarders->orderBy('created_at', 'desc')->paginate(15);

        return view('landlord.boarders.index', compact('boarders'));
    }

    public function landlordKickBoarders(Request $request, $boardersId)
    {
        $boarders = PropertyBoarder::with(['property', 'tenant'])->findOrFail($boardersId);
        $property = $boarders->property;
        $tenant = $boarders->tenant;

        // Ensure the authenticated user is the landlord of the property
        if ($property->userID !== Auth::id()) {
            return redirect()->back()->with('crud_error', 'Unauthorized action.');
        }

        if ($boarders->status === 'active') {
            $boarders->status = 'kicked';
            $boarders->save();

            // Update the latest approved application for this boarder to 'kicked'
            $application = \App\Models\PropertyApplication::where('property_id', $property->propertyID)
                ->where('user_id', $boarders->user_id)
                ->where('status', 'approved')
                ->latest('application_date')
                ->first();
            if ($application) {
                $application->status = 'kicked';
                $application->save();
            }

            // Decrement number of boarders
            $property->decrement('number_of_boarders');
            $this->checkAndUpdatePropertyStatus($property->propertyID);

            // Send kicked email
            if ($tenant && $tenant->email) {
                Mail::to($tenant->email)->send(new BoarderKickedMail($boarders));
            }

            $this->logActivity('kicked_boarder', "Kicked boarder for property: {$property->propertyName}");

            return redirect()->back()->with('crud_success', 'Boarder kicked successfully.');
        }
        return redirect()->back()->with('crud_error', 'Boarder cannot be kicked.');
    }

    public function tenantCancelApplication($applicationId)
    {
        $application = PropertyApplication::with(['property.landlord', 'tenant'])
            ->where('id', $applicationId)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'approved'])
            ->firstOrFail();

        $application->status = 'cancelled';
        $application->save();

        // Send email notification to landlord
        if ($application->property && $application->property->landlord && $application->property->landlord->email) {
            try {
                Mail::to($application->property->landlord->email)->send(new ApplicationCancelledMail($application));
            } catch (\Exception $e) {
                // Log the error but don't stop the cancellation process
                \Log::error('Failed to send application cancellation notification email: ' . $e->getMessage());
            }
        }

        $this->logActivity('cancel_application', "Cancelled application for property: {$application->property->propertyName}");

        return redirect()->back()->with('crud_success', 'Application cancelled successfully.');
    }

    // function for updating the status of the property
    public function checkAndUpdatePropertyStatus($propertyId)
    {
        $property = Property::findOrFail($propertyId);

        if ($property->number_of_boarders >= $property->vacancy) {
            $property->propertyStatus = 'Fullyoccupied';
        } elseif ($property->number_of_boarders < $property->vacancy) {
            $property->propertyStatus = 'Available';
        }

        $property->save();
    }
    
    // API endpoint to get property images for editing
    public function getPropertyImages($id)
    {
        $property = Property::where('propertyID', $id)->where('userID', auth()->id())->firstOrFail();
        
        return response()->json([
            'images' => $property->all_images ?? []
        ]);
    }

    // Admin API endpoint to get property images for editing
    public function adminGetPropertyImages($id)
    {
        $property = Property::findOrFail($id);
        
        return response()->json([
            'images' => $property->all_images ?? []
        ]);
    }

    /**
     * Get weather data for a property
     */
    public function getWeatherData($id)
    {
        try {
            $property = Property::findOrFail($id);
            
            if (!$property->latitude || !$property->longitude) {
                return response()->json([
                    'error' => 'Property coordinates not available'
                ], 400);
            }

            $weatherService = new WeatherService();
            $weatherData = $weatherService->getWeatherData(
                $property->latitude, 
                $property->longitude
            );

            return response()->json([
                'success' => true,
                'data' => $weatherData,
                'property' => [
                    'id' => $property->propertyID,
                    'name' => $property->propertyName,
                    'location' => $property->propertyLocation
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Weather data fetch error', [
                'property_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Unable to fetch weather data'
            ], 500);
        }
    }

}
