# Enhanced Weather Alert System - Landlords & Tenants

## 🎯 **System Overview**

The SmartStay Weather Alert System has been enhanced to provide proactive weather notifications to **both landlords and tenants** about severe weather conditions affecting their properties. The system now sends alerts **once per day** to prevent spam and alert fatigue.

## ✨ **New Features**

### 👥 **Multi-Recipient Notifications**
- **Landlords**: Receive alerts for all their properties
- **Tenants**: Receive alerts for properties they are boarding at
- **Active Boarders Only**: Only tenants with 'active' status receive alerts
- **Personalized Content**: Different recommendations for landlords vs tenants

### 📅 **Daily Alert Limits**
- **Once per day**: Maximum 1 alert per property per day
- **24-hour cache**: Prevents spam and alert fatigue
- **Smart filtering**: Only sends alerts when conditions warrant notification

### 📧 **Enhanced Email Templates**
- **User-specific content**: Different messaging for landlords and tenants
- **Role-based recommendations**: Tailored advice based on user type
- **Property context**: Clear indication of which property the alert is for
- **Action buttons**: Different links for landlords vs tenants

## 🔧 **System Components**

### 1. **Enhanced WeatherAlertController**
```php
// New functionality:
- Sends emails to both landlords and active tenants
- Tracks email count per property
- Daily cache system (24-hour limits)
- Error handling for individual email failures
- Detailed logging for both landlords and tenants
```

### 2. **Updated WeatherAlertMail**
```php
// New parameters:
- $userType: 'landlord' or 'tenant'
- Dynamic subject lines with user context
- Personalized email content based on user role
```

### 3. **Enhanced Email Template**
```html
<!-- New features: -->
- Conditional content for landlords vs tenants
- Role-specific recommendations
- Different action buttons based on user type
- Personalized messaging and context
```

### 4. **Updated Console Command**
```bash
# Enhanced output:
- Shows total emails sent (landlords + tenants)
- Displays recipient count per property
- Reports email failures separately
- Daily limit enforcement
```

## 📊 **Alert Distribution**

### **Landlord Alerts**
- **Recipients**: Property owners
- **Content**: Property management focus
- **Recommendations**: 
  - Secure outdoor items
  - Check property condition
  - Monitor for damage
  - Check on tenants
  - Prepare emergency supplies

### **Tenant Alerts**
- **Recipients**: Active boarders
- **Content**: Personal safety focus
- **Recommendations**:
  - Stay indoors during severe weather
  - Secure personal belongings
  - Contact landlord if issues arise
  - Keep emergency contacts handy
  - Report property damage

## 🧪 **Testing Results**

### **Successful Tests Performed:**

1. **✅ Multi-Recipient Alerts**
   - Property 21 (Negros Test): Sent to 2 recipients (1 landlord + 1 tenant)
   - Both emails delivered successfully
   - Different content for each recipient type

2. **✅ Daily Limit Enforcement**
   - First alert: "Alert sent to 2 recipient(s)"
   - Second attempt: "Alert already sent today"
   - 24-hour cache working correctly

3. **✅ Tenant-Specific Content**
   - Subject: "🚨 SEVERE WEATHER ALERT - Property Name (Your Boarding Property)"
   - Content: Tenant-focused safety recommendations
   - Action buttons: Links to tenant dashboard

4. **✅ Landlord-Specific Content**
   - Subject: "🚨 SEVERE WEATHER ALERT - Property Name"
   - Content: Property management recommendations
   - Action buttons: Links to landlord dashboard

## 📧 **Email Examples**

### **Landlord Email**
```
Subject: 🚨 SEVERE WEATHER ALERT - Negros Test

Dear Property Owner,

We are sending you this weather alert for your property "Negros Test" 
located at "Barangay 1, Talubangi, Kabankalan, Negros Occidental".

Recommended Actions:
- IMMEDIATE ACTION REQUIRED: Take necessary precautions
- Secure outdoor furniture and loose items around the property
- Check for any potential flooding risks in the area
- Ensure emergency supplies are available
- Check on your tenants and ensure their safety
- Monitor local news and weather updates

[View Property Details] [Manage Properties]
```

### **Tenant Email**
```
Subject: 🚨 SEVERE WEATHER ALERT - Negros Test (Your Boarding Property)

Dear Vin John,

We are sending you this weather alert for the property you are boarding at: 
"Negros Test" located at "Barangay 1, Talubangi, Kabankalan, Negros Occidental".

As a tenant at this property, it's important for you to be aware of severe 
weather conditions that may affect your safety and the property.

Recommended Actions:
- IMMEDIATE ACTION REQUIRED: Take necessary precautions
- Stay indoors and avoid unnecessary travel
- Secure your personal belongings and important documents
- Ensure you have emergency supplies (water, food, flashlight)
- Contact your landlord if you notice any property damage
- Keep emergency contacts handy (landlord, local authorities)

[View Property Details] [My Properties]
```

## 🚀 **Usage Commands**

### **Test Mode (No Emails Sent)**
```bash
# Test all properties
php artisan weather:send-alerts --test

# Test specific property
php artisan weather:send-alerts --property-id=21 --test
```

### **Real Email Testing**
```bash
# Send alerts for all properties
php artisan weather:send-alerts

# Send alert for specific property
php artisan weather:send-alerts --property-id=21
```

### **Expected Output**
```bash
✅ Alert sent for Negros Test to 2 recipient(s)
   Type: heavy_rain_forecast, Severity: severe

⚠️  No alert sent for Negros Test
   Reason: Alert already sent today
```

## 📈 **System Benefits**

### **For Landlords**
- **Property Protection**: Early warning for property damage risks
- **Tenant Safety**: Ensure tenant safety during severe weather
- **Proactive Management**: Take preventive measures before damage occurs
- **Professional Communication**: Automated, professional weather alerts

### **For Tenants**
- **Personal Safety**: Stay informed about weather risks at their boarding property
- **Emergency Preparedness**: Get specific recommendations for their situation
- **Landlord Communication**: Clear guidance on when to contact landlord
- **Peace of Mind**: Know that weather conditions are being monitored

### **For the System**
- **Reduced Spam**: Daily limits prevent alert fatigue
- **Comprehensive Coverage**: Both property owners and occupants are informed
- **Scalable**: Handles multiple tenants per property efficiently
- **Reliable**: Robust error handling and logging

## 🔧 **Configuration**

### **Daily Alert Limits**
```php
// Cache key format: weather_alert_sent_{propertyID}_{Y-m-d}
// Duration: 86400 seconds (24 hours)
Cache::put($cacheKey, true, 86400);
```

### **Tenant Filtering**
```php
// Only active tenants receive alerts
$activeTenants = $property->boarders()
    ->where('status', 'active')
    ->with('tenant')
    ->get();
```

### **Email Personalization**
```php
// Different content based on user type
$userType = 'tenant'; // or 'landlord'
new WeatherAlertMail($alertData, $property, $user, $userType);
```

## 📊 **Monitoring & Logging**

### **Success Logs**
```php
Log::info('Weather alert sent to landlord', [
    'property_id' => $property->propertyID,
    'landlord_email' => $property->landlord->email,
    'alert_type' => $alert['type'],
    'severity' => $alert['severity']
]);

Log::info('Weather alert sent to tenant', [
    'property_id' => $property->propertyID,
    'tenant_email' => $boarder->tenant->email,
    'tenant_name' => $boarder->tenant->first_name . ' ' . $boarder->tenant->last_name,
    'alert_type' => $alert['type'],
    'severity' => $alert['severity']
]);
```

### **Error Handling**
```php
// Individual email failures don't stop the process
// Errors are logged and reported in the response
$errors[] = 'Landlord email failed: ' . $e->getMessage();
$errors[] = 'Tenant email failed: ' . $e->getMessage();
```

## 🎯 **Production Setup**

### **Scheduled Execution**
```php
// In app/Console/Kernel.php
$schedule->command('weather:send-alerts')->dailyAt('08:00');
// or
$schedule->command('weather:send-alerts')->hourly();
```

### **Gmail Configuration**
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

## ✅ **System Status**

The enhanced weather alert system is **fully operational** with the following features:

- ✅ **Multi-recipient notifications** (landlords + tenants)
- ✅ **Daily alert limits** (once per day per property)
- ✅ **Personalized email content** based on user role
- ✅ **Comprehensive error handling** and logging
- ✅ **Spam prevention** with 24-hour cache
- ✅ **Active tenant filtering** (only active boarders)
- ✅ **Professional email templates** with role-specific recommendations
- ✅ **Scalable architecture** for multiple properties and tenants

## 🚀 **Ready for Production**

The enhanced weather alert system is ready for production deployment and will provide comprehensive weather monitoring and notification services for both property owners and tenants, ensuring everyone stays informed and safe during severe weather conditions.

---

**SmartStay Enhanced Weather Alert System** - Keeping both property owners and tenants safe with intelligent, personalized weather notifications! 🌤️👥📧
