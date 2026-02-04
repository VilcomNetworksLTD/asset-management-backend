Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->string('supplier_name');
    $table->string('location');
    $table->string('contact');
    $table->timestamps();
});
