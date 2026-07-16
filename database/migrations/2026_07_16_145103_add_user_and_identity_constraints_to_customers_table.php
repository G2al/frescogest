<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->unique()->constrained()->nullOnDelete();
            $table->string('phone_normalized', 32)->nullable()->after('phone')->index();
        });

        DB::table('customers')->orderBy('id')->each(function (object $customer): void {
            DB::table('customers')->where('id', $customer->id)->update([
                'email' => $this->normalizeEmail($customer->email),
                'phone_normalized' => $this->normalizePhone($customer->phone),
                'vat_number' => $this->normalizeIdentifier($customer->vat_number),
                'tax_code' => $this->normalizeIdentifier($customer->tax_code),
            ]);
        });

        foreach (['email', 'vat_number', 'tax_code'] as $column) {
            $duplicate = DB::table('customers')
                ->select($column)
                ->whereNotNull($column)
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->value($column);

            if ($duplicate !== null) {
                throw new RuntimeException("Duplicate customer {$column}: {$duplicate}");
            }
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('email');
            $table->unique('vat_number');
            $table->unique('tax_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique(['vat_number']);
            $table->dropUnique(['tax_code']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('phone_normalized');
        });
    }

    private function normalizeEmail(?string $email): ?string
    {
        $normalized = mb_strtolower(trim((string) $email));

        return $normalized === '' ? null : $normalized;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) $phone);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeIdentifier(?string $identifier): ?string
    {
        $normalized = strtoupper((string) preg_replace('/[^a-zA-Z0-9]+/', '', (string) $identifier));

        return $normalized === '' ? null : $normalized;
    }
};
