<?php $__env->startSection('content'); ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e(session('error')); ?>

            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Create Tour</strong>
                <div class="small text-body-secondary">Create a new multi-leg tour for pilots</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('admin.tours.index')); ?>">
                <span class="nav-icon">←</span> Back to tours
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo e(route('admin.tours.store')); ?>">
                <?php echo csrf_field(); ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Tour name <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="name" name="name" type="text" value="<?php echo e(old('name')); ?>" required placeholder="e.g. Transatlantic Crossing">
                        <?php $__errorArgs = ['name'];
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
                    <div class="col-md-6">
                        <label class="form-label" for="price_cents">Price (cents) <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control <?php $__errorArgs = ['price_cents'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="price_cents" name="price_cents" type="number" value="<?php echo e(old('price_cents', 0)); ?>" required min="0">
                        <?php $__errorArgs = ['price_cents'];
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
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="origin_airport_id">Origin airport <span class="text-body-secondary">(required)</span></label>
                        <div class="airport-search">
                            <input class="form-control <?php $__errorArgs = ['origin_airport_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="origin_airport_search" type="text" value="<?php echo e(old('origin_airport_id', $tour->originAirport?->identifier ?? '')); ?>" placeholder="Search airports..." autocomplete="off">
                            <input type="hidden" id="origin_airport_id" name="origin_airport_id" value="<?php echo e(old('origin_airport_id', $tour->originAirport?->id ?? '')); ?>" required>
                            <div id="origin_airport_results" class="airport-results" style="display: none;"></div>
                        </div>
                        <?php $__errorArgs = ['origin_airport_id'];
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
                    <div class="col-md-6">
                        <label class="form-label" for="destination_airport_id">Destination airport <span class="text-body-secondary">(required)</span></label>
                        <div class="airport-search">
                            <input class="form-control <?php $__errorArgs = ['destination_airport_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="destination_airport_search" type="text" value="<?php echo e(old('destination_airport_id', $tour->destinationAirport?->identifier ?? '')); ?>" placeholder="Search airports..." autocomplete="off">
                            <input type="hidden" id="destination_airport_id" name="destination_airport_id" value="<?php echo e(old('destination_airport_id', $tour->destinationAirport?->id ?? '')); ?>" required>
                            <div id="destination_airport_results" class="airport-results" style="display: none;"></div>
                        </div>
                        <?php $__errorArgs = ['destination_airport_id'];
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
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="total_distance_nm">Total distance (nautical miles)</label>
                        <input class="form-control <?php $__errorArgs = ['total_distance_nm'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="total_distance_nm" name="total_distance_nm" type="number" value="<?php echo e(old('total_distance_nm')); ?>" min="0">
                        <?php $__errorArgs = ['total_distance_nm'];
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
                    <div class="col-md-6">
                        <label class="form-label" for="estimated_duration_minutes">Estimated duration (minutes)</label>
                        <input class="form-control <?php $__errorArgs = ['estimated_duration_minutes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="estimated_duration_minutes" name="estimated_duration_minutes" type="number" value="<?php echo e(old('estimated_duration_minutes')); ?>" min="0">
                        <?php $__errorArgs = ['estimated_duration_minutes'];
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
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="description" name="description" rows="3"><?php echo e(old('description')); ?></textarea>
                    <?php $__errorArgs = ['description'];
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

                <div class="mb-3 form-check">
                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" <?php echo e(old('is_active', true) ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="is_active">Active tour (visible to pilots)</label>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Create tour</button>
                    <a class="btn btn-outline-secondary" href="<?php echo e(route('admin.tours.index')); ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    function initAirportSearch(searchInputId, hiddenInputId, resultsDivId, url) {
        const searchInput = document.getElementById(searchInputId);
        const hiddenInput = document.getElementById(hiddenInputId);
        const resultsDiv = document.getElementById(resultsDivId);
        let timeout;

        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            const query = this.value.trim();

            if (query.length < 1) {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
                hiddenInput.value = '';
                return;
            }

            timeout = setTimeout(async () => {
                try {
                    const response = await fetch(url + '?query=' + encodeURIComponent(query));
                    const data = await response.json();
                    resultsDiv.innerHTML = '';

                    if (data.results && data.results.length > 0) {
                        data.results.forEach(function (airport) {
                            const div = document.createElement('div');
                            div.className = 'airport-result';
                            div.textContent = airport.text;
                            div.dataset.id = airport.id;
                            div.onclick = function () {
                                searchInput.value = airport.text;
                                hiddenInput.value = airport.id;
                                resultsDiv.style.display = 'none';
                                resultsDiv.innerHTML = '';
                            };
                            resultsDiv.appendChild(div);
                        });
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                } catch (e) {
                    console.error('Airport search error:', e);
                }
            }, 250);
        });

        // Close results when clicking outside
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAirportSearch('origin_airport_search', 'origin_airport_id', 'origin_airport_results', '<?php echo e(route('admin.airports.search')); ?>');
        initAirportSearch('destination_airport_search', 'destination_airport_id', 'destination_airport_results', '<?php echo e(route('admin.airports.search')); ?>');
    });
</script>
<?php $__env->stopPush(); ?>

<style>
    .airport-search {
        position: relative;
    }
    .airport-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 300px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #d2d8e3;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        z-index: 1050;
    }
    .airport-result {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
    }
    .airport-result:hover {
        background-color: #f8fafc;
    }
    .airport-result:last-child {
        border-bottom: none;
    }
</style>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/admin/tours/create.blade.php ENDPATH**/ ?>