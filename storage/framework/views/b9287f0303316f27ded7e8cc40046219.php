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
                    <p class="text-white-50 text-uppercase fw-semibold small mb-2">Tour details</p>
                    <h1 class="h2 mb-2"><?php echo e($tour->name); ?></h1>
                    <p class="mb-0 text-white-50"><?php echo e($tour->description ?? 'No description available.'); ?></p>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0 text-lg-end">
                    <span class="badge bg-white text-primary fs-6 px-3 py-2">$<?php echo e(number_format($tour->price_cents / 100)); ?></span>
                    <p class="small mb-0 mt-3 text-white-50"><?php echo e($tour->legs_count ?? 0); ?> leg<?php echo e($tour->legs_count ?? 0 !== 1 ? 's' : ''); ?> · <?php echo e($tour->total_distance_nm ?? '—'); ?> NM</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><strong>Tour itinerary</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>From</th>
                                <th>To</th>
                                <th class="text-center">Distance</th>
                                <th class="text-center">Est. time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $tour->legs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="text-center"><?php echo e($leg->leg_number); ?></td>
                                    <td><?php echo e($leg->departureAirport?->identifier ?? '—'); ?></td>
                                    <td><?php echo e($leg->arrivalAirport?->identifier ?? '—'); ?></td>
                                    <td class="text-center"><?php echo e($leg->distance_nm ?? '—'); ?> NM</td>
                                    <td class="text-center"><?php echo e($leg->estimated_minutes ?? '—'); ?> min</td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td class="text-center py-4 text-body-secondary" colspan="5">No legs defined for this tour.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><strong>Book this tour</strong></div>
                <div class="card-body">
                    <p class="small text-body-secondary">Complete all legs of this tour to earn the tour payout.</p>

                    <div class="mb-3">
                        <strong class="d-block text-body-secondary small text-uppercase">Total price</div>
                        <div class="fw-semibold">$<?php echo e(number_format($tour->price_cents / 100)); ?></div>
                    </div>

                    <?php if($alreadyBooked): ?>
                        <button class="btn btn-outline-success w-100" disabled>Already booked</button>
                    <?php else: ?>
                        <form method="POST" action="<?php echo e(route('tours.book', $tour)); ?>">
                            <?php echo csrf_field(); ?>
                            <button class="btn btn-primary w-100" type="submit">Book tour</button>
                        </form>
                    <?php endif; ?>

                    <div class="mt-3">
                        <a class="btn btn-outline-secondary w-100" href="<?php echo e(route('tours.index')); ?>">Back to tours</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/crew-center/tours/show.blade.php ENDPATH**/ ?>