<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?= $this->renderSection('title') ?></title>

    <!-- Bootstrap core CSS -->
    <link href="<?= base_url('vendor/cronjob/css/bootstrap.min.css'); ?>" rel="stylesheet">

    <!-- Fontawesome -->
    <link href="<?= base_url('vendor/cronjob/icons/fontawesome/css/all.min.css'); ?>" rel="stylesheet">

    <?= $this->renderSection('pageStyles') ?>
</head>

    <?php if ($cronjobLoggedIn) { ?>
        <div class="container">
            <nav class="navbar navbar-expand-lg bg-light">
                <div class="container-fluid">
                    <a class="navbar-brand" href="<?= base_url('cronjob');?>">
                        <img src="<?= base_url('vendor/cronjob/img/logo.png'); ?>" alt="Logo" width="50" height="50" class="d-inline-block align-text-top">
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbar">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <a class="nav-link" href="<?= base_url('cronjob/login/logout'); ?>">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    <?php } ?>

<body class="<?= $this->renderSection('bodyClass') ?>">
<?= $this->renderSection('main') ?>

<script src="<?= base_url('vendor/cronjob/js/jquery.min.js'); ?>"></script>
<script src="<?= base_url('vendor/cronjob/js/bootstrap.min.js'); ?>"></script>
<script src="<?= base_url('vendor/cronjob/js/jquery.dataTables.min.js'); ?>"></script>
<script src="<?= base_url('vendor/cronjob/js/jquery.dataTables.bootstrap.min.js'); ?>"></script>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
