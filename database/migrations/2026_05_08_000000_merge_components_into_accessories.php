<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new columns to accessories table safely
        Schema::table('accessories', function (Blueprint $table) {
            if (!Schema::hasColumn('accessories', 'type')) {
                $table->string('type')->default('accessory');
            }
            
            if (!Schema::hasColumn('accessories', 'asset_id')) {
                $table->foreignId('asset_id')->nullable()->constrained('assets');
            }
        });

        // 2. Only migrate data if the components table still exists
        if (Schema::hasTable('components')) {
            DB::transaction(function () {
                $components = DB::table('components')->get();
                $map = [];

                foreach ($components as $component) {
                    $newId = DB::table('accessories')->insertGetId([
                        'name' => $component->name,
                        'category' => $component->category,
                        'model_number' => null,
                        'serial_no' => $component->serial_no,
                        'total_qty' => $component->total_qty,
                        'remaining_qty' => $component->remaining_qty,
                        'price' => $component->price,
                        'asset_id' => $component->asset_id,
                        'type' => 'component',
                        'created_at' => $component->created_at,
                        'updated_at' => $component->updated_at,
                        'deleted_at' => $component->deleted_at,
                    ]);
                    $map[$component->id] = $newId;
                }

                // Migrate pivot: component_user -> accessory_user
                if (Schema::hasTable('component_user') && Schema::hasTable('accessory_user')) {
                    $pivotRows = DB::table('component_user')->get();
                    foreach ($pivotRows as $row) {
                        if (!isset($map[$row->component_id])) {
                            continue;
                        }
                        DB::table('accessory_user')->insert([
                            'user_id' => $row->user_id,
                            'accessory_id' => $map[$row->component_id],
                            'quantity' => $row->quantity,
                            'returned_at' => $row->returned_at,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ]);
                    }
                }
            });

            // 3. Drop component pivot and components table only AFTER successful migration
            Schema::dropIfExists('component_user');
            Schema::dropIfExists('components');
        }
    }

    public function down(): void
    {
        // Reversal not implemented due to data loss risk
        throw new \Exception('Down migration not reversible.');
    }
};