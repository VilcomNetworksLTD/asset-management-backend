Schema::create('issues', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained();
    $table->foreignId('asset_id')->constrained();
    $table->foreignId('ticket_id')->nullable()->constrained();
    $table->foreignId('status_id')->constrained();
    $table->timestamps();
});
