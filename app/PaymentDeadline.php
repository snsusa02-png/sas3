<?php

namespace App;

class PaymentDeadline extends Model
{

    public function calendar_events() {
        return $this->morphMany('App\CalendarEvent', 'parent');
    }

    // ...

}
