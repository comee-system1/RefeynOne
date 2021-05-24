
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LS SOLUTIONS</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <?= $this->Html->css('/plugins/fontawesome-free/css/all.min.css') ?>
    <?= $this->Html->css('/plugins/icheck-bootstrap/icheck-bootstrap.min.css') ?>
    <?= $this->Html->css('/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') ?>

    <?= $this->Html->css('/plugins/adminLTE/css/adminlte.css') ?>
    <?= $this->Html->script('/plugins/adminLTE/js/adminlte.js') ?>

    <?= $this->Html->css('/css/basic.css') ?>


    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>

</head>
<body class="hold-transition lockscreen sidebar-collapse">

<div class="wrapper">
    <?= $this->element('nav'); ?>
    <?= $this->Flash->render() ?>

    <?= $this->fetch('content') ?>

    <footer class="fixed-bottom p-2 text-center">
        <?php /*
        <?= $this->Html->image("lss-logo_2.png",[
            'dev'=>false,
            'class'=>'__logo',
            'alt'=>'ライフサイエンスソリューション株式会社'
        ],
        ); ?>
        */ ?>
    </footer>
</div>
<!-- jQuery -->
<?= $this->Html->script('/plugins/jquery/jquery.min.js') ?>
<?= $this->Html->script('/plugins/jquery/jquery-ui.js') ?>
<!-- Bootstrap 4 -->
<?= $this->Html->script('/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>
<?= $this->Html->script('/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') ?>

<?= $this->Html->script('/plugins/chart.js/Chart.min.js') ?>

<!-- AdminLTE App -->
<?= $this->Html->script('/plugins/adminLTE/js/adminlte.min.js') ?>
<?= $this->Html->script('/dists/bundle.js') ?>

</body>
</html>
