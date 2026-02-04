Schema::create('statuses', function (Blueprint $table) {
    $table->id();
    $table->string('status_name');
    $table->timestamps();
});
