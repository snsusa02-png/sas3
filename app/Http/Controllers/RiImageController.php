<?php

namespace App\Http\Controllers;

use App\refitem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Str;

use App\ri_image;

use App\Traits\UploadFileTrait;

//use App\Traits\DeleteFileTrait;


class RiImageController extends Controller
{
    use UploadFileTrait;

    //  use DeleteFileTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($refitmid)
    {
        $refitem = refitem::find($refitmid);
        if ($refitem)
            return view('ri_images.load', compact(['refitem']));
        else
            return back();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store0(Request $request)
    {
        $this->validate($request, [

            'refitmid' => 'required',
            'filename' => 'required',
            'filename.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'

        ]);

        if ($request->hasfile('filename')) {

            $refitmid = $request->refitmid;
            $folder = 'images/product/' . $refitmid . '/';

            $refitem = refitem::find($refitmid);
            $product_slug = Str::slug($refitem->name);

            //определить начальный порядок изображения для $refitmid
            $ordr = ri_image::where('refitmid', $refitmid)->max('ordr');
            if (!isset($ordr)) $ordr = 0;

            foreach ($request->file('filename') as $image) {

                // Get image file

                // Make a image name based on user name and current timestamp
                //$name = str_slug($request->input('name')).'_'.time();

                //имя файла без расширения
                // - либо по оригинальному имени файла
                $name = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                // - либо имя файла делаем производным от названия продукта
                //$name = $product_slug;

                // - либо комбинируем
                //$name = $product_slug . '_' . str_slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));

                $extension = $image->getClientOriginalExtension();
                $fullname = $name . '.' . $extension;

                // Make a file path where image will be stored [ folder path + file name + file extension]
                $filePath = $folder . $fullname;
//dd(mb_strlen($filePath));
                $fileuri = Storage::disk('public')->getAdapter()
                    ->applyPathPrefix($filePath);
                if (file_exists($fileuri)) {
                    $name = $name . '_' . time();
                    $fullname = $name . '.' . $extension;
                    $filePath = $folder . $fullname;
                }

                // Upload image
                $this->uploadOne($image, $folder, 'public', $fullname);

                $fileuri = Storage::disk('public')->getAdapter()
                    ->applyPathPrefix($filePath);
                if (file_exists($fileuri)) {
                    // Save to table
                    $rec = new ri_image();
                    $rec->refitmid = $refitmid;
                    $rec->filename = $filePath;
                    $rec->filesize = $image->getSize();
                    $rec->ordr = ++$ordr;
                    $rec->save();

                    //$ri = refitem::find($refitmid);

                    if ($ordr == 1 or !isset($refitem->photourl)) {
                        //=> только что привязали 1-е фото к продукту,
                        // или по какой-то причине уже было привязано фото,
                        // но не было сохранено в refitems.photourl
                        //то сохраним его в refitems.photourl
                        $refitem->photourl = $filePath;
                        $refitem->save();
                    }
                }
            }
            //dd($data);
        }

        return back()->with('success', 'Фото успешно загружены!');
    }

    public function store(Request $request)
    {
        $this->validate($request, [

            'refitmid' => 'required',
            'photos' => 'required',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'

        ]);

        if ($request->hasfile('photos')) {

            $refitmid = $request->refitmid;
            $folder = 'images/product/' . $refitmid . '/';

            $refitem = refitem::find($refitmid);
            $product_slug = Str::slug($refitem->name);

            //определить начальный порядок изображения для $refitmid
            $ordr = ri_image::where('refitmid', $refitmid)->max('ordr');
            if (!isset($ordr)) $ordr = 0;

            foreach ($request->photos as $photo) {

                //$filename = $photo->store('photos');

                // Get image file

                // Make a image name based on user name and current timestamp
                //$name = str_slug($request->input('name')).'_'.time();

                //имя файла без расширения
                // - либо по оригинальному имени файла
                $name = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);

                // - либо имя файла делаем производным от названия продукта
                //$name = $product_slug;

                // - либо комбинируем
                //$name = $product_slug . '_' . str_slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME));

                $extension = $photo->getClientOriginalExtension();
                $fullname = $name . '.' . $extension;

                // Make a file path where image will be stored [ folder path + file name + file extension]
                $filePath = $folder . $fullname;
//dd(mb_strlen($filePath));

                $fileuri = Storage::disk('public')->getAdapter()
                    ->applyPathPrefix($filePath);

                if (file_exists($fileuri)) {
                    $name = $name . '_' . time();
                    $fullname = $name . '.' . $extension;
                    $filePath = $folder . $fullname;
                }

                // Upload image
                $this->uploadOne($photo, $folder, 'public', $fullname);

                $fileuri = Storage::disk('public')->getAdapter()
                    ->applyPathPrefix($filePath);

                if (file_exists($fileuri)) {
                    // Save to table
                    $rec = new ri_image();
                    $rec->refitmid = $refitmid;
                    $rec->filename = $filePath;
                    $rec->filesize = $photo->getSize();
                    $rec->ordr = ++$ordr;
                    $rec->save();

                    if (($ordr == 1 or !isset($refitem->photourl))) {
                        //=> только что привязали 1-е фото к продукту,
                        // или по какой-то причине уже было привязано фото,
                        // но не было сохранено в refitems.photourl
                        //то сохраним его в refitems.photourl
                        $refitem->photourl = 'storage/' . $filePath;
                        $refitem->save();
                    }
                }

            }
            //echo "Upload Successfully";
        }

        return back()->with('success', 'Фото успешно загружены!');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\ri_image $ri_image
     * @return \Illuminate\Http\Response
     */
    public function show(ri_image $ri_image)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\ri_image $ri_image
     * @return \Illuminate\Http\Response
     */
    public function edit(ri_image $ri_image)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\ri_image $ri_image
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ri_image $ri_image)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\ri_image $ri_image
     * @return \Illuminate\Http\Response
     */
//    public function destroy0($id)
//    {
//        $dd = ri_image::find($id);
//        if (isset($dd)) {
//            //Удалим файл с фото
//            $folder = substr($dd->filename, 0, strrpos($dd->filename, "/") + 1);
//            $filename = substr(strrchr($dd->filename, "/"), 1);
//            $this->deleteOne($folder, 'public', $filename);
//
//            $dd->delete();
//
//            //todo: переупорядочить оставшиеся фото с 1 до N...
//
////            return redirect(route('ri_image.upload'))->with('success', 'Файл с фото удален.');
//            return back()->with('success', 'Файл с фото удален.');
//        }
//        return back()->with('warning', 'Something wrong!');
//    }

    public function destroy($id)
    {//удаление файла с фото перенесено в модель

        $delcnt = ri_image::destroy($id);
        if ($delcnt) {
            //todo: переупорядочить оставшиеся фото с 1 до N...

            return back()->with('success', 'Файл с фото удален.');
        }
        return back()->with('warning', 'Something wrong!');
    }
}
