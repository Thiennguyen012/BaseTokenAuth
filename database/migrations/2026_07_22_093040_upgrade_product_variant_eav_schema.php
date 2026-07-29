<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing installations ran the prototype migrations before the controlled-EAV schema.
        if (!Schema::hasColumn('product_variants', 'combination_key')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->string('combination_key', 64)->nullable()->after('sku');
            });

            DB::table('product_variants')->orderBy('id')->each(function ($variant) {
                $optionIds = DB::table('product_variant_values')
                    ->where('product_variant_id', $variant->id)
                    ->orderBy('variant_option_id')
                    ->pluck('variant_option_id');

                DB::table('product_variants')->where('id', $variant->id)->update([
                    'combination_key' => hash('sha256', $optionIds->implode(':')),
                ]);
            });

            $duplicate = DB::table('product_variants')
                ->select('product_id', 'combination_key', DB::raw('COUNT(*) AS aggregate'))
                ->groupBy('product_id', 'combination_key')
                ->having('aggregate', '>', 1)
                ->first();

            if ($duplicate) {
                throw new RuntimeException('Không thể nâng cấp EAV: một sản phẩm đang có nhiều variant trùng tổ hợp option.');
            }

        }

        $variantIndexes = collect(Schema::getIndexes('product_variants'))->pluck('name');
        if (!$variantIndexes->contains('product_variants_product_id_combination_key_unique')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->unique(['product_id', 'combination_key']);
            });
        }

        if (Schema::hasColumn('product_variant_values', 'variant_group_id')) {
            Schema::table('product_variant_values', function (Blueprint $table) {
                $table->dropUnique('pvv_pvid_vgid_unique');
                $table->dropForeign(['variant_group_id']);
                $table->dropColumn('variant_group_id');
            });
        }
    }

    public function down(): void
    {
        // This migration intentionally has no automatic downgrade because recreating
        // the redundant group id safely requires deriving and validating every row.
    }
};
