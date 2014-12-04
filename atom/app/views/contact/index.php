<section id="main-content">
    <div class="container">
        <h1>Contact Us</h1>
        <form id="test" action="/contact" method="post">
            <?= Atom\Security::antiCsrfField(); ?>
            <label>First name</label>
            <input type="text" name="firstname" value="<?= $model->fieldValue('firstname'); ?>">
            <?= $model->fieldErrors('firstname', 'div', 'error'); ?>
            <label>Last name</label>
            <input type="text" name="lastname" value="<?= $model->fieldValue('lastname'); ?>">
            <?= $model->fieldErrors('lastname', 'div', 'error'); ?>
            <label>Email</label>
            <input type="email" name="email" value="<?= $model->fieldValue('email'); ?>">
            <?= $model->fieldErrors('email', 'div', 'error'); ?>
            <label>Description</label>
            <textarea name="description"><?= $model->fieldValue('description'); ?></textarea>
            <?= $model->fieldErrors('description', 'div', 'error'); ?>
            <div>
            <input type="submit" id="submit" value="Submit">
            </div>
        </form>
    </div>
</section>
<script type="text/javascript">
    //$(document).ready(function() {
    //    $('#submit').on('click', function(e) {
    //        e.preventDefault();
    //        $.ajax({
    //            type: "POST",
    //            url: "/contact",
    //            data: $('#test').serialize(),
    //            success: function(data){
    //            },
    //            dataType: 'json'
    //        });
    //    });
    //});
</script>