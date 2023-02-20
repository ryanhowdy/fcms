<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;

class AddressBookController extends Controller
{
    /**
     * Show the addressbook main page
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $addresses = Address::join('users as u', 'addresses.user_id', '=', 'u.id')
            ->select('addresses.*', 'u.name')
            ->orderBy('u.name')
            ->get();

        $grouped = [];

        $prevLetter = '';

        foreach ($addresses as $i => $address)
        {
            $letter = substr($address->name, 0, 1);

            if ($prevLetter !== $letter)
            {
                $prevLetter = $letter;

                $grouped[$prevLetter][] = $address;
            }
        }

        return view('addressbook.index', ['addresses' => $grouped]);
    }

    /**
     * Show the calendar main page
     *
     * @return json
     */
    public function show(int $id)
    {
        $address = Address::findOrFail($id)->toArray();

        if (!is_null($address['address']) || !is_null($address['city']) || !is_null($address['state']))
        {
            $address['hasAddress'] = 1;
        }

        if (!is_null($address['address']) && !is_null($address['city']) && !is_null($address['state']))
        {
            $address['fullAddress'] = $address['address'].', '.$address['city'].' '.$address['state'];

            $address['map'] = str_replace(' ', '+', $address['fullAddress']);
        }

        return response()->json($address);
    }
}
