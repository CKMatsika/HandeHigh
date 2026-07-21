<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        // Zimbabwe Mobile Money Methods
        $mobileMoneyMethods = [
            [
                'name' => 'EcoCash',
                'code' => 'ecocash',
                'type' => 'mobile_money',
                'provider' => 'Econet Wireless',
                'currency' => 'USD',
                'description' => 'Zimbabwe\'s most popular mobile money service',
                'transaction_fee_percentage' => 0.5,
                'fixed_transaction_fee' => 0.50,
                'minimum_amount' => 1.00,
                'maximum_amount' => 5000.00,
                'config' => [
                    'merchant_code' => 'ECO123456',
                    'ussd_code' => '*151#',
                    'app_name' => 'EcoCash App',
                ],
            ],
            [
                'name' => 'OneMoney',
                'code' => 'onemoney',
                'type' => 'mobile_money',
                'provider' => 'NetOne',
                'currency' => 'USD',
                'description' => 'NetOne\'s mobile money service',
                'transaction_fee_percentage' => 0.3,
                'fixed_transaction_fee' => 0.30,
                'minimum_amount' => 1.00,
                'maximum_amount' => 3000.00,
                'config' => [
                    'merchant_code' => 'ONE789012',
                    'ussd_code' => '*111#',
                    'app_name' => 'OneMoney App',
                ],
            ],
            [
                'name' => 'Omari',
                'code' => 'omari',
                'type' => 'mobile_money',
                'provider' => 'Telecel',
                'currency' => 'USD',
                'description' => 'Telecel\'s mobile money service',
                'transaction_fee_percentage' => 0.4,
                'fixed_transaction_fee' => 0.40,
                'minimum_amount' => 1.00,
                'maximum_amount' => 2500.00,
                'config' => [
                    'merchant_code' => 'OMA345678',
                    'ussd_code' => '*143#',
                    'app_name' => 'Omari App',
                ],
            ],
            [
                'name' => 'Innbucks',
                'code' => 'innbucks',
                'type' => 'mobile_money',
                'provider' => 'InnBucks',
                'currency' => 'USD',
                'description' => 'InnBucks mobile money service',
                'transaction_fee_percentage' => 0.2,
                'fixed_transaction_fee' => 0.25,
                'minimum_amount' => 1.00,
                'maximum_amount' => 2000.00,
                'config' => [
                    'merchant_code' => 'INN901234',
                    'ussd_code' => '*156#',
                    'app_name' => 'InnBucks App',
                ],
            ],
        ];

        // Card Payment Methods
        $cardMethods = [
            [
                'name' => 'Visa Card',
                'code' => 'visa',
                'type' => 'card',
                'provider' => 'Visa International',
                'currency' => 'USD',
                'description' => 'Visa debit and credit cards',
                'transaction_fee_percentage' => 2.5,
                'fixed_transaction_fee' => 0.00,
                'minimum_amount' => 5.00,
                'maximum_amount' => 10000.00,
                'config' => [
                    'card_types' => ['debit', 'credit'],
                    'supported_networks' => ['visa'],
                ],
            ],
            [
                'name' => 'Mastercard',
                'code' => 'mastercard',
                'type' => 'card',
                'provider' => 'Mastercard International',
                'currency' => 'USD',
                'description' => 'Mastercard debit and credit cards',
                'transaction_fee_percentage' => 2.5,
                'fixed_transaction_fee' => 0.00,
                'minimum_amount' => 5.00,
                'maximum_amount' => 10000.00,
                'config' => [
                    'card_types' => ['debit', 'credit'],
                    'supported_networks' => ['mastercard'],
                ],
            ],
            [
                'name' => 'American Express',
                'code' => 'amex',
                'type' => 'card',
                'provider' => 'American Express',
                'currency' => 'USD',
                'description' => 'American Express cards',
                'transaction_fee_percentage' => 3.0,
                'fixed_transaction_fee' => 0.00,
                'minimum_amount' => 10.00,
                'maximum_amount' => 10000.00,
                'config' => [
                    'card_types' => ['credit'],
                    'supported_networks' => ['amex'],
                ],
            ],
        ];

        // Bank Transfer Methods
        $bankTransferMethods = [
            [
                'name' => 'Bank Transfer - ZB Bank',
                'code' => 'zb_bank',
                'type' => 'bank_transfer',
                'provider' => 'ZB Bank',
                'currency' => 'USD',
                'description' => 'Direct bank transfer from ZB Bank accounts',
                'transaction_fee_percentage' => 0.0,
                'fixed_transaction_fee' => 2.00,
                'minimum_amount' => 10.00,
                'maximum_amount' => null,
                'config' => [
                    'bank_code' => 'ZB',
                    'account_name' => 'School Account',
                    'account_number' => '1234567890',
                    'branch_code' => '001',
                ],
            ],
            [
                'name' => 'Bank Transfer - CBZ',
                'code' => 'cbz_bank',
                'type' => 'bank_transfer',
                'provider' => 'CBZ Bank',
                'currency' => 'USD',
                'description' => 'Direct bank transfer from CBZ Bank accounts',
                'transaction_fee_percentage' => 0.0,
                'fixed_transaction_fee' => 2.00,
                'minimum_amount' => 10.00,
                'maximum_amount' => null,
                'config' => [
                    'bank_code' => 'CBZ',
                    'account_name' => 'School Account',
                    'account_number' => '0987654321',
                    'branch_code' => '002',
                ],
            ],
            [
                'name' => 'Bank Transfer - Steward Bank',
                'code' => 'steward_bank',
                'type' => 'bank_transfer',
                'provider' => 'Steward Bank',
                'currency' => 'USD',
                'description' => 'Direct bank transfer from Steward Bank accounts',
                'transaction_fee_percentage' => 0.0,
                'fixed_transaction_fee' => 2.00,
                'minimum_amount' => 10.00,
                'maximum_amount' => null,
                'config' => [
                    'bank_code' => 'ST',
                    'account_name' => 'School Account',
                    'account_number' => '1122334455',
                    'branch_code' => '003',
                ],
            ],
        ];

        // Online Payment Methods
        $onlineMethods = [
            [
                'name' => 'PayPal',
                'code' => 'paypal',
                'type' => 'online',
                'provider' => 'PayPal Inc.',
                'currency' => 'USD',
                'description' => 'PayPal online payment service',
                'transaction_fee_percentage' => 3.4,
                'fixed_transaction_fee' => 0.30,
                'minimum_amount' => 1.00,
                'maximum_amount' => 5000.00,
                'config' => [
                    'merchant_id' => 'paypal_merchant_id',
                    'api_endpoint' => 'https://api.paypal.com',
                ],
            ],
            [
                'name' => 'Stripe',
                'code' => 'stripe',
                'type' => 'online',
                'provider' => 'Stripe Inc.',
                'currency' => 'USD',
                'description' => 'Stripe online payment processing',
                'transaction_fee_percentage' => 2.9,
                'fixed_transaction_fee' => 0.30,
                'minimum_amount' => 1.00,
                'maximum_amount' => 10000.00,
                'config' => [
                    'merchant_id' => 'stripe_merchant_id',
                    'api_endpoint' => 'https://api.stripe.com',
                ],
            ],
        ];

        // Insert all payment methods
        foreach ($mobileMoneyMethods as $method) {
            PaymentMethod::create($method);
        }

        foreach ($cardMethods as $method) {
            PaymentMethod::create($method);
        }

        foreach ($bankTransferMethods as $method) {
            PaymentMethod::create($method);
        }

        foreach ($onlineMethods as $method) {
            PaymentMethod::create($method);
        }

        $this->command->info('Payment methods seeded successfully!');
    }
}
