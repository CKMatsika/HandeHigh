<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;

class FeeReminderNotification extends BaseNotification
{
    use Queueable;

    protected $invoice;
    protected $daysOverdue;

    public function __construct(Invoice $invoice, $daysOverdue = 0)
    {
        $this->invoice = $invoice;
        $this->daysOverdue = $daysOverdue;
        $this->via = $this->getNotificationChannels($invoice->student->user);
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->getSubject())
            ->markdown('emails.fee-reminder', [
                'invoice' => $this->invoice,
                'daysOverdue' => $this->daysOverdue,
                'student' => $this->invoice->student
            ]);
    }

    public function toNexmo($notifiable)
    {
        return (new NexmoMessage)
            ->content($this->getSmsContent());
    }

    public function toArray($notifiable)
    {
        return array_merge(parent::toArray($notifiable), [
            'invoice_id' => $this->invoice->id,
            'amount' => $this->invoice->balance,
            'due_date' => $this->invoice->due_date,
            'days_overdue' => $this->daysOverdue,
            'type' => $this->getNotificationType(),
        ]);
    }

    protected function getSubject()
    {
        return $this->daysOverdue > 0 
            ? "Reminder: {$this->daysOverdue} Days Overdue Payment"
            : "Upcoming Fee Payment Due"
    }

    protected function getSmsContent()
    {
        $studentName = $this->invoice->student->full_name;
        $amount = number_format($this->invoice->balance, 2);
        $dueDate = $this->invoice->due_date->format('M j, Y');
        
        if ($this->daysOverdue > 0) {
            return "Dear Parent, {$studentName}'s fee payment of {$amount} is {$this->daysOverdue} days overdue. Please make payment to avoid restrictions. Due: {$dueDate}";
        }
        
        return "Reminder: {$amount} fee for {$studentName} is due on {$dueDate}. Please make payment to avoid late charges.";
    }

    protected function getNotificationChannels($user)
    {
        $channels = ['database'];
        
        if ($user->email_notifications) {
            $channels[] = 'mail';
        }
        
        if ($user->sms_notifications) {
            $channels[] = 'nexmo';
        }
        
        return $channels;
    }
}
