<?php

namespace App\Imports;

use App\new_bot_ri_lim;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;

class newBotRiLimsImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        //если нет кол-ва - выходим
        if (!isset($row[4])) {
            return null;
        }
        if (!is_numeric(str_replace(',', '.', $row[4]))) {
            return null;
        }

        //заголовок
        if ($row[0] == '1' and $row[1] == '2' and $row[2] == '3' and $row[3] == '4') {
            return null;
        }

        //если название - "левое" - выходим
        if ($row[2] == '...') {
            return null;
        }


        $tsum = $row[12];
        $sum = null;
        if (isset($tsum)) {
            //Log::info("sum_cell {$tsum}  for ".trim($row[2]));
            $elms = explode(chr(10), $tsum);
            $sum = str_replace(',', '.', $elms[0]);
            if (is_numeric($sum)) {
                //$sum = $sum * 1.2;  //добавим НДС
            }
        }

        $qty = round(str_replace(',', '.', $row[4]), 6);

        if (1 == 0) {
            //        $tprice = $row[5];
            $tprice = $row[9];
            $smet_price_calc = null;
            $price = null;
            if (isset($tprice)) {
                //Log::info("price_cell {$tprice}");
                $elms = explode(chr(10), $tprice);
                $price = str_replace(',', '.', $elms[0]);
                if (is_numeric($price)) {
                    $price = $price * 1.2;  //добавим НДС
                    $smet_price_calc = $elms[1] ?? '';
                    $smet_price_calc = str_replace(',', '.', $smet_price_calc);
                    //$smet_price_calc = $smet_price_calc;
                }
            }
            if (!isset($price))
                return null;
        } else {

            $tprice = $row[9];
            $smet_price_calc = null;
            $price = null;
            if (isset($tprice)) {
                //Log::info("price_cell {$tprice}");
                $elms = explode(chr(10), $tprice);
                $price = str_replace(',', '.', $elms[0]);
                if (is_numeric($price)) {
                    //$price = $price * 1.2;  //добавим НДС
                    $smet_price_calc = $elms[1] ?? '';
                    $smet_price_calc = str_replace(',', '.', $smet_price_calc);
                    //$smet_price_calc = $smet_price_calc;
                }
            }
            if ($qty > 0)
                $price = round($sum / $qty, 4);
            else
                Log::info("imported item with 0 qty:" . trim($row[2]));
        }

        $name = trim($row[2]);
        $name = str_replace(chr(10), ' ', $name);
        $name = preg_replace('/[\x00-\x1F\x7F\x0D]/u', ' ', $name);
        $name = preg_replace('/\s\s+/', ' ', $name);
        $name = str_replace('...', '', $name);
        $name = substr($name, 0, 300);

        return new new_bot_ri_lim([
            'code' => $row[1],
            'name' => $name,
            'unit' => $row[3] ?? 'шт',
            'qty' => $qty,
            'smet_price_calc' => $smet_price_calc,
            'smet_price' => $price,
            'smet_sum' => $sum,
        ]);
    }
}
