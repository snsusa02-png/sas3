<?php

namespace App\Http\Controllers;

use App\checkrqst;
use App\Http\Controllers\Controller;
use App\Mail\DemoEmail;
use App\sysobj;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Swift_SmtpTransport;
use Swift_Mailer;
use Auth;
use App\proj_mailbox;

class MailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function send()
    {
        $objDemo = new \stdClass();
        $objDemo->demo_one = 'Demo One Value';
        $objDemo->demo_two = 'Demo Two Value';
        $objDemo->sender = 'SenderUserName';
        $objDemo->receiver = 'ReceiverUserName';

        //Mail::to("shevchenko.s@basko.su")->send(new DemoEmail($objDemo));
        Mail::to("robot@basko.su")->send(new DemoEmail($objDemo));
    }

    public function basic_email()
    {
        $data = array('name' => "Elton Gandhi");

        Mail::send(['text' => 'mails.test2'], $data, function ($message) {
            $message->to('shevchenko.s@basko.su', 'Tutorials Point')
                ->subject('Laravel Basic Testing Mail')
                ->from('robot@basko.su', 'БАСКО');
        });
        echo "Basic Email Sent. Check your inbox.";
    }

    public function html_email()
    {
        $mail = new Mail();
        //dd($mail);

        $data = array('name' => "Иван Григорьевич");

        $backup = Mail::getSwiftMailer();


//        $username = 'shevchenko.s@basko.su';
//        $password = 'vcHe_1956';

        $username = 'sneg9@basko.su';
        $password = 'fntgvofvtucvxhus';
        $from = ['address' => $username, 'name' => 'Сергей Шевченко - Баско Консалтинг'];

        $altConfig = [
            'host' => 'smtp.yandex.ru'
            , 'port' => '465'
            , 'username' => $username
            , 'password' => $password
            , 'encryption' => 'ssl'
            , 'from' => $from
        ];

        $fromAddressBackup = config('mail.from.address');
        $fromNameBackup = config('mail.from.name');

        $this->initAltMailer($altConfig);

        //dd(config('mail'), $transport);

        Mail::send('mails.test2', $data,
            function ($message) use ($username) {
                $message->to('shevchenko.s@basko.su', 'Tutorials Point ' . now()->format('H:i:s'))
                    ->subject('Laravel send Mail from different senders: ' . $username);
                //$message->from($username, 'Sergey Shevchenko');
            });

        Mail::setSwiftMailer($backup);

        $username = 'robot@basko.su';
        $password = 'Robo7812';
        $from = ['address' => $username, 'name' => 'ГК Баско - robot'];

        $altConfig = [
            'host' => 'smtp.yandex.ru'
            , 'port' => '465'
            , 'username' => $username
            , 'password' => $password
            , 'encryption' => 'ssl'
            , 'from' => $from
        ];
        //Mail::setSwiftMailer($this->constructCustomMailer($altConfig));

        $this->initAltMailer($altConfig);

        Mail::send('mails.test2', $data,
            function ($message) use ($from) {
                $message->to('shevchenko.s@basko.su', 'diff from ' . now()->format('H:i:s'))
                    ->subject('Заявка на инспекцию строительного контроля (' . $from['name'] . ')')
                    ->attach(storage_path('app/files/1761/') . 'rqst_23.pdf', ['as' => 'Заявка.pdf']);
//                    ->attach(public_path('/images/fileicons') . '/code-signing.png'
//                        , [
//                            'as' => 'oil.png',//как отображать имя файла в письме
//                            'mime' => 'image/png',//х.з. но вдруг само не определит
//                        ]);

            });

        echo "HTML Email Sent. Check your inbox.";
    }


    public function attachment_email()
    {
        $data = array('name' => "Virat Gandhi");
        Mail::send('mails.test2', $data, function ($message) {
            $message->to('shevchenko.s@basko.su', 'Tutorials Point')->subject
            ('Laravel Testing Mail with Attachment');
            $message->attach('C:\laravel-master\laravel\public\uploads\image.png');
            $message->attach('C:\laravel-master\laravel\public\uploads\test.txt');
            $message->from('shevchenko.s@basko.su', 'Virat Gandhi');
        });
        echo "Email Sent with attachment. Check your inbox.";
    }

    public function email_checkrqst($rqstid)
    {

        $checkrqst = checkrqst::find($rqstid);
        if (!isset($checkrqst))
            return redirect(route('home'))->with(['error' => 'Error!']);

        //Договорились - всю переписку по проекту вести от одного адреса ЭП
        //найти первый активный адрес, связанный с данным объектом строительства (через проект)
        $proj_mailbox = proj_mailbox::from('proj_mailboxes as pmb')
            ->where('projid', $checkrqst->buildobj->projectid)
            ->first();
        //dd($proj_mailbox);
        if (!isset($proj_mailbox))
            return redirect(route('home'))->with(['error' => 'Ошибка! Не задан адрес электронной почты отправителя']);

        $userid = Auth::user()->id;
        $user_fio = Auth::user()->shortfio;

        $filename = "rqst_{$checkrqst->id}.pdf";

        $username = $proj_mailbox->imap_username ?? 'sneg9@basko.su';
        $password = $proj_mailbox->imap_password ?? 'fntgvofvtucvxhus';
        $from = ['address' => $username, 'name' => $user_fio];

        $altConfig = [
            'host' => 'smtp.yandex.ru'
            , 'port' => '465'
            , 'username' => $username
            , 'password' => $password
            , 'encryption' => 'ssl'
            , 'from' => $from
        ];


        //адреса получателей -------------------------------------
        $to_emails = ['sk.stroyproekt@list.ru'];
//        $to_emails = ['shevchenko.s@basko.su'];
        $bcc_emails = ['sneg9@basko.su', 'shevchenko.s@basko.su'];
        //идея - отобрать адреса ЭП организаций, участников Заявки. И Адреса должны быть с тегом "Строительный контроль"


        //Данные для формы письма --------------------------------
        $data = array('name' => "Евгений Валерьевич");


        $backup = Mail::getSwiftMailer();
        $backup_fromAddress = config('mail.from.address');
        $backup_fromName = config('mail.from.name');
        try {
            //смена конфигурации почтовика
            $this->initAltMailer($altConfig);

            //Отправка письма
            Mail::send('mails.checkrqst', $data,
                function ($message) use ($from, $to_emails, $bcc_emails, $filename, $checkrqst) {

                    //Соглашение - Рассчитываем, что Запрос на инспекцию ВСЕГДА имеет pdf-файл с запросом

                    $message->to($to_emails)
                        ->bcc($bcc_emails)
                        ->from($from['address'], $from['name'])
                        ->subject('Заявка на инспекцию строительного контроля (' . $from['name'] . ')')
                        ->attach(storage_path('app/files/1761/') . $filename
                            , ['as' => 'Заявка на инспекцию №' . ($checkrqst->docnum ?? '-') . '.pdf']);
                });

            //отметим время отправки ---------------------------------------------------------------
            checkrqst::where('id', $rqstid)->update([
                'send_at' => now(), 'send_by' => $userid, 'send_hash' => $checkrqst->hash()
                , 'updated_at' => DB::raw('updated_at')
                , 'updated_by' => DB::raw('updated_by')
            ]);
            //--------------------------------------------------------------------------------------


        } catch (\Exception $e) {

            Log::error("Email CheckRqst" . $checkrqst->id . "\n"
                . $e->getMessage()
            );
        }

        //восстановить конфигурацию почты ------------------------------------
        Mail::setSwiftMailer($backup);
        if (isset($backup_fromAddress)) {
            Mail::alwaysFrom($backup_fromAddress, $backup_fromName ?? '');
        }
        //--------------------------------------------------------------------


        //echo "Заявка отправлена.";
        return redirect(route('checkrqsts.edit', $checkrqst->id))->with(['success' => 'Заявка отправлена']);
    }


    public function sendCustomMail($altConfig)
    {
        // validate $altConfig here...

        $backup = \Mail::getSwiftMailer();

        $customMailer = $this->constructCustomMailer($altConfig);

        \Mail::setSwiftMailer($customMailer);

        if (isset($altConfig['from'])) {
            $fromAddressBackup = config('mail.from.address');
            \Mail::alwaysFrom($altConfig['from']);
        }


        $ret = $this->doTheActualSendingOfTheMail(); // ;)


        \Mail::setSwiftMailer($backup);

        if (isset($fromAddressBackup)) {
            \Mail::alwaysFrom($fromAddressBackup);
        }

        return $ret;
    }


    protected function constructCustomMailer($altConfig): \Swift_Mailer
    {
        $transport = new Swift_SmtpTransport();
        $transport->setHost($altConfig['host']);
        $transport->setPort($altConfig['port']);
        $transport->setUsername($altConfig['username']);
        $transport->setPassword($altConfig['password']);
        $transport->setEncryption($altConfig['encryption']);

        $customMailer = new Swift_Mailer($transport);

        return $customMailer;
    }

    protected function initAltMailer($altConfig)
    {
        Mail::setSwiftMailer($this->constructCustomMailer($altConfig));
        //dd($altConfig['from']['address']);
        if (isset($altConfig['from'])) {
            $fromAddressBackup = config('mail.from.address');
            Mail::alwaysFrom($altConfig['from']['address'], $altConfig['from']['name']);
        }
    }
}
