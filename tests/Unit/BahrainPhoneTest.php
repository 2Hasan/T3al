<?php

namespace Tests\Unit;

use App\Support\BahrainPhone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BahrainPhoneTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function equivalentSpellings(): array
    {
        return [
            'local' => ['36123456'],
            'with spaces' => ['3612 3456'],
            'with country code' => ['+973 3612 3456'],
            'with 00 prefix' => ['0097336123456'],
            'arabic digits' => ['٣٦١٢٣٤٥٦'],
        ];
    }

    #[DataProvider('equivalentSpellings')]
    public function test_every_spelling_of_a_number_normalizes_to_the_same_value(string $input): void
    {
        $this->assertSame('97336123456', BahrainPhone::normalize($input));
    }

    public function test_it_rejects_numbers_that_are_not_bahraini_mobiles(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BahrainPhone::normalize('12345');
    }

    public function test_is_valid_reports_without_throwing(): void
    {
        $this->assertTrue(BahrainPhone::isValid('36123456'));
        $this->assertFalse(BahrainPhone::isValid('abc'));
    }

    public function test_stored_numbers_are_formatted_for_display(): void
    {
        $this->assertSame('+973 3612 3456', BahrainPhone::format('97336123456'));
        $this->assertSame('12345', BahrainPhone::format('12345'));
    }
}
