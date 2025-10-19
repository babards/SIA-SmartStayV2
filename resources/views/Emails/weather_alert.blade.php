<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weather Alert - SmartStay</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
        }
        .severe { background-color: #ffebee; border-left: 5px solid #f44336; }
        .moderate { background-color: #fff3e0; border-left: 5px solid #ff9800; }
        .minor { background-color: #e3f2fd; border-left: 5px solid #2196f3; }
        
        .alert-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .alert-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .alert-subtitle {
            font-size: 16px;
            color: #666;
        }
        .property-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .weather-details {
            background-color: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .weather-item {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .weather-item:last-child {
            border-bottom: none;
        }
        .weather-label {
            font-weight: bold;
            color: #555;
        }
        .weather-value {
            color: #333;
        }
        .recommendations {
            background-color: #fff8e1;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .recommendations h3 {
            margin-top: 0;
            color: #f57c00;
        }
        .recommendations ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .recommendations li {
            margin: 8px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .severity-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .severity-severe { background-color: #f44336; color: white; }
        .severity-moderate { background-color: #ff9800; color: white; }
        .severity-minor { background-color: #2196f3; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $alertData['severity'] ?? 'moderate' }}">
            <div class="alert-icon">
                @if(($alertData['severity'] ?? 'moderate') === 'severe')
                    🚨
                @elseif(($alertData['severity'] ?? 'moderate') === 'moderate')
                    ⚠️
                @else
                    ℹ️
                @endif
            </div>
            <div class="alert-title">Weather Alert</div>
            <div class="alert-subtitle">
                <span class="severity-badge severity-{{ $alertData['severity'] ?? 'moderate' }}">
                    {{ ucfirst($alertData['severity'] ?? 'moderate') }} Alert
                </span>
            </div>
        </div>

        <p>Dear {{ $user->first_name ?? 'Valued User' }},</p>
        
        @if($userType === 'tenant')
            <p>We are sending you this weather alert for the property you are boarding at: <strong>{{ $property->propertyName }}</strong> located at <strong>{{ $property->propertyLocation }}</strong>.</p>
            <p>As a tenant at this property, it's important for you to be aware of severe weather conditions that may affect your safety and the property.</p>
        @else
            <p>We are sending you this weather alert for your property <strong>{{ $property->propertyName }}</strong> located at <strong>{{ $property->propertyLocation }}</strong>.</p>
        @endif

        <div class="property-info">
            <h3 style="margin-top: 0;">📍 Property Information</h3>
            <p><strong>Property:</strong> {{ $property->propertyName }}</p>
            <p><strong>Location:</strong> {{ $property->propertyLocation }}</p>
            <p><strong>Alert Time:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>

        @if(isset($alertData['severity_summary']))
        <div class="weather-details">
            <h3 style="margin-top: 0;">⚠️ Weather Risk Summary</h3>
            @if(!empty($alertData['severity_summary']['severe']))
                <div style="margin: 10px 0; padding: 10px; background-color: #ffebee; border-left: 4px solid #f44336; border-radius: 4px;">
                    <strong style="color: #d32f2f;">🔴 SEVERE:</strong>
                    @foreach($alertData['severity_summary']['severe'] as $type => $days)
                        @if(!empty($days))
                            {{ ucfirst(str_replace('_', ' ', $type)) }} 
                            @if(in_array('current', $days))
                                (Current)
                            @else
                                ({{ implode(', ', $days) }})
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif
            @if(!empty($alertData['severity_summary']['moderate']))
                <div style="margin: 10px 0; padding: 10px; background-color: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px;">
                    <strong style="color: #f57c00;">🟡 MODERATE:</strong>
                    @foreach($alertData['severity_summary']['moderate'] as $type => $days)
                        @if(!empty($days))
                            {{ ucfirst(str_replace('_', ' ', $type)) }} 
                            @if(in_array('current', $days))
                                (Current)
                            @else
                                ({{ implode(', ', $days) }})
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif
            @if(!empty($alertData['severity_summary']['minor']))
                <div style="margin: 10px 0; padding: 10px; background-color: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                    <strong style="color: #1976d2;">🔵 MINOR:</strong>
                    @foreach($alertData['severity_summary']['minor'] as $type => $days)
                        @if(!empty($days))
                            {{ ucfirst(str_replace('_', ' ', $type)) }} 
                            @if(in_array('current', $days))
                                (Current)
                            @else
                                ({{ implode(', ', $days) }})
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
        @endif

        <div class="weather-details">
            <h3 style="margin-top: 0;">🌤️ Current Weather Conditions</h3>
            <div class="weather-item">
                <span class="weather-label">Condition:</span>
                <span class="weather-value">{{ $alertData['weather_description'] ?? 'Unknown' }} {{ $alertData['weather_icon'] ?? '' }}</span>
            </div>
            <div class="weather-item">
                <span class="weather-label">Temperature:</span>
                <span class="weather-value">{{ $alertData['temperature'] ?? 'N/A' }}°C</span>
            </div>
            <div class="weather-item">
                <span class="weather-label">Current Rain:</span>
                <span class="weather-value">{{ $alertData['precipitation'] ?? 'N/A' }} mm</span>
            </div>
            <div class="weather-item">
                <span class="weather-label">Wind Speed:</span>
                <span class="weather-value">{{ $alertData['wind_speed'] ?? 'N/A' }} km/h</span>
            </div>
            <div class="weather-item">
                <span class="weather-label">Rain Chance:</span>
                <span class="weather-value">{{ $alertData['precipitation_probability'] ?? 'N/A' }}%</span>
            </div>
        </div>

        @if(isset($alertData['forecast']) && count($alertData['forecast']) > 0)
        <div class="weather-details">
            <h3 style="margin-top: 0;">📅 Upcoming Weather (Next 4 Days)</h3>
            @foreach(array_slice($alertData['forecast'], 0, 4) as $day)
            <div class="weather-item" style="border-left: 4px solid {{ $day['severity_color'] ?? '#666666' }}; padding-left: 10px;">
                <span class="weather-label">
                    {{ $day['day_name'] ?? 'Unknown' }}:
                    @if(isset($day['severity_icon']))
                        <span style="font-size: 14px;">{{ $day['severity_icon'] }}</span>
                    @endif
                    @if(isset($day['severity']) && $day['severity'] !== 'normal')
                        <span style="font-size: 11px; font-weight: bold; color: {{ $day['severity_color'] ?? '#666666' }}; text-transform: uppercase;">
                            ({{ $day['severity'] }})
                        </span>
                    @endif
                </span>
                <span class="weather-value">
                    {{ $day['weather_description'] ?? 'Unknown' }} {{ $day['weather_icon'] ?? '' }} 
                    ({{ $day['temp_min'] ?? 'N/A' }}°C - {{ $day['temp_max'] ?? 'N/A' }}°C)
                    @if(($day['precipitation_probability'] ?? 0) > 0)
                        | {{ $day['precipitation_probability'] }}% rain chance
                    @endif
                    @if(($day['precipitation'] ?? 0) > 0)
                        | {{ $day['precipitation'] }}mm expected
                    @endif
                </span>
            </div>
            @endforeach
        </div>
        @endif

        <div class="recommendations">
            <h3>🛡️ Smart Recommendations</h3>
            <ul>
                @if($userType === 'tenant')
                    @if(($alertData['severity'] ?? 'moderate') === 'severe')
                        <li><strong>🔴 IMMEDIATE ACTION REQUIRED:</strong> Take necessary precautions for severe weather conditions</li>
                        <li>Stay indoors and avoid unnecessary travel</li>
                        <li>Secure your personal belongings and important documents</li>
                        <li>Ensure you have emergency supplies (water, food, flashlight)</li>
                        <li>Contact your landlord if you notice any property damage</li>
                        <li>Monitor local news and weather updates</li>
                        <li>Keep emergency contacts handy (landlord, local authorities)</li>
                    @elseif(($alertData['severity'] ?? 'moderate') === 'moderate')
                        <li><strong>🟡 MODERATE ALERT:</strong> Be prepared for changing weather conditions</li>
                        <li>Secure your personal items and close windows</li>
                        <li>Consider postponing outdoor activities</li>
                        <li>Keep emergency contacts handy</li>
                        <li>Report any property issues to your landlord</li>
                    @else
                        <li><strong>🔵 MINOR ALERT:</strong> Stay informed about weather changes</li>
                        <li>Be prepared for potential weather variations</li>
                        <li>Contact your landlord if you have concerns</li>
                    @endif
                @else
                    @if(($alertData['severity'] ?? 'moderate') === 'severe')
                        <li><strong>🔴 IMMEDIATE ACTION REQUIRED:</strong> Take necessary precautions for severe weather conditions</li>
                        <li>Secure outdoor furniture and loose items around the property</li>
                        <li>Check for any potential flooding risks in the area</li>
                        <li>Ensure emergency supplies are available</li>
                        <li>Stay indoors and avoid unnecessary travel</li>
                        <li>Monitor local news and weather updates</li>
                        <li>Check on your tenants and ensure their safety</li>
                    @elseif(($alertData['severity'] ?? 'moderate') === 'moderate')
                        <li><strong>🟡 MODERATE ALERT:</strong> Be prepared for changing weather conditions</li>
                        <li>Secure any loose outdoor items</li>
                        <li>Check property for potential weather-related issues</li>
                        <li>Consider postponing outdoor activities</li>
                        <li>Keep emergency contacts handy</li>
                        <li>Inform your tenants about the weather conditions</li>
                    @else
                        <li><strong>🔵 MINOR ALERT:</strong> Stay informed about weather changes</li>
                        <li>Check property condition regularly</li>
                        <li>Be prepared for potential weather variations</li>
                    @endif
                @endif
                
                @if(isset($alertData['severity_summary']))
                    @if(!empty($alertData['severity_summary']['severe']))
                        <li><strong>🔴 SEVERE CONDITIONS:</strong> Multiple severe weather events detected - prioritize safety measures</li>
                    @endif
                    @if(!empty($alertData['severity_summary']['moderate']))
                        <li><strong>🟡 MODERATE CONDITIONS:</strong> Additional moderate weather events - prepare accordingly</li>
                    @endif
                    @if(!empty($alertData['severity_summary']['minor']))
                        <li><strong>🔵 MINOR CONDITIONS:</strong> Minor weather events - stay aware and prepared</li>
                    @endif
                @endif
                
                @if(($alertData['precipitation_probability'] ?? 0) > 70)
                    <li>⚠️ High rain chance ({{ $alertData['precipitation_probability'] ?? 0 }}%) - check for drainage issues</li>
                @elseif(($alertData['precipitation'] ?? 0) > 5)
                    <li>⚠️ Heavy rainfall detected - monitor for flooding</li>
                @endif
                
                @if(($alertData['wind_speed'] ?? 0) > 30)
                    <li>💨 Strong winds expected - secure outdoor items</li>
                @endif
                
                @if(($alertData['temperature'] ?? 0) > 35)
                    <li>🌡️ High temperature - ensure proper ventilation</li>
                @endif
            </ul>
        </div>


        <div class="footer">
            <p>This is an automated weather alert from SmartStay.</p>
            <p>For immediate weather emergencies, please contact local authorities.</p>
            <p>SmartStay - Your Smart Property Management Solution</p>
            <p><small>You are receiving this alert because you have weather notifications enabled for this property. 
            @if($userType === 'tenant')
                As a tenant, you receive alerts for properties you are boarding at.
            @else
                As a property owner, you receive alerts for your properties.
            @endif
            </small></p>
        </div>
    </div>
</body>
</html>
