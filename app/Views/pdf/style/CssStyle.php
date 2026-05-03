<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 7pt;
        margin: 0.5cm;
        padding: 0;
    }
    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }
    .divider {
        width: 100%;
        border-bottom: 1px solid #000;
        margin-top: 2px;
        margin-bottom: 2px;
        padding: 0;
    }
    .item-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .item-table thead th {
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
        padding: 5px 3px;
        font-size: 7pt;
        text-align: left;
    }
    .item-table tbody td {
        border-bottom: 1px solid #ddd;
        padding: 5px 3px;
        font-size: 7pt;
        vertical-align: top;
    }
    .item-table tbody tr:last-child td {
        border-bottom: none;
    }
</style>