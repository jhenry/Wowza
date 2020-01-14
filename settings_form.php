<h1>Wowza Settings</h1>

<?php if ($message): ?>
<div class="alert <?=$message_type?>"><?=$message?></div>
<?php endif; ?>

<form method="post">

    <div class="form-group <?=(isset ($errors['wowza_upload_dir'])) ? 'has-error' : ''?>">
        <label class="control-label">Wowza upload directory base path:</label>
        <input class="form-control" type="text" name="wowza_upload_dir" value="<?=$data['wowza_upload_dir']?>" />
    </div>
    <div class="form-group <?=(isset ($errors['wowza_rtmp_host'])) ? 'has-error' : ''?>">
        <label class="control-label">RTMP Host:</label>
        <input class="form-control" type="text" name="wowza_rtmp_host" value="<?=$data['wowza_rtmp_host']?>" />
    </div>

    <input type="hidden" value="yes" name="submitted" />
    <input type="hidden" name="nonce" value="<?=$formNonce?>" />
    <input type="submit" class="button" value="Update Settings" />

</form>
