<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeatherAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $alertData;
    public $property;
    public $user;
    public $userType;

    /**
     * Create a new message instance.
     */
    public function __construct($alertData, $property, $user, $userType = 'landlord')
    {
        $this->alertData = $alertData;
        $this->property = $property;
        $this->user = $user;
        $this->userType = $userType; // 'landlord' or 'tenant'
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $severity = $this->alertData['severity'] ?? 'moderate';
        $alertType = $this->alertData['type'] ?? 'weather';
        $userTypeText = $this->userType === 'tenant' ? ' (Your Boarding Property)' : '';
        
        $subject = match($severity) {
            'severe' => "🚨 SEVERE WEATHER ALERT - {$this->property->propertyName}{$userTypeText}",
            'moderate' => "⚠️ Weather Alert - {$this->property->propertyName}{$userTypeText}",
            'minor' => "ℹ️ Weather Notice - {$this->property->propertyName}{$userTypeText}",
            default => "Weather Alert - {$this->property->propertyName}{$userTypeText}"
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.weather_alert',
            with: [
                'alertData' => $this->alertData,
                'property' => $this->property,
                'user' => $this->user,
                'userType' => $this->userType
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
