<?php

use App\Models\User;

function getDaysArray()
{
    $daysValues = array_values(\Illuminate\Support\Carbon::getDays());
//    dd($daysValues);
    $daysKeys = array_map(function ($element) {
        return \Illuminate\Support\Str::lower($element);
    }, $daysValues);

    dd($daysKeys);
    $daysValuesTranslated = array_map(function ($element) {
        return translateDay($element);
    }, $daysKeys);

    return array_combine($daysKeys, $daysValuesTranslated);
}

function translateDay(string $element): string
{
    return \Illuminate\Support\Str::title(\Illuminate\Support\Carbon::createFromIsoFormat('dddd', $element)->isoFormat('dddd'));
}

function exportFilePrefix($name)
{
    return env('APP_NAME') . " " . $name;

}

function adminUser()
{
    $user = User::whereHas('role', function ($q) {
        $q->where('name', "Admin");
    })->first();
    return $user;
//    dd($user);
}

function updateUserInFirebase($user_id = null)
{

    $database = app('firebase.database');
    $user = \request()->user();
    if ($user_id != null) {
        $user = User::find($user_id);
//        dd($user);
    }
    if (empty($user))
        return 0;
    $storeData['token'] = "";
    $storeData['name'] = $user->name;
    $storeData['profile'] = $user->profile;

    $database->getReference('users/' . $user->user_id)->update($storeData);
}
