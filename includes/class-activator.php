<?php

if (!defined('ABSPATH')) {
    exit;
}

class ALYNT_PG_Activator {
    public static function activate() {
        flush_rewrite_rules();
    }
}
