<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add serial_no and asset_id to accessories
        Schema::table('accessories', function (Blueprint $table) {
            if (!Schema::hasColumn('accessories', 'serial_no')) {
                $table->string('serial_no')->nullable()->after('model_number');
            }
        });

        // Migrate components to accessories
        $components = DB::table('components')->get();
        foreach ($components as $component) {
            $accessoryId = DB::table('accessories')->insertGetId([
                'name' => $component->name,
                'category' => $component->category,
                'model_number' => null, // Components didn't have model number
                'serial_no' => $component->serial_no,
                'total_qty' => $component->total_qty,
                'remaining_qty' => $component->remaining_qty,
                'price' => $component->price,
                'created_at' => $component->created_at,
                'updated_at' => $component->updated_at,
                'deleted_at' => $component->deleted_at,
            ]);

            // Migrate component_user pivot data to accessory_user
            $componentUsers = DB::table('component_user')->where('component_id', $component->id)->get();
            foreach ($componentUsers as $cu) {
                DB::table('accessory_user')->insert([
                    'user_id' => $cu->user_id,
                    'accessory_id' => $accessoryId,
                    'quantity' => $cu->quantity,
                    'returned_at' => $cu->returned_at,
                    'created_at' => $cu->created_at,
                    'updated_at' => $cu->updated_at,
                ]);
            }
        }

        // Drop component_user and components tables
        Schema::dropIfExists('component_user');
        Schema::dropIfExists('components');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->string('category'); 
            $table->string('serial_no')->nullable();
            $table->integer('total_qty')->default(0);
            $table->integer('remaining_qty')->default(0);
            $table->foreignId('asset_id')->nullable()->constrained('assets');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('component_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('component_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
        
        Schema::table('accessories', function (Blueprint $table) {
            $table->dropColumn(['serial_no']);
        });
    }
};
