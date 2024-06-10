<?php include 'base.php' ?>

<?php $vs->template->startblock('title') ?>
    Test title
<?php $vs->template->endblock() ?>

<?php $vs->template->startblock('content') ?>
    <p>Test content</p>
<?php $vs->template->endblock() ?>

<?php $vs->template->startblock('scripts');?>

<?php $vs->template->endblock();?>
