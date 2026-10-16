<?php $__env->startSection('content'); ?>
    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e(session('error')); ?>

            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Tours</strong>
                <div class="small text-body-secondary">Multi-leg flight tours for pilots</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('admin.tours.create')); ?>">
                <span class="nav-icon">✚</span> Add tour
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tour</th>
                        <th>Route</th>
                        <th class="text-center">Legs</th>
                        <th class="text-center">Bookings</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><strong><?php echo e($tour->name); ?></strong></td>
                            <td>
                                <?php echo e($tour->originAirport?->identifier ?? '—'); ?>

                                <span class="mx-1 text-body-secondary">→</span>
                                <?php echo e($tour->destinationAirport?->identifier ?? '—'); ?>

                            </td>
                            <td class="text-center"><?php echo e($tour->legs_count ?? 0); ?></td>
                            <td class="text-center"><?php echo e($tour->bookings_count ?? 0); ?></td>
                            <td class="text-center">$<?php echo e(number_format($tour->price_cents / 100)); ?></td>
                            <td class="text-center">
                                <span class="badge <?php echo e($tour->is_active ? 'bg-success' : 'bg-secondary'); ?>"><?php echo e($tour->is_active ? 'Active' : 'Inactive'); ?></span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo e(route('admin.tours.edit', $tour)); ?>">Edit</a>
                                <form method="POST" action="<?php echo e(route('admin.tours.destroy', $tour)); ?>" onsubmit="return confirm('Delete this tour? All legs and bookings will be removed.')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td class="text-center py-4 text-body-secondary" colspan="7">No tours have been created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">
            <?php echo e($tours->total()); ?> tour<?php echo e($tours->total() !== 1 ? 's' : ''); ?> on record · <?php echo e($tours->perPage()); ?> per page
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/admin/tours/index.blade.php ENDPATH**/ ?>