<?php

if (!defined('ABSPATH')) {
    exit;
}

class ALYNT_PG_Deactivator {
    public static function deactivate() {
        flush_rewrite_rules();
    }
}
