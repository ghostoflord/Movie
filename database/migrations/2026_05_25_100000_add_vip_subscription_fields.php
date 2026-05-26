<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'vip_expires_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('vip_expires_at')->nullable()->after('role');
            });
        }

        if (Schema::hasTable('vnpay_transactions')) {
            Schema::table('vnpay_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('vnpay_transactions', 'package')) {
                    $table->string('package', 16)->nullable()->after('order_info');
                }
                if (! Schema::hasColumn('vnpay_transactions', 'duration_months')) {
                    $table->unsignedTinyInteger('duration_months')->nullable()->after('package');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vnpay_transactions')) {
            Schema::table('vnpay_transactions', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('vnpay_transactions', 'package')) {
                    $cols[] = 'package';
                }
                if (Schema::hasColumn('vnpay_transactions', 'duration_months')) {
                    $cols[] = 'duration_months';
                }
                if ($cols !== []) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasColumn('users', 'vip_expires_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('vip_expires_at');
            });
        }
    }
};
