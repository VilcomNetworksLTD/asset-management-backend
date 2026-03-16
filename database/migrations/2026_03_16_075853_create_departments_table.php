<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Departments Table
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Add department_id to Users if not exists
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('email')->constrained('departments')->nullOnDelete();
            });
            
            // Migrate existing string data to IDs (if the old 'department' column exists)
            if (Schema::hasColumn('users', 'department')) {
                $users = DB::table('users')->whereNotNull('department')->where('department', '!=', '')->get();
                foreach ($users as $u) {
                    $name = trim($u->department);
                    if (!$name) continue;

                    // Find or Create Department
                    $dept = DB::table('departments')->where('name', $name)->first();
                    if (!$dept) {
                        $id = DB::table('departments')->insertGetId([
                            'name' => $name, 
                            'created_at' => now(), 
                            'updated_at' => now()
                        ]);
                    } else {
                        $id = $dept->id;
                    }
                    
                    // Update User
                    DB::table('users')->where('id', $u->id)->update(['department_id' => $id]);
                }
            }
        }

        // 3. Add department_id to Licenses if not exists
        if (Schema::hasTable('licenses') && !Schema::hasColumn('licenses', 'department_id')) {
            Schema::table('licenses', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('expiry_date')->constrained('departments')->nullOnDelete();
            });

            // Migrate existing string data to IDs (if the old 'departments' column exists)
            if (Schema::hasColumn('licenses', 'departments')) {
                $licenses = DB::table('licenses')->whereNotNull('departments')->where('departments', '!=', '')->get();
                foreach ($licenses as $l) {
                    $name = trim($l->departments);
                    if (!$name) continue;

                    // Find or Create Department
                    $dept = DB::table('departments')->where('name', $name)->first();
                    if (!$dept) {
                        $id = DB::table('departments')->insertGetId([
                            'name' => $name, 
                            'created_at' => now(), 
                            'updated_at' => now()
                        ]);
                    } else {
                        $id = $dept->id;
                    }

                    // Update License
                    DB::table('licenses')->where('id', $l->id)->update(['department_id' => $id]);
                }
            }
        }

        // 4. Drop old columns after data migration is complete
        if (Schema::hasColumn('users', 'department')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }
        if (Schema::hasColumn('licenses', 'departments')) {
            Schema::table('licenses', function (Blueprint $table) {
                $table->dropColumn('departments');
            });
        }
    }

    public function down(): void
    {
        // Reverse operations
        // 1. Add old string columns back
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('email');
        });
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('departments')->nullable()->after('expiry_date');
        });

        // (Data migration back to string is complex and often skipped in 'down' methods)

        // 2. Drop foreign key columns
        if (Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }
        if (Schema::hasColumn('licenses', 'department_id')) {
            Schema::table('licenses', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }

        // 3. Drop the departments table
        Schema::dropIfExists('departments');
    }
};
