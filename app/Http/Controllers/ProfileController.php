<?php

namespace App\Http\Controllers;

use App\objpref;
use App\preftype;
use App\usrsysright;
use Auth;
use Hash;
use App\User;
use App\objlog;

use Illuminate\Http\Request;

use App\Traits\UploadFileTrait;
use App\Traits\DeleteFileTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use UploadFileTrait;
    use DeleteFileTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
//        $showLink2User = User::isWorkInOwnOrg(auth()->user()->id);
        $showLink2User = usrsysright::isUserHasRightByCode(auth()->user()->id, 'users.read');

        $userid = auth()->user()->id;

        $rec = User::find($userid);
        $rec->prefs = preftype::from('preftypes as t')
            ->leftJoin('objprefs as p', function ($j) use ($userid) {
                $j->on('p.preftypeid', 't.id')
                    ->where('p.objid', $userid);
            })
            ->wherein('t.id', [21, 34])//todo: в PrefTypes Добавить поле SysObjID и отбирать по нему
            ->select('t.id as preftypeid', 't.name as prefname', 't.valsrctype', 't.valsrcdef', 'p.prefvalue')
            ->get();
        foreach ($rec->prefs as $pref) {
            $arr = [];
            if ($pref->valsrctype == 'LST') {
                $t = explode(';', $pref->valsrcdef);
                $elm = null;
                foreach ($t as $v) {
                    $e = explode('|', $v);
                    $elm = [$e[0] => $e[1]];
                    $arr += $elm;
                }
            }
            $pref->vals = $arr;
        }

        $rec->log = objlog::where('sysobjid', 3)
            ->where('objid', $userid)
            ->select('write_at', 'info', 'errlvl')
            ->orderBy('write_at', 'DESC')->limit(12)->get()
            ->toArray();
        return view('auth.profile', compact('rec', 'showLink2User'));
    }

    public function updateProfile(Request $request)
    {
        // Form validation
        $request->validate([
            'lname' => 'required',
            'fname' => 'required',
            'profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:1024'
        ]);

        // Get current user
        $userid = auth()->user()->id;
        $user = User::findOrFail($userid);
        // Set user name

        $user->lname = $request->input('lname');
        $user->fname = $request->input('fname');
        $user->mname = $request->input('mname');
        $user->name = $user->lname . ' ' . $user->fname . ' ' . $user->mname;


        // Check if a profile image has been uploaded
        if ($request->has('profile_image')) {


            if (isset($user->profile_image)) {
                //Удалим предыдущее фото
                $folder = substr($user->profile_image, 0, strrpos($user->profile_image, "/") + 1);
                $filename = substr(strrchr($user->profile_image, "/"), 1);
                $this->deleteOne($folder, 'public', $filename);
                //def: deleteOne($folder = null, $disk = 'public', $filename = null)
                //dd($folder,  $filename);
            }

            // Папка для хранения фото с аватарами пользователей
            //$folder = '/uploads/avatars/';
            $folder = '/uploads/avatars';

            // Get image file
            $image = $request->file('profile_image');
            // Make a image name based on user name and current timestamp
            $name = Str::slug($request->input('name')) . '_' . time();
            // Make a file path where image will be stored [ folder path + file name + file extension]
            // Upload image
            $file = $this->uploadOne($image, $folder, 'public', $name . '.' . $image->getClientOriginalExtension());
            // Set user profile image path in database to filePath
            $user->profile_image = Storage::url($file);
        }


        // Persist user record to database
        $user->save();

        //обработаем преференции -----------------------------------------------------------
        $prefs = $request->input('pref');
        //$prefs = $request->input('pref[21]');
        foreach ($prefs as $key => $val) {
            objpref::setOrClrPrefVal(3, $userid, $key, $val);
        }
        //----------------------------------------------------------------------------------


        objlog::log_info(3, $user->id, 'Пользователь изменил данные своего профиля', 4);

        // Return user back and show a flash message
        return redirect()->back()->with(['status' => 'Профиль обновлен успешно!']);
    }

    public function showChangePasswordForm()
    {
        return view('auth.changepassword');
    }

    public function changePassword(Request $request)
    {
        if (!(Hash::check($request->get('current-password'), Auth::user()->password))) {
            // The passwords matches
            return redirect()->back()->with("error", "Ваш действующий пароль не соответствует паролю который вы указали сейчас. Пожалуйста, попробуйте снова.");
        }
        if (strcmp($request->get('current-password'), $request->get('new-password')) == 0) {
            //Current password and new password are same
            return redirect()->back()->with("error", "Новый пароль не может совпадать с действующим паролем. Пожалуйста, придумайте другой пароль.");
        }
        $validatedData = $request->validate([
            'current-password' => 'required',
            'new-password' => 'required|string|min:6|confirmed',
        ]);
        //Change Password
        $user = Auth::user();
        $user->password = bcrypt($request->get('new-password'));
        $user->save();

        objlog::log_info(3, $user->id, 'Пользователь изменил свой пароль', 4);

        return redirect()->back()->with("success", "Пароль успешно изменен!");
    }

}