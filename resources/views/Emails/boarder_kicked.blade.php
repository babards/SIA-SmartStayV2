<p>Hi {{ $boarder->tenant->first_name ?? $boarder->tenant->name }},</p>
<p>We regret to inform you that you have been <span style="color:red;font-weight:bold;">removed</span> as a boarder from <strong>{{ $boarder->property->propertyName ?? 'the property' }}</strong> by the landlord.</p>
<p>If you have any questions, please contact the landlord or support.</p>
<p>Thank you for using SmartStay.</p> 