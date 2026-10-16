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
                <strong>Virtual Airline Bank Account</strong>
                <div class="small text-body-secondary">Financial overview of SKYOS Air Virtual</div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4 mb-4">
                <div class="col-xl-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="text-white-50 small text-uppercase fw-semibold">Current Balance</div>
                            <div class="fs-3 fw-semibold">$<?php echo e(number_format($netBalance, 2)); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="text-white-50 small text-uppercase fw-semibold">Total Revenue</div>
                            <div class="fs-3 fw-semibold">$<?php echo e(number_format($totalRevenue, 2)); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="text-white-50 small text-uppercase fw-semibold">Total Expenses</div>
                            <div class="fs-3 fw-semibold">$<?php echo e(number_format($totalExpenses, 2)); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card bg-<?php echo e($netBalance >= 0 ? 'success' : 'danger'); ?> text-white">
                        <div class="card-body">
                            <div class="text-white-50 small text-uppercase fw-semibold">Net Result</div>
                            <div class="fs-3 fw-semibold">$<?php echo e(number_format($netBalance, 2)); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><strong>Recent Transactions</strong></div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $recentTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td><?php echo e($transaction->created_at->format('M d, Y H:i')); ?></td>
                                            <td><span class="badge <?php echo e($transaction->type === 'revenue' ? 'bg-success' : 'bg-danger'); ?>"><?php echo e(ucfirst($transaction->type)); ?></span></td>
                                            <td><?php echo e($transaction->category); ?></td>
                                            <td><?php echo e($transaction->description); ?></td>
                                            <td class="text-end <?php echo e($transaction->amount_cents >= 0 ? 'text-success' : 'text-danger'); ?>">$<?php echo e(number_format($transaction->amount_cents / 100, 2)); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td class="text-center py-4 text-body-secondary" colspan="5">No financial transactions yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/admin/finance/index.blade.php ENDPATH**/ ?>