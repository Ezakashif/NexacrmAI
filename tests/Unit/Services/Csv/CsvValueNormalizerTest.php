<?php

namespace Tests\Unit\Services\Csv;

use App\Services\Csv\CsvValueNormalizer;
use Tests\TestCase;

class CsvValueNormalizerTest extends TestCase
{
    public function test_email_returns_null_for_null_empty_or_whitespace(): void
    {
        $this->assertNull(CsvValueNormalizer::email(null));
        $this->assertNull(CsvValueNormalizer::email(''));
        $this->assertNull(CsvValueNormalizer::email('   '));
        $this->assertNull(CsvValueNormalizer::email("\t\n"));
    }

    public function test_email_lowercases_plain_addresses(): void
    {
        $this->assertSame('user@example.com', CsvValueNormalizer::email('User@Example.COM'));
        $this->assertSame('user@example.com', CsvValueNormalizer::email('  user@example.com  '));
    }

    public function test_email_trims_surrounding_quotes(): void
    {
        $this->assertSame('user@example.com', CsvValueNormalizer::email('"user@example.com"'));
        $this->assertSame('user@example.com', CsvValueNormalizer::email("'user@example.com'"));
    }

    public function test_email_extracts_display_text_from_excel_hyperlink_formula(): void
    {
        $formula = '=HYPERLINK("mailto:target@x.com","display@x.com")';

        $this->assertSame('display@x.com', CsvValueNormalizer::email($formula));
    }

    public function test_email_extracts_address_from_mailto_url(): void
    {
        $this->assertSame('user@example.com', CsvValueNormalizer::email('mailto:user@example.com'));
        $this->assertSame(
            'user+tag@example.com',
            CsvValueNormalizer::email('mailto:user%2Btag@example.com'),
        );
    }

    public function test_email_extracts_first_email_token_from_junk(): void
    {
        $this->assertSame(
            'lead@acme.com',
            CsvValueNormalizer::email('Contact: lead@acme.com (preferred)'),
        );
    }

    public function test_email_returns_lowercased_value_when_no_email_token_is_extracted(): void
    {
        $this->assertSame('not-an-email', CsvValueNormalizer::email('not-an-email'));
        $this->assertSame('@@@', CsvValueNormalizer::email('@@@'));
    }

    public function test_apply_header_aliases_copies_alias_when_canonical_missing(): void
    {
        $data = [
            'e-mail' => 'user@example.com',
            'name' => 'Ada Lovelace',
        ];

        $result = CsvValueNormalizer::applyHeaderAliases($data, [
            'e-mail' => 'email',
        ]);

        $this->assertSame('user@example.com', $result['email']);
        $this->assertSame('Ada Lovelace', $result['name']);
    }

    public function test_apply_header_aliases_copies_alias_when_canonical_empty(): void
    {
        $data = [
            'email' => '   ',
            'e-mail' => 'user@example.com',
        ];

        $result = CsvValueNormalizer::applyHeaderAliases($data, [
            'e-mail' => 'email',
        ]);

        $this->assertSame('user@example.com', $result['email']);
    }

    public function test_apply_header_aliases_keeps_canonical_when_already_filled(): void
    {
        $data = [
            'email' => 'primary@example.com',
            'e-mail' => 'alias@example.com',
        ];

        $result = CsvValueNormalizer::applyHeaderAliases($data, [
            'e-mail' => 'email',
        ]);

        $this->assertSame('primary@example.com', $result['email']);
    }

    public function test_apply_header_aliases_ignores_empty_alias(): void
    {
        $data = [
            'e-mail' => '   ',
        ];

        $result = CsvValueNormalizer::applyHeaderAliases($data, [
            'e-mail' => 'email',
        ]);

        $this->assertArrayNotHasKey('email', $result);
    }

    public function test_apply_header_aliases_handles_multiple_aliases(): void
    {
        $data = [
            'phone number' => '555-0100',
            'e-mail' => 'user@example.com',
        ];

        $result = CsvValueNormalizer::applyHeaderAliases($data, [
            'e-mail' => 'email',
            'phone number' => 'phone',
        ]);

        $this->assertSame('user@example.com', $result['email']);
        $this->assertSame('555-0100', $result['phone']);
    }

    public function test_apply_header_aliases_leaves_data_unchanged_when_no_aliases_match(): void
    {
        $data = ['name' => 'Ada'];

        $result = CsvValueNormalizer::applyHeaderAliases($data, [
            'e-mail' => 'email',
        ]);

        $this->assertSame($data, $result);
    }
}
