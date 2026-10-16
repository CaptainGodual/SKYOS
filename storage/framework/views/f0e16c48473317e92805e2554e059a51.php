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
                <strong>Downloads</strong>
                <div class="small text-body-secondary">Files available to pilots in the Crew Center</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('admin.downloads.create')); ?>">
                <span class="nav-icon">✚</span> Add download
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th class="text-center">Version</th>
                        <th class="text-center">Size</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $downloads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $download): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><strong><?php echo e($download->name); ?></strong></td>
                            <td><?php echo e($download->category); ?></td>
                            <td class="text-center"><?php echo e($download->version ?? '—'); ?></td>
                            <td class="text-center"><?php echo e($download->size_bytes ? number_format($download->size_bytes / 1000000, 1) . ' MB' : '—'); ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo e(route('admin.downloads.edit', $download)); ?>">Edit</a>
                                <form method="POST" action="<?php echo e(route('admin.downloads.destroy', $download)); ?>" onsubmit="return confirm('Remove this download? Pilots will no longer be able to download it.')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td class="text-center py-4 text-body-secondary" colspan="5">No downloads have been published yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">
            <?php echo e($downloads->total()); ?> download<?php echo e($downloads->total() !== 1 ? 's' : ''); ?> on record · <?php echo e($downloads->perPage()); ?> per page
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/admin/downloads/index.blade.php ENDPATH**/ ?>