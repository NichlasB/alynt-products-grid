import { AlyntProductsGrid } from './grid/AlyntProductsGrid.js';

const $ = window.jQuery;

$(document).ready(function() {
    $('.alynt-pg-container').each(function() {
        new AlyntProductsGrid(this);
    });
});
