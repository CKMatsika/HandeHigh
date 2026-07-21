<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invoice;
use App\Notifications\FeeReminderNotification;
use Carbon\Carbon;

class NotificationService
{
    public function sendFeeReminders()
    {
        $today = now()->startOfDay();
        
        // Get invoices that are due or overdue
        $invoices = Invoice::with(['student.user'])
            ->where('status', '!=', 'paid')
            ->where('due_date', '<=', $today->copy()->addDays(7))
            ->get();

        foreach ($invoices as $invoice) {
            $daysOverdue = $today->diffInDays($invoice->due_date, false) * -1;
            $daysOverdue = max(0, $daysOverdue);
            
            // Only send reminders for due dates within 7 days in past or future
            if ($daysOverdue <= 7 || $invoice->due_date->isToday()) {
                $this->sendFeeReminder($invoice, $daysOverdue);
            }
        }
    }

    protected function sendFeeReminder(Invoice $invoice, int $daysOverdue)
    {
        try {
            $invoice->student->user->notify(
                new FeeReminderNotification($invoice, $daysOverdue)
            );
            
            // Log the notification
            $invoice->notifications()->create([
                'type' => 'fee_reminder',
                'data' => [
                    'days_overdue' => $daysOverdue,
                    'amount' => $invoice->balance,
                ],
                'sent_at' => now(),
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Failed to send fee reminder: " . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
            ]);
        }
    }

    public function sendResultPublishedNotification($result)
    {
        // Implementation for result published notifications
    }

    public function sendAnnouncement($announcement, $recipients)
    {
        // Implementation for general announcements
    }

    public function sendDisciplinaryNotice($notice, $student)
    {
        // Implementation for disciplinary notices
    }
}
