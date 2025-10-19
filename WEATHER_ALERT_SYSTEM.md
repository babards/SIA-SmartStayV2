# Weather Alert System Documentation

## Overview
The SmartStay Weather Alert System provides proactive notifications to property owners about severe weather conditions that could affect their properties. The system monitors weather conditions in real-time and sends email alerts via Gmail when severe weather is detected.

## Features

### 🌤️ Weather Monitoring
- **Real-time weather data** from Open-Meteo API
- **7-day weather forecast** monitoring
- **Philippines-specific** weather data and coordinates validation
- **Multiple weather conditions** monitoring:
  - Heavy rainfall and flooding risks
  - Strong winds and storm conditions
  - Extreme temperatures (heat/cold)
  - Thunderstorms and severe weather
  - High humidity conditions

### 📧 Email Notifications
- **Gmail integration** for reliable email delivery
- **Beautiful HTML email templates** with weather details
- **Severity-based alerts** (Minor, Moderate, Severe)
- **Property-specific information** in each alert
- **Actionable recommendations** for each weather condition

### ⚙️ Smart Alert Management
- **Spam prevention** - maximum 1 alert per hour per property
- **Severity filtering** - only send alerts above minimum severity threshold
- **Quiet hours support** - respect user's preferred notification times
- **Daily limits** - prevent alert fatigue with configurable daily limits

## System Components

### 1. WeatherService (`app/Services/WeatherService.php`)
Enhanced with weather alert detection methods:
- `checkWeatherAlerts()` - Main method to check for alert conditions
- `checkCurrentWeatherAlerts()` - Check current weather conditions
- `checkForecastAlerts()` - Check upcoming weather conditions
- `getAlertRecommendations()` - Get specific recommendations for each alert type
- `shouldSendAlert()` - Determine if an alert should be sent based on user preferences

### 2. WeatherAlertMail (`app/Mail/WeatherAlertMail.php`)
Email notification class that:
- Formats weather alert emails with severity-based styling
- Includes property information and weather details
- Provides actionable recommendations
- Uses dynamic subject lines based on severity

### 3. WeatherAlertController (`app/Http/Controllers/WeatherAlertController.php`)
API controller for managing weather alerts:
- `sendWeatherAlerts()` - Send alerts for all properties
- `sendAlertForProperty()` - Send alert for specific property
- `testWeatherAlert()` - Test alert system for a property
- `getWeatherAlertStatus()` - Get current alert status for a property
- `getWeatherAlertRecommendations()` - Get recommendations for alert types

### 4. SendWeatherAlerts Command (`app/Console/Commands/SendWeatherAlerts.php`)
Console command for scheduled execution:
- `weather:send-alerts` - Send alerts for all properties
- `weather:send-alerts --property-id=123` - Send alert for specific property
- `weather:send-alerts --test` - Test mode (no emails sent)
- Progress tracking and detailed logging

### 5. WeatherAlertSettings Model (`app/Models/WeatherAlertSettings.php`)
User preferences and settings:
- Alert severity thresholds
- Quiet hours configuration
- Daily alert limits
- Specific alert type preferences
- Property-specific or global settings

## Alert Types and Thresholds

### Current Weather Alerts
| Alert Type | Severity | Threshold | Description |
|------------|----------|-----------|-------------|
| Heavy Rain | Severe | >10mm | Risk of flooding and property damage |
| Moderate Rain | Moderate | >5mm | Monitor for potential issues |
| Strong Wind | Severe | >50 km/h | Secure outdoor items |
| Moderate Wind | Moderate | >30 km/h | Light outdoor items at risk |
| Extreme Heat | Severe | >38°C | Risk of heat-related issues |
| High Temperature | Moderate | >35°C | Monitor temperature conditions |
| Thunderstorm | Moderate/Severe | Weather codes 95,96,99 | Storm conditions present |
| High Humidity | Minor | >90% | Monitor for mold and moisture |

### Forecast Alerts
| Alert Type | Severity | Threshold | Description |
|------------|----------|-----------|-------------|
| Heavy Rain Forecast | Severe | >15mm | Prepare for potential flooding |
| Strong Wind Forecast | Moderate | >40 km/h | Secure outdoor items |
| Extreme Heat Forecast | Severe | >38°C | Take heat precautions |

## Usage

### Manual Testing
```bash
# Test all properties (no emails sent)
php artisan weather:send-alerts --test

# Test specific property
php artisan weather:send-alerts --property-id=123 --test

# Send actual alerts for all properties
php artisan weather:send-alerts

# Send alert for specific property
php artisan weather:send-alerts --property-id=123
```

### Scheduled Execution
Add to your Laravel scheduler (`app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule)
{
    // Run weather alerts every hour
    $schedule->command('weather:send-alerts')
             ->hourly()
             ->withoutOverlapping();
}
```

### API Endpoints
```php
// Get weather alert status for a property
GET /weather-alerts/status/{propertyId}

// Test weather alert for a property
POST /weather-alerts/test/{propertyId}

// Get alert recommendations
GET /weather-alerts/recommendations?alert_type=heavy_rain&severity=severe

// Admin: Send alerts for all properties
POST /admin/weather-alerts/send-all
```

## Email Template Features

### Visual Design
- **Responsive HTML design** that works on all devices
- **Severity-based color coding** (Red for severe, Orange for moderate, Blue for minor)
- **Weather icons and emojis** for visual appeal
- **Professional layout** with clear sections

### Content Sections
1. **Alert Header** - Severity indicator and alert type
2. **Property Information** - Property name, location, and alert time
3. **Current Weather** - Temperature, precipitation, wind, humidity
4. **Forecast** - Next 3 days weather outlook
5. **Recommendations** - Specific actions based on alert type and severity
6. **Action Buttons** - Links to property management and dashboard

### Example Alert Content
```
🚨 SEVERE WEATHER ALERT - Sample Property

Dear John Doe,

We are sending you this weather alert for your property "Sample Property" 
located at "Valencia City, Bukidnon".

Current Conditions:
- Temperature: 28°C
- Precipitation: 12mm (Heavy rainfall detected)
- Wind Speed: 35 km/h
- Humidity: 85%

Recommended Actions:
- Check drainage systems around the property
- Move valuable items to higher ground
- Prepare sandbags if in flood-prone area
- Monitor local flood warnings
- Have emergency supplies ready
```

## Configuration

### Environment Variables
Ensure your `.env` file has proper mail configuration:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="SmartStay Weather Alerts"
```

### Cache Configuration
The system uses Laravel's cache to prevent spam:
- **Cache Key**: `weather_alert_sent_{propertyID}_{Y-m-d-H}`
- **Duration**: 1 hour
- **Purpose**: Prevent multiple alerts for the same property within an hour

## Monitoring and Logging

### Log Files
All weather alert activities are logged in Laravel's log files:
- **Successful alerts**: Property ID, landlord email, alert type, severity
- **Failed alerts**: Error messages and stack traces
- **Batch operations**: Summary of total properties processed, alerts sent, errors

### Command Output
The console command provides detailed feedback:
- Progress bars for batch operations
- Real-time status updates
- Summary statistics
- Error reporting

## Security Considerations

### Rate Limiting
- Maximum 1 alert per property per hour
- Configurable daily alert limits per user
- Quiet hours to respect user preferences

### Data Privacy
- Only property owners receive alerts for their properties
- No sensitive user data exposed in logs
- Email addresses are logged only for debugging purposes

### API Security
- All endpoints require authentication
- Admin endpoints require admin role
- Input validation on all parameters

## Troubleshooting

### Common Issues

1. **No alerts being sent**
   - Check if properties have valid coordinates
   - Verify weather API is responding
   - Check mail configuration
   - Review log files for errors

2. **Emails not being delivered**
   - Verify Gmail SMTP settings
   - Check if app password is correct
   - Ensure sender email is verified
   - Check spam folders

3. **Too many alerts**
   - Adjust severity thresholds
   - Configure quiet hours
   - Set daily alert limits
   - Review alert types enabled

### Debug Commands
```bash
# Test specific property with detailed output
php artisan weather:send-alerts --property-id=123 --test

# Check weather data for a property
php artisan tinker
>>> $property = App\Models\Property::find(123);
>>> $weatherService = new App\Services\WeatherService();
>>> $alerts = $weatherService->checkWeatherAlerts($property->latitude, $property->longitude);
>>> dd($alerts);
```

## Future Enhancements

### Planned Features
- **SMS notifications** for severe alerts
- **Push notifications** via mobile app
- **Weather alert history** and analytics
- **Custom alert thresholds** per property
- **Integration with local weather services**
- **Multi-language support** for alerts
- **Weather trend analysis** and predictions

### Integration Opportunities
- **Property management systems**
- **Emergency services APIs**
- **Insurance company notifications**
- **Tenant communication systems**
- **Maintenance scheduling systems**

## Support

For technical support or questions about the Weather Alert System:
1. Check the log files for error messages
2. Test the system using the `--test` flag
3. Verify all configuration settings
4. Review the API documentation
5. Contact the development team for assistance

---

**SmartStay Weather Alert System** - Keeping your properties safe with proactive weather monitoring and intelligent notifications.
