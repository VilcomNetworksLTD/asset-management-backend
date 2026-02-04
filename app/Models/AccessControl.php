Schema::create('access_controls', function (Blueprint $table) {
    $table->id();
    $table->string('access_levels');
    $table->timestamps();
});
