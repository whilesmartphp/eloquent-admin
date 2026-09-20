<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('admin.mail_templates_table', 'admin_mail_templates'), function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('key');
            $table->boolean('enabled')->default(true);
            $table->string('subject');
            $table->text('body');
            $table->string('cta_label')->nullable();
            $table->string('cta_url', 2000)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['owner_type', 'owner_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('admin.mail_templates_table', 'admin_mail_templates'));
    }
};
