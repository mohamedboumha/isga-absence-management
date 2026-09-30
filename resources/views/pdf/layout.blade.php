<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('titre')</title>
    <style>
        @page {
            margin: 28px 32px 40px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #111;
        }

        .entete {
            border-bottom: 2px solid #111;
            padding-bottom: 6px;
            margin-bottom: 16px;
        }

        .entete .ecole {
            font-size: 14px;
            font-weight: bold;
        }

        .entete .edition {
            float: right;
            color: #666;
            font-size: 9px;
            margin-top: 4px;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 10px;
        }

        table.infos {
            margin-bottom: 12px;
        }

        table.infos td {
            padding: 2px 16px 2px 0;
            vertical-align: top;
        }

        table.infos td.label {
            color: #555;
        }

        table.liste {
            width: 100%;
            border-collapse: collapse;
        }

        table.liste th, table.liste td {
            border: 1px solid #999;
            padding: 4px 6px;
            text-align: left;
        }

        table.liste th {
            background: #eee;
            font-weight: bold;
        }

        table.liste tr.total td {
            font-weight: bold;
            background: #f3f3f3;
        }

        .droite {
            text-align: right;
        }

        .centre {
            text-align: center;
        }

        .ok {
            color: #166534;
        }

        .ko {
            color: #b91c1c;
        }

        .vide {
            color: #777;
            text-align: center;
            padding: 16px;
        }

        .pied {
            position: fixed;
            bottom: -24px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #888;
        }
    </style>
</head>
<body>
<div class="entete">
    <span class="edition">Édité le {{ now()->format('d/m/Y à H:i') }}</span>
    <div class="ecole">{{ config('app.name') }}</div>
</div>

@yield('contenu')

<div class="pied">Document généré par {{ config('app.name') }} — gestion des absences</div>
</body>
</html>
