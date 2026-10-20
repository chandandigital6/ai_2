<?php
namespace App\Observers;
use App\Models\Order;
use App\Services\GrowyaNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
class GrowyaOrderNotificationObserver implements ShouldHandleEventsAfterCommit {
    private function send(array $ids,string $type,string $title,string $message,string $url,int $businessId):void {
        try {app(GrowyaNotificationService::class)->send($ids,$type,$title,$message,$url,$businessId);}
        catch(\Throwable $e){Log::error('Growya order notification failed',['error'=>$e->getMessage()]);}
    }
    public function created(Order $order):void {
        $ids=app(GrowyaNotificationService::class)->ownerOf($order->business_id);
        $this->send($ids,'new_order','New customer order','Order '.$order->order_number.' placed.',
            route('businesses.orders.show',[$order->business_id,$order->id],false),$order->business_id);
    }
    public function updated(Order $order):void {
        if(!$order->wasChanged('status') || !$order->customer_id)return;
        $this->send([(int)$order->customer_id],'order_status_changed','Order status updated',
            'Order '.$order->order_number.' is now '.$order->status.'.',
            route('growya.orders.show',$order,false),$order->business_id);
    }
}
