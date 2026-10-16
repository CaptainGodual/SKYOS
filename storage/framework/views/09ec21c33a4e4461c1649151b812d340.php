<?php $__env->startSection('content'); ?>
    <?php if(session('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo e(session('success')); ?><button class="btn-close" data-coreui-dismiss="alert" type="button"></button></div><?php endif; ?>
    <?php if(session('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo e(session('error')); ?><button class="btn-close" data-coreui-dismiss="alert" type="button"></button></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Airports</strong>
                <div class="small text-body-secondary">Imported airport reference data</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('admin.airports.import')); ?>">
                    <span class="nav-icon">⤓</span> Import CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('admin.airports.index')); ?>" class="row g-3 mb-3 align-items-end">
                <div class="col-md-4 col-xl-3">
                    <input class="form-control" name="search" placeholder="Search by code, name, or city" value="<?php echo e($search ?? ''); ?>">
                </div>
                <div class="col-md-3 col-xl-2">
                    <input class="form-control" name="country" placeholder="Country code (e.g. US)" value="<?php echo e($country ?? ''); ?>">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <?php if($search ?? $country): ?>
                        <a class="btn btn-outline-secondary" href="<?php echo e(route('admin.airports.index')); ?>">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>IATA</th>
                            <th>ICAO</th>
                            <th>Name</th>
                            <th>City</th>
                            <th>Country</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Hub</th>
                            <th class="text-center">Lat · Lng</th>
                            <th class="text-end">Time zone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $airports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $airport): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><span class="fw-semibold"><?php echo e($airport->identifier); ?></span></td>
                                <td><?php echo e($airport->iata_code ?? '—'); ?></td>
                                <td><?php echo e($airport->icao_code ?? '—'); ?></td>
                                <td><?php echo e($airport->name); ?></td>
                                <td><?php echo e($airport->municipality ?? '—'); ?></td>
                                <td><?php echo e($airport->country_code ?? '—'); ?></td>
                                <td class="text-center"><?php echo e($airport->type); ?></td>
                                <td class="text-center">
                                    <form method="POST" action="<?php echo e(route('admin.airports.toggle-hub', $airport)); ?>" class="d-inline mb-0">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <input type="checkbox" <?php echo e($airport->is_hub ? 'checked' : ''); ?>

                                            onclick="this.form.submit()" class="form-check-input mt-1">
                                    </form>
                                </td>
                                <td class="text-center"><?php echo e($airport->latitude ?? '—'); ?> · <?php echo e($airport->longitude ?? '—'); ?></td>
                                <td class="text-end"><?php echo e($airport->time_zone ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td class="text-center py-4 text-body-secondary" colspan="10">No airports have been imported yet. <a href="<?php echo e(route('admin.airports.import')); ?>">Import a CSV</a> to get started.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php echo e($airports->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/admin/airports/index.blade.php ENDPATH**/ ?>