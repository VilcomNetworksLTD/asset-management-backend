Schema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('role');
    $table->foreignId('access_control_id')->constrained();
    $table->string('contact');
    $table->timestamps();
});
