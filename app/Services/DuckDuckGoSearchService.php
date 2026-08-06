<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DuckDuckGoSearchService
{
    private array $zimbabweDomains = [
        'zimsec.co.zw',
        'moe.gov.zw',
        'zw',
        'africa',
        'co.zw',
        'org.zw',
    ];
    
    /**
     * Search for educational resources using multiple free APIs
     */
    public function search(string $query, int $limit = 10): array
    {
        $cacheKey = 'edu_search_' . md5($query);
        
        return Cache::remember($cacheKey, 86400, function () use ($query, $limit) {
            $allResults = [];
            
            $allResults = array_merge($allResults, $this->searchOpenLibrary($query, $limit));
            
            $allResults = array_merge($allResults, $this->searchWikipedia($query, $limit));
            
            $allResults = array_merge($allResults, $this->searchWikibooks($query, $limit));
            
            return $allResults;
        });
    }
    
    /**
     * Search Open Library for textbooks
     */
    private function searchOpenLibrary(string $query, int $limit): array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://openlibrary.org/search.json', [
                    'q' => $query,
                    'limit' => $limit,
                    'fields' => 'title,author_name,first_publish_year,isbn,key,subject,availability',
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $results = [];
                
                foreach ($data['docs'] ?? [] as $doc) {
                    $title = $doc['title'] ?? '';
                    $authors = $doc['author_name'] ?? ['Unknown'];
                    $isbn = $doc['isbn'][0] ?? null;
                    $key = $doc['key'] ?? '';
                    
                    $url = $key ? "https://openlibrary.org{$key}" : '';
                    
                    $subjects = $doc['subject'] ?? [];
                    $subjectStr = count($subjects) > 0 ? implode(', ', array_slice($subjects, 0, 3)) : '';
                    
                    $availability = $doc['availability'] ?? [];
                    $isAvailable = ($availability['available_to_borrow'] ?? false) || 
                                   ($availability['available_to_browse'] ?? false) ||
                                   ($availability['is_readable'] ?? false) ||
                                   ($availability['openlibrary_edition'] ?? false);
                    
                    $results[] = [
                        'title' => $title . ' by ' . implode(', ', array_slice($authors, 0, 2)),
                        'url' => $url,
                        'snippet' => $subjectStr ?: 'Open Library textbook resource',
                        'domain' => 'openlibrary.org',
                        'source' => 'Open Library',
                        'type' => 'textbook',
                        'availability' => $isAvailable ? 'Free to read' : 'Reference only',
                    ];
                }
                
                return $results;
            }
            
            return [];
        } catch (\Exception $e) {
            Log::warning('Open Library search failed', ['query' => $query, 'error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Search Wikipedia for educational content
     */
    private function searchWikipedia(string $query, int $limit): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'SchoolERP/1.0 (Educational Resource Finder)',
                ])
                ->get('https://en.wikipedia.org/w/api.php', [
                    'action' => 'query',
                    'list' => 'search',
                    'srsearch' => $query,
                    'format' => 'json',
                    'srlimit' => $limit,
                    'srnamespace' => 0,
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $results = [];
                
                foreach ($data['query']['search'] ?? [] as $item) {
                    $title = $item['title'] ?? '';
                    $snippet = strip_tags($item['snippet'] ?? '');
                    
                    $results[] = [
                        'title' => $title,
                        'url' => 'https://en.wikipedia.org/wiki/' . urlencode(str_replace(' ', '_', $title)),
                        'snippet' => substr($snippet, 0, 200),
                        'domain' => 'en.wikipedia.org',
                        'source' => 'Wikipedia',
                        'type' => 'reference',
                    ];
                }
                
                return $results;
            }
            
            return [];
        } catch (\Exception $e) {
            Log::warning('Wikipedia search failed', ['query' => $query, 'error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Search Wikibooks for free textbooks
     */
    private function searchWikibooks(string $query, int $limit): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'SchoolERP/1.0 (Educational Resource Finder)',
                ])
                ->get('https://en.wikibooks.org/w/api.php', [
                    'action' => 'query',
                    'list' => 'search',
                    'srsearch' => $query,
                    'format' => 'json',
                    'srlimit' => min($limit, 5),
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $results = [];
                
                foreach ($data['query']['search'] ?? [] as $item) {
                    $title = $item['title'] ?? '';
                    $snippet = strip_tags($item['snippet'] ?? '');
                    
                    $results[] = [
                        'title' => $title . ' (Free Textbook)',
                        'url' => 'https://en.wikibooks.org/wiki/' . urlencode(str_replace(' ', '_', $title)),
                        'snippet' => substr($snippet, 0, 200),
                        'domain' => 'en.wikibooks.org',
                        'source' => 'Wikibooks',
                        'type' => 'textbook',
                    ];
                }
                
                return $results;
            }
            
            return [];
        } catch (\Exception $e) {
            Log::warning('Wikibooks search failed', ['query' => $query, 'error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Search specifically for Zimbabwe educational resources
     */
    public function searchZimbabweResources(string $subject, string $topic, string $level = 'secondary'): array
    {
        $queries = [
            "{$subject} {$topic} Zimbabwe curriculum",
            "ZIMSEC {$subject} {$topic} past papers",
            "Zimbabwe school {$subject} resources",
        ];
        
        $allResults = [];
        
        foreach ($queries as $query) {
            $results = $this->search($query, 5);
            $allResults = array_merge($allResults, $results);
        }
        
        return $this->filterZimbabweRelevant($allResults);
    }
    
    /**
     * Filter results for Zimbabwe relevance
     */
    private function filterZimbabweRelevant(array $results): array
    {
        $filtered = [];
        $seen = [];
        
        foreach ($results as $result) {
            $url = $result['url'];
            
            if (in_array($url, $seen)) {
                continue;
            }
            
            $seen[] = $url;
            
            $isZimbabweRelevant = false;
            
            foreach ($this->zimbabweDomains as $domain) {
                if (str_contains($result['domain'], $domain)) {
                    $isZimbabweRelevant = true;
                    break;
                }
            }
            
            $zimbabweKeywords = ['zimbabwe', 'zimsec', 'harare', 'bulawayo', 'african', 'curriculum'];
            foreach ($zimbabweKeywords as $keyword) {
                if (str_contains(strtolower($result['title']), $keyword) ||
                    str_contains(strtolower($result['snippet']), $keyword)) {
                    $isZimbabweRelevant = true;
                    break;
                }
            }
            
            $educationalKeywords = ['textbook', 'revision', 'past paper', 'syllabus', 'study', 'exam', 'notes', 'tutorial', 'learn', 'education', 'mathematics', 'science', 'history', 'geography'];
            $isEducational = false;
            foreach ($educationalKeywords as $keyword) {
                if (str_contains(strtolower($result['title']), $keyword) ||
                    str_contains(strtolower($result['snippet']), $keyword)) {
                    $isEducational = true;
                    break;
                }
            }
            
            if ($isZimbabweRelevant || $isEducational) {
                $result['relevance_score'] = $this->calculateRelevance($result);
                $filtered[] = $result;
            }
        }
        
        usort($filtered, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);
        
        return array_slice($filtered, 0, 10);
    }
    
    /**
     * Calculate relevance score for a result
     */
    private function calculateRelevance(array $result): float
    {
        $score = 0;
        
        foreach ($this->zimbabweDomains as $domain) {
            if (str_contains($result['domain'], $domain)) {
                $score += 10;
                break;
            }
        }
        
        $text = strtolower($result['title'] . ' ' . $result['snippet']);
        
        if (str_contains($text, 'zimsec')) $score += 5;
        if (str_contains($text, 'zimbabwe')) $score += 4;
        if (str_contains($text, 'textbook')) $score += 3;
        if (str_contains($text, 'revision')) $score += 3;
        if (str_contains($text, 'past paper')) $score += 4;
        if (str_contains($text, 'syllabus')) $score += 3;
        if (str_contains($text, 'free')) $score += 2;
        if (str_contains($text, 'mathematics')) $score += 1;
        if (str_contains($text, 'science')) $score += 1;
        
        return $score;
    }
}
