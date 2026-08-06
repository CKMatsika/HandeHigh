<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class ZimbabweCurriculumService
{
    private array $setBooks;
    private array $keywords;
    private array $revisionKits;
    
    public function __construct()
    {
        $this->setBooks = $this->loadJson('zimbabwe-set-books.json');
        $this->keywords = $this->loadJson('zimbabwe-keywords.json');
        $this->revisionKits = $this->loadJson('zimbabwe-revision-kits.json');
    }
    
    /**
     * Load JSON file from storage/curriculum
     */
    private function loadJson(string $filename): array
    {
        $cacheKey = 'curriculum_' . pathinfo($filename, PATHINFO_FILENAME);
        
        return Cache::remember($cacheKey, 86400, function () use ($filename) {
            $path = storage_path("curriculum/{$filename}");
            
            if (!File::exists($path)) {
                return [];
            }
            
            return json_decode(File::get($path), true) ?? [];
        });
    }
    
    /**
     * Get set books for a specific subject and grade/form
     */
    public function getSetBooks(string $subject, string $grade, string $level = 'secondary'): array
    {
        $gradeKey = $this->normalizeGradeKey($grade, $level);
        $subjectKey = $this->normalizeSubjectKey($subject);
        
        $levelData = $this->setBooks[$level] ?? [];
        $gradeData = $levelData[$gradeKey] ?? [];
        $subjectData = $gradeData[$subjectKey] ?? [];
        
        return $subjectData['textbooks'] ?? [];
    }
    
    /**
     * Get search keywords for a subject and grade
     */
    public function getSearchKeywords(string $subject, string $grade, string $level = 'secondary'): array
    {
        $gradeKey = $this->normalizeGradeKey($grade, $level);
        $subjectKey = $this->normalizeSubjectKey($subject);
        
        $levelData = $this->keywords[$level] ?? [];
        $gradeData = $levelData[$gradeKey] ?? [];
        $subjectData = $gradeData['subjects'][$subjectKey] ?? [];
        
        return $subjectData;
    }
    
    /**
     * Get revision resources for a subject
     */
    public function getRevisionResources(string $subject): array
    {
        $subjectKey = $this->normalizeSubjectKey($subject);
        
        $resources = [];
        
        foreach ($this->revisionKits['free_online_platforms'] ?? [] as $platform) {
            if (in_array('all', $platform['subjects'] ?? []) ||
                in_array($subjectKey, $platform['subjects'] ?? [])) {
                $resources[] = $platform;
            }
        }
        
        foreach ($this->revisionKits['zimbabwe_specific'] ?? [] as $resource) {
            $resources[] = $resource;
        }
        
        $guides = $this->revisionKits['revision_guides'][$subjectKey] ?? [];
        foreach ($guides as $guide) {
            $resources[] = [
                'name' => $guide,
                'url' => '#',
                'type' => 'revision_guide',
                'description' => $guide,
            ];
        }
        
        return $resources;
    }
    
    /**
     * Get all resources for a subject and grade
     */
    public function getSubjectResources(string $subject, string $grade, string $level = 'secondary'): array
    {
        return [
            'set_books' => $this->getSetBooks($subject, $grade, $level),
            'keywords' => $this->getSearchKeywords($subject, $grade, $level),
            'revision_resources' => $this->getRevisionResources($subject),
        ];
    }
    
    /**
     * Normalize grade key to match JSON structure
     */
    private function normalizeGradeKey(string $grade, string $level): string
    {
        $grade = strtolower(trim($grade));
        
        if ($level === 'primary') {
            $grade = str_replace('grade ', 'grade_', $grade);
            $grade = str_replace('class ', 'grade_', $grade);
        } else {
            $grade = str_replace('form ', 'form_', $grade);
        }
        
        return $grade;
    }
    
    /**
     * Normalize subject key to match JSON structure
     */
    private function normalizeSubjectKey(string $subject): string
    {
        $subject = strtolower(trim($subject));
        
        $subjectMap = [
            'eng' => 'english',
            'eng language' => 'english',
            'eng literature' => 'english',
            'maths' => 'mathematics',
            'math' => 'mathematics',
            'sci' => 'science',
            'bio' => 'science',
            'physics' => 'science',
            'chem' => 'science',
            'hist' => 'history',
            'geo' => 'geography',
            'sho' => 'shona',
            'chi shona' => 'shona',
            'chiShona' => 'shona',
            'nde' => 'ndebele',
            'isindebele' => 'ndebele',
            'ict' => 'ict',
            'computer science' => 'ict',
            'pe' => 'physical_education',
            'physical education' => 'physical_education',
            'env' => 'environmental_science',
            'environmental science' => 'environmental_science',
            'social science' => 'social_science',
            'combined maths science' => 'combined_maths_science',
        ];
        
        return $subjectMap[$subject] ?? str_replace(' ', '_', $subject);
    }
    
    /**
     * Get all available levels
     */
    public function getLevels(): array
    {
        return ['primary', 'secondary'];
    }
    
    /**
     * Get all grades for a level
     */
    public function getGrades(string $level): array
    {
        $levelData = $this->setBooks[$level] ?? [];
        return array_keys($levelData);
    }
    
    /**
     * Get all subjects
     */
    public function getSubjects(): array
    {
        return [
            'english',
            'mathematics',
            'science',
            'history',
            'geography',
            'shona',
            'ndebele',
            'ict',
            'physical_education',
            'environmental_science',
            'social_science',
        ];
    }
}
