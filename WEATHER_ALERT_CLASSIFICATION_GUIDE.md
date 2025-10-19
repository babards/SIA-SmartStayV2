# Weather Alert Classification System

## 🚨 **How Weather Alerts Are Classified**

The system automatically classifies weather conditions into **3 severity levels** based on specific thresholds and weather codes. Here's exactly how it works:

## 📊 **Alert Classification Table**

### **🌧️ RAINFALL ALERTS** (Based on Weather Codes & Precipitation Probability)

| Weather Code | Condition | Severity | Alert Type | Description |
|--------------|-----------|----------|------------|-------------|
| **95, 96, 99** | Thunderstorm | 🔴 **SEVERE** | `thunderstorm` | Severe weather with thunder and lightning |
| **63, 81** | Moderate Rain | 🟡 **MODERATE** | `moderate_rain` | Moderate rainfall conditions |
| **61, 80** | Light Rain | 🔵 **MINOR** | `light_rain` | Light rainfall conditions |

**Forecast Rainfall (Next 4 Days):**
| Weather Code | Condition | Severity | Alert Type | Description |
|--------------|-----------|----------|------------|-------------|
| **95, 96, 99** | Thunderstorm Forecast | 🔴 **SEVERE** | `thunderstorm_forecast` | Severe weather forecasted |
| **63, 81** | Moderate Rain Forecast | 🟡 **MODERATE** | `moderate_rain_forecast` | Moderate rainfall forecasted |
| **61, 80** | Light Rain Forecast | 🔵 **MINOR** | `light_rain_forecast` | Light rainfall forecasted |

### **💨 WIND ALERTS**

| Condition | Threshold | Severity | Alert Type | Description |
|-----------|-----------|----------|------------|-------------|
| **Strong Wind** | > 50 km/h | 🔴 **SEVERE** | `strong_wind` | Secure outdoor items |
| **Moderate Wind** | > 30 km/h | 🟡 **MODERATE** | `moderate_wind` | Light outdoor items at risk |
| **Light Wind** | ≤ 30 km/h | ✅ **No Alert** | - | Normal conditions |

**Forecast Wind:**
| Condition | Threshold | Severity | Alert Type | Description |
|-----------|-----------|----------|------------|-------------|
| **Strong Wind Forecast** | > 40 km/h | 🟡 **MODERATE** | `strong_wind_forecast` | Secure outdoor items |

### **🌡️ TEMPERATURE ALERTS**

| Condition | Threshold | Severity | Alert Type | Description |
|-----------|-----------|----------|------------|-------------|
| **Extreme Heat** | > 38°C | 🔴 **SEVERE** | `extreme_heat` | Risk of heat-related issues |
| **High Temperature** | > 35°C | 🟡 **MODERATE** | `high_temperature` | Monitor temperature conditions |
| **Normal Temperature** | ≤ 35°C | ✅ **No Alert** | - | Normal conditions |

**Forecast Temperature:**
| Condition | Threshold | Severity | Alert Type | Description |
|-----------|-----------|----------|------------|-------------|
| **Extreme Heat Forecast** | > 38°C | 🔴 **SEVERE** | `extreme_heat_forecast` | Take heat precautions |

### **⛈️ THUNDERSTORM ALERTS** (Integrated with Rainfall)

Thunderstorm alerts are now integrated with rainfall alerts based on weather codes:
- **Codes 95, 96, 99** = **SEVERE** alerts (all thunderstorms are considered severe)
- Thunderstorm forecasts are checked for the **next 4 days**
- All thunderstorm conditions trigger immediate severe alerts

## 🎯 **Severity Level Definitions**

### 🔴 **SEVERE ALERTS**
- **Immediate action required**
- **High risk** to property and safety
- **Urgent attention** needed
- **Examples**: Thunderstorms (codes 95, 96, 99), Strong winds (>50 km/h), Extreme heat (>38°C)

### 🟡 **MODERATE ALERTS**
- **Caution advised**
- **Moderate risk** to property
- **Preventive measures** recommended
- **Examples**: Moderate rain (codes 63, 81), Moderate winds (>30 km/h), High temperature (>35°C)

### 🔵 **MINOR ALERTS**
- **Informational only**
- **Low risk** but worth monitoring
- **Maintenance awareness**
- **Examples**: Light rain (codes 61, 80)

## 🔍 **How Classification Works**

### **Step 1: Data Collection**
```php
// System collects current weather data
$current = [
    'precipitation' => 12.5,    // mm
    'precipitation_probability' => 85, // %
    'wind_speed' => 35,         // km/h
    'temperature' => 28,        // °C
    'weather_code' => 95        // WMO code
];
```

### **Step 2: Weather Code Classification**
```php
// Thunderstorm check (SEVERE)
if (in_array($weather_code, [95, 96, 99])) {
    $severity = 'severe';
    $alertType = 'thunderstorm';
}
// Moderate rain check (MODERATE)
elseif (in_array($weather_code, [63, 81])) {
    $severity = 'moderate';
    $alertType = 'moderate_rain';
}
// Light rain check (MINOR)
elseif (in_array($weather_code, [61, 80])) {
    $severity = 'minor';
    $alertType = 'light_rain';
}

// Wind and temperature checks remain unchanged
if ($wind_speed > 50) {
    $severity = 'severe';
    $alertType = 'strong_wind';
}
```

### **Step 3: Alert Generation**
```php
$alert = [
    'type' => $alertType,
    'severity' => $severity,
    'message' => $message,
    'description' => $description,
    'weather_data' => $current
];
```

## 📧 **Email Subject Lines by Severity**

### **Severe Alerts:**
```
🚨 SEVERE WEATHER ALERT - Property Name
🚨 SEVERE WEATHER ALERT - Property Name (Your Boarding Property)
```

### **Moderate Alerts:**
```
⚠️ Weather Alert - Property Name
⚠️ Weather Alert - Property Name (Your Boarding Property)
```

### **Minor Alerts:**
```
ℹ️ Weather Notice - Property Name
ℹ️ Weather Notice - Property Name (Your Boarding Property)
```

## 🎨 **Visual Indicators**

### **Email Styling:**
- **Severe**: Red background, red border, 🚨 icon
- **Moderate**: Orange background, orange border, ⚠️ icon
- **Minor**: Blue background, blue border, ℹ️ icon

### **Severity Badges:**
- **Severe**: Red badge with "SEVERE ALERT"
- **Moderate**: Orange badge with "MODERATE ALERT"
- **Minor**: Blue badge with "MINOR ALERT"

## 📊 **Real Examples**

### **Example 1: Thunderstorm Alert (SEVERE)**
```php
// Weather Data
$weather_code = 95; // Thunderstorm
$precipitation_probability = 85; // %

// Classification
if (in_array($weather_code, [95, 96, 99])) {
    $severity = 'severe';        // 🔴 SEVERE
    $message = 'Thunderstorm detected';
    $description = 'Thunderstorm activity detected. Prepare for severe weather.';
}
```

### **Example 2: Moderate Rain Alert (MODERATE)**
```php
// Weather Data
$weather_code = 63; // Moderate rain
$precipitation = 8.5; // mm

// Classification
if (in_array($weather_code, [63, 81])) {
    $severity = 'moderate';      // 🟡 MODERATE
    $message = 'Moderate rainfall detected';
    $description = 'Moderate rainfall conditions detected. Monitor for potential issues.';
}
```

### **Example 3: Light Rain Alert (MINOR)**
```php
// Weather Data
$weather_code = 61; // Light rain
$precipitation = 2.3; // mm

// Classification
if (in_array($weather_code, [61, 80])) {
    $severity = 'minor';         // 🔵 MINOR
    $message = 'Light rainfall detected';
    $description = 'Light rainfall conditions detected. Normal weather conditions.';
}
```

## ⚙️ **Customization Options**

The weather code classifications can be easily modified in the `WeatherService.php` file:

```php
// Customize thunderstorm codes (SEVERE)
if (in_array($weather_code, [95, 96, 99])) {
    $severity = 'severe';
    $alertType = 'thunderstorm';
}

// Customize moderate rain codes (MODERATE)
elseif (in_array($weather_code, [63, 81])) {
    $severity = 'moderate';
    $alertType = 'moderate_rain';
}

// Customize light rain codes (MINOR)
elseif (in_array($weather_code, [61, 80])) {
    $severity = 'minor';
    $alertType = 'light_rain';
}

// Wind and temperature thresholds remain customizable
if ($wind_speed > 40) {           // Change from 50 km/h to 40 km/h
    $severity = 'severe';
}

if ($temperature > 40) {          // Change from 38°C to 40°C
    $severity = 'severe';
}
```

## 🎯 **Summary**

The weather alert classification system uses **WMO weather codes** and **precipitation probability** to automatically determine alert severity:

- **🔴 SEVERE**: Thunderstorms (codes 95, 96, 99), Strong winds (>50 km/h), Extreme heat (>38°C)
- **🟡 MODERATE**: Moderate rain (codes 63, 81), Moderate winds (>30 km/h), High temperature (>35°C)
- **🔵 MINOR**: Light rain (codes 61, 80)

**Key Features:**
- **Rainfall-focused**: Uses weather codes for accurate rain classification
- **4-day forecast**: Checks next 4 days for weather conditions
- **No humidity alerts**: Removed to focus on critical weather conditions
- **Maintained wind/temperature**: Keeps existing wind and temperature alert systems

The system is designed to be **accurate**, **reliable**, and **focused on property safety** to ensure property owners and tenants receive appropriate alerts based on actual weather risks.

---

**SmartStay Weather Alert Classification** - Weather code-based, automatically-classified weather alerts! 🌤️📊🚨
