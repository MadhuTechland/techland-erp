<?php

namespace App\Http\Controllers;

use App\Models\TimeTracker;
use App\Models\TrackPhoto;
use App\Models\Utility;
use App\Models\Projects;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class TimeTrackerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (\Auth::user()->type == 'company') {
            $treckers = TimeTracker::where('created_by',\Auth::user()->creatorId())->get();
        } else {
            $treckers = TimeTracker::where('user_id',\Auth::user()->id)->where('created_by',\Auth::user()->creatorId())->get();
        }

       return view('time_trackers.index',compact('treckers'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TimeTracker  $timeTracker
     * @return \Illuminate\Http\Response
     */
    public function show(TimeTracker $timeTracker)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TimeTracker  $timeTracker
     * @return \Illuminate\Http\Response
     */
    public function edit(TimeTracker $timeTracker)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TimeTracker  $timeTracker
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TimeTracker $timeTracker)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TimeTracker  $timeTracker
     * @return \Illuminate\Http\Response
     */
    public function destroy($timetracker_id)
    {
        $timetrecker = TimeTracker::find($timetracker_id);
        $timetrecker->delete();

        return redirect()->back()->with('success', __('TimeTracker successfully deleted.'));

    }

    public function getTrackerImages(Request $request){

        $tracker = TimeTracker::where('id', $request->id)->where('created_by',\Auth::user()->creatorId())->first();

        if (!$tracker) {
            return redirect()->back()->with('error', __('Permission denied!'));
        }

        if (\Auth::user()->type == 'company') {
            $images = TrackPhoto::where('track_id',$request->id)->where('created_by',\Auth::user()->creatorId())->get();
        } else {
            $images = TrackPhoto::where('track_id',$request->id)->where('user_id',\Auth::user()->id)->where('created_by',\Auth::user()->creatorId())->get();
        }

        return view('time_trackers.images',compact('images','tracker'));
    }

    public function removeTrackerImages(Request $request){



        $images = TrackPhoto::find($request->id);
        if($images){
            $url= $images->img_path;
            if($images->delete()){
                \Storage::delete($url);
                return Utility::success_res(__('Tracker Photo remove successfully.'));
            }else{
                return Utility::error_res(__('opps something wren wrong.'));
            }
        }else{
            return Utility::error_res(__('opps something wren wrong.'));
        }

    }

    public function removeTracker(Request $request)
    {


        $track = TimeTracker::find($request->input('id'));
        if($track)
        {
            $track->delete();

            return Utility::success_res(__('Track remove successfully.'));
        }
        else
        {
            return Utility::error_res(__('Track not found.'));
        }
    }

    /**
     * Display all employee screenshots grouped by user and date
     */
    public function employeeScreenshots(Request $request)
    {
        // Only company users can view all employee screenshots
        if (\Auth::user()->type != 'company') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = \Auth::user()->creatorId();

        // Get date filter
        $selectedDate = $request->input('date', Carbon::today()->format('Y-m-d'));
        $selectedUserId = $request->input('user_id', '');

        // Get all users who have screenshots
        $usersWithPhotos = User::whereIn('id', function($query) use ($creatorId) {
            $query->select('user_id')
                ->from('track_photos')
                ->where('created_by', $creatorId)
                ->whereNotNull('user_id')
                ->distinct();
        })->get();

        // Build query for screenshots
        $photosQuery = TrackPhoto::where('created_by', $creatorId)
            ->whereDate('time', $selectedDate)
            ->with(['user', 'tracker']);

        if ($selectedUserId) {
            $photosQuery->where('user_id', $selectedUserId);
        }

        $photos = $photosQuery->orderBy('user_id')
            ->orderBy('time', 'desc')
            ->get();

        // Group photos by user
        $photosByUser = $photos->groupBy('user_id');

        // Get available dates that have screenshots
        $availableDates = TrackPhoto::where('created_by', $creatorId)
            ->selectRaw('DATE(time) as date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->limit(30)
            ->pluck('date');

        return view('time_trackers.employee_screenshots', compact(
            'photosByUser',
            'usersWithPhotos',
            'selectedDate',
            'selectedUserId',
            'availableDates'
        ));
    }

    /**
     * Get screenshots for a specific user on a specific date (AJAX)
     */
    public function getUserScreenshots(Request $request)
    {
        if (\Auth::user()->type != 'company') {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $userId = $request->input('user_id');
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        $photos = TrackPhoto::where('created_by', \Auth::user()->creatorId())
            ->where('user_id', $userId)
            ->whereDate('time', $date)
            ->with('tracker')
            ->orderBy('time', 'desc')
            ->get();

        return view('time_trackers.user_screenshots_partial', compact('photos'));
    }
}
