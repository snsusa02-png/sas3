<?php
//https://graker.ru/news/2019/08/22/events-calendar-on-laravel-and-vuejs-part-1

namespace App;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{

    protected $guarded = [];

    protected $with = ['parent'];

    public function parent() {
        return $this->morphTo();
    }

    public static function makeDepartureEvent(Order $order) {
        self::makeFlightEvent($order, 'departure');
    }

    public static function makeReturnEvent(Order $order) {
        self::makeFlightEvent($order, 'return_flight');
    }

    /**
     * Код создания событий почти не отличается, поэтому они создаются из одного метода
     */
    protected static function makeFlightEvent(Order $order, $type) {
        $date = ($type == 'departure') ? $order->flight_date : $order->return_date;
        if (!$date) {
            // если дата не определена, удаляем существующее событие (вдруг оно уже было создано ранее)
            $event = $order->calendar_events->filter(function (CalendarEvent $item) use ($type) {
                return $item->type == $type;
            })->first();
            optional($event)->delete();
            return;
        }

        // метод генерирует текст события на базе каких-то данных заказа
        // (этот текст будет видно на календаре)
        // реализуйте его самостоятельно или подставьте любой текст
        $title = static::makeOrderEventTitle($order);

        // метод сначала проверит, есть ли уже ранее созданное событие, и обновит его, если есть
        // а если нет - создаст новое
        static::updateOrCreate([
            'type' => $type,
            'parent_id' => $order->id,
            'parent_type' => Order::class,
        ], [
            'title' => $title,
            'start' => $date,
            // дату окончания не указываем, предполагая, что событие заканчивается в тот же день
            'end' => NULL,
        ]);
    }

    public static function makePaymentDeadlineEvent(PaymentDeadline $paymentDeadline) {
            // метод генерирует текст события на базе каких-то данных о сроках платежа
            // (этот текст будет видно на календаре)
            // реализуйте его самостоятельно или подставьте любой текст
            $title = static::makeDeadlineEventTitle($paymentDeadline);

            static::updateOrCreate([
                'type' => 'payment_deadline',
                'parent_id' => $paymentDeadline->id,
                'parent_type' => PaymentDeadline::class,
            ], [
                'title' => $title,
                'start' => $paymentDeadline->due,
                'end' => NULL,
            ]);
        }
        
}
