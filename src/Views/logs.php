<?= $this->extend(config('CronJob')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Views.dashboard') ?> <?= $this->endSection() ?>


<?= $this->section('pageStyles') ?>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    
    <main class="container m-auto">

        <table id="tableJobs" class="table table-bordered table-striped table-hover">
            <thead class="bg-secondary text-white">
                <tr>
                    <th>Start At</th>
                    <th>End At</th>
                    <th>Duration</th>
                    <th>Output</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log->start_at ?></td>
                        <td><?= $log->end_at ?></td>
                        <td><?= $log->duration ?></td>
                        <td><?= $log->output ?></td>
                        <td><?= $log->error ?></td>
                    </tr>
                <?php endforeach ?>          
            </tbody>
        </table>
    </main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        $(document).ready(function () {
            $('#tableJobs').DataTable({
                responsive: true,
                columns: [
                    { title: 'Start At' },
                    { title: 'End At' },
                    { title: 'Duration' },
                    { title: 'Output' },
                    { title: 'Error' }
                ],
            });
        });
    </script>
<?= $this->endSection() ?>
