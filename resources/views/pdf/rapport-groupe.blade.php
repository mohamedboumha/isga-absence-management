@extends('pdf.layout')

@section('titre', "Rapport d'absences — {$groupe->nom}")

@section('contenu')
    <h1>Rapport d'absences du groupe {{ $groupe->nom }}</h1>

    <table class="infos">
        <tr>
            <td class="label">Filière</td>
            <td>{{ $groupe->filiere->nom }}</td>
            <td class="label">Niveau</td>
            <td>{{ $groupe->niveau }}</td>
            <td class="label">Année</td>
            <td>{{ $groupe->annee_universitaire->libelle }}</td>
        </tr>
        <tr>
            <td class="label">Période</td>
            <td>{{ $periode }}</td>
            <td class="label">Séances tenues</td>
            <td>{{ $nb_seances }} ({{ $heures }} h)</td>
            <td class="label">Effectif</td>
            <td>{{ count($lignes) }}</td>
        </tr>
    </table>

    <table class="liste">
        <thead>
        <tr>
            <th>N°</th>
            <th>CNE</th>
            <th>Étudiant</th>
            <th class="droite">Absences</th>
            <th class="droite">Non justifiées</th>
            <th class="droite">Heures</th>
            <th class="droite">Taux</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($lignes as $index => $ligne)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $ligne['cne'] }}</td>
                <td>{{ $ligne['nom_complet'] }}</td>
                <td class="droite">{{ $ligne['nb_absences'] }}</td>
                <td class="droite">{{ $ligne['nb_non_justifiees'] }}</td>
                <td class="droite">{{ $ligne['heures'] }}</td>
                <td class="droite">{{ $ligne['taux'] }} %</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="vide">Aucun étudiant dans ce groupe.</td>
            </tr>
        @endforelse

        <tr class="total">
            <td colspan="3">Total du groupe</td>
            <td class="droite">{{ $total['nb_absences'] }}</td>
            <td class="droite">{{ $total['nb_non_justifiees'] }}</td>
            <td class="droite">{{ $total['heures'] }}</td>
            <td class="droite">{{ $total['taux'] }} %</td>
        </tr>
        </tbody>
    </table>
@endsection
