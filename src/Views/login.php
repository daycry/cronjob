<?= $this->extend(config('CronJob')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Views.login') ?> <?= $this->endSection() ?>


<?= $this->section('pageStyles') ?>
<link href="<?= base_url('vendor/cronjob/css/login.css'); ?>" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('bodyClass') ?>text-center<?= $this->endSection() ?>

<?= $this->section('main') ?>
    
    <main class="form-signin w-100 m-auto">
        <?= form_open('cronjob/login/validation'); ?>
            <img class="mb-4" src="<?= base_url('vendor/cronjob/img/logo.png'); ?>" alt="" width="70" height="70">
            <h1 class="h3 mb-3 fw-normal">Please sign in</h1>

            <div class="form-floating">
            <input type="text" class="form-control" name="username" placeholder="Username">
            <label for="floatingInput">Username</label>
            </div>
            <div class="form-floating">
            <input type="password" class="form-control" name="password" placeholder="Password">
            <label for="floatingPassword">Password</label>
            </div>

            </div>
            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
            <p class="mt-5 mb-3 text-muted">&copy; <?= date('Y')?></p>
        </form>
    </main>
<?= $this->endSection() ?>
