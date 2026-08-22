<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Result;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Assessment;
use Carbon\Carbon;

class AIController extends Controller
{
    public function studentPerformancePrediction(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'student_ids' => 'array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $schoolId = $request->school_id;
        $studentIds = $request->student_ids ?? [];

        if (empty($studentIds)) {
            $students = Student::where('school_id', $schoolId)->get();
        } else {
            $students = Student::whereIn('id', $studentIds)
                ->where('school_id', $schoolId)
                ->get();
        }

        $predictions = $students->map(function($student) {
            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'current_gpa' => $this->calculateStudentGPA($student),
                'predicted_gpa' => $this->predictGPA($student),
                'risk_level' => $this->calculateRiskLevel($student),
                'recommendations' => $this->generateRecommendations($student),
                'confidence_score' => $this->calculateConfidence($student),
            ];
        });

        return response()->json([
            'predictions' => $predictions,
            'generated_at' => now(),
            'model_version' => 'v1.0',
        ]);
    }

    public function timetableGeneration(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'term' => 'required|string',
            'academic_year' => 'required|string',
            'constraints' => 'array',
        ]);

        $schoolId = $request->school_id;
        $constraints = $request->constraints ?? [];

        $timetable = $this->generateOptimalTimetable($schoolId, $constraints);

        return response()->json([
            'timetable' => $timetable,
            'optimization_score' => $this->calculateTimetableScore($timetable),
            'generated_at' => now(),
        ]);
    }

    public function academicRecommendations(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'student_id' => 'required|exists:students,id',
            'subject_area' => 'nullable|string',
        ]);

        $student = Student::findOrFail($request->student_id);
        
        $recommendations = [
            'academic_focus' => $this->recommendAcademicFocus($student),
            'study_strategies' => $this->recommendStudyStrategies($student),
            'resource_suggestions' => $this->recommendResources($student),
            'career_paths' => $this->recommendCareerPaths($student),
            'skill_development' => $this->recommendSkills($student),
        ];

        return response()->json([
            'student' => $student->load(['results.assessment.subject']),
            'recommendations' => $recommendations,
            'generated_at' => now(),
        ]);
    }

    public function financialForecasting(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'forecast_months' => 'integer|min:1|max:24',
        ]);

        $schoolId = $request->school_id;
        $months = $request->forecast_months ?? 12;

        $forecast = [
            'revenue_forecast' => $this->forecastRevenue($schoolId, $months),
            'expense_forecast' => $this->forecastExpenses($schoolId, $months),
            'cash_flow_projection' => $this->projectCashFlow($schoolId, $months),
            'enrollment_projection' => $this->projectEnrollment($schoolId, $months),
            'risk_factors' => $this->identifyFinancialRisks($schoolId),
        ];

        return response()->json([
            'forecast' => $forecast,
            'forecast_period' => $months . ' months',
            'generated_at' => now(),
            'confidence_level' => '85%',
        ]);
    }

    public function chatbotResponse(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'user_type' => 'required|in:student,parent,teacher,admin',
            'school_id' => 'required|exists:schools,id',
            'context' => 'nullable|array',
        ]);

        $response = $this->generateChatbotResponse(
            $request->message,
            $request->user_type,
            $request->school_id,
            $request->context ?? []
        );

        return response()->json([
            'response' => $response,
            'suggested_actions' => $this->suggestActions($request->message, $request->user_type),
            'related_topics' => $this->findRelatedTopics($request->message),
            'generated_at' => now(),
        ]);
    }

    // AI Service Methods
    protected function calculateStudentGPA($student)
    {
        return Result::where('student_id', $student->id)
            ->whereHas('assessment', function($query) {
                $query->where('term', Carbon::now()->term);
            })
            ->avg('score') ?? 0;
    }

    protected function predictGPA($student)
    {
        // Simplified prediction algorithm
        $currentGPA = $this->calculateStudentGPA($student);
        $historicalTrend = $this->getHistoricalTrend($student);
        $attendanceFactor = $this->getAttendanceFactor($student);
        
        // Apply weighted factors
        $predictedGPA = ($currentGPA * 0.6) + ($historicalTrend * 0.3) + ($attendanceFactor * 0.1);
        
        return min(100, max(0, $predictedGPA));
    }

    protected function calculateRiskLevel($student)
    {
        $gpa = $this->calculateStudentGPA($student);
        $attendanceRate = $this->getAttendanceRate($student);
        $assignmentCompletion = $this->getAssignmentCompletion($student);
        
        $riskScore = 0;
        
        if ($gpa < 50) $riskScore += 40;
        elseif ($gpa < 60) $riskScore += 20;
        
        if ($attendanceRate < 80) $riskScore += 30;
        elseif ($attendanceRate < 90) $riskScore += 10;
        
        if ($assignmentCompletion < 70) $riskScore += 30;
        elseif ($assignmentCompletion < 85) $riskScore += 10;
        
        if ($riskScore >= 60) return 'high';
        if ($riskScore >= 30) return 'medium';
        return 'low';
    }

    protected function generateRecommendations($student)
    {
        $recommendations = [];
        $gpa = $this->calculateStudentGPA($student);
        
        if ($gpa < 60) {
            $recommendations[] = 'Increase study time by 2-3 hours daily';
            $recommendations[] = 'Join study groups for challenging subjects';
            $recommendations[] = 'Schedule regular meetings with teachers';
        }
        
        if ($this->getAttendanceRate($student) < 90) {
            $recommendations[] = 'Improve attendance to avoid missing important content';
        }
        
        return $recommendations;
    }

    protected function calculateConfidence($student)
    {
        $dataPoints = Result::where('student_id', $student->id)->count();
        
        if ($dataPoints >= 20) return 0.95;
        if ($dataPoints >= 10) return 0.85;
        if ($dataPoints >= 5) return 0.75;
        return 0.60;
    }

    protected function generateOptimalTimetable($schoolId, $constraints)
    {
        // Simplified timetable generation
        $classes = SchoolClass::where('school_id', $schoolId)->get();
        $teachers = Teacher::where('school_id', $schoolId)->get();
        
        $timetable = [];
        
        foreach ($classes as $class) {
            foreach ($teachers->take(5) as $teacher) {
                $timetable[] = [
                    'class_id' => $class->id,
                    'teacher_id' => $teacher->id,
                    'subject' => 'General Studies',
                    'day' => 'Monday',
                    'time' => '09:00-10:00',
                    'room' => 'Room ' . rand(1, 20),
                ];
            }
        }
        
        return $timetable;
    }

    protected function calculateTimetableScore($timetable)
    {
        // Simplified scoring algorithm
        return 85.5;
    }

    protected function recommendAcademicFocus($student)
    {
        $results = $student->results()->with('assessment.subject')->get();
        $weakSubjects = $results->where('score', '<', 60)->pluck('assessment.subject.name');
        $strongSubjects = $results->where('score', '>=', 80)->pluck('assessment.subject.name');
        
        return [
            'strengthen' => $weakSubjects->toArray(),
            'leverage' => $strongSubjects->toArray(),
        ];
    }

    protected function recommendStudyStrategies($student)
    {
        return [
            'Use flashcards for memorization',
            'Practice with past papers',
            'Form study groups',
            'Use spaced repetition techniques',
        ];
    }

    protected function recommendResources($student)
    {
        return [
            'Online tutorials and videos',
            'Library resources',
            'Peer tutoring programs',
            'Teacher office hours',
        ];
    }

    protected function recommendCareerPaths($student)
    {
        // Simplified career recommendations based on performance
        $gpa = $this->calculateStudentGPA($student);
        
        if ($gpa >= 80) {
            return ['University Education', 'Professional Training', 'Research'];
        } elseif ($gpa >= 60) {
            return ['College Programs', 'Technical Training', 'Apprenticeships'];
        } else {
            return ['Skills Training', 'Vocational Programs', 'Workforce Entry'];
        }
    }

    protected function recommendSkills($student)
    {
        return [
            'Critical thinking',
            'Communication skills',
            'Digital literacy',
            'Problem-solving',
        ];
    }

    protected function forecastRevenue($schoolId, $months)
    {
        $historicalRevenue = Payment::where('school_id', $schoolId)
            ->where('payment_date', '>=', now()->subMonths(12))
            ->get();
        
        $monthlyAverage = $historicalRevenue->sum('amount') / 12;
        $growthRate = 0.05; // 5% growth assumption
        
        $forecast = [];
        for ($i = 1; $i <= $months; $i++) {
            $forecast[] = [
                'month' => now()->addMonths($i)->format('Y-m'),
                'predicted_revenue' => $monthlyAverage * pow(1 + $growthRate, $i),
            ];
        }
        
        return $forecast;
    }

    protected function forecastExpenses($schoolId, $months)
    {
        // Similar logic for expense forecasting
        return [];
    }

    protected function projectCashFlow($schoolId, $months)
    {
        return [];
    }

    protected function projectEnrollment($schoolId, $months)
    {
        return [];
    }

    protected function identifyFinancialRisks($schoolId)
    {
        return [
            'Seasonal enrollment fluctuations',
            'Economic factors affecting fee payments',
            'Unexpected maintenance costs',
        ];
    }

    protected function generateChatbotResponse($message, $userType, $schoolId, $context)
    {
        // Simplified chatbot logic
        $message = strtolower($message);
        
        if (str_contains($message, 'fee')) {
            return "I can help you with fee information. Please specify if you need to check your balance, make a payment, or understand fee structures.";
        }
        
        if (str_contains($message, 'result')) {
            return "I can help you access your academic results. Would you like to see your latest grades or academic performance summary?";
        }
        
        if (str_contains($message, 'schedule')) {
            return "I can help you with your class schedule and timetable. Let me know what specific information you need.";
        }
        
        return "I'm here to help! You can ask me about fees, results, schedules, attendance, and other school-related information.";
    }

    protected function suggestActions($message, $userType)
    {
        if (str_contains($message, 'fee')) {
            return ['View Fee Statement', 'Make Payment', 'Contact Finance Office'];
        }
        
        return ['View Dashboard', 'Contact Support', 'Schedule Meeting'];
    }

    protected function findRelatedTopics($message)
    {
        return ['Academic Performance', 'Financial Information', 'School Policies'];
    }

    // Helper methods (simplified implementations)
    protected function getHistoricalTrend($student) { return 0; }
    protected function getAttendanceFactor($student) { return 0; }
    protected function getAttendanceRate($student) { return 95; }
    protected function getAssignmentCompletion($student) { return 90; }
}
