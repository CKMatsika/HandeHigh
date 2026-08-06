<?php

namespace App\Services;

use App\Models\Student;
use App\Models\CareerGuidanceAssessment;
use App\Models\CareerPath;
use App\Models\StudentCareerInterest;
use App\Models\Result;

class CareerCounselorService
{
    private CareerGuidanceEngine $engine;

    public function __construct(CareerGuidanceEngine $engine)
    {
        $this->engine = $engine;
    }

    public function getWelcomeMessage(Student $student): array
    {
        $assessment = CareerGuidanceAssessment::where('student_id', $student->id)
            ->where('status', 'published')
            ->latest()->first();

        if (!$assessment) {
            return [
                'role' => 'counsellor',
                'message' => "Hi {$student->first_name}! 👋 I'm your AI Career Counsellor. I'm here to help you explore careers that match your strengths and interests. To get started, I'll need to analyze your academic performance first. An admin needs to generate your assessment. In the meantime, tell me — what career are you interested in?",
                'type' => 'welcome',
                'actions' => [],
            ];
        }

        $strengths = $assessment->strengths ?? [];
        $careers = $assessment->suggested_careers ?? [];
        $topStrengths = array_map(fn($s) => $s['subject'], array_slice($strengths, 0, 3));
        $topCareers = array_map(fn($c) => $c['name'], array_slice($careers, 0, 3));

        return [
            'role' => 'counsellor',
            'message' => "Welcome back, {$student->first_name}! 🌟 I've analyzed your performance and I'm excited to help you plan your future. Your strengths are in " . implode(', ', $topStrengths) . ". Based on your results, careers like " . implode(', ', $topCareers) . " could be a great fit. What would you like to explore?",
            'type' => 'welcome',
            'has_assessment' => true,
            'assessment_id' => $assessment->id,
            'actions' => [
                ['label' => 'Tell me about a career', 'value' => 'explore_career'],
                ['label' => 'My suggested careers', 'value' => 'show_careers'],
                ['label' => 'How can I improve?', 'value' => 'improvement_tips'],
                ['label' => 'My strengths & weaknesses', 'value' => 'strengths_weaknesses'],
                ['label' => 'I want to be a...', 'value' => 'my_choice'],
            ],
        ];
    }

    public function processMessage(Student $student, string $message): array
    {
        $assessment = CareerGuidanceAssessment::where('student_id', $student->id)
            ->where('status', 'published')
            ->latest()->first();

        $lower = strtolower(trim($message));

        if ($lower === 'explore_career' || $this->matchesIntent($lower, ['explore', 'tell me about', 'suggest', 'career options'])) {
            return $this->handleExploreCareer($student, $assessment);
        }

        if ($lower === 'show_careers' || $this->matchesIntent($lower, ['my suggested', 'show careers', 'what careers', 'recommended'])) {
            return $this->handleShowCareers($assessment);
        }

        if ($lower === 'improvement_tips' || $this->matchesIntent($lower, ['improve', 'improvement', 'get better', 'study tips', 'help me improve'])) {
            return $this->handleImprovementTips($assessment);
        }

        if ($lower === 'strengths_weaknesses' || $this->matchesIntent($lower, ['strength', 'weakness', 'good at', 'bad at', 'my performance'])) {
            return $this->handleStrengthsWeaknesses($assessment);
        }

        if ($lower === 'my_choice' || $this->matchesIntent($lower, ['i want to be', 'i want to become', 'my dream', 'my goal', 'interested in'])) {
            return [
                'role' => 'counsellor',
                'message' => "That's wonderful! 🎯 Tell me — what career are you interested in? Just type the name of the career you're dreaming about!",
                'type' => 'ask_career_choice',
                'actions' => [],
            ];
        }

        // Check if student is naming a career
        $careerPaths = CareerPath::where('school_id', $student->school_id)
            ->where('is_active', true)->get();

        $matchedPath = $careerPaths->first(fn($p) =>
            str_contains(strtolower($p->name), $lower) || str_contains($lower, strtolower($p->name))
        );

        if ($matchedPath) {
            return $this->handleCareerDetail($matchedPath, $assessment);
        }

        // Check if they mentioned a subject
        if ($this->matchesIntent($lower, ['math', 'mathematics', 'english', 'science', 'physics', 'chemistry', 'biology', 'history'])) {
            return $this->handleSubjectInquiry($lower, $assessment);
        }

        // Save their career interest
        if (str_word_count($message) <= 5 && !$this->isGreeting($lower)) {
            $this->saveCareerInterest($student, $message);
            return $this->handleNewCareerInterest($student, $message, $assessment);
        }

        // Default: save as interest and give general guidance
        if (!$this->isGreeting($lower)) {
            $this->saveCareerInterest($student, $message);
        }

        return [
            'role' => 'counsellor',
            'message' => $this->getGeneralGuidance($student, $assessment),
            'type' => 'general',
            'actions' => [
                ['label' => 'Explore careers', 'value' => 'explore_career'],
                ['label' => 'My suggested careers', 'value' => 'show_careers'],
                ['label' => 'Improvement tips', 'value' => 'improvement_tips'],
                ['label' => 'Tell me a career', 'value' => 'my_choice'],
            ],
        ];
    }

    private function handleExploreCareer(Student $student, ?CareerGuidanceAssessment $assessment): array
    {
        if (!$assessment) {
            return [
                'role' => 'counsellor',
                'message' => "I'd love to help you explore careers! First, I need to look at your academic performance. Please ask your teacher or admin to generate a career assessment for you. Once that's done, come back and I'll show you careers that match your strengths!",
                'type' => 'no_assessment',
                'actions' => [],
            ];
        }

        $careers = $assessment->suggested_careers ?? [];
        if (empty($careers)) {
            return [
                'role' => 'counsellor',
                'message' => "Based on your current results, I don't have specific career matches yet. But don't worry! Every student's journey is unique. Tell me what you enjoy doing — what subjects do you like? What are your hobbies?",
                'type' => 'no_careers',
                'actions' => [['label' => 'Tell me my strengths', 'value' => 'strengths_weaknesses']],
            ];
        }

        $parts = ["Here are the careers that match your profile, {$student->first_name}! 🎯\n\n"];
        foreach (array_slice($careers, 0, 4) as $i => $career) {
            $match = $career['match_percentage'] ?? '—';
            $parts[] = ($i + 1) . ". **{$career['name']}** — {$match}% match";
            if (!empty($career['description'])) {
                $parts[] = "   > {$career['description']}";
            }
        }
        $parts[] = "\nWhich one interests you? Just type the name! Or tell me a career you're curious about.";

        return [
            'role' => 'counsellor',
            'message' => implode("\n", $parts),
            'type' => 'careers_list',
            'careers' => $careers,
            'actions' => array_map(fn($c) => ['label' => $c['name'], 'value' => $c['name']], array_slice($careers, 0, 4)),
        ];
    }

    private function handleShowCareers(?CareerGuidanceAssessment $assessment): array
    {
        return $this->handleExploreCareer($assessment?->student, $assessment);
    }

    private function handleImprovementTips(?CareerGuidanceAssessment $assessment): array
    {
        if (!$assessment) {
            return [
                'role' => 'counsellor',
                'message' => "Great question! Here are some universal study tips that work for everyone:\n\n📚 **Create a study schedule** — 25 minutes of focused study, 5-minute breaks\n✏️ **Practice past exam papers** — they help you understand the format\n👥 **Study with friends** — explaining concepts to others helps you learn\n🌙 **Get enough sleep** — your brain needs rest to remember things\n\nWould you like tips for a specific subject?",
                'type' => 'tips',
                'actions' => [['label' => 'Math tips', 'value' => 'mathematics'], ['label' => 'English tips', 'value' => 'english']],
            ];
        }

        $tips = $assessment->improvement_tips ?? [];
        $areas = $assessment->areas_for_improvement ?? [];

        $parts = ["Here are ways you can grow and improve, {$assessment->student?->first_name}! 🌱\n\nRemember: every improvement, no matter how small, is a step forward.\n"];

        if (!empty($areas)) {
            foreach (array_slice($areas, 0, 3) as $area) {
                $parts[] = "📌 **{$area['subject']}**: {$area['message']}";
            }
        }

        if (!empty($tips)) {
            $parts[] = "\n💡 **Quick tips:**";
            foreach ($tips as $tip) {
                $parts[] = "• {$tip}";
            }
        }

        $parts[] = "\nYou're doing great! Every successful person started exactly where you are. What else would you like to know?";

        return [
            'role' => 'counsellor',
            'message' => implode("\n", $parts),
            'type' => 'tips',
            'actions' => [['label' => 'Show my strengths', 'value' => 'strengths_weaknesses'], ['label' => 'Explore careers', 'value' => 'explore_career']],
        ];
    }

    private function handleStrengthsWeaknesses(?CareerGuidanceAssessment $assessment): array
    {
        if (!$assessment) {
            return [
                'role' => 'counsellor',
                'message' => "To give you personalized feedback, I need to analyze your results first. Please ask your teacher to generate a career assessment for you!",
                'type' => 'no_assessment',
                'actions' => [],
            ];
        }

        $strengths = $assessment->strengths ?? [];
        $improvements = $assessment->areas_for_improvement ?? [];

        $parts = ["Here's your personalized performance review, {$assessment->student?->first_name}! 📊\n"];

        if (!empty($strengths)) {
            $parts[] = "\n✨ **Your Strengths:**";
            foreach ($strengths as $s) {
                $parts[] = "• {$s['message']}";
            }
        }

        if (!empty($improvements)) {
            $parts[] = "\n🌱 **Areas to Grow:**";
            foreach ($improvements as $a) {
                $parts[] = "• {$a['message']}";
            }
        }

        $parts[] = "\nYour strengths show where you naturally excel — these could lead to an exciting career! Your growth areas are just opportunities to discover new strengths. What would you like to explore next?";

        return [
            'role' => 'counsellor',
            'message' => implode("\n", $parts),
            'type' => 'review',
            'actions' => [
                ['label' => 'Suggested careers', 'value' => 'show_careers'],
                ['label' => 'Improvement tips', 'value' => 'improvement_tips'],
            ],
        ];
    }

    private function handleCareerDetail(CareerPath $path, ?CareerGuidanceAssessment $assessment): array
    {
        $match = '';
        if ($assessment) {
            $careers = $assessment->suggested_careers ?? [];
            $matched = collect($careers)->firstWhere('name', $path->name);
            $match = $matched ? " (you have a {$matched['match_percentage']}% match with this career! 🎯)" : '';
        }

        $parts = ["Let me tell you about **{$path->name}**! {$match}\n"];

        if ($path->description) {
            $parts[] = "\n📖 **What it is:** {$path->description}";
        }

        if ($path->typical_subjects) {
            $parts[] = "\n📚 **Subjects involved:** {$path->typical_subjects}";
        }

        if ($path->education_level) {
            $parts[] = "\n🎓 **Education needed:** {$path->education_level}";
        }

        if ($path->skills) {
            $parts[] = "\n💪 **Skills you'll need:** {$path->skills}";
        }

        if ($path->outlook) {
            $parts[] = "\n🔮 **Career outlook:** {$path->outlook}";
        }

        $parts[] = "\nWould you like to explore another career, or shall we look at how you can work toward this goal?";

        return [
            'role' => 'counsellor',
            'message' => implode("\n", $parts),
            'type' => 'career_detail',
            'path' => $path,
            'actions' => [
                ['label' => 'Explore more careers', 'value' => 'explore_career'],
                ['label' => 'How to improve', 'value' => 'improvement_tips'],
                ['label' => 'Tell me another career', 'value' => 'my_choice'],
            ],
        ];
    }

    private function handleSubjectInquiry(string $subject, ?CareerGuidanceAssessment $assessment): array
    {
        $tipsMap = [
            'math' => "Mathematics is a fantastic subject! It builds logical thinking. Try:\n• Practicing 3-4 problems daily\n• Understanding the 'why' behind formulas\n• Relating math to real-life situations like budgeting or sports statistics",
            'mathematics' => "Mathematics is a fantastic subject! It builds logical thinking. Try:\n• Practicing 3-4 problems daily\n• Understanding the 'why' behind formulas\n• Relating math to real-life situations like budgeting or sports statistics",
            'english' => "English opens doors to communication! To improve:\n• Read for 15 minutes every day — stories, news, anything you enjoy\n• Write a short paragraph about your day\n• Learn 3 new words each week and use them in sentences",
            'science' => "Science is about curiosity! To excel:\n• Ask questions about how things work\n• Do simple experiments at home\n• Watch science videos to see concepts in action",
            'physics' => "Physics explains the universe! Great career paths include engineering, astronomy, and architecture. Focus on understanding concepts through real-world examples.",
            'chemistry' => "Chemistry is everywhere! Careers include medicine, pharmacy, and research. Practice balancing equations and understanding the periodic table.",
            'biology' => "Biology is the study of life! It leads to careers in medicine, environmental science, and biotechnology. Draw diagrams to understand processes.",
            'history' => "History teaches us about the world! It's great for careers in law, journalism, and education. Create timelines to connect events.",
        ];

        foreach ($tipsMap as $key => $tip) {
            if (str_contains($subject, $key)) {
                return [
                    'role' => 'counsellor',
                    'message' => $tip . "\n\nWould you like to know which careers are related to this subject?",
                    'type' => 'subject_tip',
                    'actions' => [
                        ['label' => 'Careers from this subject', 'value' => 'explore_career'],
                        ['label' => 'Another subject', 'value' => 'strengths_weaknesses'],
                    ],
                ];
            }
        }

        return [
            'role' => 'counsellor',
            'message' => "{$subject} is a valuable subject! Keep working hard at it — every subject teaches you useful skills. Would you like to explore careers related to your favorite subjects?",
            'type' => 'subject_tip',
            'actions' => [['label' => 'Explore careers', 'value' => 'explore_career']],
        ];
    }

    private function handleNewCareerInterest(Student $student, string $career, ?CareerGuidanceAssessment $assessment): array
    {
        $matchInfo = '';
        if ($assessment) {
            $careers = $assessment->suggested_careers ?? [];
            $matched = collect($careers)->first(fn($c) =>
                str_contains(strtolower($c['name']), strtolower($career))
            );
            if ($matched) {
                $matchInfo = "\n\nGreat news! Based on your performance, you already have a {$matched['match_percentage']}% match with this career! 🎯 Keep working hard and you'll be well on your way.";
            } else {
                $matchInfo = "\n\nTo pursue this career, focus on building strong all-around skills. Your current strengths will definitely help!";
            }
        }

        return [
            'role' => 'counsellor',
            'message' => "Wow, {$student->first_name}, becoming a **{$career}** sounds amazing! 🚀\n\nIt's wonderful that you have a goal — that's the first step to success.{$matchInfo}\n\nI've saved your interest so we can track your progress. Would you like to explore what it takes to become a {$career}, or shall I suggest other careers that match your strengths?",
            'type' => 'career_interest_saved',
            'actions' => [
                ['label' => 'Explore this career', 'value' => $career],
                ['label' => 'Suggested careers', 'value' => 'show_careers'],
                ['label' => 'How to improve', 'value' => 'improvement_tips'],
            ],
        ];
    }

    private function getGeneralGuidance(Student $student, ?CareerGuidanceAssessment $assessment): string
    {
        if (!$assessment) {
            return "Hi {$student->first_name}! 👋 I'm your AI Career Counsellor. I can help you explore careers, find your strengths, and discover paths you might love. To give you personalized advice, I need to analyze your results. Ask your teacher or admin to generate a career assessment, then come back and we'll explore together! In the meantime, tell me — what are you interested in?";
        }

        $strengths = $assessment->strengths ?? [];
        $topSubject = !empty($strengths) ? $strengths[0]['subject'] : 'your studies';

        return "Hi {$student->first_name}! 🌟 I'm here to help you on your career journey. I can see you're doing great in {$topSubject}! You can ask me:\n\n• 'Show me my suggested careers'\n• 'How can I improve?'\n• 'Tell me about a career'\n• 'I want to be a...'\n\nWhat would you like to explore?";
    }

    private function saveCareerInterest(Student $student, string $career): void
    {
        StudentCareerInterest::updateOrCreate(
            ['student_id' => $student->id],
            [
                'school_id' => $student->school_id,
                'desired_career' => $career,
                'student_id' => $student->id,
            ]
        );
    }

    private function matchesIntent(string $input, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($input, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function isGreeting(string $input): bool
    {
        $greetings = ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'howdy', 'sup', 'yo'];
        return in_array($input, $greetings) || str_contains($input, 'how are you');
    }
}
