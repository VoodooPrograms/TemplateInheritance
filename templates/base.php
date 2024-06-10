<html>
<head>
    <title><?php $vs->template->startblock('title');?> <?php $vs->template->endblock();?> </title>
</head>
<body>

<p>Base Template</p>
<?php $vs->template->startblock('content');?>
<p>Default Content from base template</p>
<?php $vs->template->endblock();?>

<?php $vs->template->startblock('scripts');?>

<?php $vs->template->endblock();?>

</body>
</html>