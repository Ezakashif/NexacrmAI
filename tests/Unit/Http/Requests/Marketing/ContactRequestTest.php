<?php

namespace Tests\Unit\Http\Requests\Marketing;

use App\Http\Requests\Marketing\ContactRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContactRequestTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $data
     * @return \Illuminate\Validation\Validator
     */
    private function validate(array $data)
    {
        $request = new ContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_valid_contact_payload_passes_validation(): void
    {
        $validator = $this->validate([
            'name' => 'Alex Morgan',
            'email' => 'alex@example.com',
            'company' => 'Northline',
            'phone' => '+1 555 010 2000',
            'message' => 'We would like a walkthrough.',
            'intent' => 'demo',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_required_fields_are_enforced(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('message', $validator->errors()->toArray());
    }

    public function test_custom_messages_are_used_for_required_fields(): void
    {
        $validator = $this->validate([]);

        $this->assertSame('Please enter your name.', $validator->errors()->first('name'));
        $this->assertSame('Please enter your email address.', $validator->errors()->first('email'));
        $this->assertSame('Please include a short message.', $validator->errors()->first('message'));
    }

    public function test_email_must_be_valid_format(): void
    {
        $validator = $this->validate([
            'name' => 'Alex Morgan',
            'email' => 'not-an-email',
            'message' => 'Hello',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertSame('Please enter a valid email address.', $validator->errors()->first('email'));
    }

    public function test_optional_fields_may_be_omitted(): void
    {
        $validator = $this->validate([
            'name' => 'Alex Morgan',
            'email' => 'alex@example.com',
            'message' => 'Hello there.',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_field_max_lengths_are_enforced(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 121),
            'email' => str_repeat('a', 250).'@example.com',
            'company' => str_repeat('b', 161),
            'phone' => str_repeat('1', 41),
            'message' => str_repeat('c', 5001),
            'intent' => str_repeat('d', 41),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('company', $validator->errors()->toArray());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        $this->assertArrayHasKey('message', $validator->errors()->toArray());
        $this->assertArrayHasKey('intent', $validator->errors()->toArray());
    }
}
