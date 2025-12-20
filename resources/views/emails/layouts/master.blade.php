<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'NusaHire')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <style>
        body {
            font-family: 'Instrument Sans', Arial, Helvetica, sans-serif;
            font-size: 15px;
            color: #222;
            background: #fff;
            margin: 0;
            padding: 24px;
        }

        a {
            color: #1a73e8;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        ul {
            padding-left: 20px;
        }

        li {
            margin-bottom: 4px;
        }

        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 24px 0;
        }

        .footer {
            margin-top: 32px;
            text-align: left;
            font-size: 13px;
        }

        .footer img {
            height: 32px;
            margin-bottom: 4px;
        }

        .text-center {
            text-align: center;
        }

        .text-gray {
            color: #888;
        }

        .mb-2 {
            margin-bottom: 8px;
        }
    </style>
</head>

<body>
    @yield('content')
    <br>
    @include('emails.partials.footer')
</body>

</html>
