<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Cancelled</title>
</head>
<body>
    <div class="container">
        <p>Hi {{ $property->landlord->first_name }} {{ $property->landlord->last_name }},</p>
        
        <p>An application for <span class="highlight">{{ $property->propertyName }}</span> has been cancelled.</p>
        
        <p><strong>Tenant:</strong> {{ $tenant->first_name }} {{ $tenant->last_name }}</p>
        <p><strong>Email:</strong> {{ $tenant->email }}</p>
        <p><strong>Original Application Date:</strong> {{ $application->application_date->format('F j, Y') }}</p>
        <p><strong>Cancellation Date:</strong> {{ $application->updated_at->format('F j, Y') }}</p>
        
        <p><strong>Original Message:</strong></p>
        <p style="font-style: italic; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
            "{{ $application->message }}"
        </p>
        
        <p>The application has been successfully cancelled and is no longer active.</p>
        
        <p style="margin-top: 30px;">Thank you for using SmartStay!</p>
    </div>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .highlight {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</body>
</html> 