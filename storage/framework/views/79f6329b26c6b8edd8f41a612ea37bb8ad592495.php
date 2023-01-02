<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Email Template</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500&display=swap" rel="stylesheet">
<style>
    body {
        margin: 0;
    }

    * {
        margin: 0;
        padding: 0;
    }

    table {
        border-spacing: 0px;
    }

    td {
        padding: 0;
    }

    img {
        border: 0;
    }

    .wrapper {
        width: 100%;
        table-layout: fixed;
        background: none;
        padding-bottom: 40px;
        padding-top: 40px;

    }

    .main {
        width: 100%;
        max-width: 600px;
        border-spacing: 0;

        border-radius: 15px 15px 0px 0px;
        margin-top: 50px;

    }

    .two-column {
        text-align: center;
        font-size: 0;
    }

    .two-column .column {
        width: 100%;
        max-width: 300px;
        display: inline-block;

    }

    .two-column .column-2 tbody {
        float: right;
    }

    button {
        background: #F89D15;
        color: #fff;
        border: none;
        padding: 13.5px 43px;
        border-radius: 3px;
        cursor: pointer;
    }

    @media(max-width:550px) {
        .two-column .column-2 tbody {
            float: unset;
            padding-top: 15px;
        }

        .two-column .column {
            display: table;
            margin: auto;
        }

        .two-column .column-2 {
            padding-top: 15px;
        }
    }
</style><?php /**PATH /var/www/html/canonizer-3.0-api/resources/views/layouts/head.blade.php ENDPATH**/ ?>