Schema::create('tickets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('asset_id')->constrained();
    $table->foreignId('employee_id')->constrained();
    $table->foreignId('status_id')->constrained();
    $table->text('communication_log')->nullable();
    $table->timestamps();
});
