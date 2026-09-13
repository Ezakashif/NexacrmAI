<?php

namespace Tests\Unit\Http\Requests\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestRulesTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function validate(array $data)
    {
        $request = new LoginRequest;

        return Validator::make($data, $request->rules());
    }

    public function test_valid_credentials_payload_passes_validation(): void
    {
        $validator = $this->validate([
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_email_and_password_are_required(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_email_must_be_valid_format(): void
    {
        $validator = $this->validate([
            'email' => 'not-an-email',
            'password' => 'secret-password',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_password_must_be_string(): void
    {
        $validator = $this->validate([
            'email' => 'user@example.com',
            'password' => 123456,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }
}
