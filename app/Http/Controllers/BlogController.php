<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BM\consts\Response;
use App\Blogtest;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    //

    public function blogtest(Request $request){
        DB::beginTransaction();
        try {
            $data = $request->only(
                'age',
                'name',
                'avatar',
                'gender',
                'description'

            );

            Blogtest::create($data);
            DB::commit();
            return '保存成功';
        } catch (\Exception $e) {
            DB::rollBack();

            return  $e->getMessage();
        }








    }

    public function show(Request $request, $id)
    {
        $value = $request->session()->get('key');

        //
    }
}
