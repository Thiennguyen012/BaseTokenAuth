<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variant_options', function (Blueprint $table) {
            $table->index('variant_group_id', 'variant_options_variant_group_id_index');
        });

        Schema::table('variant_options', function (Blueprint $table) {
            $table->dropUnique('variant_options_variant_group_id_option_code_unique');
            $table->foreignId('product_variant_group_id')
                ->nullable()
                ->after('id')
                ->constrained('product_variant_groups')
                ->cascadeOnDelete();
        });

        $originalOptions = DB::table('variant_options')->whereNull('product_variant_group_id')->get();
        $configurations = DB::table('product_variant_groups')->get();
        $optionMap = [];

        foreach ($configurations as $configuration) {
            foreach ($originalOptions->where('variant_group_id', $configuration->variant_group_id) as $option) {
                $newId = DB::table('variant_options')->insertGetId([
                    'product_variant_group_id' => $configuration->id,
                    'variant_group_id' => $option->variant_group_id,
                    'option_code' => $option->option_code,
                    'option_name' => $option->option_name,
                    'sort_order' => $option->sort_order,
                    'is_active' => $option->is_active,
                    'created_at' => $option->created_at,
                    'updated_at' => $option->updated_at,
                ]);
                $optionMap[$configuration->product_id . ':' . $option->id] = $newId;
            }
        }

        $variantValues = DB::table('product_variant_values as value')
            ->join('product_variants as variant', 'variant.id', '=', 'value.product_variant_id')
            ->select('value.id', 'value.variant_option_id', 'variant.product_id')
            ->get();

        foreach ($variantValues as $value) {
            $newOptionId = $optionMap[$value->product_id . ':' . $value->variant_option_id] ?? null;
            if ($newOptionId) {
                DB::table('product_variant_values')->where('id', $value->id)->update([
                    'variant_option_id' => $newOptionId,
                ]);
            }
        }

        DB::table('variant_options')->whereNull('product_variant_group_id')->delete();

        Schema::table('variant_options', function (Blueprint $table) {
            $table->dropForeign(['variant_group_id']);
            $table->dropIndex('variant_options_variant_group_id_index');
        });

        Schema::table('variant_options', function (Blueprint $table) {
            $table->dropColumn('variant_group_id');
            $table->foreignId('product_variant_group_id')->nullable(false)->change();
            $table->unique(['product_variant_group_id', 'option_code'], 'pvg_option_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('variant_options', function (Blueprint $table) {
            $table->dropUnique('pvg_option_code_unique');
            $table->foreignId('variant_group_id')->nullable()->after('product_variant_group_id');
        });

        $configurations = DB::table('product_variant_groups')->get()->keyBy('id');
        $productOptions = DB::table('variant_options')->get();
        $globalMap = [];

        foreach ($productOptions as $option) {
            $configuration = $configurations->get($option->product_variant_group_id);
            if (!$configuration) {
                continue;
            }
            $key = $configuration->variant_group_id . ':' . $option->option_code;
            if (!isset($globalMap[$key])) {
                $globalMap[$key] = DB::table('variant_options')->insertGetId([
                    'product_variant_group_id' => null,
                    'variant_group_id' => $configuration->variant_group_id,
                    'option_code' => $option->option_code,
                    'option_name' => $option->option_name,
                    'sort_order' => $option->sort_order,
                    'is_active' => $option->is_active,
                    'created_at' => $option->created_at,
                    'updated_at' => $option->updated_at,
                ]);
            }
            DB::table('product_variant_values')->where('variant_option_id', $option->id)->update([
                'variant_option_id' => $globalMap[$key],
            ]);
        }

        DB::table('variant_options')->whereNotNull('product_variant_group_id')->delete();

        Schema::table('variant_options', function (Blueprint $table) {
            $table->dropForeign(['product_variant_group_id']);
            $table->dropColumn('product_variant_group_id');
            $table->foreign('variant_group_id')->references('id')->on('variant_groups')->cascadeOnDelete();
            $table->foreignId('variant_group_id')->nullable(false)->change();
            $table->unique(['variant_group_id', 'option_code']);
        });
    }
};
