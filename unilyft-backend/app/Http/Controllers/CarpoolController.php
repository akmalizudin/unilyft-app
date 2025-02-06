<?php

namespace App\Http\Controllers;

use App\Models\Carpool;
use App\Models\Feedback;
use App\Models\Notification;
use App\Models\RideOffer;
use App\Models\RideRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Log;

class CarpoolController extends Controller
{
    public function index()
    {
        $carpoolOffers = Carpool::whereNotNull('driver_id')->get();
        $carpoolRequests = Carpool::whereNull('driver_id')->get();

        return response()->json([
            'offers' => $carpoolOffers,
            'requests' => $carpoolRequests,
        ]);
    }

    // public function getOffers(Request $request)
    // {
    //     $userId = $request->input('user_id');
    //     $sortBy = $request->input('sort_by', 'date');

    //     // Update expired rides to "Completed"
    //     Carpool::where('status', '!=', 'Completed')
    //         ->where('date', '<', Carbon::now()->subDay()->toDateString())
    //         ->update(['status' => 'Completed']);

    //     $carpoolOffers = Carpool::whereNotNull('driver_id')
    //         ->where('status', '!=', 'Completed')
    //         ->whereDoesntHave('users', function ($query) use ($userId) {
    //             $query->where('user_id', $userId);
    //         })
    //         ->with('driver')
    //         ->orderBy($sortBy, 'desc') // Add this line to sort by date in descending order
    //         ->get();

    //     return response()->json(['data' => $carpoolOffers]);
    // }

    public function getOffers(Request $request)
    {
        $userId = $request->input('user_id');
        $sortBy = $request->input('sort_by', 'date');

        // Update expired rides to "Completed"
        Carpool::where('status', '!=', 'Completed')
            ->where('date', '<', Carbon::now()->subDay()->toDateString())
            ->update(['status' => 'Completed']);

        try {
            $carpoolOffers = Carpool::whereNotNull('driver_id')
                ->where('status', '!=', 'Completed')
                ->where('date', '>=', Carbon::now()->toDateString())
                ->whereDoesntHave('users', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->with('driver')
                ->when($sortBy == 'gender', function ($query) {
                    $query->whereHas('driver', function ($query) {
                        $query->where('gender', 'female');
                    });
                })
                ->when($sortBy != 'gender', function ($query) use ($sortBy) {
                    $query->orderBy($sortBy, 'asc');
                }, function ($query) {
                    $query->orderBy('date', 'asc');
                })
                ->get();

            return response()->json(['data' => $carpoolOffers]);
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error fetching carpool offers: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching carpool offers.'], 500);
        }
    }

    // public function getRequests(Request $request)
    // {
    //     $userId = $request->input('user_id');
    //     $sortBy = $request->input('sort_by', 'date');

    //     // Update expired rides to "Completed"
    //     Carpool::where('status', '!=', 'Completed')
    //         ->where('date', '<', Carbon::now()->subDay()->toDateString())
    //         ->update(['status' => 'Completed']);

    //     $carpoolRequests = Carpool::whereNull('driver_id')
    //         ->where('status', '!=', 'Completed')
    //         ->where('requestor_id', '!=', $userId) // Add this line
    //         ->whereDoesntHave('users', function ($query) use ($userId) {
    //             $query->where('user_id', $userId);
    //         })
    //         ->with('requestor')
    //         ->orderBy($sortBy, 'desc') // Add this line to sort by date in descending order
    //         ->get();

    //     return response()->json(['data' => $carpoolRequests]);
    // }

    public function getRequests(Request $request)
    {
        $userId = $request->input('user_id');
        $sortBy = $request->input('sort_by', 'date');

        // Update expired rides to "Completed"
        Carpool::where('status', '!=', 'Completed')
            ->where('date', '<', Carbon::now()->subDay()->toDateString())
            ->update(['status' => 'Completed']);

        try {
            $carpoolRequests = Carpool::whereNull('driver_id')
                ->where('status', '!=', 'Completed')
                ->where('requestor_id', '!=', $userId)
                ->whereDoesntHave('users', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->with('requestor')
                ->when($sortBy == 'gender', function ($query) {
                    $query->whereHas('requestor', function ($query) {
                        $query->where('gender', 'female');
                    });
                })
                ->when($sortBy != 'gender', function ($query) use ($sortBy) {
                    $query->orderBy($sortBy, 'asc');
                }, function ($query) {
                    $query->orderBy('date', 'asc');
                })
                ->get();

            return response()->json(['data' => $carpoolRequests]);
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error fetching carpool requests: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching carpool requests.'], 500);
        }
    }


    // used to rate the ride requests
    public function showRequested($id)
    {
        $carpool = Carpool::find($id);
        $driverId = $carpool->users()->where('carpool_user.role', 'driver')->first()->id;
        $carpool->provider = User::find($driverId);
        $hasFeedback = Feedback::where('carpoolId', $id)->exists();
        $carpool->has_feedback = $hasFeedback;

        return response()->json($carpool);
    }


    // with feedback
    public function showOffer($id)
    {
        $carpool = Carpool::whereNotNull('driver_id')
            ->with(['driver', 'feedback'])
            ->find($id);

        if (!$carpool) {
            return response()->json(['message' => 'Carpool offer not found'], 404);
        }

        $hasFeedback = $carpool->feedback->isNotEmpty();
        $carpool->has_feedback = $hasFeedback;

        return response()->json($carpool);
    }

    public function showRequest($id)
    {
        $carpool = Carpool::whereNull('driver_id')->with('requestor')->find($id);
        if (!$carpool) {
            return response()->json(['message' => 'Carpool request not found'], 404);
        }

        // Check if the ride has been provided by a driver
        $provider = $carpool->users()->wherePivot('carpool_user.role', 'driver')->first();

        $response = [
            'carpool' => $carpool,
            'provider' => $provider
        ];

        return response()->json($response);
    }

    // a general show
    public function showCarpool($id)
    {
        $carpool = Carpool::with(['driver', 'requestor', 'feedback.author'])->find($id);

        if (!$carpool) {
            return response()->json(['message' => 'Carpool not found'], 404);
        }

        $hasFeedback = $carpool->feedback->isNotEmpty();
        $carpool->has_feedback = $hasFeedback;

        // Check if the ride has been provided by a driver
        $provider = $carpool->users()->wherePivot('carpool_user.role', 'driver')->first();

        $response = [
            'carpool' => $carpool,
            'provider' => $provider
        ];

        return response()->json($response);
    }


    public function createOffer(Request $request)
    {
        // Validate the input data
        $validated = $request->validate([
            'driver_id' => 'required',
            'start_location' => 'required',
            'destination' => 'required',
            'date' => 'required',
            'time' => 'required',
            'description' => 'nullable',
            'available_seats' => 'required',
        ]);

        // Set default description if empty
        if (empty($validated['description'])) {
            $validated['description'] = "Hi I am looking for a carpooling partner that is going to the same direction as me. Feel free to join!";
        }

        // Create a new carpool offer instance
        $carpool = new Carpool();
        // $carpool->driver_id = auth()->user()->id;
        $carpool->driver_id = $validated['driver_id'];
        $carpool->requestor_id = null;
        $carpool->start_location = $validated['start_location'];
        $carpool->destination = $validated['destination'];
        $carpool->date = $validated['date'];
        $carpool->time = $validated['time'];
        $carpool->description = $validated['description'];
        $carpool->available_seats = $validated['available_seats'];
        $carpool->status = 'Active';

        // Save the carpool offer instance
        $carpool->save();

        // Attach the driver to the carpool as the driver
        $carpool->users()->attach($carpool->driver_id, ['role' => 'driver']);

        return response()->json(['message' => 'Carpool offer created successfully', 'carpool' => $carpool]);
    }

    public function createRequest(Request $request)
    {
        // Validate the input data
        $validated = $request->validate([
            'requestor_id' => 'required',
            'start_location' => 'required',
            'destination' => 'required',
            'date' => 'required',
            'time' => 'required',
            'description' => 'nullable',
            'number_of_passenger' => 'required',
        ]);

        // Set default description if empty
        if (empty($validated['description'])) {
            $validated['description'] = "Hi I am looking for anyone that is going to the same direction as me and is willing to provide the ride for me. Thank you in advance!";
        }

        // Create a new carpool request instance
        $carpool = new Carpool();
        $carpool->driver_id = null;
        $carpool->requestor_id = $validated['requestor_id'];
        $carpool->start_location = $validated['start_location'];
        $carpool->destination = $validated['destination'];
        $carpool->date = $validated['date'];
        $carpool->time = $validated['time'];
        $carpool->description = $validated['description'];
        $carpool->number_of_passenger = $validated['number_of_passenger'];
        $carpool->status = 'Active';

        // Save the carpool request instance
        $carpool->save();

        return response()->json(['message' => 'Carpool request created successfully', 'carpool' => $carpool]);
    }

    // to join ride
    public function joinRide(Request $request, $carpoolId)
    {
        // Find the carpool offer
        $carpool = Carpool::find($carpoolId);

        // Check if the carpool offer exists and has available seats
        if (!$carpool || $carpool->available_seats <= 0) {
            return response()->json(['message' => 'Carpool offer not found or no available seats'], 404);
        }

        // Get the user ID from the request parameter
        $user_id = $request->input('user_id');

        // Check if the user is the driver of the carpool offer
        if ($carpool->driver_id == $user_id) {
            return response()->json(['message' => 'You cannot join your own ride offer'], 400);
        }

        // Check if the user has already joined the ride
        if ($carpool->users()->where('user_id', $user_id)->exists()) {
            return response()->json(['message' => 'You have already joined this ride'], 400);
        }

        // Decrement the available seats
        $carpool->available_seats -= 1;
        $carpool->save();

        // Attach the user to the carpool
        $carpool->users()->attach($user_id);

        // Create a notification for the driver
        Notification::create([
            'user_id' => $carpool->driver_id,
            'message' => 'A new passenger has joined your carpool offer.',
            'carpool_id' => $carpool->id,
            'type' => 'carpool_offer_joined',
        ]);

        return response()->json(['message' => 'You have joined the ride successfully', 'carpool' => $carpool]);
    }

    // Function to request to join a carpool
    public function requestJoinRide(Request $request, $carpoolId)
    {
        // Find the carpool offer
        $carpool = Carpool::find($carpoolId);

        // Check if the carpool offer exists and has available seats
        if (!$carpool || $carpool->available_seats <= 0) {
            return response()->json(['message' => 'Carpool offer not found or no available seats'], 404);
        }

        // Get the user ID from the request parameter
        $user_id = $request->input('user_id');

        // Check if the user is the driver of the carpool offer
        if ($carpool->driver_id == $user_id) {
            return response()->json(['message' => 'You cannot join your own ride offer'], 400);
        }

        // Check if the user has already requested to join the ride
        if ($carpool->users()->where('user_id', $user_id)->where('status', 'pending')->exists()) {
            return response()->json(['message' => 'You have already requested to join this ride'], 400);
        }

        // Create a join request
        $carpool->users()->attach($user_id, ['status' => 'pending']);

        // Create a notification for the driver
        Notification::create([
            'user_id' => $carpool->driver_id,
            'message' => 'A new passenger has requested to join your carpool offer.',
            'carpool_id' => $carpool->id,
            'type' => 'carpool_offer_requested',
        ]);

        return response()->json(['message' => 'Join request sent successfully']);
    }

    // Function for the driver to accept or decline the join request
    public function respondJoinRequest(Request $request, $carpoolId, $userId)
    {
        // Find the carpool offer
        $carpool = Carpool::find($carpoolId);

        // Check if the carpool offer exists
        if (!$carpool) {
            return response()->json(['message' => 'Carpool offer not found'], 404);
        }

        // Check if the user making the request is the driver
        if ($carpool->driver_id != $request->user()->id) {
            return response()->json(['message' => 'Only the driver can respond to join requests'], 403);
        }

        // Check if the join request exists
        $joinRequest = $carpool->users()->where('user_id', $userId)->where('status', 'pending')->first();
        if (!$joinRequest) {
            return response()->json(['message' => 'Join request not found'], 404);
        }

        // Check if the driver accepted or declined the request
        if ($request->input('accept')) {
            // Update the join request status to accepted
            $carpool->users()->updateExistingPivot($userId, ['status' => 'accepted']);

            // Decrement the available seats
            $carpool->available_seats -= 1;
            $carpool->save();

            // Create a notification for the user
            Notification::create([
                'user_id' => $userId,
                'message' => 'Your join request has been accepted.',
                'carpool_id' => $carpool->id,
                'type' => 'carpool_offer_accepted',
            ]);

            return response()->json(['message' => 'Join request accepted']);
        } else {
            // Update the join request status to declined
            $carpool->users()->updateExistingPivot($userId, ['status' => 'declined']);

            // Create a notification for the user
            Notification::create([
                'user_id' => $userId,
                'message' => 'Your join request has been declined.',
                'carpool_id' => $carpool->id,
                'type' => 'carpool_offer_declined',
            ]);

            return response()->json(['message' => 'Join request declined']);
        }
    }

    public function provideRide(Request $request, $carpoolId)
    {
        // Find the carpool request
        $carpool = Carpool::find($carpoolId);

        // Check if the carpool request exists
        if (!$carpool) {
            return response()->json(['message' => 'Carpool request not found'], 404);
        }
        // Get the user ID from the request parameter
        $userId = $request->input('user_id');

        // Check if the user is the requestor of the carpool request
        if ($carpool->requestor_id == $userId) {
            return response()->json(['message' => 'You cannot provide your own ride request'], 400);
        }

        // Attach the current user as the driver to the carpool
        $carpool->users()->attach($userId, ['role' => 'driver']);

        // Attach the passenger to the carpool
        $carpool->users()->attach($carpool->requestor_id, ['role' => 'passenger']);

        // Update the status of the carpool to 'pending'
        $carpool->status = 'Pending';
        $carpool->save();

        // Create a notification for the driver
        Notification::create([
            'user_id' => $carpool->requestor_id,
            'message' => 'A user has provided a ride for your carpool request.',
            'carpool_id' => $carpool->id,
            'type' => 'carpool_request_provided',
        ]);

        return response()->json(['message' => 'Ride provided successfully']);
    }

    public function update(Request $request, $id)
    {
        $carpool = Carpool::find($id);

        // Check if the carpool exists
        if (!$carpool) {
            return response()->json(['message' => 'Carpool not found'], 404);
        }

        // Validate the request
        $validated = $request->validate([
            'status' => 'required|in:Completed',
            'triggered_by_id' => 'required',
        ]);

        // Update the carpool status
        $carpool->update(['status' => $validated['status']]);

        // Notify based on carpool type
        if ($carpool->driver_id && !$carpool->requestor_id) {
            // Notify all passengers
            $passengers = $carpool->users()->wherePivot('carpool_user.role', 'passenger')->get();
            foreach ($passengers as $passenger) {
                Notification::create([
                    'user_id' => $passenger->id,
                    'message' => 'Your joined carpool ride to ' . $carpool->destination . ' has been set to completed.',
                    'carpool_id' => $carpool->id,
                    'type' => 'carpool_completed',
                    'triggered_by_id' => $validated['triggered_by_id'],
                ]);
            }
        } elseif (!$carpool->driver_id && $carpool->requestor_id) {
            // Notify the requestor
            Notification::create([
                'user_id' => $carpool->requestor_id,
                'message' => 'Your carpool ride request to ' . $carpool->destination . ' has been set to completed.',
                'carpool_id' => $carpool->id,
                'type' => 'carpool_completed',
                'triggered_by_id' => $validated['triggered_by_id'],
            ]);
        }

        return response()->json(['message' => 'Carpool status updated successfully', 'carpool' => $carpool]);
    }

    // new seperated
    public function getUserJoinedRides(Request $request)
    {
        $userId = $request->input('user_id');
        \DB::enableQueryLog(); // Enable query log

        // Check if the user is associated with any carpools
        $userCarpools = \DB::table('carpool_user')
            ->where('user_id', $userId)
            ->where('role', 'passenger')
            // ->where('status', 'accepted')
            ->get();

        Log::info('User Carpools: ' . $userCarpools);

        // Check if the carpools exist
        $carpools = Carpool::where('requestor_id', '!=', $userId)->get();
        Log::info('Carpools: ' . $carpools);

        // Add the requestor_id condition back to the query and handle null values
        $joinedRides = Carpool::whereHas('users', function ($query) use ($userId) {
            $query->where('carpool_user.user_id', $userId)
                ->where('carpool_user.role', 'passenger');
            // ->where('carpool_user.status', 'accepted');
        })
            ->where(function ($query) use ($userId) {
                $query->where('requestor_id', '!=', $userId)
                    ->orWhereNull('requestor_id');
            })
            ->with([
                'driver',
                'feedback' => function ($query) {
                    $query->select('id', 'carpoolId', 'authorId', 'rating', 'comment');
                },
                'users' => function ($query) use ($userId) {
                    $query->where('carpool_user.user_id', $userId)
                        ->select('users.*', 'carpool_user.status as request_status');
                }
            ])
            ->orderBy('date', 'asc')
            ->get();

        // Check if the query returns any results
        if ($joinedRides->isEmpty()) {
            Log::warning('No joined rides found for user_id: ' . $userId);
        }

        // Convert the collection to an array to ensure proper serialization
        $joinedRidesArray = $joinedRides->toArray();

        return response()->json($joinedRidesArray);
    }

    public function getUserPublishedRides(Request $request)
    {
        $userId = $request->input('user_id');

        $publishedRides = Carpool::where('driver_id', $userId)
            ->orWhere('requestor_id', $userId)
            ->with('feedback.author')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($carpool) {
                if ($carpool->status === 'Active' && !is_null($carpool->driver_id)) {
                    $carpool->passengers_count = $carpool->users()
                        ->where('carpool_user.role', 'passenger')
                        ->where('carpool_user.status', 'accepted')
                        ->count();
                }
                return $carpool;
            });

        return response()->json($publishedRides);
    }

    public function getUserProvidedRides(Request $request)
    {
        $userId = $request->input('user_id');

        $providedRides = Carpool::whereHas('users', function ($query) use ($userId) {
            $query->where('carpool_user.user_id', $userId)
                ->where('carpool_user.role', 'driver')
                ->whereNotIn('carpool_id', Carpool::where('driver_id', $userId)->pluck('id')->toArray());
        })
            ->with('requestor')
            ->with('feedback.author')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($providedRides);
    }

    public function rateCarpool(Request $request, $carpoolId)
    {
        // Validate the request
        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string',
            'author_id' => 'required|integer',
        ]);

        // Get the user ID from the request
        $authorId = $validated['author_id'];

        // Check if the user has already joined the carpool
        $carpool = Carpool::find($carpoolId);
        if (!$carpool->users()->where('user_id', $authorId)->exists()) {
            return response()->json(['message' => 'You have not joined this carpool'], 400);
        }

        // Check if the user has already rated the carpool
        $existingFeedback = Feedback::where('carpoolId', $carpoolId)->where('authorId', $authorId)->first();
        if ($existingFeedback) {
            return response()->json(['message' => 'You have already rated this carpool'], 400);
        }

        // Create a new feedback instance
        Feedback::create([
            'carpoolId' => $carpoolId,
            'authorId' => $authorId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'date' => Carbon::now()->toDateString(),
        ]);

        // Determine the driver and create a notification
        if ($carpool->driver_id) {
            // Carpool offer scenario
            $driverId = $carpool->driver_id;
        } else {
            // Carpool request scenario
            $driver = $carpool->users()->wherePivot('role', 'driver')->first();
            if ($driver) {
                $driverId = $driver->id;
            } else {
                return response()->json(['message' => 'Invalid carpool data'], 400);
            }
        }

        // Create a notification for the driver
        Notification::create([
            'user_id' => $driverId,
            'message' => 'A passenger has rated your carpool with a rating of ' . $validated['rating'] . ' stars.',
            'carpool_id' => $carpool->id,
            'type' => 'carpool_rated',
        ]);

        // Update the driver's rating
        $this->updateDriverRating($driverId);

        return response()->json(['message' => 'Feedback saved successfully']);
    }

    public function hasRated(Request $request)
    {
        $carpoolId = $request->query('carpoolId');
        $authorId = $request->query('authorId');

        $existingFeedback = Feedback::where('carpoolId', $carpoolId)
            ->where('authorId', $authorId)
            ->first();
        // change to ->first(); if want to see the value

        // return response()->json(['has_rated' => $existingFeedback]);
        return response()->json($existingFeedback);
    }

    public function getDriverFeedback($driverId)
    {
        $feedbacks = Feedback::whereHas('carpool', function ($query) use ($driverId) {
            $query->where('driver_id', $driverId);
        })->with('author')->get();

        return response()->json($feedbacks);
    }

    public function updateDriverRating($driverId)
    {
        // Get all feedback for the driver's carpools
        $feedbacks = Feedback::whereHas('carpool', function ($query) use ($driverId) {
            $query->where('driver_id', $driverId)
                ->orWhereHas('users', function ($query) use ($driverId) {
                    $query->where('carpool_user.user_id', $driverId)->where('carpool_user.role', 'driver');
                });
        })->get();

        // Calculate the average rating
        $averageRating = $feedbacks->unique('carpoolId')->avg('rating');

        // Update the driver's rating
        $driver = User::find($driverId);
        $driver->rating = $averageRating;
        $driver->save();

        return response()->json(['message' => 'Driver rating updated successfully', 'rating' => $averageRating]);
    }

    public function getPassengerList($id)
    {
        $carpool = Carpool::find($id);

        if (!$carpool) {
            return response()->json(['message' => 'Carpool not found'], 404);
        }

        $passengers = $carpool->users()
            ->wherePivot('role', 'passenger')
            // ->wherePivot('status', 'accepted')
            ->withPivot('status')
            ->get();
        return response()->json(['passengers' => $passengers]);
    }

    public function removePassenger(Request $request, $carpoolId)
    {
        $carpool = Carpool::find($carpoolId);

        if (!$carpool) {
            return response()->json(['message' => 'Carpool not found'], 404);
        }

        $userId = $request->input('passengerId');

        // Check if the user is a passenger in the carpool
        $isPassenger = $carpool->users()->wherePivot('carpool_user.role', 'passenger')->wherePivot('user_id', $userId)->exists();

        if (!$isPassenger) {
            return response()->json(['message' => 'User is not a passenger in this carpool'], 400);
        }

        // Detach the user from the carpool
        $carpool->users()->detach($userId);

        // Create a notification for the user being removed
        Notification::create([
            'user_id' => $userId,
            'message' => 'You have been removed from the carpool: ' . $carpool->start_location . ' - ' . $carpool->destination,
            'carpool_id' => $carpool->id,
            'type' => 'carpool_removed',
        ]);

        return response()->json(['message' => 'Passenger removed successfully']);
    }

    public function getCarpoolStats()
    {
        // Get the total number of carpools
        $totalCarpools = Carpool::count();

        // Get the total number of active carpools (status not 'Completed')
        $activeCarpools = Carpool::where('status', '!=', 'Completed')->count();

        // Fetch all carpools with specific fields
        $carpools = Carpool::select('id', 'start_location', 'driver_id', 'destination', 'status', 'created_at', 'available_seats', 'number_of_passenger')->get();

        // Get the total number of carpools older than 7 days
        $totalCarpoolsOlderThanWeek = Carpool::where('created_at', '<', now()->subDays(7))->count();

        // Get the total number of active carpools older than 7 days
        $activeCarpoolsOlderThanWeek = Carpool::where('status', '!=', 'Completed')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        // Calculate the number of carpools created within the last 7 days
        $totalCarpoolsLastWeek = $totalCarpools - $totalCarpoolsOlderThanWeek;

        // Calculate the number of active carpools created within the last 7 days
        $activeCarpoolsLastWeek = $activeCarpools - $activeCarpoolsOlderThanWeek;

        // Log the values
        Log::info('Total Carpools: ' . $totalCarpools);
        Log::info('Active Carpools: ' . $activeCarpools);
        Log::info('Carpools: ' . $carpools);
        Log::info('Total Carpools Older Than Week: ' . $totalCarpoolsOlderThanWeek);
        Log::info('Active Carpools Older Than Week: ' . $activeCarpoolsOlderThanWeek);
        Log::info('Total Carpools Last Week: ' . $totalCarpoolsLastWeek);
        Log::info('Active Carpools Last Week: ' . $activeCarpoolsLastWeek);
        Log::info('Total Carpools Comparison: ' . $totalCarpoolsLastWeek);
        Log::info('Active Carpools Comparison: ' . $activeCarpoolsLastWeek);

        return response()->json([
            'total_carpools' => $totalCarpools,
            'active_carpools' => $activeCarpools,
            'carpools' => $carpools,
            'total_carpools_comparison' => $totalCarpoolsLastWeek,
            'active_carpools_comparison' => $activeCarpoolsLastWeek,
        ]);
    }

    public function destroy($id)
    {
        $carpool = Carpool::find($id);
        if ($carpool) {
            // Delete related feedback entries
            Feedback::where('carpoolId', $id)->delete();
            // Delete the carpool
            $carpool->delete();
        }
        return response()->json(['message' => 'Carpool deleted successfully']);
    }
}
