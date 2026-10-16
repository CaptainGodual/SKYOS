<?php $__env->startSection('content'); ?>
    <?php if(session('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo e(session('success')); ?><button class="btn-close" data-coreui-dismiss="alert" type="button"></button></div><?php endif; ?>
    <?php if(session('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo e(session('error')); ?><button class="btn-close" data-coreui-dismiss="alert" type="button"></button></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Import Airports</strong>
                <div class="small text-body-secondary">Bulk import airport reference data from a CSV file</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('admin.airports.index')); ?>">
                <span class="nav-icon">←</span> Back to airports
            </a>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-6">
                    <form method="POST" action="<?php echo e(route('admin.airports.import.store')); ?>" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label class="form-label" for="file">CSV file <span class="text-body-secondary">(required)</span></label>
                            <input class="form-control <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="file" name="file" type="file" accept=".csv,.txt" required>
                            <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3 text-body-secondary small">
                            <ul class="list-unstyled mb-0">
                                <li>Expected columns: <code>identifier</code>, <code>iata_code</code>, <code>icao_code</code>, <code>name</code>, <code>type</code>, <code>latitude</code>, <code>longitude</code>, <code>elevation</code>, <code>country_code</code>, <code>region_code</code>, <code>municipality</code>, <code>time_zone</code></li>
                                <li>Existing airports are updated by <code>identifier</code> on re-import.</li>
                                <li>Maximum file size: 20 MB.</li>
                            </ul>
                        </div>

                        <button class="btn btn-primary" type="submit">Import airports</button>
                    </form>
                </div>
                <div class="col-lg-6">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h5 class="card-title">Sample format</h5>
                            <p class="card-text small text-body-secondary">The first row must be a header. Blank or missing optional values are stored as null.</p>
                            <pre class="small bg-white p-2 border rounded"><code>identifier,iata_code,icao_code,name,type,latitude,longitude,elevation,country_code,region_code,municipality,time_zone
NTGA,AAA,NTGA,Anaa Airport,medium_airport,-17.3526,-145.51,3,PF,PF-04,Anaa,Pacific/Tahiti
YARY,AAB,YARY,Arrabury Airport,small_airport,-26.6964,141.049,102,AU,AU-QLD,Tanbar,Australia/Brisbane</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/admin/airports/import.blade.php ENDPATH**/ ?>