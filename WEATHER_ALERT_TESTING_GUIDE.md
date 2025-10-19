# Weather Alert System Testing Guide

## 🧪 Complete Testing Methods

### 1. **Test Mode (Safest Method)**

Test the system without sending actual emails:

```bash
# Test all properties
php artisan weather:send-alerts --test

# Test specific property
php artisan weather:send-alerts --property-id=21 --test
```

**Expected Output:**
- Shows which properties have alerts
- Displays alert types and severity
- Shows email addresses that would receive alerts
- No actual emails are sent

### 2. **Real Email Testing**

Send actual email alerts:

```bash
# Send alerts for all properties
php artisan weather:send-alerts

# Send alert for specific property
php artisan weather:send-alerts --property-id=21
```

**What to Check:**
- ✅ Command shows "Alert sent" message
- ✅ Email appears in recipient's inbox
- ✅ Email has proper formatting and content
- ✅ Email includes weather details and recommendations

### 3. **Spam Prevention Testing**

Test the hourly limit feature:

```bash
# Send first alert
php artisan weather:send-alerts --property-id=1

# Try to send again immediately (should be blocked)
php artisan weather:send-alerts --property-id=1
```

**Expected Result:**
- First command: "Alert sent"
- Second command: "Alert already sent this hour"

### 4. **API Endpoint Testing**

Test the web API endpoints:

```bash
# Start Laravel server
php artisan serve

# Test weather alert status (replace with actual property ID)
curl -X GET "http://127.0.0.1:8000/weather-alerts/status/21" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Test weather alert recommendations
curl -X GET "http://127.0.0.1:8000/weather-alerts/recommendations?alert_type=heavy_rain&severity=severe" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 5. **Email Content Verification**

Check the email content in the recipient's inbox:

**Email Should Contain:**
- ✅ Proper subject line with severity indicator
- ✅ Property name and location
- ✅ Current weather conditions
- ✅ 3-day weather forecast
- ✅ Specific recommendations based on alert type
- ✅ Professional HTML formatting
- ✅ Action buttons to property management

**Email Structure:**
```
Subject: 🚨 SEVERE WEATHER ALERT - Property Name

Content:
- Alert severity badge
- Property information
- Current weather data
- Forecast information
- Actionable recommendations
- Links to property management
```

## 🔍 Current Test Results

Based on your system, here are the current test results:

### Properties with Alerts:
1. **Property ID 21 (Negros Test)**
   - Alert Type: Heavy rain forecast
   - Severity: Severe
   - Email: ezraevander09@gmail.com
   - Status: ✅ Alert sent successfully

2. **Property ID 1 (test)**
   - Alert Type: High humidity
   - Severity: Minor
   - Email: vinjohn302@gmail.com
   - Status: ✅ Alert sent successfully

3. **Property ID 19 (Cebu test)**
   - Alert Type: High humidity
   - Severity: Minor
   - Email: vinjohn302@gmail.com
   - Status: Ready for testing

### Properties without Alerts:
- Property ID 18 (test5) - Normal conditions
- Property ID 20 (CDO) - Normal conditions
- Property ID 8 (test4) - Normal conditions

## 📧 Email Testing Checklist

### Before Testing:
- [ ] Gmail SMTP is configured in `.env`
- [ ] App password is set up for Gmail
- [ ] Test email addresses are valid
- [ ] Laravel mail configuration is correct

### During Testing:
- [ ] Run test mode first to verify alerts are detected
- [ ] Send real alerts to test email addresses
- [ ] Check spam folders if emails don't arrive
- [ ] Verify email content and formatting
- [ ] Test spam prevention (hourly limits)

### After Testing:
- [ ] Verify all emails were received
- [ ] Check email formatting and content
- [ ] Test links in emails work correctly
- [ ] Verify recommendations are appropriate
- [ ] Check logs for any errors

## 🚨 Troubleshooting Common Issues

### 1. **No Emails Received**
```bash
# Check mail configuration
php artisan config:cache
php artisan config:clear

# Test mail sending
php artisan tinker
>>> Mail::raw('Test email', function($msg) { $msg->to('your-email@gmail.com')->subject('Test'); });
```

### 2. **No Alerts Detected**
```bash
# Check weather data for specific property
php artisan tinker
>>> $property = App\Models\Property::find(21);
>>> $weatherService = new App\Services\WeatherService();
>>> $alerts = $weatherService->checkWeatherAlerts($property->latitude, $property->longitude);
>>> dd($alerts);
```

### 3. **Command Errors**
```bash
# Clear cache and config
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Check logs
tail -f storage/logs/laravel.log
```

## 📊 Testing Commands Summary

| Command | Purpose | Expected Result |
|---------|---------|-----------------|
| `php artisan weather:send-alerts --test` | Test all properties | Shows alerts without sending emails |
| `php artisan weather:send-alerts --property-id=21 --test` | Test specific property | Shows alerts for that property |
| `php artisan weather:send-alerts --property-id=21` | Send real alert | Sends email to property owner |
| `php artisan weather:send-alerts` | Send all alerts | Processes all properties with alerts |

## 🎯 Next Steps for Production

1. **Set up scheduled execution:**
   ```php
   // In app/Console/Kernel.php
   $schedule->command('weather:send-alerts')->hourly();
   ```

2. **Configure Gmail app password:**
   - Enable 2-factor authentication
   - Generate app password
   - Update `.env` file

3. **Set up monitoring:**
   - Monitor log files
   - Set up email notifications for system errors
   - Track alert delivery rates

4. **User preferences:**
   - Allow users to configure alert settings
   - Set up quiet hours
   - Configure severity thresholds

## ✅ Success Indicators

Your weather alert system is working correctly if:
- ✅ Test mode shows detected alerts
- ✅ Real alerts are sent successfully
- ✅ Emails arrive in recipient inboxes
- ✅ Email content is properly formatted
- ✅ Spam prevention works (hourly limits)
- ✅ No errors in log files
- ✅ API endpoints respond correctly

---

**Ready to test!** Use the commands above to verify your weather alert system is working properly. Start with test mode, then move to real email testing.
