<?php $__env->startSection('content'); ?>
    <div class="card"><div class="card-header"><strong>Add flight route</strong><div class="small text-body-secondary">Publish a new scheduled route to the crew board</div></div><div class="card-body"><?php echo $__env->make('admin.flights._form', ['action' => route('admin.flights.store'), 'buttonLabel' => 'Add route', 'flight' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/admin/flights/create.blade.php ENDPATH**/ ?>