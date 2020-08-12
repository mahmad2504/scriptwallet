<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Redirect,Response, Artisan;
class zaahmad extends Controller
{
 	public function UpdatEpics()
    {
		Artisan::queue('zaahmad:updatepics', []);
	}
}
