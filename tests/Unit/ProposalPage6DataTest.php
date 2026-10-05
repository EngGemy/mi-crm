<?php

namespace Tests\Unit;

use App\Models\Lookup;
use App\Models\PoultryQuotation;
use App\Services\Poultry\ProposalPage6Data;
use Tests\TestCase;

class ProposalPage6DataTest extends TestCase
{
    public function test_belt_lines_follow_the_form_instead_of_the_printed_two(): void
    {
        $quote = new PoultryQuotation;
        $quote->setRelation('manureMotorCount', new Lookup(['label_ar' => '2 ماتور']));
        $quote->setRelation('motorPower', new Lookup(['label_ar' => '1.5 حصان']));
        $quote->setRelation('beltsPerLine', new Lookup(['label_ar' => '5 سيور']));
        $quote->setRelation('innerBeltLength', new Lookup(['label_ar' => '12 متر']));
        $quote->setRelation('outerBeltLength', new Lookup(['label_ar' => '8 متر']));

        $data = (new ProposalPage6Data)->from($quote);

        $this->assertSame('5 سيور', $data['belts']);
        $this->assertSame('2 ماتور', $data['motor_count']);
        $this->assertSame('12 متر', $data['inner_belt']);
        $this->assertSame('8 متر', $data['outer_belt']);

        $belts = view('poultry.proposal.page6-belts', $data)->render();
        $motors = view('poultry.proposal.page6-motors', $data)->render();

        $this->assertStringContainsString('5 سيور', $motors);
        $this->assertStringContainsString('12 متر', $belts);
        $this->assertStringContainsString('8 متر', $belts);
        $this->assertStringNotContainsString('عدد 2 سير', $belts);
    }
}
