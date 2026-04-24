<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\{BaseParsingEvent, ParsingCompleteEvent};
use App\Mail\ReportReadyMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Order;
use App\Services\ReportService;


class ParsingListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }
    
    private function onParsingCompleteEvent(ParsingCompleteEvent $event)
    {
        $order = $event->getOrder();
        $orderMail = $order->customer->email;
        $reportService = new ReportService();
        $searchQueries = $order->searchQueries;
        $from = $event->parsingTimeFrom;
        $to     = $event->parsingTimeTo;
        $reports = [];

        foreach ($searchQueries as $sq) {
            try {    
                $reports[]  = $reportService->baseReport($from, $to, $sq->id);
            }
            catch (\Exception $ex) {
                Log::channel('daily')->error("Error making report for search query {$sq->query_text} for dates {$from} - {$to}: {$ex->getMessage()}");
            }
        }

        Mail::to($orderMail)->send(new ReportReadyMail($reports, $from, $to, $order->title));
        Log::channel('daily')->debug('Mail sent');
    }
    /**
     * Handle the event.
     */
    public function handle(BaseParsingEvent $event): void
    {
        $methodName = 'on'.(array_slice(explode('\\',get_class($event)),-1))[0];
        if (method_exists($this, $methodName)) {
            $this->$methodName($event);
        }
        else {
            Log::channel('daily')->error('Event handler not found for '.get_class($event));
        }
    }
}
