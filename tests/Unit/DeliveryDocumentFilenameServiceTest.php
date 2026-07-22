<?php

namespace Tests\Unit;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\DeliveryDocument;
use App\Models\Order;
use App\Services\Documents\DeliveryDocumentFilenameService;
use Tests\TestCase;

class DeliveryDocumentFilenameServiceTest extends TestCase
{
    public function test_single_customer_export_contains_name_and_type(): void
    {
        $customer = new Customer([
            'company_name' => 'Ristorante Da Luigi',
            'type' => CustomerType::Restaurant,
        ]);
        $customer->id = 10;
        $order = (new Order)->setRelation('customer', $customer);
        $document = (new DeliveryDocument(['document_number' => 'BC-2026-000001']))
            ->setRelation('order', $order);

        $filenames = new DeliveryDocumentFilenameService;

        $this->assertStringContainsString(
            'ristorante-da-luigi-ristoratore.pdf',
            $filenames->forCollection(collect([$document])),
        );
        $this->assertSame(
            'bolla-bc-2026-000001-ristorante-da-luigi-ristoratore.pdf',
            $filenames->forDocument($document),
        );
    }
}
