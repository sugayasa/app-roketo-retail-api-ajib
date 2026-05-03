<style>
    .header-container {
        width: 100%;
        border-collapse: collapse;
    }
    .company-box {
        width: 100%;
        float: left;
    }
    .logo-placeholder {
        width: 80px;
        height: 80px;
        float: left;
        text-align: center;
        line-height: 80px;
    }
    .company-text h3 {
        font-size: 10pt;
        margin: 0 0 4px 0;
    }
    .company-text p {
        font-size: 7pt;
        margin: 0;
        line-height: 1.2;
    }
</style>
<div class="header-container clearfix">
    <div class="company-box">
        <div class="logo-placeholder">
            <img src="<?= PATH_STORAGE_LOGO_PERUSAHAAN ?><?= $LOGO ?>" width="60" height="60" alt="Logo">
        </div>
        <div class="company-text" style="overflow: hidden;">
            <h3><?= $NAMAPERUSAHAAN ?></h3><br/>
            <p><?= $ALAMAT ?></p>
            <p><?= $KOTA ?>, <?= $PROVINSI ?></p>
        </div>
    </div>
</div>