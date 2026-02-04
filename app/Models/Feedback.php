Schema::create('feedback', function (Blueprint $table) {
    $table->id();
    $table->foreignId('asset_id')->constrained();
    $table->foreignId('employee_id')->constrained();
    $table->text('comments');
    $table->timestamps();
});
