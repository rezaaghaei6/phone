// database/migrations/2025_07_11_000003_create_recaptcha_logs_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recaptcha_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 12)->nullable();
            $table->string('ip_address');
            $table->string('recaptcha_token');
            $table->float('recaptcha_score');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recaptcha_logs');
    }
};