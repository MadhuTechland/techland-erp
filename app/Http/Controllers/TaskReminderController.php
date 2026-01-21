<?php

namespace App\Http\Controllers;

use App\Models\TaskReminderRecipient;
use App\Models\TaskReminderTemplate;
use App\Models\TaskReminderSchedule;
use App\Models\TaskReminderLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Models\ProjectTask;
use App\Models\TaskStage;
use App\Services\GoogleChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskReminderController extends Controller
{
    /**
     * Display the task reminder settings page
     */
    public function index()
    {
        if (!Auth::user()->can('manage company settings')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $creatorId = Auth::user()->creatorId();

        // Seed defaults if not exists
        TaskReminderTemplate::seedDefaultsForCompany($creatorId);
        TaskReminderSchedule::seedDefaultsForCompany($creatorId);

        // Get all configuration data
        $recipients = TaskReminderRecipient::forCreator($creatorId)->get();
        $templates = TaskReminderTemplate::forCreator($creatorId)->get();
        $schedules = TaskReminderSchedule::forCreator($creatorId)->get();

        // Get departments and designations for selection
        $departments = Department::where('created_by', $creatorId)->get();
        $designations = Designation::where('created_by', $creatorId)->get();

        // Get all users for exclusion selection
        $users = User::where('created_by', $creatorId)
            ->where('type', '!=', 'client')
            ->where('type', '!=', 'company')
            ->where('type', '!=', 'super admin')
            ->orderBy('name')
            ->get();

        // Get user types
        $userTypes = ['employee', 'company', 'client'];

        // Get recent logs
        $recentLogs = TaskReminderLog::forCreator($creatorId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get statistics
        $statistics = TaskReminderLog::getStatistics($creatorId, now()->subDays(30), now());

        return view('task_reminders.index', compact(
            'recipients',
            'templates',
            'schedules',
            'departments',
            'designations',
            'users',
            'userTypes',
            'recentLogs',
            'statistics'
        ));
    }

    /**
     * Save recipient configuration
     */
    public function saveRecipients(Request $request)
    {
        if (!Auth::user()->can('manage company settings')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $creatorId = Auth::user()->creatorId();

        // Clear existing recipients for this creator
        TaskReminderRecipient::forCreator($creatorId)->delete();

        // Save excluded departments
        if ($request->has('excluded_departments')) {
            foreach ($request->excluded_departments as $deptId) {
                TaskReminderRecipient::create([
                    'type' => TaskReminderRecipient::TYPE_DEPARTMENT,
                    'type_id' => $deptId,
                    'should_receive' => false,
                    'created_by' => $creatorId,
                ]);
            }
        }

        // Save excluded designations
        if ($request->has('excluded_designations')) {
            foreach ($request->excluded_designations as $desigId) {
                TaskReminderRecipient::create([
                    'type' => TaskReminderRecipient::TYPE_DESIGNATION,
                    'type_id' => $desigId,
                    'should_receive' => false,
                    'created_by' => $creatorId,
                ]);
            }
        }

        // Save excluded user types
        if ($request->has('excluded_user_types')) {
            foreach ($request->excluded_user_types as $userType) {
                TaskReminderRecipient::create([
                    'type' => TaskReminderRecipient::TYPE_USER_TYPE,
                    'type_name' => $userType,
                    'should_receive' => false,
                    'created_by' => $creatorId,
                ]);
            }
        }

        // Save excluded individual users
        if ($request->has('excluded_users')) {
            foreach ($request->excluded_users as $userId) {
                TaskReminderRecipient::create([
                    'type' => TaskReminderRecipient::TYPE_USER,
                    'type_id' => $userId,
                    'should_receive' => false,
                    'created_by' => $creatorId,
                ]);
            }
        }

        return redirect()->back()->with('success', __('Recipient settings saved successfully.'));
    }

    /**
     * Save schedule settings
     */
    public function saveSchedule(Request $request)
    {
        if (!Auth::user()->can('manage company settings')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'type' => 'required|string',
            'scheduled_time' => 'required',
            'is_enabled' => 'boolean',
            'include_weekends' => 'boolean',
        ]);

        $creatorId = Auth::user()->creatorId();

        TaskReminderSchedule::updateOrCreate(
            [
                'type' => $request->type,
                'created_by' => $creatorId,
            ],
            [
                'scheduled_time' => $request->scheduled_time,
                'is_enabled' => $request->has('is_enabled'),
                'include_weekends' => $request->has('include_weekends'),
            ]
        );

        return redirect()->back()->with('success', __('Schedule settings saved successfully.'));
    }

    /**
     * Edit template form
     */
    public function editTemplate($id)
    {
        if (!Auth::user()->can('manage company settings')) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $template = TaskReminderTemplate::findOrFail($id);

        return view('task_reminders.edit_template', compact('template'));
    }

    /**
     * Update template
     */
    public function updateTemplate(Request $request, $id)
    {
        if (!Auth::user()->can('manage company settings')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'message_template' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template = TaskReminderTemplate::findOrFail($id);

        $template->update([
            'name' => $request->name,
            'message_template' => $request->message_template,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->back()->with('success', __('Template updated successfully.'));
    }

    /**
     * Preview template with sample data
     */
    public function previewTemplate(Request $request)
    {
        if (!Auth::user()->can('manage company settings')) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $template = new TaskReminderTemplate([
            'message_template' => $request->message_template,
        ]);

        $sampleUser = new User([
            'name' => 'John Doe',
        ]);

        $sampleTaskData = [
            'count' => 3,
            'tasks' => collect([
                (object)['name' => 'Fix login bug'],
                (object)['name' => 'Update dashboard UI'],
                (object)['name' => 'Write unit tests'],
            ]),
        ];

        $preview = $template->parseTemplate($sampleUser, $sampleTaskData);

        return response()->json(['preview' => $preview]);
    }

    /**
     * Send test reminder
     */
    public function sendTestReminder(Request $request)
    {
        if (!Auth::user()->can('manage company settings')) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $request->validate([
            'type' => 'required|string',
            'user_id' => 'required|integer',
        ]);

        $creatorId = Auth::user()->creatorId();
        $user = User::findOrFail($request->user_id);

        $template = TaskReminderTemplate::forCreator($creatorId)
            ->where('type', $request->type)
            ->active()
            ->first();

        if (!$template) {
            return response()->json(['error' => __('No active template found for this type.')], 400);
        }

        // Get task data for in-progress reminders
        $taskData = ['count' => 0, 'tasks' => collect()];
        if ($request->type === TaskReminderTemplate::TYPE_IN_PROGRESS) {
            $inProgressStage = TaskStage::where('name', 'In Progress')
                ->where('created_by', $creatorId)
                ->first();

            if ($inProgressStage) {
                $tasks = ProjectTask::where('assign_to', 'LIKE', '%' . $user->id . '%')
                    ->where('stage_id', $inProgressStage->id)
                    ->get();

                $taskData = [
                    'count' => $tasks->count(),
                    'tasks' => $tasks,
                ];
            }
        }

        $message = $template->parseTemplate($user, $taskData);

        // Send via Google Chat
        $googleChat = new GoogleChatService();
        if (!$googleChat->isConfigured()) {
            return response()->json(['error' => __('Google Chat webhook is not configured.')], 400);
        }

        $chatMessage = [
            'text' => $message,
        ];

        $sent = $googleChat->sendMessage($chatMessage);

        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => __('Test reminder sent successfully!'),
                'preview' => $message,
            ]);
        } else {
            return response()->json(['error' => __('Failed to send message to Google Chat.')], 500);
        }
    }

    /**
     * Get eligible users preview
     */
    public function getEligibleUsers()
    {
        if (!Auth::user()->can('manage company settings')) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $creatorId = Auth::user()->creatorId();
        $users = TaskReminderRecipient::getEligibleUsers($creatorId);

        $userData = $users->map(function ($user) {
            $employee = \App\Models\Employee::where('user_id', $user->id)->first();
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $employee && $employee->department ? $employee->department->name : 'N/A',
                'designation' => $employee && $employee->designation ? $employee->designation->name : 'N/A',
            ];
        });

        return response()->json(['users' => $userData]);
    }

    /**
     * View reminder logs
     */
    public function logs(Request $request)
    {
        if (!Auth::user()->can('manage company settings')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $creatorId = Auth::user()->creatorId();

        $query = TaskReminderLog::forCreator($creatorId)
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->where('reminder_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->where('reminder_date', '<=', $request->end_date);
        }

        // Filter by response status
        if ($request->has('response_status')) {
            if ($request->response_status === 'received') {
                $query->withResponse();
            } elseif ($request->response_status === 'pending') {
                $query->awaitingResponse();
            }
        }

        $logs = $query->paginate(25);

        // Get statistics for the filtered range
        $startDate = $request->start_date ?? now()->subDays(30)->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();
        $statistics = TaskReminderLog::getStatistics($creatorId, $startDate, $endDate);

        return view('task_reminders.logs', compact('logs', 'statistics'));
    }
}
