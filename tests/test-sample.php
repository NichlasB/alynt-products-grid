<?php

class Test_Sample extends PHPUnit\Framework\TestCase {
    public function test_plugin_bootstraps() {
        $this->assertTrue(defined('ALYNT_PG_VERSION'));
        $this->assertSame('1.0.1', ALYNT_PG_VERSION);
        $this->assertTrue(class_exists('Alynt_Products_Grid'));
        $this->assertTrue(class_exists('ALYNT_PG_Activator'));
        $this->assertTrue(class_exists('ALYNT_PG_Deactivator'));
    }
}
