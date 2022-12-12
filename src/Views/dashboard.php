<?= $this->extend(config('CronJob')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Views.dashboard') ?> <?= $this->endSection() ?>


<?= $this->section('pageStyles') ?>
<?= $this->endSection() ?>

<?= $this->section('bodyClass') ?>text-center<?= $this->endSection() ?>

<?= $this->section('main') ?>
    
    <main class="container m-auto">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="bg-secondary text-white">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Expression</th>
                        <th>Last run</th>
                        <th>Next run</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <?php $cron = new \Cron\CronExpression($job->getExpression()); ?>
                        <tr>
                            <td><?= $job->name ?></td>
                            <td><?= $job->getType() ?></td>
                            <td><?= $job->getExpression() ?></td>
                            <td><?= $job->lastRun() ?></td>
                            <td><?= $cron->getNextRunDate()->format('Y-m-d H:i:s') ?></td>
                            <td>
                                <div class="dropup-center btn-group dropstart">
                                    <a href="#" class="btn btn-secondary dropdown-toggle" role="button" data-bs-auto-close="true" data-bs-reference="parent" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-gear"></i></a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item logs" href="#">Logs</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>          
                </tbody>
            </table>
        </div>
    </main>
<?= $this->endSection() ?>
