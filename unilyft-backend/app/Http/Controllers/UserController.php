<?php

namespace App\Http\Controllers;

use App\Models\Carpool;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Arr;
use Log;
use Symfony\Component\HttpFoundation\Response;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json(['user' => $users]);
    }

    public function show($id)
    {
        $user = User::find($id);
        return response()->json($user);

    }

    public function create()
    {
        return view('users.create');
    }

    public function viewUserDetail($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $vehiclePhoto = $user->getMedia('vehicle_photo')->first();
        $user->vehicle_photo = $vehiclePhoto ? [
            'uri' => $vehiclePhoto->getFullUrl(),
            'name' => $vehiclePhoto->file_name,
            'type' => $vehiclePhoto->mime_type,
        ] : null;

        // Hide the 'media' attribute
        $user->makeHidden('media');

        return response()->json(['user' => $user]);
    }

    public function store(Request $request)
    {
        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        // ...
        $user->save();
        return redirect()->route('users.index');
    }

    public function update(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::debug($request);

        DB::beginTransaction();
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'email' => ['sometimes', 'nullable', 'string', 'lowercase', 'email', 'max:255'],
            'phoneNumber' => ['sometimes', 'nullable', 'string', 'max:11'],
            'password' => ['sometimes', 'nullable', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'avatar' => ['nullable'],
        ]);

        $user->update(Arr::except($validated, ['avatar']));


        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('user_avatar');
            $file = $request->file('avatar');
            $user
                ->addMedia($file)
                ->toMediaCollection('user_avatar');
        }

        DB::commit();

        $user->save();

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    public function uploadDrivingLicense(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'driving_license' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg',],
            'vehicle_model' => ['required', 'nullable', 'string'],
            'vehicle_year' => ['required', 'nullable', 'integer'],
            'registration_number' => ['required', 'nullable', 'string'],
        ]);

        $user->clearMediaCollection('driving_license');

        $user->update([
            'vehicle_model' => $request->input('vehicle_model'),
            'vehicle_year' => $request->input('vehicle_year'),
            'registration_number' => $request->input('registration_number'),
        ]);
        // $user->update(Arr::except($validated, ['driving_license']));

        if ($request->hasFile('driving_license')) {
            $file = $request->file('driving_license');
            $user
                ->addMedia($file)
                ->toMediaCollection('driving_license');
        }

        $user->save();

        // Create a notification for the admins
        $admins = User::whereIn('id', [1, 2])->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'message' => 'User ' . $user->name . ' has uploaded their driving license.',
                'triggered_by_id' => $user->id,
                'type' => 'driving_license',
            ]);
        }

        return response()->json(['message' => 'Driving license uploaded successfully', 'user' => $user]);
    }

    public function getDrivingLicenses()
    {

        $users = User::select('id', 'name', 'isVerified')->whereHas('media', function ($query) {
            $query->where('collection_name', 'driving_license');
        })->get();

        $drivingLicenses = [];
        // $avatarMedia = [];

        foreach ($users as $user) {
            $media = $user->getMedia('driving_license')->first();
            $avatarMedia = $user->getMedia('user_avatar')->first();
            if ($media) {
                $avatar = [
                    'uri' => $avatarMedia ? $avatarMedia->getFullUrl() : config('app.default.image.uri'),
                    'name' => $avatarMedia ? $avatarMedia->file_name : config('app.default.image.name'),
                    'type' => $avatarMedia ? $avatarMedia->mime_type : config('app.default.image.type'),
                ];
                $drivingLicenses[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'isVerified' => $user->isVerified,
                    'avatar' => $avatar,
                    'driving_license' => [
                        'uri' => $media->getFullUrl(),
                        'name' => $media->file_name,
                        'type' => $media->mime_type,
                    ],
                ];
            }
        }

        return $drivingLicenses;
    }

    public function showDrivingLicense($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            // You can throw an exception or return a specific error message here
            return null;
        }

        $media = $user->getMedia('driving_license')->first();
        if (!$media) {
            return null;
        }

        $avatarMedia = $user->getMedia('user_avatar')->first();
        if (!$avatarMedia) {
            $avatar = [
                'uri' => config('app.default.image.uri'),
                'name' => config('app.default.image.name'),
                'type' => config('app.default.image.type'),
            ];
        } else {
            $avatar = [
                'uri' => $avatarMedia->getFullUrl(),
                'name' => $avatarMedia->file_name,
                'type' => $avatarMedia->mime_type,
            ];
        }

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'isVerified' => $user->isVerified,
            'vehicle' => [
                'model' => $user->vehicle_model,
                'year' => $user->vehicle_year,
                'registration_number' => $user->registration_number,
            ],
            'avatar' => $avatar,
            'driving_license' => [
                'uri' => $media->getFullUrl(),
                'name' => $media->file_name,
                'type' => $media->mime_type,
            ],
        ];
    }

    public function updateVehicleDetails(Request $request, $id)
    {

        DB::beginTransaction();
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'vehicle_model' => ['sometimes', 'nullable', 'string'],
            'vehicle_year' => ['sometimes', 'nullable', 'integer'],
            'registration_number' => ['sometimes', 'nullable', 'string'],
            'vehicle_photo' => ['nullable'],
        ]);

        // $user->fill($validated)->save();
        $user->update(Arr::except($validated, ['vehicle_photo']));

        if ($request->hasFile('vehicle_photo')) {
            $user->clearMediaCollection('vehicle_photo');
            $file = $request->file('vehicle_photo');
            $user
                ->addMedia($file)
                ->toMediaCollection('vehicle_photo');
        }

        DB::commit();
        $user->save();

        return response()->json(['message' => 'Vehicle details updated successfully', 'user' => $user]);
    }


    public function verify($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $currentUser = auth()->user();
        if (!$currentUser || $currentUser->role !== 'admin') {
            return response()->json(['message' => 'Only admins can verify users'], 403);
        }

        $user->isVerified = true;
        $user->save();

        $user->clearMediaCollection('vehicle_photo');

        Notification::create([
            'user_id' => $user->id,
            'message' => 'Your account has been verified. You may publish carpool offers now.',
            'carpool_id' => null,
            'type' => 'account_verified',
        ]);

        return response()->json(['message' => 'User verified successfully', 'user' => $user]);
    }

    public function unverify($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $currentUser = auth()->user();
        if (!$currentUser || $currentUser->role !== 'admin') {
            return response()->json(['message' => 'Only admins can unverify users'], 403);
        }

        $user->isVerified = false;
        $user->save();

        $user->clearMediaCollection('driving_license');
        
        if ($user->hasMedia('vehicle_photo')) {
            $user->clearMediaCollection('vehicle_photo');
        }


        Notification::create([
            'user_id' => $user->id,
            'message' => 'Your account has been unverified. You can no longer publish carpool offers. Please contact the admin for more information.',
            'carpool_id' => null,
            'type' => 'account_verified',
        ]);

        return response()->json(['message' => 'User unverified successfully', 'user' => $user]);
    }

    public function getUsersData()
    {
        // Get the number of registered users excluding admins
        $userCount = User::where('role', '!=', 'admin')->count();

        // Get the count of users with isVerified true excluding admins
        $verifiedUserCount = User::where('isVerified', true)->where('role', '!=', 'admin')->count();

        // Fetch all users with specific fields excluding admins
        $users = User::select('id', 'name', 'gender', 'isVerified', 'email_verified_at')
            ->where('role', '!=', 'admin')
            ->get();

        // Get the number of registered users older than 7 days excluding admins
        $userCountOlderThanWeek = User::where('created_at', '<', now()->subDays(7))
            ->where('role', '!=', 'admin')
            ->count();

        // Get the count of verified users older than 7 days excluding admins
        $verifiedUserCountOlderThanWeek = User::where('isVerified', true)
            ->where('created_at', '<', now()->subDays(7))
            ->where('role', '!=', 'admin')
            ->count();

        // Calculate the number of users who registered within the last 7 days
        $userCountLastWeek = $userCount - $userCountOlderThanWeek;

        // Calculate the number of verified users who registered within the last 7 days
        $verifiedUserCountLastWeek = $verifiedUserCount - $verifiedUserCountOlderThanWeek;

        // Log the values
        Log::info('User Count: ' . $userCount);
        Log::info('Verified User Count: ' . $verifiedUserCount);
        Log::info('Users: ' . $users);
        Log::info('User Count Older Than Week: ' . $userCountOlderThanWeek);
        Log::info('Verified User Count Older Than Week: ' . $verifiedUserCountOlderThanWeek);
        Log::info('User Count Last Week: ' . $userCountLastWeek);
        Log::info('Verified User Count Last Week: ' . $verifiedUserCountLastWeek);
        Log::info('User Count Comparison: ' . $userCountLastWeek);
        Log::info('Verified User Count Comparison: ' . $verifiedUserCountLastWeek);

        return response()->json([
            'user_count' => $userCount,
            'verified_user_count' => $verifiedUserCount,
            'users' => $users,
            'user_count_comparison' => $userCountLastWeek,
            'verified_user_count_comparison' => $verifiedUserCountLastWeek,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Delete all carpools where the user is the driver or the requestor
        Carpool::where('driver_id', $id)->orWhere('requestor_id', $id)->delete();

        // Delete the user
        $user->delete();

        return response()->json(['message' => 'User and their carpools deleted successfully']);
    }
}
