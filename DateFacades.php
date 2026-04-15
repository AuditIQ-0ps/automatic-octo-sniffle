<?php
namespace App\Facades;
use Illuminate\Support\Facades\Facade;

class DateFacades extends Facade{
    protected static function getFacadeAccessor() { 
		return 'DateHelper'; 
	}
}



