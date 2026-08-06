<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\DuckDuckGoSearchService;
use App\Services\ZimbabweCurriculumService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AIResourceController extends Controller
{
    public function __construct(
        private DuckDuckGoSearchService $searchService,
        private ZimbabweCurriculumService $curriculumService
    ) {}
    
    /**
     * Search for AI resources based on subject, topic, and grade
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string',
            'topic' => 'required|string',
            'grade' => 'required|string',
            'level' => 'required|in:primary,secondary',
        ]);
        
        $user = Auth::user();
        $school = $user?->school;
        
        if (!$school) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $subject = $request->input('subject');
        $topic = $request->input('topic');
        $grade = $request->input('grade');
        $level = $request->input('level');
        
        $setBooks = $this->curriculumService->getSetBooks($subject, $grade, $level);
        
        $keywords = $this->curriculumService->getSearchKeywords($subject, $grade, $level);
        
        $searchResults = [];
        foreach ($keywords as $keyword) {
            $results = $this->searchService->search($keyword . ' ' . $topic, 3);
            $searchResults = array_merge($searchResults, $results);
        }
        
        $searchResults = $this->categorizeResults($searchResults);
        
        $revisionResources = $this->curriculumService->getRevisionResources($subject);
        
        return response()->json([
            'success' => true,
            'data' => [
                'set_books' => $this->formatSetBooks($setBooks, $subject, $grade, $level),
                'web_resources' => $searchResults,
                'revision_resources' => $this->formatRevisionResources($revisionResources),
            ],
            'meta' => [
                'subject' => $subject,
                'topic' => $topic,
                'grade' => $grade,
                'level' => $level,
                'total_results' => count($searchResults['web'] ?? []) + count($setBooks),
            ],
        ]);
    }
    
    /**
     * Get curriculum information
     */
    public function curriculum(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string',
            'grade' => 'required|string',
            'level' => 'required|in:primary,secondary',
        ]);
        
        $subject = $request->input('subject');
        $grade = $request->input('grade');
        $level = $request->input('level');
        
        $resources = $this->curriculumService->getSubjectResources($subject, $grade, $level);
        
        return response()->json([
            'success' => true,
            'data' => $resources,
        ]);
    }
    
    /**
     * Get available grades and subjects
     */
    public function metadata(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'levels' => $this->curriculumService->getLevels(),
                'grades' => [
                    'primary' => $this->curriculumService->getGrades('primary'),
                    'secondary' => $this->curriculumService->getGrades('secondary'),
                ],
                'subjects' => $this->curriculumService->getSubjects(),
            ],
        ]);
    }
    
    /**
     * Categorize search results
     */
    private function categorizeResults(array $results): array
    {
        $categorized = [
            'web' => [],
            'videos' => [],
            'papers' => [],
            'notes' => [],
        ];
        
        foreach ($results as $result) {
            $url = strtolower($result['url']);
            $title = strtolower($result['title']);
            
            if (str_contains($url, 'youtube.com') || str_contains($url, 'vimeo.com') ||
                str_contains($title, 'video') || str_contains($title, 'tutorial')) {
                $result['type'] = 'video';
                $categorized['videos'][] = $result;
            } elseif (str_contains($url, 'past paper') || str_contains($url, 'pastpaper') ||
                      str_contains($title, 'past paper') || str_contains($title, 'pastpaper') ||
                      str_contains($url, 'zimsec.co.zw')) {
                $result['type'] = 'past_paper';
                $categorized['papers'][] = $result;
            } elseif (str_contains($url, 'notes') || str_contains($title, 'notes') ||
                      str_contains($title, 'revision') || str_contains($title, 'guide')) {
                $result['type'] = 'notes';
                $categorized['notes'][] = $result;
            } else {
                $result['type'] = 'web';
                $categorized['web'][] = $result;
            }
        }
        
        return $categorized;
    }
    
    /**
     * Format set books for frontend
     */
    private function formatSetBooks(array $books, string $subject, string $grade, string $level): array
    {
        $formatted = [];
        
        foreach ($books as $book) {
            $formatted[] = [
                'title' => $book,
                'type' => 'textbook',
                'category' => 'set_books',
                'source' => 'Zimbabwe Curriculum',
                'description' => "Ministry approved textbook for {$subject} {$grade}",
                'relevance' => 10,
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Format revision resources for frontend
     */
    private function formatRevisionResources(array $resources): array
    {
        $formatted = [];
        
        foreach ($resources as $resource) {
            $formatted[] = [
                'title' => $resource['name'],
                'url' => $resource['url'],
                'type' => $resource['type'],
                'category' => 'revision_resource',
                'description' => $resource['description'] ?? '',
                'source' => 'Zimbabwe Educational Resources',
                'relevance' => 7,
            ];
        }
        
        return $formatted;
    }
}
