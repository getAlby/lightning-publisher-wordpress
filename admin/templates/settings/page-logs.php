<?php

// If this file is called directly, abort.
defined('WPINC') || die; ?>

<div class="wrap lnp">
    <h1><?php echo $this->get_page_title(); ?></h1>
    <div id="log-viewer-select">
        <div class="alignright">
            <form action="" method="GET">
                <?php
                    $logs_directory_path = trailingslashit(wp_upload_dir()['basedir']). "wp-lightning-logs"; 
                    if (!file_exists($logs_directory_path)) {
                        mkdir($logs_directory_path, 0775, true);
                    }
                    $files = array_slice(array_diff(scandir($logs_directory_path, 1), array('..', '.')), 0 , 10);
                ?>
                <select name="log_file">
                    <?php
                        $date = date('Y-m-d');
                    ?>
                    <?php foreach($files as $file): ?>
                        <option value="<?php echo urlencode($file); ?>"><?php echo $file; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="page" value="lnp_settings_logs">
                <button type="submit" class="button" value="View">View</button>
            </form>
        </div>
        <div class="clear"></div>
    </div>
    <div id="log-viewer">
        <?php
        $log_file = urldecode($_GET['log_file']) ?? "{$date}.log"; 
        $log_path = "{$logs_directory_path}/{$log_file}";
        if (file_exists($log_path) && $logs = file_get_contents($log_path)) {
            echo "<pre>{$logs}</pre>";
        } else {
            echo "<pre>No logs found</pre>";
        }
        ?>
    </div>
</div>