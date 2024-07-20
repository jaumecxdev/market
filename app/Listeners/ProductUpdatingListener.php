<?php

namespace App\Listeners;

use App\Events\ProductUpdatingEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProductUpdatingListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  ProductUpdatingEvent  $event
     * @return void
     */
    public function handle(ProductUpdatingEvent $event)
    {
        // NEW NAME: $event->product->getAttribute('name')
        // OLD NAME: $event->product->getOriginal('name')

        if ($event->product->fix_text) {
            $event->product->name = $event->product->getOriginal('name');
            $event->product->keywords = $event->product->getOriginal('keywords');
            $event->product->shortdesc = $event->product->getOriginal('shortdesc');
            $event->product->longdesc = $event->product->getOriginal('longdesc');
        }
    }
}
