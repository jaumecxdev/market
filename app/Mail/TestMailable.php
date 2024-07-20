<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $testdata;
    protected $testdata2;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($testdata)
    {
        $this->testdata = $testdata;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from('order@mpespecialist.com')
            ->subject('TEST SUBJECT')
            ->view('emails.test');
    }
}
