<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Webklex\PHPIMAP\ClientManager;
use App\mimetype;
use App\proj_mail;
use Storage;
use File;

class proj_mailbox extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'proj_mailboxes';
    static public $sysobjid = 535;


    public static function getNewMail()
    {
        //Опрос почтовых ящиков проектов и импорт писем и вложений

        $cm = new ClientManager(config_path('imap.php'));
        //dd($cm);

        $mailboxes = self::where('active', 1)->orderby('id', 'desc')->get();
        //dd($mailboxes);

        $newmail_cnt = 0;
        foreach ($mailboxes as $mailbox) {

            //$oClient = Webklex\IMAP\Facades\Client::account('default');

            //$oClient = $cm->account('default');
//        $oClient->host = $mailbox->imap_host;
//        $oClient->port = $mailbox->imap_port;
//        $oClient->encryption = $mailbox->imap_encryption;
//        $oClient->validate_cert = $mailbox->imap_validate_cert;
//        $oClient->authentication = $mailbox->imap_authentication;
//        $oClient->username = $mailbox->imap_username;
//        $oClient->password = $mailbox->imap_password;


            $oClient = $cm->make([
                'host' => $mailbox->imap_host,
                'port' => $mailbox->imap_port,
                'encryption' => $mailbox->imap_encryption,
                'validate_cert' => $mailbox->imap_validate_cert,
                'username' => $mailbox->imap_username,
                'password' => $mailbox->imap_password,
                'protocol' => $mailbox->imap_protocol
            ]);

            //Connect to the IMAP Server
            $oClient->connect();

            //dd(public_path('attachments/'));
            $storagePath = storage_path('app/public/files/536/');

            //Get all Mailboxes
            /** @var \Webklex\IMAP\Support\FolderCollection $aFolder */
            $aFolder = $oClient->getFolders();
            //$aFolder = $oClient->getFolders(false)->where("name", 'INBOX')->first();
            //$bFolder = $oClient->getFolderByName('INBOX');

            //Loop through every Mailbox
            /** @var \Webklex\IMAP\Folder $oFolder */
            //dd($aFolder, $bFolder);
            foreach ($aFolder as $oFolder) {
                if ($oFolder->name == 'INBOX') {

                    //Get all Messages of the current Mailbox $oFolder
                    /** @var \Webklex\IMAP\Support\MessageCollection $aMessage */

                    //$aMessage = $oFolder->messages()->all()->get();
                    //$aMessage = $oFolder->query()->since('01.03.2021')->get();
                    //  $aMessage = $bFolder->query()->since(now()->subDays(3))->get();

                    $aMessage = $oFolder->query()->since(now()->subDays(2))
                        ->setFetchFlags(false)
                        ->setFetchBody(false)
                        //->setFetchAttachment(false)
                        ->setFetchOrder("desc")
                        ->get();

                    /** @var \Webklex\IMAP\Message $oMessage */
                    foreach ($aMessage as $oMessage) {

                        $uid = $oMessage->getUid();

                        if (1 == 0) {
                            echo '<hr />';
                            echo 'uid: ' . $uid . '<br />';
                            echo $oMessage->getDate() . '<br />';
                            //var_dump($oMessage->getFrom());
                            echo $oMessage->getFrom()[0]->mail . '<br />';
                            echo $oMessage->getSubject() . '<br />';
                            echo 'Attachments: ' . $oMessage->getAttachments()->count() . '<br />';
                            //echo $oMessage->getHTMLBody(true);
                            echo $oMessage->getTextBody(true) . '<br/>';
                            //echo '<br/>' . __DIR__ . '<br/>';
                            //var_dump($oMessage->getFlags());
                        }


                        //Проверим, сохраняли ли это письмо ранее?
                        $proj_mail = proj_mail::where(['mailboxid' => $mailbox->id, 'uid' => $uid])
                            ->first();

                        if (!isset($proj_mail)) {

                            /** @var \Webklex\IMAP\Folder $oFolder */
                            /** @var \Webklex\IMAP\Message $oMessage */

                            $oMessage = $oFolder->query()->getMessageByUid($uid);

                            if (isset($oMessage)) {

                                $proj_mail = proj_mail::addOrUpdate(['mailboxid' => $mailbox->id, 'uid' => $uid],
                                    [
                                        'projid' => $mailbox->projid,
                                        'created_at' => $oMessage->getDate(),
                                        'mail_from' => $oMessage->getFrom()[0]->mail,
                                        'mail_to' => $oMessage->getTo()[0]->mail,
                                        'subject' => $oMessage->getSubject(),
                                        'text_body' => $oMessage->getTextBody(true),
                                        'html_body' => $oMessage->getHTMLBody(true),
                                        'attachment_cnt' => $oMessage->getAttachments()->count(),
                                    ]);

                                $newmail_cnt++;

                                /**
                                 * @var \Webklex\IMAP\Support\AttachmentCollection $aAttachment
                                 */
                                $aAttachment = $oMessage->getAttachments();

                                $folder = 'files/' . 536 . '/' . $proj_mail->id . '/';
                                $attachment_num = 0;
                                $aAttachment->each(function ($oAttachment) use (
                                    $mailbox, $oMessage, $proj_mail, $folder, $storagePath, $attachment_num
                                ) {
                                    /** @var \Webklex\IMAP\Attachment $oAttachment */
                                    $attachment_num++;

                                    $mimetype = mimetype::where('mimetype', $oAttachment->content_type)
                                        ->first();
                                    $filename = $oAttachment->name;
                                    if ($filename == 'undefined') {
                                        $filename = $attachment_num . '.' . $mimetype->extension ?? '';
                                    }
                                    if ($oMessage->getUid() == 1054) {
                                        //dd($oAttachment, $filename);
                                    }
                                    $attributes = $oAttachment->getAttributes();
                                    //dd($attributes, $attributes['size'],$oAttachment->name);
                                    $filesize = $attributes['size'] ?? null;

                                    $filepath = $storagePath . $proj_mail->id . '/';
                                    File::makeDirectory($filepath, $mode = 0777, true, true);

                                    $oAttachment->save($filepath, $filename);

                                    //dd($filepath, $folder, $filename);
                                    if (file_exists($filepath . $filename)) {
                                        // Save to table
                                        $rec = new objfile();
                                        $rec->sysobjid = 536;
                                        $rec->objid = $proj_mail->id;
                                        $rec->sysfiletype_id = 4;   //todo: заплатка. Нужно разобраться почему этот параметр обязателен
                                        $rec->mimetypeid = $mimetype->id;
                                        $rec->publicfilename = $filename;
                                        $rec->systemfilename = $folder . $filename;
                                        $rec->filesize = $filesize;
                                        $rec->notes = '';
                                        $rec->doctypeid = 221;  //Прочие документы
                                        //$rec->ordr = ++$ordr;
                                        $rec->save();
                                    }
                                    echo '<br/> attachment saved to ' . $filepath . $filename . '<br/>';
                                });

                                //Move the current Message to 'INBOX.read'
//            if ($oMessage->moveToFolder('INBOX.read') == true) {
//                echo 'Message has ben moved';
//            } else {
//                echo 'Message could not be moved';
//            }

                            }
                        }
                    }
                }
            }
        }

        echo("New mail count: {$newmail_cnt}");
        return ("New mail count: {$newmail_cnt}");
    }

}
