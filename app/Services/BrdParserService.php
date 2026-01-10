<?php

namespace App\Services;

use App\Models\BrdDocument;
use App\Models\IssueType;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskStage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrdParserService
{
    protected $apiKey;
    protected $apiEndpoint;
    protected $model;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->apiEndpoint = 'https://api.openai.com/v1/chat/completions';
        $this->model = 'gpt-4o-mini'; // Cost-effective model, can change to 'gpt-4o' for better results
    }

    /**
     * Extract text from PDF file using multiple methods
     */
    public function extractTextFromPdf(string $filePath): string
    {
        // Method 1: Try Spatie PDF to Text (requires pdftotext binary)
        try {
            if (class_exists(\Spatie\PdfToText\Pdf::class)) {
                $text = \Spatie\PdfToText\Pdf::getText($filePath);
                if (!empty(trim($text))) {
                    return $this->cleanText($text);
                }
            }
        } catch (\Exception $e) {
            Log::info('Spatie PDF extraction failed, trying fallback: ' . $e->getMessage());
        }

        // Method 2: Try native PHP PDF reading (basic extraction)
        try {
            $text = $this->extractTextNative($filePath);
            if (!empty(trim($text))) {
                return $this->cleanText($text);
            }
        } catch (\Exception $e) {
            Log::info('Native PDF extraction failed: ' . $e->getMessage());
        }

        // Method 3: Return a placeholder message asking user to paste content
        throw new \Exception('Could not automatically extract text from PDF. Please ensure the PDF contains selectable text (not scanned images).');
    }

    /**
     * Native PHP PDF text extraction (basic)
     */
    protected function extractTextNative(string $filePath): string
    {
        $content = file_get_contents($filePath);

        // Look for text streams in PDF
        $text = '';

        // Extract text between stream and endstream
        if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Try to decompress if FlateDecode
                $decompressed = @gzuncompress($stream);
                if ($decompressed !== false) {
                    $stream = $decompressed;
                }

                // Extract text objects
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $stream, $textMatches)) {
                    foreach ($textMatches[1] as $textMatch) {
                        // Extract strings from array
                        if (preg_match_all('/\((.*?)\)/s', $textMatch, $strings)) {
                            $text .= implode('', $strings[1]) . ' ';
                        }
                    }
                }

                // Also try Tj operator
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $stream, $tjMatches)) {
                    $text .= implode(' ', $tjMatches[1]) . ' ';
                }
            }
        }

        // Clean up extracted text
        $text = preg_replace('/[^\x20-\x7E\n\r]/', ' ', $text);

        return $text;
    }

    /**
     * Clean extracted text
     */
    protected function cleanText(string $text): string
    {
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return $text;
    }

    /**
     * Parse BRD with OpenAI ChatGPT
     */
    public function parseWithAI(BrdDocument $brd): array
    {
        $prompt = $this->buildPrompt($brd);

        try {
            $response = Http::timeout(180)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiEndpoint, [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert Product Manager and Agile Coach. You analyze Business Requirements Documents and create comprehensive product backlogs. Always respond with valid JSON only.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 8192,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API request failed: ' . $response->body());
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '';

            // Extract JSON from response
            $jsonData = $this->extractJsonFromResponse($content);

            return $jsonData;
        } catch (\Exception $e) {
            Log::error('OpenAI BRD parsing failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Alias for backward compatibility
     */
    public function parseWithGemini(BrdDocument $brd): array
    {
        return $this->parseWithAI($brd);
    }

    /**
     * Build the AI prompt
     */
    protected function buildPrompt(BrdDocument $brd): string
    {
        $teamInfo = $brd->getTeamPromptString();
        $milestoneInfo = $brd->getMilestonePromptString();
        $brdContent = $brd->extracted_text;

        // Truncate if too long (Gemini has context limits)
        if (strlen($brdContent) > 50000) {
            $brdContent = substr($brdContent, 0, 50000) . "\n\n[Document truncated due to length...]";
        }

        return <<<PROMPT
You are an expert Product Manager and Agile Coach. Analyze the following Business Requirements Document (BRD) and create a comprehensive product backlog with Epics, Stories, and Tasks.

## TEAM MEMBERS AND THEIR SKILLS:
{$teamInfo}

## PROJECT MILESTONES AND DEADLINES:
{$milestoneInfo}

## BRD DOCUMENT CONTENT:
{$brdContent}

## YOUR TASK:
1. Analyze the BRD and identify major feature areas (these become Epics)
2. Break down each Epic into User Stories with clear acceptance criteria
3. Break down each Story into specific development Tasks
4. Estimate hours for each Story based on complexity
5. Suggest the best team member for each Story based on their skills
6. Assign Stories to appropriate milestones based on dependencies and deadlines
7. Set priority levels (critical, high, medium, low) based on business value and dependencies

## OUTPUT FORMAT:
Return a valid JSON object with the following structure. ONLY return the JSON, no other text:

{
  "project_summary": "Brief 2-3 sentence summary of the project",
  "epics": [
    {
      "name": "Epic Name",
      "description": "Brief description of this epic",
      "milestone": "Milestone name from the list above",
      "stories": [
        {
          "name": "User Story Name",
          "description": "As a [user], I want [feature] so that [benefit]",
          "acceptance_criteria": [
            "Criterion 1",
            "Criterion 2"
          ],
          "estimated_hrs": 16,
          "priority": "high",
          "suggested_assignee": "Team member name",
          "assignment_reason": "Why this person is best suited",
          "tasks": [
            {
              "name": "Task name",
              "estimated_hrs": 4,
              "type": "backend|frontend|mobile|testing|design"
            }
          ]
        }
      ]
    }
  ],
  "risks": [
    "Potential risk or concern identified from the BRD"
  ],
  "assumptions": [
    "Assumption made during analysis"
  ]
}

IMPORTANT:
- Ensure all JSON is valid and properly escaped
- Use realistic hour estimates (4-40 hours per story)
- Match assignees to actual team member names provided
- Distribute work across team members based on their skills
- Consider milestone deadlines when assigning priorities
PROMPT;
    }

    /**
     * Extract JSON from AI response
     */
    protected function extractJsonFromResponse(string $response): array
    {
        // Try to find JSON in the response
        $response = trim($response);

        // Remove markdown code blocks if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
            $response = $matches[1];
        }

        // Try to parse JSON
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to find JSON object in response
            if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse AI response as JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Generate actual project and tasks from parsed data
     */
    public function generateBacklog(BrdDocument $brd, int $userId): array
    {
        $parsedData = $brd->parsed_data;

        if (empty($parsedData) || empty($parsedData['epics'])) {
            throw new \Exception('No parsed data available to generate backlog');
        }

        // Create or get project
        $project = $this->getOrCreateProject($brd, $userId);

        // Get issue types
        $epicType = IssueType::where('name', 'Epic')->where('created_by', $userId)->first()
            ?? IssueType::where('name', 'Epic')->first();
        $storyType = IssueType::where('name', 'Story')->where('created_by', $userId)->first()
            ?? IssueType::where('name', 'Story')->first();
        $taskType = IssueType::where('name', 'Task')->where('created_by', $userId)->first()
            ?? IssueType::where('name', 'Task')->first();

        // Get default stage
        $defaultStage = TaskStage::where('created_by', $userId)->orderBy('order')->first()
            ?? TaskStage::orderBy('order')->first();

        // Create milestones mapping
        $milestonesMap = $this->createMilestones($brd, $project, $userId);

        // Build user mapping from team data
        $userMap = $this->buildUserMap($brd->team_data);

        $createdItems = [
            'epics' => [],
            'stories' => [],
            'tasks' => [],
        ];

        foreach ($parsedData['epics'] as $epicData) {
            // Create Epic
            $epic = $this->createTask([
                'name' => $epicData['name'],
                'description' => $epicData['description'] ?? '',
                'project_id' => $project->id,
                'issue_type_id' => $epicType ? $epicType->id : null,
                'stage_id' => $defaultStage ? $defaultStage->id : null,
                'milestone_id' => $milestonesMap[$epicData['milestone']] ?? null,
                'priority' => 'high',
                'created_by' => $userId,
            ]);
            $createdItems['epics'][] = $epic;

            // Create Stories under Epic
            foreach ($epicData['stories'] ?? [] as $storyData) {
                $assigneeId = $userMap[$storyData['suggested_assignee']] ?? null;

                $story = $this->createTask([
                    'name' => $storyData['name'],
                    'description' => $this->formatStoryDescription($storyData),
                    'project_id' => $project->id,
                    'issue_type_id' => $storyType ? $storyType->id : null,
                    'stage_id' => $defaultStage ? $defaultStage->id : null,
                    'milestone_id' => $milestonesMap[$epicData['milestone']] ?? null,
                    'parent_id' => $epic->id,
                    'priority' => $storyData['priority'] ?? 'medium',
                    'estimated_hrs' => $storyData['estimated_hrs'] ?? 8,
                    'assign_to' => $assigneeId ? (string)$assigneeId : null,
                    'created_by' => $userId,
                ]);
                $createdItems['stories'][] = $story;

                // Create Tasks under Story
                foreach ($storyData['tasks'] ?? [] as $taskData) {
                    $task = $this->createTask([
                        'name' => $taskData['name'],
                        'project_id' => $project->id,
                        'issue_type_id' => $taskType ? $taskType->id : null,
                        'stage_id' => $defaultStage ? $defaultStage->id : null,
                        'milestone_id' => $milestonesMap[$epicData['milestone']] ?? null,
                        'parent_id' => $story->id,
                        'priority' => $storyData['priority'] ?? 'medium',
                        'estimated_hrs' => $taskData['estimated_hrs'] ?? 4,
                        'assign_to' => $assigneeId ? (string)$assigneeId : null,
                        'created_by' => $userId,
                    ]);
                    $createdItems['tasks'][] = $task;
                }
            }
        }

        // Update BRD status
        $brd->update([
            'status' => BrdDocument::STATUS_GENERATED,
            'project_id' => $project->id,
        ]);

        return [
            'project' => $project,
            'created' => $createdItems,
            'stats' => [
                'epics' => count($createdItems['epics']),
                'stories' => count($createdItems['stories']),
                'tasks' => count($createdItems['tasks']),
            ],
        ];
    }

    /**
     * Get or create project
     */
    protected function getOrCreateProject(BrdDocument $brd, int $userId): Project
    {
        if ($brd->project_id) {
            return Project::find($brd->project_id);
        }

        return Project::create([
            'project_name' => $brd->project_name ?? 'Project from BRD',
            'description' => $brd->project_description ?? ($brd->parsed_data['project_summary'] ?? ''),
            'status' => 'in_progress',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'created_by' => $userId,
        ]);
    }

    /**
     * Create milestones from BRD data
     */
    protected function createMilestones(BrdDocument $brd, Project $project, int $userId): array
    {
        $map = [];

        if (empty($brd->milestone_data)) {
            return $map;
        }

        foreach ($brd->milestone_data as $milestoneData) {
            $milestone = Milestone::create([
                'project_id' => $project->id,
                'title' => $milestoneData['name'],
                'description' => $milestoneData['description'] ?? '',
                'status' => 'incomplete',
                'due_date' => $milestoneData['deadline'] ?? null,
            ]);

            $map[$milestoneData['name']] = $milestone->id;
        }

        return $map;
    }

    /**
     * Build user ID map from team data
     */
    protected function buildUserMap(array $teamData = null): array
    {
        $map = [];

        if (empty($teamData)) {
            return $map;
        }

        foreach ($teamData as $member) {
            if (!empty($member['user_id']) && !empty($member['name'])) {
                $map[$member['name']] = $member['user_id'];
            }
        }

        return $map;
    }

    /**
     * Format story description with acceptance criteria
     */
    protected function formatStoryDescription(array $storyData): string
    {
        $desc = $storyData['description'] ?? '';

        if (!empty($storyData['acceptance_criteria'])) {
            $desc .= "\n\n**Acceptance Criteria:**\n";
            foreach ($storyData['acceptance_criteria'] as $criterion) {
                $desc .= "- {$criterion}\n";
            }
        }

        if (!empty($storyData['assignment_reason'])) {
            $desc .= "\n**Assignment Note:** " . $storyData['assignment_reason'];
        }

        return $desc;
    }

    /**
     * Create a task with auto issue key
     */
    protected function createTask(array $data): ProjectTask
    {
        // Generate issue key
        $project = Project::find($data['project_id']);
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $project->project_name ?? 'PRJ'), 0, 3));
        $count = ProjectTask::where('project_id', $data['project_id'])->count() + 1;
        $data['issue_key'] = $prefix . '-' . $count;

        return ProjectTask::create($data);
    }
}
