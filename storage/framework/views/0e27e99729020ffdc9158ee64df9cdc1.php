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

    <div class="card page-hero mb-4 text-white">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="text-white-50 text-uppercase fw-semibold small mb-2">Tour center</p>
                    <h1 class="h2 mb-2">Available tours</h1>
                    <p class="mb-0 text-white-50">Book multi-leg flight tours and earn career payouts.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php $__empty_1 = true; $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="card-title mb-1"><?php echo e($tour->name); ?></h5>
                                <p class="card-text small text-body-secondary"><?php echo e($tour->description ?? 'No description available.'); ?></p>
                            </div>
                            <span class="badge bg-success">Active</span>
                        </div>

                        <div class="mb-3">
                            <strong class="d-block text-body-secondary small text-uppercase">Route</div>
                            <div class="fw-semibold">
                                <?php echo e($tour->originAirport?->identifier ?? '—'); ?>

                                <span class="mx-1 text-body-secondary">→</span>
                                <?php echo e($tour->destinationAirport?->identifier ?? '—'); ?>

                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="text-body-secondary small text-uppercase">Legs</div>
                                <div><?php echo e($tour->legs_count ?? 0); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="text-body-secondary small text-uppercase">Distance</div>
                                <div><?php echo e($tour->total_distance_nm ?? '—'); ?> NM</div>
                            </div>
                            <div class="col-6">
                                <div class="text-body-secondary small text-uppercase">Duration</div>
                                <div><?php echo e($tour->estimated_duration_minutes ?? '—'); ?> min</div>
                            </div>
                            <div class="col-6">
                                <div class="text-body-secondary small text-uppercase">Price</div>
                                <div>$<?php echo e(number_format($tour->price_cents / 100)); ?></div>
                            </div>
                        </div>

                        <?php if($bookedTourIds->contains($tour->id)): ?>
                            <button class="btn btn-outline-success w-100" disabled>Already booked</button>
                        <?php else: ?>
                            <form method="POST" action="<?php echo e(route('tours.book', $tour)); ?>">
                                <?php echo csrf_field(); ?>
                                <button class="btn btn-primary w-100" type="submit">Book tour</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <p class="text-body-secondary">No tours are currently available.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php echo e($tours->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/crew-center/tours/index.blade.php ENDPATH**/ ?>