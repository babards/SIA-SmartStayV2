<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\PropertyApplication;

class ApplicationCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $property;
    public $tenant;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(PropertyApplication $application)
    {
        $this->application = $application;
        $this->property = $application->property;
        $this->tenant = $application->tenant;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Application Cancelled - ' . $this->property->propertyName)
                    ->view('emails.application-cancelled')
                    ->with([
                        'application' => $this->application,
                        'property' => $this->property,
                        'tenant' => $this->tenant,
                    ]);
    }
} 