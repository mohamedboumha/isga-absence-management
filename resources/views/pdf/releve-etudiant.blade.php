@extends('pdf.layout')

@section('titre', "Relevé d'absences — {$etudiant->nom_complet}")

@section('contenu')
    <h1>Relevé d'absences</h1>

    <table class="infos">
        <tr>
            <td class="label">Étudiant</td>
            <td><strong>{{ $etudiant->nom_complet }}</strong></td>
            <td class="label">CNE</td>
            <td>{{ $etudiant->cne }}</td>
        </tr>
        <tr>
            <td class="label">Groupe</td>
            <td>{{ $etudiant->groupe->nom }} ({{ $etudiant->groupe->filiere->nom }})</td>
            <td class="label">Période</td>
            <td>{{ $periode }}</td>
        </tr>
    </table>

    <table class="liste">
        <thead>
        <tr>
            <th>Date</th>
            <th>Horaire</th>
            <th>Module</th>
            <th>Type</th>
            <th>Statut</th>
            <th>Remarque</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($absences as $absence)
            <tr>
                <td>{{ $absence['date'] }}</td>
                <td>{{ $absence['horaire'] }}</td>
                <td>{{ $absence['module'] }}</td>
                <td>{{ $absence['type'] }}</td>
                <td class="{{ $absence['justifiee'] ? 'ok' : 'ko' }}">{{ $absence['justifiee'] ? 'Justifiée' : 'Non justifiée' }}</td>
                <td>{{ $absence['remarque'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="vide">Aucune absence sur cette période.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <table class="infos" style="margin-top: 14px;">
        <tr>
            <td class="label">Absences</td>
            <td><strong>{{ $total['nb_absences'] }}</strong></td>
            <td class="label">Justifiées</td>
            <td>{{ $total['nb_justifiees'] }}</td>
            <td class="label">Heures d'absence</td>
            <td>{{ $total['heures'] }} h</td>
            <td class="label">Taux d'absence</td>
            <td><strong>{{ $total['taux'] }} %</strong></td>
        </tr>
    </table>
@endsection
