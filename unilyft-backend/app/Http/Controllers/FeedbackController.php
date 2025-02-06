<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedback = Feedback::with('author', 'carpool')->get(); // return feedback with author and carpool information
        return response()->json($feedback);
    }

    public function show($id)
    {
        $feedback = Feedback::find($id);
        return response()->json($feedback);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'carpoolId' => 'required|exists:carpools,id',
            'authorId' => 'required|exists:users,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string',
        ]);

        Feedback::create([
            'carpoolId' => $validated['carpoolId'],
            'authorId' => $validated['authorId'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            // 'date' => Carbon::now()->toDateString(),
        ]);

        return response()->json(['message' => 'Feedback saved successfully']);
    }

    public function update(Request $request, $id)
    {
        $feedback = Feedback::find($id);

        $validated = $request->validate([
            'carpoolId' => 'sometimes|required',
            'authorId' => 'sometimes|required',
            'rating' => 'sometimes|required|integer|between:1,5',
            'comment' => 'sometimes|required|string',
            'date' => 'sometimes|required',
        ]);
        $feedback->update($validated);
    }

    public function destroy($id)
    {
        $feedback = Feedback::find($id);
        $feedback->delete();
    }

}
