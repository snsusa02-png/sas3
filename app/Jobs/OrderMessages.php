<?php

namespace App\Jobs;

use App\Mail\OrderTaken2Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

//use Illuminate\Support\Facades\Auth;
//задумавыалось как письмо клиенту после начала обработки заказа, пока хватает и на всё остальное

//use DB;
use Log;
use App\objlog;

class OrderMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderid;
    protected $code;
    protected $flagsetter;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $code, $flagsetter)
    {
        $this->orderid = $data;
        $this->code = $code;
        $this->flagsetter = $flagsetter;
    }
    private function sendmail($to,$data){
        //Специально для Сергея Павловича
        //Вылетать может не только на на синхронной асинхронной отправке
        //Но и если email-адрес оказался неправильным
        //У меня тесты из за этого не проходят
        try{
           Mail::to($to)
            ->send(new OrderTaken2Client($data));
            $msg='Письмо  отправлено на: '.
                        $to.". Тема:".$data->subject;
            objlog::log_info_admin(131, $this->orderid, $msg);
            //Log::info($msg);
        }
        catch(\Exception $e){
            Log::error("Email to:".$to."\n"
                            //.'Data: '.var_export($data,true)."\n"
                            .$e->getMessage()
            );
        }
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //специально для Станислава Александровича: try-catch тут делать не надо.
        //потому как синхронная отправка нужна только для отладки и exception вывалит в браузер
        //в продакшене отправка должна быть отложенной/фоновой и exception выпадет в laravel.log
        $Data4Customer = new \stdClass();//Инфа клиента
        $Data4Manager = new \stdClass();//Инфа манагера
        //часть инфы одинаковая для всех случаев
        $Data4Customer->order = \App\order::whereId($this->orderid)->with('items')
            ->first();//first даёт объект, get даёт коллекцию

        info('OrderMessage.handle this.orderid='.$this->orderid);
        info('OrderMessage.handle this.code='.$this->code);
        info('OrderMessage.handle this.flagsetter='.$this->flagsetter);
        $Data4Customer->order->buyerstaff_fio = \App\User::getFIO($Data4Customer->order->workuserid);

        $Data4Manager->order = $Data4Customer->order;


//        if ($Data4Customer->order->buyerstaffid) {
//            $Data4Customer->userinfo = \App\User::getUserInfo($Data4Customer->order->buyerstaffid);
//        }
//        if ($Data4Manager->order->ownstaffid) {
//            $Data4Manager->userinfo = \App\User::getUserInfo($Data4Manager->order->ownstaffid);
//        }
        //20200504 SNS. здесь не определены order->buyerstaffid или order->ownstaffid
        $Data4Manager->userinfo = \App\User::getUserInfo($Data4Manager->order->rqstuserid);

        $Data4Customer->Signer = "Ваш ________";
        $Data4Customer->greeting = "Здравствуйте, уважаемый ";
        $Data4Manager->Signer = $Data4Customer->Signer;
        $Data4Manager->greeting = $Data4Customer->greeting;
        $Data4Customer->cansend = false;
        $Data4Manager->cansend = false;
        $Data4Customer->showoffer = false;
        $Data4Manager->showoffer = false;
        switch ($this->code) {
            case 'ORD.TAKEMNGR'://Заказ взят в работу
                info('ORD.TAKEMNGR orderid=' . $this->orderid . ' flagsetter=' . $this->flagsetter);
                //Заполним уведомление клиенту
                if ($Data4Customer->order->rqstuserid) {
                    $Data4Customer->subject = 'Ваш заказ №'.$Data4Customer->order->id.' взят в работу';
                    $Data4Customer->reason = "Ваш заказ принят в работу нашим менеджером. В ближайшее время он свяжемся с вами";
                    $Data4Customer->cansend = true;
                } else {
                    //Если заказ без конкретного buyerstaffid, значит менеджеры балуются
                }
                //Если менеджер не сам захватил, а его назначили - его тоже надо уведомить
                if (($Data4Manager->order->workuserid) && ($this->flagsetter != $Data4Manager->order->workuserid)) {
                    $Data4Manager->reason = "Вы назначены куратором заказа.";
                    $Data4Manager->subject = 'Новое назначение.';
                    $Data4Manager->cansend = true;
                }
                break;
            case 'ORD.PUTCSTMR'://Клиент чего-то заказал
                //Отправим уведомление клиенту если это не Дальойл
                if (($Data4Customer->order->rqstuserid) && ($Data4Customer->order->orgid != 1/* $Data4View->order->ownorgid*/)) {
                    $Data4Customer->reason = "Вы разместили заказ в личном кабинете Дальойл. В ближайшее время наши менеджеры обратятся к Вам для уточнения деталей.";
                    $Data4Customer->subject = 'Информация о заказе.';
                    $Data4Customer->cansend = true;
                } else {
                    //Если заказ без конкретного buyerstaffid, значит программисты балуются
                }
                //Уведомим кураторов клиента если он не мы
                if ($Data4Manager->order->orgid != 1) {
                    $Data4Manager->subject = 'Новый заказ из ЛК (' . $Data4Manager->order->org->name . ')';
                    $Data4Manager->reason = "Наш клиент (" . $Data4Manager->order->buyerstaff_fio . ' / ' . $Data4Manager->order->org->name . ") разместил заказ в личном кабинете на сайте.";
                    if ($Data4Manager->order->ownstaffid) {
                        $Data4Manager->cansend = true;//почему-то ответственный менеджер уже есть, ему и отправим
                    } else {

//                        foreach (\App\org_curator::whereOrgid($Data4Manager->order->orgid)->get() as $curator) {
//                            $Data4Manager->userinfo = \App\orgstaff::getUserInfo($curator->staffid);
//                            if ($Data4Manager->userinfo) {
//                                Mail::to($Data4Manager->userinfo->email)->send(new OrderTaken2Client($Data4Manager));
//                            }
//                            $Data4Manager->cansend = false;//отправили уже
//                        }


                        foreach (\App\org_curator::
                            OrgActiveCuratorList($Data4Manager->order->orgid) as $curator) {

                            //$Data4Manager->userinfo = \App\orgstaff::getUserInfo($curator->staffid);
                            $Data4Manager->userinfo = \App\User::getUserInfo4Mail($curator->userid);
                            $this->sendmail($curator->email,$Data4Manager);
                            $Data4Manager->cansend = false;//отправили уже

                        }
                    }

                }
                break;
            case 'ORD.OFFERCMPLT'://Менеджер подтвердил заказ\выставил встречное предлжение
                if ($Data4Customer->order->buyerstaffid) {
                    $Data4Customer->subject = 'Заказ №'.$Data4Customer->order->id.' обработан';
                    $Data4Customer->reason = "Наши менеджеры обработали Ваш заказ. Ознакомтесь с наличием товара и текущей ценой ниже:";
                    $Data4Customer->cansend = true;
                    $Data4Customer->showoffer = true;
                } else {
                    //Если заказ без конкретного buyerstaffid, значит менеджеры балуются
                }
            default:
        }
        if ($Data4Customer->cansend ) {
            $this->sendmail($Data4Customer->userinfo->email,$Data4Customer);
        }
        if ($Data4Manager->cansend and isset($Data4Manager->userinfo->email)) {
            $this->sendmail($Data4Manager->userinfo->email,$Data4Manager);
        }

    }
}
