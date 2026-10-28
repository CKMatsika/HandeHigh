<?php

namespace App\Services;

use App\Models\SchoolClass;

/**
 * Resolves a student's grade from the school's class register when possible.
 * Class names identify streams; classes.grade is the canonical grade/form.
 */
class StudentClassMappingService
{
    private array $resolvedClassCache = [];

    public function resolveClass(
        int $schoolId,
        ?string $className,
        ?string $grade = null,
        ?string $academicYear = null,
    ): ?SchoolClass {
        if (blank($className)) {
            return null;
        }

        $cacheKey = implode('|', [$schoolId, mb_strtolower(trim($className)), trim((string) $grade), trim((string) $academicYear)]);
        if (array_key_exists($cacheKey, $this->resolvedClassCache)) {
            return $this->resolvedClassCache[$cacheKey];
        }

        $classes = SchoolClass::where('school_id', $schoolId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($className))])
            ->when($academicYear, fn ($query) => $query->where(function ($yearQuery) use ($academicYear) {
                $yearQuery->where('academic_year', $academicYear)->orWhereNull('academic_year');
            }))
            ->orderByDesc('academic_year')
            ->get();

        if ($classes->isEmpty()) {
            return $this->resolvedClassCache[$cacheKey] = null;
        }

        $gradeMatches = $classes->filter(fn (SchoolClass $class) => $this->gradesMatch($class->grade, $grade));

        if ($gradeMatches->count() === 1) {
            return $this->resolvedClassCache[$cacheKey] = $gradeMatches->first();
        }

        // A unique class name is sufficient to resolve a stream even when an
        // imported grade is stale; the class register then supplies the grade.
        return $this->resolvedClassCache[$cacheKey] = ($classes->count() === 1 ? $classes->first() : null);
    }

    public function gradeAliases(?string $grade): array
    {
        if (blank($grade)) {
            return [];
        }

        $cleaned = trim($grade);
        $aliases = [$cleaned, mb_strtolower($cleaned), mb_convert_case($cleaned, MB_CASE_TITLE, 'UTF-8')];

        if (preg_match('/^(?:form|grade)\s*(\d+)$/i', $cleaned, $matches)) {
            $number = $matches[1];
            $aliases = array_merge($aliases, [$number, "Form {$number}", "Form{$number}", "Grade {$number}", "Grade{$number}"]);
        } elseif (is_numeric($cleaned)) {
            $number = (string) (int) $cleaned;
            $aliases = array_merge($aliases, [$number, "Form {$number}", "Form{$number}", "Grade {$number}", "Grade{$number}"]);
        }

        return array_values(array_unique(array_map(fn ($value) => trim((string) $value), $aliases)));
    }

    public function gradesMatch(?string $left, ?string $right): bool
    {
        if (blank($left) || blank($right)) {
            return blank($left) && blank($right);
        }

        return count(array_intersect($this->gradeAliases($left), $this->gradeAliases($right))) > 0;
    }

    public function canonicalGrade(int $schoolId, ?string $grade, ?string $className, ?string $academicYear = null): ?string
    {
        return $this->resolveClass($schoolId, $className, $grade, $academicYear)?->grade
            ?: (filled($grade) ? trim($grade) : null);
    }

    public function gradeAliasesForSchool(int $schoolId, ?string $grade): array
    {
        $aliases = $this->gradeAliases($grade);
        if (blank($grade)) {
            return $aliases;
        }

        $classes = SchoolClass::where('school_id', $schoolId)->get(['name', 'grade']);
        foreach ($classes as $class) {
            if (mb_strtolower(trim((string) $class->name), 'UTF-8') === mb_strtolower(trim($grade), 'UTF-8')) {
                $aliases = array_merge($aliases, $this->gradeAliases($class->grade));
            }

            if ($this->gradesMatch($class->grade, $grade)) {
                $aliases[] = trim((string) $class->name);
                $aliases[] = mb_strtolower(trim((string) $class->name), 'UTF-8');
            }
        }

        return array_values(array_unique($aliases));
    }
}
