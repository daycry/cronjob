<?= $this->extend(config('CronJob')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Views.login') ?> <?= $this->endSection() ?>


<?= $this->section('pageStyles') ?>
<link href="<?= base_url('vendor/cronjob/css/login.css'); ?>" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('bodyClass') ?>text-center<?= $this->endSection() ?>

<?= $this->section('main') ?>

    <main class="form-signin w-100 m-auto">
        <?= form_open('cronjob/login/validation'); ?>
            <?php if (config('CronJob')->enableCSRFProtection): ?>
                <?= csrf_field() ?>
            <?php endif; ?>

            <img class="mb-4" src="<?= base_url('vendor/cronjob/img/logo.png'); ?>" alt="" width="70" height="70">
            <h1 class="h3 mb-3 fw-normal">Please sign in</h1>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= esc($error) ?>
                </div>
            <?php endif; ?>

            <div class="form-floating">
                <input type="text" class="form-control" name="username" placeholder="Username" required maxlength="100">
                <label for="floatingInput">Username</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" name="password" placeholder="Password" required maxlength="255">
                <label for="floatingPassword">Password</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
            <p class="mt-5 mb-3 text-muted">&copy; <?= date('Y')?></p>
        </form>
    </main>
<?= $this->endSection() ?>
