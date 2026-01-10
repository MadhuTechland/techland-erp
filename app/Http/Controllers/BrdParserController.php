<?php

namespace App\Http\Controllers;

use App\Models\BrdDocument;
use App\Models\Project;
use App\Models\SkillOption;
use App\Models\User;
use App\Models\Utility;
use App\Services\BrdParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BrdParserController extends Controller
{
    protected $brdService;

    public function __construct(BrdParserService $brdService)
    {
        $this->brdService = $brdService;
    }

    /**
     * Step 1: Show wizard / upload form
     */
    public function index()
    {
        $user = Auth::user();

        // Get existing BRD documents
        $brdDocuments = BrdDocument::where('created_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get existing projects for linking
        if ($user->type == 'company') {
            $projects = Project::where('created_by', $user->id)->get();
        } else {
            $projectIds = $user->projects()->pluck('projects.id');
            $projects = Project::whereIn('id', $projectIds)->get();
        }

        return view('brd-parser.index', compact('brdDocuments', 'projects'));
    }

    /**
     * Step 1: Handle BRD upload
     */
    public function uploadBrd(Request $request)
    {
        // Validate - either file or text is required
        $validator = Validator::make($request->all(), [
            'brd_file' => 'nullable|file|mimes:pdf|max:10240', // 10MB max
            'brd_text' => 'nullable|string|min:50',
            'project_name' => 'required|string|max:255',
            'project_description' => 'nullable|string',
            'existing_project_id' => 'nullable|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->withInput();
        }

        // Check that at least one input method is provided
        $hasFile = $request->hasFile('brd_file');
        $hasText = !empty(trim($request->brd_text)) && strlen(trim($request->brd_text)) >= 50;

        if (!$hasFile && !$hasText) {
            return redirect()->back()
                ->with('error', 'Please upload a PDF file or paste BRD content (minimum 50 characters).')
                ->withInput();
        }

        $user = Auth::user();
        $extractedText = null;
        $filePath = null;
        $originalName = null;

        // Try to extract from PDF first
        if ($hasFile) {
            $file = $request->file('brd_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $dir = 'uploads/brd_documents';

            // Store file
            $filePath = $file->storeAs($dir, $fileName, 'public');
            $originalName = $file->getClientOriginalName();

            // Try to extract text from PDF
            $fullPath = storage_path('app/public/' . $filePath);
            try {
                $extractedText = $this->brdService->extractTextFromPdf($fullPath);
            } catch (\Exception $e) {
                // PDF extraction failed - check if we have text fallback
                if ($hasText) {
                    $extractedText = trim($request->brd_text);
                } else {
                    return redirect()->back()
                        ->with('error', 'Failed to extract text from PDF: ' . $e->getMessage() . ' Please paste the BRD content manually.')
                        ->withInput();
                }
            }
        } else {
            // No file, use pasted text
            $extractedText = trim($request->brd_text);
            $originalName = 'Pasted BRD Content';
        }

        // Create BRD document record
        $brd = BrdDocument::create([
            'project_id' => $request->existing_project_id,
            'project_name' => $request->project_name,
            'project_description' => $request->project_description,
            'file_path' => $filePath,
            'original_name' => $originalName,
            'extracted_text' => $extractedText,
            'status' => BrdDocument::STATUS_UPLOADED,
            'created_by' => $user->id,
        ]);

        return redirect()->route('brd.team', $brd->id)->with('success', 'BRD uploaded successfully. Now configure your team.');
    }

    /**
     * Step 2: Team setup form
     */
    public function teamSetup($brdId)
    {
        $brd = BrdDocument::findOrFail($brdId);
        $this->checkBrdAccess($brd);

        $user = Auth::user();

        // Get available employees
        if ($user->type == 'company') {
            $employees = User::where('created_by', $user->id)
                ->where('type', '!=', 'client')
                ->orderBy('name')
                ->get();
        } else {
            $employees = User::where('created_by', $user->creatorId())
                ->where('type', '!=', 'client')
                ->orderBy('name')
                ->get();
        }

        // Get skill options
        $skills = SkillOption::getGroupedByCategory($user->creatorId());
        $allSkills = SkillOption::getAll($user->creatorId());

        return view('brd-parser.team-setup', compact('brd', 'employees', 'skills', 'allSkills'));
    }

    /**
     * Step 2: Save team configuration
     */
    public function saveTeam(Request $request, $brdId)
    {
        $brd = BrdDocument::findOrFail($brdId);
        $this->checkBrdAccess($brd);

        $validator = Validator::make($request->all(), [
            'team' => 'required|array|min:1',
            'team.*.user_id' => 'required|exists:users,id',
            'team.*.skills' => 'required|array|min:1',
            'team.*.experience' => 'required|string',
            'team.*.role' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->withInput();
        }

        // Build team data with names
        $teamData = [];
        foreach ($request->team as $member) {
            $user = User::find($member['user_id']);
            $teamData[] = [
                'user_id' => $member['user_id'],
                'name' => $user ? $user->name : 'Unknown',
                'skills' => $member['skills'],
                'experience' => $member['experience'],
                'role' => $member['role'] ?? '',
            ];
        }

        $brd->update([
            'team_data' => $teamData,
            'status' => BrdDocument::STATUS_TEAM_SETUP,
        ]);

        return redirect()->route('brd.milestones', $brd->id)->with('success', 'Team configured. Now set up milestones.');
    }

    /**
     * Step 3: Milestone setup form
     */
    public function milestoneSetup($brdId)
    {
        $brd = BrdDocument::findOrFail($brdId);
        $this->checkBrdAccess($brd);

        return view('brd-parser.milestone-setup', compact('brd'));
    }

    /**
     * Step 3: Save milestones
     */
    public function saveMilestones(Request $request, $brdId)
    {
        $brd = BrdDocument::findOrFail($brdId);
        $this->checkBrdAccess($brd);

        $validator = Validator::make($request->all(), [
            'milestones' => 'required|array|min:1',
            'milestones.*.name' => 'required|string|max:255',
            'milestones.*.deadline' => 'required|date',
            'milestones.*.description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->withInput();
        }

        $brd->update([
            'milestone_data' => $request->milestones,
            'status' => BrdDocument::STATUS_MILESTONES_SETUP,
        ]);

        return redirect()->route('brd.review', $brd->id)->with('success', 'Milestones saved. Ready to generate backlog.');
    }

    /**
     * Step 4: Review page (triggers generation if needed)
     */
    public function reviewBacklog($brdId)
    {
        $brd = BrdDocument::findOrFail($brdId);
        $this->checkBrdAccess($brd);

        return view('brd-parser.review', compact('brd'));
    }

    /**
     * Generate backlog with AI (AJAX)
     */
    public function generateBacklog(Request $request, $brdId)
    {
        $brd = BrdDocument::findOrFail($brdId);
        $this->checkBrdAccess($brd);

        try {
            // Update status to processing
            $brd->update(['status' => BrdDocument::STATUS_PROCESSING]);

            // Parse with Gemini
            $parsedData = $this->brdService->parseWithGemini($brd);

            // Save parsed data
            $brd->update([
                'parsed_data' => $parsedData,
                'status' => BrdDocument::STATUS_PARSED,
                'error_message' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Backlog generated successfully',
                'data' => $parsedData,
                'stats' => $brd->getBacklogStats(),
            ]);
        } catch (\Exception $e) {
            $brd->update([
                'status' => BrdDocument::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate backlog: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get parsing status (polling)
     */
    public function getParsingStatus($brdId)
    {
        $brd = BrdDocument::findOrFail($brdId);

        return response()->json([
            'status' => $brd->status,
            'status_label' => $brd->status_label,
            'has_data' => !empty($brd->parsed_data),
            'error' => $brd->error_message,
            'stats' => $brd->getBacklogStats(),
        ]);
    }

    /**
     * Update parsed task (before confirmation)
     */
    public function updateTask(Request $request, $brdId)
    {
        $brd = BrdDocument::findOrFail($brdId);
        $this->checkBrdAccess($brd);

        $parsedData = $brd->parsed_data;

        // Update specific item in parsed_data based on path
        $epicIndex = $request->input('epic_index');
        $storyIndex = $request->input('story_index');
        $taskIndex = $request->input('task_index');
        $field = $request->input('field');
        $value = $request->input('value');

        if (isset($taskIndex)) {
            $parsedData['epics'][$epicIndex]['stories'][$storyIndex]['tasks'][$taskIndex][$field] = $value;
        } elseif (isset($storyIndex)) {
            $parsedData['epics'][$epicIndex]['stories'][$storyIndex][$field] = $value;
        } else {
            $parsedData['epics'][$epicIndex][$field] = $value;
        }

        $brd->update(['parsed_data' => $parsedData]);

        return response()->json(['success' => true]);
    }

    /**
     * Confirm and create actual tasks
     */
    public function confirmBacklog(Request $request, $brdId)
    {
        $brd = BrdDocument::findOrFail($brdId);
        $this->checkBrdAccess($brd);

        if ($brd->status !== BrdDocument::STATUS_PARSED) {
            return redirect()->back()->with('error', 'Backlog must be generated before confirmation.');
        }

        try {
            $result = $this->brdService->generateBacklog($brd, Auth::user()->id);

            return redirect()->route('projects.show', $result['project']->id)
                ->with('success', sprintf(
                    'Successfully created %d epics, %d stories, and %d tasks!',
                    $result['stats']['epics'],
                    $result['stats']['stories'],
                    $result['stats']['tasks']
                ));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create backlog: ' . $e->getMessage());
        }
    }

    /**
     * Get skill suggestions (AJAX)
     */
    public function getSkillSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        $user = Auth::user();

        $skills = SkillOption::where(function($q) use ($user) {
                $q->where('created_by', $user->creatorId())
                  ->orWhere('created_by', 1);
            })
            ->where('name', 'like', '%' . $query . '%')
            ->limit(10)
            ->get(['id', 'name', 'category']);

        return response()->json($skills);
    }

    /**
     * Authorization check
     */
    protected function checkBrdAccess($brd)
    {
        $user = Auth::user();
        if ($brd->created_by !== $user->id && $user->type !== 'company') {
            abort(403, 'Unauthorized');
        }
    }
}
