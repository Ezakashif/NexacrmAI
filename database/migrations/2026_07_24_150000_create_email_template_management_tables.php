<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('category', 64);
            $table->string('slug', 64);
            $table->string('locale', 16)->default('en');
            $table->string('name');
            $table->string('subject');
            $table->text('html_body');
            $table->text('text_body')->nullable();
            $table->json('placeholders')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('use_branding')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['category', 'locale']);
            $table->unique(['slug', 'locale']);
            $table->index(['is_active', 'category']);
        });

        Schema::create('email_send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('category', 64)->nullable();
            $table->string('locale', 16)->nullable();
            $table->string('to_email');
            $table->string('subject');
            $table->string('status', 32); // queued, sent, failed, preview, test
            $table->string('mailer', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->json('placeholders')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('related');
            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index('created_at');
        });

        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->json('role_ids');
            $table->string('status', 32)->default('pending'); // pending, accepted, revoked, expired
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'email']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
        Schema::dropIfExists('email_send_logs');
        Schema::dropIfExists('email_templates');
    }
};
