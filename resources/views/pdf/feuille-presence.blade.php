@extends('pdf.layout')

@section('titre', "Feuille de présence — {$seance->groupe->nom}")

@section('contenu')
    <h1>Feuille de présence</h1>

    <table class="infos">
        <tr>
            <td class="label">Date</td>
            <td><strong>{{ $seance->date?->format('d/m/Y') }}</strong></td>
            <td class="label">Horaire</td>
            <td>{{ $seance->horaire_render }}</td>
            <td class="label">Salle</td>
            <td>{{ $seance->salle ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Module</td>
            <td>{{ $seance->module->code }} — {{ $seance->module->intitule }} ({{ $seance->type }})</td>
            <td class="label">Groupe</td>
            <td>{{ $seance->groupe->nom }}</td>
            <td class="label">Enseignant</td>
            <td>{{ $seance->enseignant->nom_complet }}</td>
        </tr>
    </table>

    <table class="liste">
        <thead>
        <tr>
            <th style="width: 28px;">N°</th>
            <th style="width: 90px;">CNE</th>
            <th>Étudiant</th>
            <th class="centre" style="width: 50px;">Présent</th>
            <th class="centre" style="width: 50px;">Absent</th>
            <th style="width: 130px;">Signature</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($etudiants as $index => $etudiant)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $etudiant->cne }}</td>
                <td>{{ $etudiant->nom_complet }}</td>
                <td class="centre">☐</td>
                <td class="centre">☐</td>
                <td style="height: 18px;"></td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="vide">Aucun étudiant dans ce groupe.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <table class="infos" style="margin-top: 24px; width: 100%;">
        <tr>
            <td class="label">Nombre de présents : ........</td>
            <td class="label">Nombre d'absents : ........</td>
            <td class="label">Signature de l'enseignant :</td>
        </tr>
    </table>
@endsection
