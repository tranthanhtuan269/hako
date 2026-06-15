<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 16)->nullable()->unique()->after('is_admin');
            $table->foreignId('referred_by_user_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->decimal('affiliate_balance', 12, 2)->default(0)->after('referred_by_user_id');
        });

        DB::table('users')->whereNull('referral_code')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'referral_code' => $this->uniqueCode(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropColumn(['referral_code', 'affiliate_balance']);
        });
    }

    private function uniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (DB::table('users')->where('referral_code', $code)->exists());

        return $code;
    }
};
