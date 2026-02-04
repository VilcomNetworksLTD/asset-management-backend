Schema::create('transfers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained();
    $table->foreignId('asset_id')->constrained();
    $table->string('status');
    $table->timestamps();
});
