Schema::create('maintenance_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('asset_id')->constrained();
    $table->date('request_date');
    $table->date('completion_date')->nullable();
    $table->string('maintenance_type');
    $table->text('description');
    $table->date('maintenance_date');
    $table->string('status');
    $table->timestamps();
});
