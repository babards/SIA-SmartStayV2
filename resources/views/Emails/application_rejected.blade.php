<p>Hi {{ $application->tenant->first_name ?? $application->tenant->name }},</p>
<p>We regret to inform you that your application for <strong>{{ $application->property->propertyName }}</strong> has been <span style="color:red;font-weight:bold;">rejected</span>.</p>
<p>You may apply to other properties or contact the landlord for more information.</p>
<p>Thank you for using SmartStay!</p> 