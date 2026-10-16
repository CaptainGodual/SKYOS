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
                <strong>Airport Expenses</strong>
                <div class="small text-body-secondary">Define airport-specific expense costs</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('admin.expenses.create')); ?>">
                <span class="nav-icon">✚</span> Add Expense
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-center">Cost</th>
                        <th class="text-center">Airports</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $expenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $expense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><strong><?php echo e($expense->name); ?></strong></td>
                            <td><?php echo e($expense->category); ?></td>
                            <td><?php echo e($expense->description ?? '—'); ?></td>
                            <td class="text-center">$<?php echo e(number_format($expense->cost_cents / 100, 2)); ?></td>
                            <td class="text-center"><?php echo e($expense->airports->count()); ?></td>
                            <td class="text-center">
                                <span class="badge <?php echo e($expense->is_active ? 'bg-success' : 'bg-secondary'); ?>"><?php echo e($expense->is_active ? 'Active' : 'Inactive'); ?></span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo e(route('admin.expenses.edit', $expense)); ?>">Edit</a>
                                <form method="POST" action="<?php echo e(route('admin.expenses.destroy', $expense)); ?>" onsubmit="return confirm('Delete this expense?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td class="text-center py-4 text-body-secondary" colspan="7">No expenses have been defined yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">
            <?php echo e($expenses->total()); ?> expense<?php echo e($expenses->total() !== 1 ? 's' : ''); ?> on record &middot; <?php echo e($expenses->perPage()); ?> per page
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/admin/expenses/index.blade.php ENDPATH**/ ?>