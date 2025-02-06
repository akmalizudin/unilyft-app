<?php

namespace App\Http\Controllers;

use App\Models\Carpool;
use App\Models\User;
use Illuminate\Http\Request;

class CarpoolPassengerController extends Controller
{
    public function index()
    {
        $carpoolPassengers = Carpool::with('passengers')->get();
        return Response([
            'data' => $carpoolPassengers
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'carpool_id' => 'required',
            'user_id' => 'required',
        ]);

        Carpool::find($request->input('carpool_id'))->passengers()->attach($request->input('user_id'));

        return response()->json(['message' => 'Passenger added to carpool successfully']);
    }

    public function show($id)
    {
        $carpoolPassenger = Carpool::find($id)->passengers;
        return view('carpool_passengers.show', compact('carpoolPassenger'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'carpool_id' => 'required',
            'user_id' => 'required',
        ]);

        Carpool::find($id)->passengers()->detach();
        Carpool::find($request->input('carpool_id'))->passengers()->attach($request->input('user_id'));

        return response()->json(['message' => 'Passenger updated successfully']);
    }

    public function destroy($id)
    {
        Carpool::find($id)->passengers()->detach();
        return redirect()->route('carpool_passengers.index');
    }
}
