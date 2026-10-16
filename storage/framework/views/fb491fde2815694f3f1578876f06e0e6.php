<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2362d9">
    <title>Sign in · <?php echo e(config('app.name')); ?></title>
    <style>
        :root { --brand: #2362d9; --ink: #1b2436; --muted: #6b7280; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(1200px 600px at 50% -10%, #2f6be0 0%, #0f1c38 55%, #0a1225 100%);
            color: var(--ink); padding: 24px; }
        .card { width: 100%; max-width: 400px; background: #fff; border-radius: 18px;
            padding: 36px 32px; box-shadow: 0 30px 60px rgba(6, 12, 28, .45); }
        .mark { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center;
            background: var(--brand); color: #fff; font-weight: 800; font-size: 22px; margin-bottom: 20px; }
        h1 { font-size: 22px; margin: 0 0 6px; }
        p.sub { margin: 0 0 24px; color: var(--muted); font-size: 14px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        input[type=email], input[type=password] { width: 100%; padding: 11px 13px; font-size: 15px;
            border: 1px solid #d2d8e3; border-radius: 10px; outline: none; }
        input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(35, 98, 217, .16); }
        .field { margin-bottom: 18px; }
        .row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); margin-bottom: 22px; }
        button { width: 100%; padding: 12px; font-size: 15px; font-weight: 600; color: #fff;
            background: var(--brand); border: 0; border-radius: 10px; cursor: pointer; }
        button:hover { background: #1c4fb4; }
        .errors { background: #fdecec; color: #a4232b; border: 1px solid #f6c6c6;
            border-radius: 10px; padding: 10px 12px; font-size: 13px; margin-bottom: 18px; }
        .errors ul { margin: 0; padding-left: 16px; }
    </style>
</head>
<body>
    <form class="card" method="POST" action="<?php echo e(route('login')); ?>">
        <?php echo csrf_field(); ?>
        <div class="mark">S</div>
        <h1>SKYOS Crew Center</h1>
        <p class="sub">Sign in to access dispatch, ACARS and your roster.</p>

        <?php if($errors->any()): ?>
            <div class="errors">
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if(session('status')): ?>
            <div style="background:#e7f5ee;color:#1a7a4a;border:1px solid #b9e2c9;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:18px;"><?php echo e(session('status')); ?></div>
        <?php endif; ?>

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>

        <label class="row">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>

        <button type="submit">Sign in</button>
        <p style="margin-top: 18px; text-align: center; font-size: 13px; color: var(--muted);">
            New to SKYOS? <a href="<?php echo e(route('register')); ?>" style="color: var(--brand); font-weight: 600;">Create an account</a>
        </p>
    </form>
</body>
</html>
<?php /**PATH C:\Users\Hammy\Documents\ChatGPT\SKYOS_Crew_Center\resources\views/auth/login.blade.php ENDPATH**/ ?>