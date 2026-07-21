@component('mail::layout')
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            {{ config('app.name') }}
        @endcomponent
    @endslot

    # @yield('title')

    @if($daysOverdue > 0)
        <p>This is a friendly reminder that the payment for <strong>{{ $student->full_name }}</strong> is <strong>{{ $daysOverdue }} days</strong> overdue.</p>
        <p>Please make the payment as soon as possible to avoid any restrictions on school services.</p>
    @else
        <p>This is a friendly reminder that the payment for <strong>{{ $student->full_name }}</strong> is due on <strong>{{ $invoice->due_date->format('F j, Y') }}</strong>.</p>
    @endif

    <div class="payment-details" style="margin: 25px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>Invoice #:</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>Student:</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ $student->full_name }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>Amount Due:</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ number_format($invoice->balance, 2) }} {{ $invoice->currency }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>Due Date:</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    {{ $invoice->due_date->format('F j, Y') }}
                    @if($daysOverdue > 0)
                        <span style="color: #e74c3c;">({{ $daysOverdue }} days overdue)</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @component('mail::button', ['url' => route('parent.invoices.show', $invoice->id)])
        View Invoice & Make Payment
    @endcomponent

    <p>If you have already made this payment, please ignore this reminder.</p>

    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            
            @if(isset($settings['school_address']))
                <br>
                {{ $settings['school_address'] }}
            @endif
        @endcomponent
    @endslot
@endcomponent
