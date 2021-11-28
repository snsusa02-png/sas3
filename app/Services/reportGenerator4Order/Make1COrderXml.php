<?php
namespace App\Services\reportGenerator4Order;
use App\order;
use App\orditem;
use App\org;
use App\Traits\Result;

class Make1COrderXml{
    public function __construct($ordid){
        $this->ordid=$ordid;
    }

    public function generate(){
        $res=new Result;
        $order=order::find($this->ordid);
        if(!isset($order)){
            $res->err=1;
            $res->msg="Заказ с id=$this->ordid не найден";
            return $res;
        }
        $ownorg=org::find($order->ownorgid);
        $org=org::find($order->orgid);
        $orditems=orditem::where('ordid',$this->ordid)->orderBy('id')->get();
        $res=$this->make($ownorg,$org,$order,$orditems);
        return $res;
    }

    public function mimetypeid(){
        //TODO: сделать запросом
        return 3;
    }
    public function getFileTypeId(){
        return 3;
    }

    public function getGeneratorName(){
        return "Счет 1С";
    }


    private function make($ownorg, $org,$order,$orditems){
        $res=$this->make_document($ownorg,$org,$order,$orditems);
        if($res->err>0){
            return $res;
        }
        $dom = new \DOMDocument('1.0','UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->encoding="UTF-8";

       $dom->loadXML($res->obj);
        $res->obj=$dom->saveXML();
        return $res;
    }
    private function make_document($ownorg,$org,$order,$orditems){
        $res=new Result;
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Документы/>');
        $invoice=$xml->addChild('СчетНаОплатуПокупателю');
        //Номер="0000-000002" Дата="14.11.2018 14:46:45"
        //Склад="Основной склад" Контрагент="Никитина Елена Геннадьевна"
        //КонтрагентИНН="" КонтрагентКПП="" ДоговорКонтрагента=""
        $invoice->addAttribute('Организация',$org->name);
        $invoice->addAttribute('Номер',$order->id);
        $invoice->addAttribute('Дата',now()->format('d.m.Y h:i:s'));
        $invoice->addAttribute('Контрагент',$ownorg->name);
        $invoice->addAttribute('КонтрагентИНН',$ownorg->inn);
        $invoice->addAttribute('КонтрагентКПП',$ownorg->kpp);
        $partTable=$invoice->addChild('ТабличнаяЧасть');
        $listitems=$partTable->addChild('Товары');
        //<СтрокаТовары Номенклатура="Тур"
        //КодНоменклатуры="00-00000061"
        //Содержание="Тур ао Вьетнам, 28.11-09.12.2018"
        //Количество="0" Цена="185 718"
        //Сумма="185 718" ПроцентСкидки="0"
        //СуммаСкидки="0" СтавкаНДС="Без НДС" СуммаНДС="0"/>
        foreach ($orditems as $itm){
            $sl=$listitems->addChild("СтрокаТовары");
            $refitem=$itm->refitem()->first();
            $sl->addAttribute('Номенклатура', $refitem->name);
            $sl->addAttribute('КодНоменклатуры' , $refitem->code1s);
            $qty=round($itm->qty)==$itm->qty? number_format($itm->qty,0,'.',''):number_format($itm->qty,0,'.','');
            $sl->addAttribute('Количество',$qty);
            $sl->addAttribute('Цена',number_format($itm->price,2,'.',''));
            $sl->addAttribute('Сумма',number_format($itm->totalsum,2,'.',''));
            $dscnt=0;
            if(!is_null($itm->base_price)) $dscnt=($itm->base_price-$itm->price)*$itm->qty;
            if ($dscnt!=0) $dscnt=number_format($dscnt,2,'.','');
            $sl->addAttribute('СуммаСкидки',$dscnt);
        }
        $res->obj=$xml->asXML();
        return $res;
    }

}

?>
