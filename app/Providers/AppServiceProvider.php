<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Observers\OrderObserver;
use App\Order;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
//        Order::observe(OrderObserver::class);
//        PaymentDeadline::observe(PaymentDeadlineObserver::class);

        Validator::extend('equally', function ($attribute, $value, $parameters, $validator)
        {
            if(count($parameters)==0) throw new  \Exception("Параметр для правила не установлен", 1507);

            if(is_null($parameters[0])) return false;
            if(is_null($value)) return false;
            return  $value==$parameters[0];
        });

        // 20190612 SNS - ----------------------------------------------------------------
        Validator::extend('greater_than_field', function($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value > $min_value;
        });

        Validator::replacer('greater_than_field', function($message, $attribute, $rule, $parameters) {
            return str_replace(':field', $parameters[0], $message);
        });
        // -------------------------------------------------------------------------------

        // 20190612 SNS - ----------------------------------------------------------------
        Validator::extend('not_greater_than_field', function($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value <= $min_value;
        });

        Validator::replacer('not_greater_than_field', function($message, $attribute, $rule, $parameters) {
            return str_replace(':field', $parameters[0], $message);
        });
        // -------------------------------------------------------------------------------

    }
}
